<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    private const STAFF = ['owner', 'dentist', 'assistant', 'receptionist'];

    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        if (! $user->is_active) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            return redirect()->route('login')
                ->withErrors(['email' => 'These credentials do not match our records.']);
        }

        // Every staff role counts as "admin" in the clinic portal
        $effective = in_array($user->role, self::STAFF, true) ? 'admin' : $user->role;

        if (! in_array($effective, $roles, true)
            || ! in_array(session('role'), [$user->role, $effective], true)) {
            return redirect()->route('login');
        }

        if (! $user->hasVerifiedEmail()) {
            return redirect()->route('verification.notice');
        }

        return $next($request);
    }
}