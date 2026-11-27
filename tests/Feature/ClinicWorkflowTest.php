<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Patient;
use App\Models\SymptomCheck;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ClinicWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_registration_creates_a_patient_record_atomically(): void
    {
        $response = $this->post(route('signup.submit'), [
            'account_type' => 'patient', 'name' => 'New Patient', 'email' => 'new@example.test',
            'password' => 'long-password-123', 'password_confirmation' => 'long-password-123',
            'dob' => '1998-05-10', 'sex' => 'Female', 'phone' => '09123456789', 'addr' => 'Sample address',
        ]);

        $response->assertRedirect(route('verification.notice'));
        $user = User::where('email', 'new@example.test')->firstOrFail();
        $this->assertDatabaseHas('patients', ['user_id' => $user->id, 'email' => 'new@example.test']);
        $this->assertSame('patient', $user->role);
        $this->assertAuthenticatedAs($user);
        $this->assertFalse($user->hasVerifiedEmail());
    }

    public function test_patient_cannot_cancel_an_appointment(): void
    {
        [$patientA, $patientB, $dentist] = $this->makePatientPair();
        $appointment = Appointment::create([
            'patient_id' => $patientA->patient->id, 'dentist_id' => $dentist->id, 'service' => 'Routine check-up',
            'appointment_date' => now()->addDays(5)->toDateString(), 'appointment_time' => '10:00',
            'status' => 'Booked', 'slot_key' => $dentist->id . '|other|10:00',
        ]);

        $this->actingAs($patientA)->withSession(['role' => 'patient'])
            ->post(route('patient.appointments'), ['id' => $appointment->id])
            ->assertForbidden();

        $this->assertDatabaseHas('appointments', ['id' => $appointment->id, 'status' => 'Booked']);
    }

    public function test_assistant_cannot_cancel_but_assigned_dentist_can(): void
    {
        [$patientUser, , $dentist] = $this->makePatientPair();
        $appointment = Appointment::create([
            'patient_id' => $patientUser->patient->id, 'dentist_id' => $dentist->id, 'service' => 'Routine check-up',
            'appointment_date' => now()->addDays(5)->toDateString(), 'appointment_time' => '10:00', 'status' => 'Booked',
        ]);
        $assistant = User::create(['name' => 'Assistant', 'email' => 'assistant@example.test', 'password' => 'password', 'role' => 'admin', 'staff_title' => 'Assistant', 'email_verified_at' => now()]);

        $this->actingAs($assistant)->withSession(['role' => 'admin', 'admin' => ['role' => 'Assistant']])
            ->post(route('admin.calendar'), ['id' => $appointment->id, 'act' => 'cancel'])->assertForbidden();
        $this->assertSame('Booked', $appointment->fresh()->status);

        $this->actingAs($dentist)->withSession(['role' => 'admin', 'admin' => ['role' => 'Dentist']])
            ->post(route('admin.calendar'), ['id' => $appointment->id, 'act' => 'cancel'])->assertRedirect(route('admin.calendar'));
        $this->assertSame('Cancelled', $appointment->fresh()->status);
    }

    public function test_database_unique_slot_key_rejects_duplicate_booking_without_controller_check(): void
    {
        [$patientA, $patientB, $dentist] = $this->makePatientPair();
        $date = now()->addDays(6)->toDateString();
        Appointment::create([
            'patient_id' => $patientA->patient->id, 'dentist_id' => $dentist->id, 'service' => 'Routine check-up',
            'appointment_date' => $date, 'appointment_time' => '10:00', 'status' => 'Booked',
        ]);

        $this->expectException(QueryException::class);
        Appointment::create([
            'patient_id' => $patientB->patient->id, 'dentist_id' => $dentist->id, 'service' => 'Filling',
            'appointment_date' => $date, 'appointment_time' => '10:00', 'status' => 'Booked',
        ]);
    }

    public function test_booking_rejects_a_dentist_time_slot_that_is_already_taken(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-30 08:00:00'));
        [$patient, , $dentist] = $this->makePatientPair();
        $date = '2026-12-01';
        Appointment::create([
            'patient_id' => $patient->patient->id, 'dentist_id' => $dentist->id, 'service' => 'Routine check-up',
            'appointment_date' => $date, 'appointment_time' => '10:00', 'status' => 'Booked',
            'slot_key' => $dentist->id . '|' . $date . '|10:00',
        ]);

        $this->actingAs($patient)->withSession(['role' => 'patient'])
            ->post(route('patient.book'), [
                'svc' => 'Filling', 'dentist_id' => $dentist->id, 'date' => $date,
                'time' => '10:00', 'notes' => '',
            ])
            ->assertSessionHasErrors('time');
        $this->assertDatabaseCount('appointments', 1);
        Carbon::setTestNow();
    }

    public function test_symptom_check_is_validated_and_json_is_saved_as_arrays(): void
    {
        [$user] = $this->makePatientPair();
        $this->actingAs($user)->withSession(['role' => 'patient'])
            ->postJson(route('patient.symptoms'), [
                'symptoms' => [], 'pain_level' => 11, 'duration' => 'forever',
            ])->assertUnprocessable()->assertJsonValidationErrors(['symptoms', 'pain_level', 'duration']);

        $response = $this->postJson(route('patient.symptoms'), [
            'symptoms' => ['Toothache', 'Swelling in face'], 'pain_level' => 8, 'duration' => '1 to 3 days', 'area' => 'upper left',
        ])->assertOk()->assertJsonPath('disclaimer', 'Prediction only, not a diagnosis.');

        $check = SymptomCheck::latest('id')->firstOrFail();
        $this->assertIsArray($check->symptoms);
        $this->assertIsArray($check->conditions);
        $this->assertSame(['Toothache', 'Swelling in face'], $check->symptoms);
        $this->assertSame('High', $response->json('urg'));
    }

    public function test_booking_rejects_a_time_that_has_already_passed_today(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-30 10:30:00'));
        [$patient, , $dentist] = $this->makePatientPair();
        $this->actingAs($patient)->withSession(['role' => 'patient'])
            ->post(route('patient.book'), [
                'svc' => 'Filling', 'dentist_id' => $dentist->id, 'date' => '2026-11-30',
                'time' => '10:00', 'notes' => '',
            ])->assertSessionHasErrors('time');

        $this->assertDatabaseCount('appointments', 0);
        $this->post(route('patient.book'), [
            'svc' => 'Filling', 'dentist_id' => $dentist->id, 'date' => '2026-12-01',
            'time' => '12:00', 'notes' => '',
        ])->assertSessionHasErrors('time');
        $this->assertDatabaseCount('appointments', 0);
        Carbon::setTestNow();
    }

    private function makePatientPair(): array
    {
        $dentist = User::create(['name' => 'Dentist', 'email' => 'dentist@example.test', 'password' => 'password', 'role' => 'admin', 'staff_title' => 'Dentist', 'email_verified_at' => now()]);
        $users = [];
        $patients = [];
        foreach (['a', 'b'] as $suffix) {
            $user = User::create(['name' => 'Patient ' . $suffix, 'email' => $suffix . '@example.test', 'password' => 'password', 'role' => 'patient', 'email_verified_at' => now()]);
            $users[] = $user;
            $patients[] = Patient::create([
                'user_id' => $user->id, 'assigned_dentist_id' => $dentist->id, 'full_name' => $user->name,
                'date_of_birth' => '1990-01-01', 'sex' => 'Female', 'contact_number' => '09123456789',
                'address' => 'Sample address', 'email' => $user->email,
            ]);
        }
        return [$users[0], $users[1], $dentist];
    }
}
