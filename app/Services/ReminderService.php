<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Appointment;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;

class ReminderService
{
    public function send(Appointment $appointment): bool
    {
        $appointment->loadMissing('patient', 'dentist');

        $email = $appointment->patient?->email;

        if (! $email) {
            return false;
        }

        $date = Carbon::parse($appointment->appointment_date)->format('l, F j, Y');
        $time = Carbon::parse($appointment->appointment_time)->format('g:i A');

        $text = "Hello {$appointment->patient->full_name},\n\n"
              . "This is a reminder of your appointment at DentaCare.\n"
              . "Service: {$appointment->service}\n"
              . "Dentist: {$appointment->dentist->name}\n"
              . "When: {$date} at {$time}\n\n"
              . "If you need to reschedule, please contact the clinic.";

        Mail::raw($text, function ($message) use ($email) {
            $message->to($email)->subject('Appointment reminder - DentaCare');
        });

        $appointment->reminder_sent_at = Carbon::now();
        $appointment->saveQuietly();

        ActivityLog::record('Reminder sent', $appointment);

        return true;
    }
}