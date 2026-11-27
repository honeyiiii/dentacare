<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AppointmentController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $appointments = Appointment::with(['patient', 'dentist'])
            ->when($user->role === 'dentist', fn ($q) => $q->where('dentist_id', $user->id))
            ->when($user->role === 'patient', fn ($q) => $q->whereHas('patient', fn ($p) => $p->where('user_id', $user->id)))
            ->orderBy('appointment_date')
            ->orderBy('appointment_time')
            ->paginate(10);

        return view('appointments.index', compact('appointments'));
    }

    public function create(Request $request)
    {
        $user = $request->user();

        $patients = Patient::orderBy('full_name')
            ->when($user->role === 'patient', fn ($q) => $q->where('user_id', $user->id))
            ->get();

        $dentists = User::where('role', 'dentist')->where('is_active', true)->orderBy('name')->get();

        return view('appointments.create', compact('patients', 'dentists'));
    }

    public function store(Request $request)
    {
        $user = $request->user();

        // A patient can only book for their own record, whatever the form sends.
        if ($user->role === 'patient') {
            $own = Patient::where('user_id', $user->id)->first();
abort_if($own === null, 403, 'No patient record is linked to your account.');               $request->merge(['patient_id' => $own->id]);
        }

        $data = $request->validate([
            'patient_id'       => ['required', Rule::exists('patients', 'id')],
            'dentist_id'       => ['required', Rule::exists('users', 'id')->where('role', 'dentist')],
            'service'          => ['required', 'string', 'max:255'],
            'appointment_date' => ['required', 'date', 'after_or_equal:today'],
            'appointment_time' => ['required', 'date_format:H:i'],
        ]);

        $data['appointment_time'] = $data['appointment_time'] . ':00';

        $appointment = DB::transaction(function () use ($data) {
            // Lock the dentist row so simultaneous bookings for this dentist serialize.
            $dentist = User::whereKey($data['dentist_id'])->lockForUpdate()->firstOrFail();

            if ($dentist->role !== 'dentist' || ! $dentist->is_active) {
                throw ValidationException::withMessages([
                    'dentist_id' => 'Please select an active dentist.',
                ]);
            }

            $taken = Appointment::where('dentist_id', $dentist->id)
                ->whereDate('appointment_date', $data['appointment_date'])
                ->where('appointment_time', $data['appointment_time'])
                ->where('status', '!=', 'cancelled')
                ->exists();

            if ($taken) {
                throw ValidationException::withMessages([
                    'appointment_time' => 'That dentist is already booked at this date and time.',
                ]);
            }

            $appointment = Appointment::forceCreate($data + ['status' => 'scheduled']);
            ActivityLog::record('Appointment booked', $appointment);

            return $appointment;
        });

        return redirect()->route('appointments.index')->with('success', 'Appointment booked.');
    }

    public function cancel(Request $request, Appointment $appointment)
    {
        $user = $request->user();

        abort_unless(
            in_array($user->role, ['owner', 'receptionist'], true)
                || ($user->role === 'dentist' && $appointment->dentist_id === $user->id)
                || ($user->role === 'patient' && $appointment->patient?->user_id === $user->id),
            403
        );

        abort_unless($appointment->status === 'scheduled', 409, 'Only scheduled appointments can be cancelled.');

        $appointment->status = 'cancelled';
        $appointment->save();

        ActivityLog::record('Appointment cancelled', $appointment);

        return back()->with('success', 'Appointment cancelled.');
    }
}