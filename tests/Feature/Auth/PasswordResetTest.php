<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_reset_form_renders_and_valid_single_use_token_resets_password(): void
    {
        Notification::fake();
        $user = User::create(['name' => 'Patient', 'email' => 'patient@example.test', 'password' => 'old-password', 'role' => 'patient']);
        $this->get(route('forgot'))->assertOk();
        $this->post(route('password.email'), ['email' => $user->email])->assertSessionHas('status');

        Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($user) {
            $this->get(route('password.reset', $notification->token))->assertOk();
            $this->post(route('password.store'), [
                'token' => $notification->token, 'email' => $user->email,
                'password' => 'new-password-123', 'password_confirmation' => 'new-password-123',
            ])->assertRedirect(route('login'))->assertSessionHasNoErrors();
            $this->assertTrue(Hash::check('new-password-123', $user->fresh()->password));

            $this->from(route('password.reset', $notification->token))->post(route('password.store'), [
                'token' => $notification->token, 'email' => $user->email,
                'password' => 'another-password', 'password_confirmation' => 'another-password',
            ])->assertSessionHasErrors('email');

            return true;
        });
    }
}
