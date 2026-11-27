<?php

namespace Tests\Feature\Auth;

use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_renders_and_patient_login_opens_patient_dashboard(): void
    {
        $user = User::create(['name' => 'Patient', 'email' => 'patient@example.test', 'password' => 'password123', 'role' => 'patient', 'email_verified_at' => now()]);
        Patient::create(['user_id' => $user->id, 'full_name' => 'Patient', 'date_of_birth' => '1990-01-01', 'sex' => 'Female', 'contact_number' => '09123456789', 'address' => 'Address', 'email' => $user->email]);

        $this->get(route('login'))->assertRedirect(route('welcome.page'));
        $this->get(route('welcome.page'))->assertOk();
        $this->get(route('login', 'patient'))->assertOk();
        $this->post(route('login.submit'), ['email' => $user->email, 'password' => 'password123'])
            ->assertRedirect(route('patient.dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_invalid_credentials_are_rejected(): void
    {
        User::create(['name' => 'Patient', 'email' => 'patient@example.test', 'password' => 'password123', 'role' => 'patient']);

        $this->post(route('login.submit'), ['email' => 'patient@example.test', 'password' => 'wrong-password'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_unverified_login_is_sent_to_verification_notice(): void
    {
        User::create(['name' => 'Patient', 'email' => 'unverified@example.test', 'password' => 'password123', 'role' => 'patient']);

        $this->post(route('login.submit'), ['email' => 'unverified@example.test', 'password' => 'password123'])
            ->assertRedirect(route('verification.notice'));
        $this->assertAuthenticated();
        $this->get(route('patient.dashboard'))->assertRedirect(route('verification.notice'));
    }

    public function test_deactivated_user_cannot_sign_in(): void
    {
        User::create(['name' => 'Patient', 'email' => 'disabled@example.test', 'password' => 'password123', 'role' => 'patient', 'email_verified_at' => now(), 'is_active' => false]);

        $this->post(route('login.submit'), ['email' => 'disabled@example.test', 'password' => 'password123'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_deactivated_user_is_logged_out_of_an_existing_session(): void
    {
        $user = User::create(['name' => 'Patient', 'email' => 'disabled@example.test', 'password' => 'password123', 'role' => 'patient', 'email_verified_at' => now(), 'is_active' => false]);
        $this->actingAs($user)->withSession(['role' => 'patient'])
            ->get(route('patient.dashboard'))
            ->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_logout_invalidates_the_session(): void
    {
        $user = User::create(['name' => 'Patient', 'email' => 'patient@example.test', 'password' => 'password123', 'role' => 'patient']);
        $this->actingAs($user)->withSession(['role' => 'patient'])->post(route('logout'))
            ->assertRedirect(route('welcome'));
        $this->assertGuest();
    }
}
