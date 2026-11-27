<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_unverified_user_can_resend_and_confirm_a_signed_email_link(): void
    {
        Notification::fake();
        $user = User::create(['name' => 'Patient', 'email' => 'patient@example.test', 'password' => 'password123', 'role' => 'patient']);
        $this->assertTrue($user->fresh()->is_active);
        $this->actingAs($user)->get(route('verification.notice'))->assertOk();
        $this->post(route('verification.send'))->assertSessionHas('status', 'verification-link-sent');
        Notification::assertSentTo($user, VerifyEmail::class);

        $url = URL::temporarySignedRoute('verification.verify', now()->addMinutes(30), [
            'id' => $user->id, 'hash' => sha1($user->email),
        ]);
        $this->get($url)->assertRedirect(route('patient.dashboard'));
        $this->assertNotNull($user->fresh()->email_verified_at);
    }

    public function test_staff_activation_route_can_deactivate_another_account(): void
    {
        $owner = User::create(['name' => 'Owner', 'email' => 'owner@example.test', 'password' => 'password123', 'role' => 'admin', 'staff_title' => 'Owner', 'email_verified_at' => now()]);
        $staff = User::create(['name' => 'Assistant', 'email' => 'assistant@example.test', 'password' => 'password123', 'role' => 'admin', 'staff_title' => 'Assistant', 'email_verified_at' => now()]);

        $this->actingAs($owner)->withSession(['role' => 'admin', 'admin' => ['role' => 'Owner']])
            ->patch(route('admin.staff.status', $staff->id), ['is_active' => 0])
            ->assertRedirect();
        $this->assertFalse($staff->fresh()->is_active);
    }
}
