<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginThrottleTest extends TestCase
{
    use RefreshDatabase;

    public function test_failed_logins_are_throttled(): void
    {
        for ($attempt = 1; $attempt <= 6; $attempt++) {
            $this->post(route('login.submit'), ['email' => 'nobody@example.test', 'password' => 'wrong-password'])
                ->assertRedirect();
        }

        $this->post(route('login.submit'), ['email' => 'nobody@example.test', 'password' => 'wrong-password'])
            ->assertTooManyRequests();
    }
}
