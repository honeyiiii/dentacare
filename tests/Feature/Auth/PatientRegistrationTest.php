<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PatientRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_patient_registration_screen_renders_and_password_confirmation_is_required(): void
    {
        $this->get(route('signup'))->assertOk()->assertSee('Create a patient account');
        $this->post(route('signup.submit'), [
            'account_type' => 'patient', 'name' => 'New Patient', 'email' => 'new@example.test',
            'password' => 'short', 'password_confirmation' => 'different',
            'dob' => '1998-05-10', 'sex' => 'Female', 'phone' => '09123456789', 'addr' => 'Address',
        ])->assertSessionHasErrors('password');

        $this->assertDatabaseMissing('users', ['email' => 'new@example.test']);
    }
}
