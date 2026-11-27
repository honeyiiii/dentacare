<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EmailVerificationNotificationController extends Controller
{
    /**
     * Send a new email verification notification.
     */
    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->is_active, 403);
        if ($request->user()->hasVerifiedEmail()) {
            $dashboard = $request->user()->role === 'patient' ? route('patient.dashboard') : route('admin.dashboard');
            return redirect()->intended($dashboard);
        }

        $request->user()->sendEmailVerificationNotification();

        return back()->with('status', 'verification-link-sent');
    }
}
