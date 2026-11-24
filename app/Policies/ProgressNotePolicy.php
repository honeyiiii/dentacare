<?php

namespace App\Policies;

use App\Models\ProgressNote;
use App\Models\User;

class ProgressNotePolicy
{
    public function view(User $user, ProgressNote $progressNote): bool
    {
        return in_array($user->role, ['owner', 'dentist', 'assistant', 'receptionist'], true);
    }

    public function update(User $user, ProgressNote $progressNote): bool
    {
        return $progressNote->author_id === $user->id
            && in_array($user->role, ['dentist', 'assistant'], true);
    }
}
