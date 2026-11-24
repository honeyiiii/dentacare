<?php

namespace App\Http\Controllers;

use App\Models\ClinicalRecordAudit;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class StaffController extends Controller
{
    private const ROLES = ['owner', 'dentist', 'assistant', 'receptionist'];

    public function index(Request $request)
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:120'],
            'role' => ['nullable', Rule::in(self::ROLES)],
            'active' => ['nullable', 'boolean'],
        ]);

        $staff = User::query()
            ->whereIn('role', self::ROLES)
            ->when($filters['search'] ?? null, function ($query, $search): void {
                $query->where(fn ($q) => $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%"));
            })
            ->when($filters['role'] ?? null, fn ($query, $role) => $query->where('role', $role))
            ->when(array_key_exists('active', $filters), fn ($query) => $query->where('is_active', $filters['active'] ? 1 : 0))
            ->orderBy('name')
            ->paginate(25)
            ->withQueryString();

        return response()->json($staff);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
            'role' => ['required', Rule::in(self::ROLES)],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $staff = DB::transaction(function () use ($data, $request): User {
            $user = User::forceCreate([
                'name' => $data['name'],
                'email' => $data['email'],
                'role' => $data['role'],
                'password' => Hash::make($data['password']),
                'email_verified_at' => now(),
                'is_active' => true,
            ]);
            $this->audit($request->user(), $user, 'created', [], ['role' => $user->role, 'is_active' => true]);

            return $user;
        });

        return response()->json($staff->only(['id', 'name', 'email', 'role', 'is_active']), 201);
    }

    public function updateRole(Request $request, User $user)
    {
        abort_unless(in_array($user->role, self::ROLES, true), 404);
        if ($user->id === $request->user()->id) {
            throw ValidationException::withMessages(['role' => 'You cannot change your own role.']);
        }

        $data = $request->validate(['role' => ['required', Rule::in(self::ROLES)]]);
        DB::transaction(function () use ($request, $user, $data): void {
            $lockedUser = User::query()->lockForUpdate()->findOrFail($user->id);
            if ($lockedUser->role === 'owner' && $lockedUser->is_active && $data['role'] !== 'owner'
                && User::where('role', 'owner')->where('is_active', true)->lockForUpdate()->get(['id'])->count() <= 1) {
                throw ValidationException::withMessages(['role' => 'The clinic must retain at least one active owner.']);
            }
            if ($lockedUser->role === 'dentist' && $data['role'] !== 'dentist' && $this->hasUpcomingAppointments($lockedUser)) {
                throw ValidationException::withMessages(['role' => 'Reassign or cancel this dentist’s upcoming appointments first.']);
            }

            $oldRole = $lockedUser->role;
            $lockedUser->forceFill(['role' => $data['role']])->save();
            $this->audit($request->user(), $lockedUser, 'updated', ['role' => $oldRole], ['role' => $lockedUser->role]);
        });

        return response()->json($user->fresh()->only(['id', 'name', 'email', 'role', 'is_active']));
    }

    public function toggleActive(Request $request, User $user)
    {
        abort_unless(in_array($user->role, self::ROLES, true), 404);
        if ($user->id === $request->user()->id) {
            throw ValidationException::withMessages(['user' => 'You cannot deactivate your own account.']);
        }
        DB::transaction(function () use ($request, $user): void {
            $lockedUser = User::query()->lockForUpdate()->findOrFail($user->id);
            if ($lockedUser->role === 'owner' && $lockedUser->is_active
                && User::where('role', 'owner')->where('is_active', true)->lockForUpdate()->get(['id'])->count() <= 1) {
                throw ValidationException::withMessages(['user' => 'The clinic must retain at least one active owner.']);
            }
            if ($lockedUser->role === 'dentist' && $lockedUser->is_active && $this->hasUpcomingAppointments($lockedUser)) {
                throw ValidationException::withMessages(['user' => 'Reassign or cancel this dentist’s upcoming appointments first.']);
            }

            $wasActive = $lockedUser->is_active;
            $lockedUser->forceFill(['is_active' => ! $wasActive])->save();
            $this->audit($request->user(), $lockedUser, 'updated', ['is_active' => $wasActive], ['is_active' => $lockedUser->is_active]);
        });

        return response()->json($user->fresh()->only(['id', 'name', 'email', 'role', 'is_active']));
    }

    private function hasUpcomingAppointments(User $user): bool
    {
        return $user->appointmentsAsDentist()
            ->where('status', 'scheduled')
            ->whereDate('appointment_date', '>=', now()->toDateString())
            ->exists();
    }

    private function audit(User $actor, User $subject, string $event, array $before, array $after): void
    {
        ClinicalRecordAudit::create([
            'patient_id' => null,
            'user_id' => $actor->id,
            'record_type' => 'users',
            'record_id' => $subject->id,
            'event' => $event,
            'changes' => ['before' => $before, 'after' => $after],
            'created_at' => now(),
        ]);
    }
}
