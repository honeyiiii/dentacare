<?php

namespace App\Policies;

use App\Models\Appointment;
use App\Models\Patient;
use App\Models\User;

class AppointmentPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['owner', 'dentist', 'assistant', 'receptionist'], true)
            || ($user->role === 'patient' && Patient::where('user_id', $user->id)->exists());
    }

    public function view(User $user, Appointment $appointment): bool
    {
        return in_array($user->role, ['owner', 'assistant', 'receptionist'], true)
            || ($user->role === 'dentist' && $appointment->dentist_id === $user->id)
            || ($user->role === 'patient' && $appointment->patient?->user_id === $user->id);
    }

    public function create(User $user): bool
    {
        return in_array($user->role, ['owner', 'receptionist'], true)
            || ($user->role === 'patient' && Patient::where('user_id', $user->id)->exists());
    }

    public function cancel(User $user, Appointment $appointment): bool
    {
        return in_array($user->role, ['owner', 'receptionist'], true)
            || ($user->role === 'dentist' && $appointment->dentist_id === $user->id)
            || ($user->role === 'patient'
                && $appointment->patient?->user_id === $user->id
                && $appointment->status === 'scheduled'
                && ($appointment->appointment_date->toDateString() > now()->toDateString()
                    || ($appointment->appointment_date->toDateString() === now()->toDateString()
                        && $appointment->appointment_time > now()->format('H:i:s'))));
    }
}
