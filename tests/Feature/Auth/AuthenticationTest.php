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
        $user = User::create(['name' => 'Patient', 'email' => 'patient@example.test', 'password' => 'password123', 'role' => 'patient']);
        Patient::create(['user_id' => $user->id, 'full_name' => 'Patient', 'date_of_birth' => '1990-01-01', 'sex' => 'Female', 'contact_number' => '09123456789', 'address' => 'Address', 'email' => $user->email]);

        $this->get(route('login'))->assertOk();
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

    public function test_logout_invalidates_the_session(): void
    {
        $user = User::create(['name' => 'Patient', 'email' => 'patient@example.test', 'password' => 'password123', 'role' => 'patient']);
        $this->actingAs($user)->withSession(['role' => 'patient'])->post(route('logout'))
            ->assertRedirect(route('welcome'));
        $this->assertGuest();
    }
}
