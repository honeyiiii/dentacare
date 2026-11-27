<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_patient_role_cannot_open_staff_calendar_even_with_a_direct_url(): void
    {
        $patient = User::create(['name' => 'Patient', 'email' => 'patient@example.test', 'password' => 'password123', 'role' => 'patient']);
        $this->actingAs($patient)->withSession(['role' => 'patient'])
            ->get(route('admin.calendar'))
            ->assertRedirect(route('login'));
    }

    public function test_public_signup_rejects_staff_account_creation(): void
    {
        $this->post(route('signup.submit'), [
            'account_type' => 'admin', 'name' => 'Unauthorized Staff', 'email' => 'staff@example.test',
            'password' => 'long-password-123', 'password_confirmation' => 'long-password-123',
            'dob' => '1990-01-01', 'sex' => 'Female', 'phone' => '09123456789', 'addr' => 'Address',
        ])->assertSessionHasErrors('account_type');

        $this->assertDatabaseMissing('users', ['email' => 'staff@example.test']);
    }
}
