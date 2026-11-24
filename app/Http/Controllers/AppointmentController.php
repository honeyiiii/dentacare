<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AppointmentController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $appointments = Appointment::with(['patient', 'dentist'])
            ->when($user->role === 'dentist', fn ($q) => $q->where('dentist_id', $user->id))
            ->orderBy('appointment_date')
            ->orderBy('appointment_time')
            ->paginate(10);

        return view('appointments.index', compact('appointments'));
    }

    public function create()
    {
        $patients = Patient::orderBy('full_name')->get();
        $dentists = User::where('role', 'dentist')->where('is_active', true)->orderBy('name')->get();

        return view('appointments.create', compact('patients', 'dentists'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'patient_id'       => ['required', Rule::exists('patients', 'id')],
            'dentist_id'       => ['required', Rule::exists('users', 'id')->where('role', 'dentist')],
            'service'          => ['required', 'string', 'max:255'],
            'appointment_date' => ['required', 'date', 'after_or_equal:today'],
            'appointment_time' => ['required', 'date_format:H:i'],
        ]);

        // Store the time as H:i:s so the double-booking check matches exactly
        $data['appointment_time'] = $data['appointment_time'] . ':00';

        $taken = Appointment::where('dentist_id', $data['dentist_id'])
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

        return redirect()->route('appointments.index')->with('success', 'Appointment booked.');
    }

    public function cancel(Appointment $appointment)
    {
        $appointment->status = 'cancelled';
        $appointment->save();

        ActivityLog::record('Appointment cancelled', $appointment);

        return back()->with('success', 'Appointment cancelled.');
    }
}