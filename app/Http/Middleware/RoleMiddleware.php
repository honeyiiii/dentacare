<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if (!$request->user() || ! $request->user()->is_active || !in_array($request->user()->role, $roles, true)) {
            abort(403, 'You do not have access to this page.');
        }

        if (! $request->user()->hasVerifiedEmail()) return redirect()->route('verification.notice');

        return $next($request);
    }
}
