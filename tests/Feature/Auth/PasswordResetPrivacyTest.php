<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PasswordResetPrivacyTest extends TestCase
{
    use RefreshDatabase;

    public function test_reset_request_message_does_not_reveal_if_an_email_exists(): void
    {
        Notification::fake();
        User::create(['name' => 'Patient', 'email' => 'known@example.test', 'password' => 'password123', 'role' => 'patient']);

        $known = $this->from(route('forgot'))->post(route('password.email'), ['email' => 'known@example.test']);
        $unknown = $this->from(route('forgot'))->post(route('password.email'), ['email' => 'unknown@example.test']);

        $known->assertRedirect(route('forgot'))->assertSessionHas('status');
        $knownMessage = session('status');
        $unknown->assertRedirect(route('forgot'))->assertSessionHas('status');
        $this->assertSame($knownMessage, session('status'));
    }
}
