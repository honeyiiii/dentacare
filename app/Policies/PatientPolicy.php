<?php

namespace App\Policies;

use App\Models\Patient;
use App\Models\User;

class PatientPolicy
{
    private const STAFF_ROLES = ['owner', 'dentist', 'assistant', 'receptionist'];

    public function viewAny(User $user): bool
    {
        return in_array($user->role, self::STAFF_ROLES, true);
    }

    public function view(User $user, Patient $patient): bool
    {
        return in_array($user->role, self::STAFF_ROLES, true)
            || ($user->role === 'patient' && $patient->user_id === $user->id);
    }

    public function create(User $user): bool
    {
        return in_array($user->role, self::STAFF_ROLES, true);
    }

    public function update(User $user, Patient $patient): bool
    {
        return in_array($user->role, self::STAFF_ROLES, true)
            || ($user->role === 'patient' && $patient->user_id === $user->id);
    }

    public function delete(User $user, Patient $patient): bool
    {
        return in_array($user->role, self::STAFF_ROLES, true);
    }
}
