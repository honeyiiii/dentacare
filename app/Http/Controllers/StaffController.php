<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class StaffController extends Controller
{
    private const STAFF_ROLES = ['owner', 'dentist', 'assistant', 'receptionist'];

    public function index(Request $request)
    {
        $search = $request->query('search');

        $staff = User::whereIn('role', self::STAFF_ROLES)
            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        return view('staff.index', compact('staff', 'search'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'email', 'max:255', 'unique:users,email'],
            'role'     => ['required', Rule::in(self::STAFF_ROLES)],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        User::forceCreate([
            'name'              => $data['name'],
            'email'             => $data['email'],
            'role'              => $data['role'],
            'password'          => Hash::make($data['password']),
            'email_verified_at' => now(),
            'is_active'         => true,
        ]);

        return redirect()->route('staff.index')
            ->with('success', 'Staff account created.');
    }

    public function updateRole(Request $request, User $user)
    {
        abort_unless(in_array($user->role, self::STAFF_ROLES), 404);

        if ($user->id === $request->user()->id) {
            throw ValidationException::withMessages([
                'role' => 'You cannot change your own role.',
            ]);
        }

        $data = $request->validate([
            'role' => ['required', Rule::in(self::STAFF_ROLES)],
        ]);

        $user->role = $data['role'];
        $user->save();

        return back()->with('success', 'Role updated.');
    }

    public function toggleActive(Request $request, User $user)
    {
        abort_unless(in_array($user->role, self::STAFF_ROLES), 404);

        if ($user->id === $request->user()->id) {
            throw ValidationException::withMessages([
                'user' => 'You cannot deactivate your own account.',
            ]);
        }

        $user->is_active = ! $user->is_active;
        $user->save();

        return back()->with('success', $user->is_active ? 'Account reactivated.' : 'Account deactivated.');
    }
}