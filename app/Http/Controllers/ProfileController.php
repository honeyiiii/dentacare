<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\ClinicalRecordAudit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validated();
        $patient = $user->role === 'patient' ? $user->patientProfile : null;
        $patientBefore = $patient?->only(['full_name', 'email']);

        $user->fill($data);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        DB::transaction(function () use ($user, $patient, $patientBefore): void {
            $user->save();

            if ($patient) {
                $patient->update(['full_name' => $user->name, 'email' => $user->email]);
                ClinicalRecordAudit::create([
                    'patient_id' => $patient->id,
                    'user_id' => $user->id,
                    'record_type' => 'patients',
                    'record_id' => $patient->id,
                    'event' => 'updated',
                    'changes' => ['before' => $patientBefore, 'after' => $patient->only(['full_name', 'email'])],
                    'created_at' => now(),
                ]);
            }
        });

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
