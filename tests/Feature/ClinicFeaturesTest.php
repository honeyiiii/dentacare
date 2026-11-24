<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClinicFeaturesTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role): User
    {
        return User::factory()->create(['role' => $role, 'is_active' => true]);
    }

    private function patient(array $overrides = []): Patient
    {
        return Patient::forceCreate($overrides + [
            'full_name'      => 'Test Patient',
            'date_of_birth'  => '1995-01-01',
            'sex'            => 'Male',
            'contact_number' => '09123456789',
            'address'        => 'Cebu City',
            'email'          => 'patient@example.com',
        ]);
    }

    private function appointment(User $dentist, Patient $patient, array $overrides = []): Appointment
    {
        return Appointment::forceCreate($overrides + [
            'patient_id'       => $patient->id,
            'dentist_id'       => $dentist->id,
            'service'          => 'Cleaning',
            'appointment_date' => now()->addDay()->toDateString(),
            'appointment_time' => '10:00:00',
            'status'           => 'scheduled',
        ]);
    }

    public function test_receptionist_cannot_open_staff_page(): void
    {
        $this->actingAs($this->user('receptionist'))
            ->get('/staff')
            ->assertForbidden();
    }

    public function test_owner_can_open_staff_page(): void
    {
        $this->actingAs($this->user('owner'))
            ->get('/staff')
            ->assertOk();
    }

    public function test_double_booking_the_same_dentist_and_time_is_rejected(): void
    {
        $dentist = $this->user('dentist');
        $patient = $this->patient();
        $date    = now()->addDays(2)->toDateString();

        $payload = [
            'patient_id'       => $patient->id,
            'dentist_id'       => $dentist->id,
            'service'          => 'Cleaning',
            'appointment_date' => $date,
            'appointment_time' => '10:00',
        ];

        $staff = $this->user('receptionist');

        $this->actingAs($staff)->post('/appointments', $payload)
            ->assertRedirect(route('appointments.index'));

        $this->actingAs($staff)->post('/appointments', $payload)
            ->assertSessionHasErrors('appointment_time');

        $this->assertSame(1, Appointment::count());
    }

    public function test_cancelled_slot_can_be_booked_again(): void
    {
        $dentist = $this->user('dentist');
        $patient = $this->patient();
        $date    = now()->addDays(2)->toDateString();

        $this->appointment($dentist, $patient, [
            'appointment_date' => $date,
            'status'           => 'cancelled',
        ]);

        $this->actingAs($this->user('receptionist'))->post('/appointments', [
            'patient_id'       => $patient->id,
            'dentist_id'       => $dentist->id,
            'service'          => 'Cleaning',
            'appointment_date' => $date,
            'appointment_time' => '10:00',
        ])->assertRedirect(route('appointments.index'));

        $this->assertSame(2, Appointment::count());
    }

    public function test_past_dates_are_rejected(): void
    {
        $dentist = $this->user('dentist');
        $patient = $this->patient();

        $this->actingAs($this->user('receptionist'))->post('/appointments', [
            'patient_id'       => $patient->id,
            'dentist_id'       => $dentist->id,
            'service'          => 'Cleaning',
            'appointment_date' => now()->subDay()->toDateString(),
            'appointment_time' => '10:00',
        ])->assertSessionHasErrors('appointment_date');
    }

    public function test_reminders_are_sent_only_for_tomorrow_and_only_once(): void
    {
        $dentist = $this->user('dentist');
        $patient = $this->patient();

        $tomorrow  = $this->appointment($dentist, $patient);
        $later     = $this->appointment($dentist, $patient, [
            'appointment_date' => now()->addDays(5)->toDateString(),
            'appointment_time' => '11:00:00',
        ]);

        $this->artisan('appointments:send-reminders')->assertSuccessful();

        $this->assertNotNull($tomorrow->fresh()->reminder_sent_at);
        $this->assertNull($later->fresh()->reminder_sent_at);

        $firstSentAt = $tomorrow->fresh()->reminder_sent_at;

        $this->artisan('appointments:send-reminders')->assertSuccessful();

        $this->assertEquals($firstSentAt, $tomorrow->fresh()->reminder_sent_at);
    }
}