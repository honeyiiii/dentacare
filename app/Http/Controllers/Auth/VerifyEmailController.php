<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;

class VerifyEmailController extends Controller
{
    /**
     * Mark the authenticated user's email address as verified.
     */
    public function __invoke(EmailVerificationRequest $request): RedirectResponse
    {
        abort_unless($request->user()->is_active, 403);
        $dashboard = $request->user()->role === 'patient' ? route('patient.dashboard') : route('admin.dashboard');
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->intended($dashboard)->with('verified', true);
        }

        if ($request->user()->markEmailAsVerified()) {
            event(new Verified($request->user()));
        }

        return redirect()->intended($dashboard)->with('verified', true);
    }
}
