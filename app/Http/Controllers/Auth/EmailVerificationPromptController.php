<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmailVerificationPromptController extends Controller
{
    /**
     * Display the email verification prompt.
     */
    public function __invoke(Request $request): RedirectResponse|View
    {
        abort_unless($request->user()->is_active, 403);
        $dashboard = $request->user()->role === 'patient' ? route('patient.dashboard') : route('admin.dashboard');

        return $request->user()->hasVerifiedEmail()
                    ? redirect()->intended($dashboard)
                    : view('auth.verify-email');
    }
}
