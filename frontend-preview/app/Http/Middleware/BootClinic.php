<?php

namespace App\Http\Middleware;

use App\Support\Clinic;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BootClinic
{
    public function handle(Request $request, Closure $next): Response
    {
        Clinic::boot();
        view()->share('C', Clinic::class);

        return $next($request);
    }
}
