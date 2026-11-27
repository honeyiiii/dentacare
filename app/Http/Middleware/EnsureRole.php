<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    public function handle(Request $request, Closure $next, string $role): Response
    {
        if (! Auth::check()) return redirect()->route('login');
        if (! Auth::user()->is_active) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            return redirect()->route('login')->withErrors(['email' => 'These credentials do not match our records.']);
        }

        if (Auth::user()->role !== $role || session('role') !== $role) return redirect()->route('login');
        if (! Auth::user()->hasVerifiedEmail()) return redirect()->route('verification.notice');

        return $next($request);
    }
}
