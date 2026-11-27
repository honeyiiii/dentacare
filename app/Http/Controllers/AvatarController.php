<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Patient;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AvatarController extends Controller
{
    public function patient(): StreamedResponse
    {
        abort_unless(Auth::user()?->role === 'patient', 403);
        $patient = Patient::where('user_id', Auth::id())->firstOrFail();
        abort_unless($patient->avatar_path && Storage::disk('local')->exists($patient->avatar_path), 404);
        AuditLog::create(['actor_id' => Auth::id(), 'action' => 'patient.avatar_viewed', 'subject_type' => 'Patient', 'subject_id' => $patient->id]);
        return Storage::disk('local')->response($patient->avatar_path, null, ['Content-Disposition' => 'inline']);
    }

    public function staff(): StreamedResponse
    {
        abort_unless(Auth::user()?->role === 'admin', 403);
        $user = Auth::user();
        abort_unless($user->avatar_path && Storage::disk('local')->exists($user->avatar_path), 404);
        AuditLog::create(['actor_id' => Auth::id(), 'action' => 'staff.avatar_viewed', 'subject_type' => 'User', 'subject_id' => $user->id]);
        return Storage::disk('local')->response($user->avatar_path, null, ['Content-Disposition' => 'inline']);
    }
}
