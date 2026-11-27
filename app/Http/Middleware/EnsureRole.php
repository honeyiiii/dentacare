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
        return Auth::check() && Auth::user()->role === $role && session('role') === $role
            ? $next($request)
            : redirect()->route('login');
    }
}
