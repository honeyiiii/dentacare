<?php

namespace App\Policies;

use App\Models\Treatment;
use App\Models\User;

class TreatmentPolicy
{
    public function view(User $user, Treatment $treatment): bool
    {
        return in_array($user->role, ['owner', 'dentist', 'assistant', 'receptionist'], true);
    }
}
