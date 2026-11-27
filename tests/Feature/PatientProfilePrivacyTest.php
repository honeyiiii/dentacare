<?php

namespace Tests\Feature;

use App\Models\Patient;
use App\Models\Appointment;
use App\Models\PrivacyRequest;
use App\Models\QueueEntry;
use App\Models\Reminder;
use App\Models\SymptomCheck;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PatientProfilePrivacyTest extends TestCase
{
    use RefreshDatabase;

    public function test_patient_can_update_their_own_profile(): void
    {
        [$user, $patient] = $this->makePatient();
        $this->actingAs($user)->withSession(['role' => 'patient', 'user' => [
            'name' => $user->name, 'email' => $user->email, 'dob' => '1990-01-01', 'sex' => 'Female',
            'phone' => '09123456789', 'addr' => 'Address', 'photo' => '',
        ]])->get(route('patient.profile'))->assertOk();

        $this->post(route('patient.profile'), [
            'name' => 'Updated Patient', 'email' => 'updated@example.test', 'dob' => '1991-02-03',
            'sex' => 'Male', 'phone' => '09987654321', 'addr' => 'New address',
        ])->assertRedirect(route('patient.profile'));

        $this->assertDatabaseHas('users', ['id' => $user->id, 'name' => 'Updated Patient', 'email' => 'updated@example.test']);
        $this->assertDatabaseHas('patients', ['id' => $patient->id, 'full_name' => 'Updated Patient', 'contact_number' => '09987654321', 'address' => 'New address']);
    }

    public function test_patient_can_submit_a_deletion_request(): void
    {
        [$user, $patient] = $this->makePatient();
        $this->actingAs($user)->withSession(['role' => 'patient'])
            ->post(route('patient.privacy-requests.create'), ['request_type' => 'Deletion', 'message' => 'Please delete my account'])
            ->assertRedirect(route('patient.profile'));

        $this->assertDatabaseHas('privacy_requests', ['patient_id' => $patient->id, 'request_type' => 'Deletion', 'status' => 'Open']);
    }

    public function test_front_desk_processing_a_deletion_request_removes_direct_identifiers(): void
    {
        [$patientUser, $patient] = $this->makePatient();
        $dentist = User::create(['name' => 'Dentist', 'email' => 'dentist@example.test', 'password' => 'password123', 'role' => 'admin', 'staff_title' => 'Dentist']);
        $appointment = Appointment::create([
            'patient_id' => $patient->id, 'dentist_id' => $dentist->id, 'service' => 'Consultation',
            'appointment_date' => now()->addDays(5)->toDateString(), 'appointment_time' => '10:00',
            'status' => 'Booked', 'slot_key' => $dentist->id . '|delete-request|10:00', 'notes' => 'Private free text',
        ]);
        $reminder = Reminder::create(['appointment_id' => $appointment->id, 'channel' => 'email', 'status' => 'pending']);
        $queue = QueueEntry::create(['patient_id' => $patient->id, 'appointment_id' => $appointment->id, 'patient_name' => $patient->full_name, 'service' => 'Consultation', 'queue_date' => today(), 'position' => 1, 'status' => 'Waiting']);
        $patient->symptomChecks()->create(['symptoms' => ['Toothache'], 'conditions' => ['predictions' => []], 'pain_level' => 5, 'duration' => '1 to 3 days', 'urgency' => 'Low']);
        $request = PrivacyRequest::create(['patient_id' => $patient->id, 'request_type' => 'Deletion', 'status' => 'Open']);
        $frontDesk = User::create(['name' => 'Receptionist', 'email' => 'desk@example.test', 'password' => 'password123', 'role' => 'admin', 'staff_title' => 'Front desk']);

        $this->actingAs($frontDesk)->withSession(['role' => 'admin', 'admin' => ['role' => 'Front desk']])
            ->post(route('admin.privacy-requests.update', $request->id), ['status' => 'Completed'])
            ->assertRedirect();

        $this->assertDatabaseMissing('users', ['id' => $patientUser->id]);
        $this->assertDatabaseHas('patients', ['id' => $patient->id, 'full_name' => 'Deleted patient']);
        $this->assertNotNull($patient->fresh()->deleted_at);
        $this->assertSame('1900-01-01', $patient->fresh()->date_of_birth->toDateString());
        $this->assertDatabaseHas('appointments', ['id' => $appointment->id, 'notes' => null]);
        $this->assertDatabaseHas('reminders', ['id' => $reminder->id, 'status' => 'cancelled']);
        $this->assertDatabaseHas('queue_entries', ['id' => $queue->id, 'patient_id' => null, 'patient_name' => 'Deleted patient']);
        $this->assertDatabaseMissing('symptom_checks', ['patient_id' => $patient->id]);
        $this->assertDatabaseHas('privacy_requests', ['id' => $request->id, 'status' => 'Completed']);
    }

    public function test_staff_cannot_change_their_own_role_in_profile(): void
    {
        $staff = User::create(['name' => 'Receptionist', 'email' => 'staff@example.test', 'password' => 'password123', 'role' => 'admin', 'staff_title' => 'Front desk']);
        $this->actingAs($staff)->withSession(['role' => 'admin', 'admin' => ['role' => 'Front desk']])
            ->post(route('admin.profile'), ['role' => 'Dentist', 'name' => 'Receptionist'])
            ->assertRedirect(route('admin.profile'));

        $this->assertSame('Front desk', $staff->fresh()->staff_title);
    }

    private function makePatient(): array
    {
        $user = User::create(['name' => 'Patient', 'email' => 'patient@example.test', 'password' => 'password123', 'role' => 'patient']);
        $patient = Patient::create([
            'user_id' => $user->id, 'full_name' => $user->name, 'date_of_birth' => '1990-01-01',
            'sex' => 'Female', 'contact_number' => '09123456789', 'address' => 'Address', 'email' => $user->email,
        ]);
        return [$user, $patient];
    }
}
