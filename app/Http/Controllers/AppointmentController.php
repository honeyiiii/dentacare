<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AppointmentController extends Controller
{
    public function index(Request $request)
    {
        $date = $request->query('date', now()->toDateString());

        $appointments = Appointment::with(['patient', 'dentist'])
            ->whereDate('appointment_date', $date)
            ->orderBy('appointment_time')
            ->get();

        return view('appointments.index', compact('appointments', 'date'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'patient_id'       => ['required', 'exists:patients,id'],
            'dentist_id'       => ['required', Rule::exists('users', 'id')->where('role', 'dentist')],
            'service'          => ['required', 'string', 'max:255'],
            'appointment_date' => ['required', 'date', 'after_or_equal:today'],
            'appointment_time' => ['required', 'date_format:H:i'],
            'notes'            => ['nullable', 'string'],
        ]);

        $taken = Appointment::where('dentist_id', $data['dentist_id'])
            ->whereDate('appointment_date', $data['appointment_date'])
            ->where('appointment_time', $data['appointment_time'])
            ->where('status', '!=', 'cancelled')
            ->exists();

        if ($taken) {
            return back()->withInput()
                ->withErrors(['appointment_time' => 'That time slot is already booked for this dentist.']);
        }

        Appointment::create($data);

        return redirect()->route('appointments.index', ['date' => $data['appointment_date']])
            ->with('success', 'Appointment booked.');
    }

    public function cancel(Appointment $appointment)
    {
        if (in_array($appointment->status, ['done', 'cancelled'])) {
            return back()->withErrors(['status' => 'This appointment can no longer be cancelled.']);
        }

        $appointment->update(['status' => 'cancelled']);

        return back()->with('success', 'Appointment cancelled.');
    }

    public function reschedule(Request $request, Appointment $appointment)
    {
        if ($appointment->status !== 'scheduled') {
            return back()->withErrors(['status' => 'Only scheduled appointments can be rescheduled.']);
        }

        $data = $request->validate([
            'appointment_date' => ['required', 'date', 'after_or_equal:today'],
            'appointment_time' => ['required', 'date_format:H:i'],
        ]);

        $taken = Appointment::where('dentist_id', $appointment->dentist_id)
            ->whereDate('appointment_date', $data['appointment_date'])
            ->where('appointment_time', $data['appointment_time'])
            ->where('status', '!=', 'cancelled')
            ->where('id', '!=', $appointment->id)
            ->exists();

        if ($taken) {
            return back()->withInput()
                ->withErrors(['appointment_time' => 'That time slot is already booked for this dentist.']);
        }

        $appointment->update($data);

        return back()->with('success', 'Appointment rescheduled.');
    }

    public function updateStatus(Request $request, Appointment $appointment)
    {
        if ($appointment->status === 'cancelled') {
            return back()->withErrors(['status' => 'A cancelled appointment cannot change status.']);
        }

        $data = $request->validate([
            'status' => ['required', Rule::in(['waiting', 'in_progress', 'done', 'no_show'])],
        ]);

        $appointment->update($data);

        return back()->with('success', 'Status updated.');
    }
}