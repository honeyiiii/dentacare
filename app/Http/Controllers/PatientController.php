<?php

namespace App\Http\Controllers;

use App\Models\ClinicalRecordAudit;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PatientController extends Controller
{
    public function myProfile(Request $request)
    {
        $patient = Patient::where('user_id', $request->user()->id)->firstOrFail();
        $this->authorize('view', $patient);

        return response()->json($patient);
    }

    public function updateMyProfile(Request $request)
    {
        $patient = Patient::where('user_id', $request->user()->id)->firstOrFail();
        $this->authorize('update', $patient);

        $data = $request->validate([
            'date_of_birth' => ['sometimes', 'required', 'date', 'before:today'],
            'sex' => ['sometimes', 'required', 'in:Male,Female'],
            'contact_number' => ['sometimes', 'required', 'string', 'max:20'],
            'address' => ['sometimes', 'required', 'string', 'max:255'],
        ]);
        if ($data === []) {
            throw ValidationException::withMessages(['profile' => 'Provide at least one profile field to update.']);
        }

        $before = $patient->only(array_keys($data));
        DB::transaction(function () use ($request, $patient, $data, $before): void {
            $patient->update($data);
            $this->recordAudit($request, $patient, 'updated', $before, $data);
        });

        return response()->json($patient->fresh());
    }

    public function index(Request $request)
    {
        $this->authorize('viewAny', Patient::class);
        $search = $request->query('search');

        $patients = Patient::query()
            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('full_name', 'like', "%{$search}%")
                      ->orWhere('contact_number', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('patients.index', compact('patients', 'search'));
    }

    public function create()
    {
        $this->authorize('create', Patient::class);
        $accounts = User::where('role', 'patient')
            ->where('is_active', true)
            ->whereDoesntHave('patientProfile')
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        return view('patients.create', compact('accounts'));
    }

    public function store(Request $request)
    {
        $this->authorize('create', Patient::class);
        $data = $request->validate([
            'full_name'      => ['required', 'string', 'max:255'],
            'date_of_birth'  => ['required', 'date', 'before:today'],
            'sex'            => ['required', 'in:Male,Female'],
            'contact_number' => ['required', 'string', 'max:20'],
            'address'        => ['required', 'string', 'max:255'],
            'email'          => ['nullable', 'email', 'max:255'],
            'user_id'        => ['nullable', 'integer', Rule::exists('users', 'id')->where('role', 'patient')->where('is_active', true), Rule::unique('patients', 'user_id')],
        ]);

        if (! empty($data['user_id'])) {
            $account = User::findOrFail($data['user_id']);
            $data['full_name'] = $account->name;
            $data['email'] = $account->email;
        }

        DB::transaction(function () use ($data, $request): void {
            $patient = Patient::create($data);
            $this->recordAudit($request, $patient, 'created', [], $data);
        });

        return redirect()->route('patients.index')
            ->with('success', 'Patient registered successfully.');
    }

    public function show(Patient $patient)
    {
        $this->authorize('view', $patient);

        return view('patients.show', compact('patient'));
    }

    public function edit(Patient $patient)
    {
        $this->authorize('update', $patient);

        return view('patients.edit', compact('patient'));
    }

    public function update(Request $request, Patient $patient)
    {
        $this->authorize('update', $patient);

        $data = $request->validate([
            'full_name'      => ['required', 'string', 'max:255'],
            'date_of_birth'  => ['required', 'date', 'before:today'],
            'sex'            => ['required', 'in:Male,Female'],
            'contact_number' => ['required', 'string', 'max:20'],
            'address'        => ['required', 'string', 'max:255'],
            'email'          => ['nullable', 'email', 'max:255'],
        ]);

        $linkedUser = $patient->user;
        if ($linkedUser && array_key_exists('email', $data) && $data['email'] !== $linkedUser->email) {
            throw ValidationException::withMessages([
                'email' => 'Update this patient’s login email through the patient account profile instead.',
            ]);
        }

        $before = $patient->only(array_keys($data));
        DB::transaction(function () use ($request, $patient, $data, $before, $linkedUser): void {
            $patient->update($data);
            $this->recordAudit($request, $patient, 'updated', $before, $data);
            if ($linkedUser && array_key_exists('full_name', $data) && $linkedUser->name !== $data['full_name']) {
                $oldName = $linkedUser->name;
                $linkedUser->name = $data['full_name'];
                $linkedUser->save();
                ClinicalRecordAudit::create([
                    'patient_id' => $patient->id,
                    'user_id' => $request->user()->id,
                    'record_type' => 'users',
                    'record_id' => $linkedUser->id,
                    'event' => 'updated',
                    'changes' => ['before' => ['name' => $oldName], 'after' => ['name' => $linkedUser->name]],
                    'created_at' => now(),
                ]);
            }
        });

        return redirect()->route('patients.show', $patient)
            ->with('success', 'Patient record updated successfully.');
    }

    public function destroy(Request $request, Patient $patient)
    {
        $this->authorize('delete', $patient);

        DB::transaction(function () use ($request, $patient): void {
            $locked = Patient::query()->lockForUpdate()->findOrFail($patient->id);
            if ($locked->appointments()
                ->where('status', 'scheduled')
                ->whereDate('appointment_date', '>=', now()->toDateString())
                ->exists()) {
                throw ValidationException::withMessages([
                    'patient' => 'Cancel or reassign this patient’s upcoming appointments before archiving the record.',
                ]);
            }

            $patient = $locked;
            $patient->delete();
            $this->recordAudit($request, $patient, 'archived', [], ['deleted_at' => $patient->deleted_at]);
        });

        return redirect()->route('patients.index')
            ->with('success', 'Patient record archived successfully.');
    }

    private function recordAudit(Request $request, Patient $patient, string $event, array $before, array $after): void
    {
        ClinicalRecordAudit::create([
            'patient_id' => $patient->id,
            'user_id' => $request->user()->id,
            'record_type' => 'patients',
            'record_id' => $patient->id,
            'event' => $event,
            'changes' => ['before' => $before, 'after' => $after],
            'created_at' => now(),
        ]);
    }
}
