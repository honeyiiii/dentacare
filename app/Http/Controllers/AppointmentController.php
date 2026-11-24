<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\AppointmentSlotClaim;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AppointmentController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Appointment::class);
        $user = $request->user();

        $appointments = Appointment::with(['patient:id,full_name', 'dentist:id,name'])
            ->when($user->role === 'dentist', fn ($query) => $query->where('dentist_id', $user->id))
            ->when($user->role === 'patient', fn ($query) => $query->whereHas('patient', fn ($patientQuery) => $patientQuery->where('user_id', $user->id)))
            ->orderBy('appointment_date')
            ->orderBy('appointment_time')
            ->paginate(25);

        return response()->json($appointments);
    }

    public function show(Appointment $appointment)
    {
        $this->authorize('view', $appointment);

        return response()->json($appointment->load(['patient', 'dentist:id,name', 'booker:id,name']));
    }

    public function store(Request $request)
    {
        $this->authorize('create', Appointment::class);

        $isPatient = $request->user()->role === 'patient';
        $data = $request->validate([
            'patient_id' => $isPatient
                ? ['prohibited']
                : ['required', 'integer', Rule::exists('patients', 'id')->whereNull('deleted_at')],
            'dentist_id' => ['required', 'integer', Rule::exists('users', 'id')->where('role', 'dentist')->where('is_active', true)],
            'service' => ['required', 'string', 'max:120'],
            'appointment_date' => ['required', 'date', 'after_or_equal:today'],
            'appointment_time' => ['required', 'date_format:H:i'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        if ($data['appointment_date'] === now()->toDateString()
            && $data['appointment_time'] <= now()->format('H:i')) {
            throw ValidationException::withMessages([
                'appointment_time' => 'Choose a time later than now for an appointment today.',
            ]);
        }

        if ($isPatient) {
            $data['patient_id'] = Patient::where('user_id', $request->user()->id)->value('id');
            if (! $data['patient_id']) {
                abort(403, 'No patient profile is linked to this account.');
            }
        }

        // Keep the appointment and its unique active-slot claim in one transaction.
        // The database constraint is the final guard against concurrent double bookings.
        try {
            $appointment = DB::transaction(function () use ($request, $data): Appointment {
                // Serialize booking against patient archival for databases that support row locks.
                Patient::query()->lockForUpdate()->findOrFail($data['patient_id']);
                $dentist = User::query()
                    ->whereKey($data['dentist_id'])
                    ->where('role', 'dentist')
                    ->where('is_active', true)
                    ->lockForUpdate()
                    ->first();
                if (! $dentist) {
                    throw ValidationException::withMessages(['dentist_id' => 'Choose an active dentist.']);
                }

                $appointment = Appointment::create(array_merge($data, [
                    'booked_by' => $request->user()->id,
                    'appointment_time' => $data['appointment_time'].':00',
                    'status' => 'scheduled',
                ]));

                $claimed = DB::table('appointment_slot_claims')->insertOrIgnore([
                    'appointment_id' => $appointment->id,
                    'dentist_id' => $appointment->dentist_id,
                    'appointment_date' => $appointment->appointment_date->toDateString(),
                    'appointment_time' => $appointment->appointment_time,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                if ($claimed !== 1) {
                    throw ValidationException::withMessages([
                        'appointment_time' => 'That dentist already has an appointment at this date and time.',
                    ]);
                }

                return $appointment;
            });
        } catch (QueryException $exception) {
            // MySQL, PostgreSQL and SQLite report unique-key violations differently.
            // The pre-check is a friendly fast path; the unique key remains authoritative.
            $sqlState = (string) $exception->getCode();
            $driverCode = (int) ($exception->errorInfo[1] ?? 0);
            $message = strtolower($exception->getMessage());
            $isSlotConflict = $sqlState === '23505'
                || $driverCode === 1062
                || ($driverCode === 2067 && str_contains($message, 'appointment_slot_claims'))
                || ($driverCode === 19 && str_contains($message, 'unique constraint failed: appointment_slot_claims'));
            if ($isSlotConflict) {
                throw ValidationException::withMessages([
                    'appointment_time' => 'That dentist already has an appointment at this date and time.',
                ]);
            }

            throw $exception;
        }

        return response()->json($appointment->load(['patient:id,full_name', 'dentist:id,name']), 201);
    }

    public function cancel(Request $request, Appointment $appointment)
    {
        $this->authorize('cancel', $appointment);

        if ($appointment->status !== 'scheduled') {
            return response()->json(['message' => 'Only scheduled appointments can be cancelled.'], 409);
        }

        DB::transaction(function () use ($appointment): void {
            $locked = Appointment::query()->lockForUpdate()->findOrFail($appointment->id);
            if ($locked->status !== 'scheduled') {
                throw ValidationException::withMessages(['appointment' => 'This appointment has already changed. Refresh and try again.']);
            }

            AppointmentSlotClaim::where('appointment_id', $locked->id)->delete();
            $locked->update(['status' => 'cancelled']);
        });

        return response()->json(['message' => 'Appointment cancelled.', 'appointment' => $appointment->fresh()]);
    }
}
