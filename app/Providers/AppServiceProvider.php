<?php

namespace App\Providers;

use App\Models\ActivityLog;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Event::listen(Login::class, fn (Login $e) => ActivityLog::record(
            'Logged in',
            $e->user,
            null,
            $e->user->getAuthIdentifier()
        ));

        Event::listen(Logout::class, function (Logout $e) {
            if ($e->user) {
                ActivityLog::record('Logged out', $e->user, null, $e->user->getAuthIdentifier());
            }
        });

        Event::listen(Failed::class, fn (Failed $e) => ActivityLog::record(
            'Failed login',
            null,
            'Email: ' . ($e->credentials['email'] ?? 'unknown')
        ));
    }
}