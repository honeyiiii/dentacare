<?php

namespace Database\Seeders;

use App\Models\Patient;
use App\Models\Appointment;
use App\Models\Reminder;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $staff = [
            ['Owner', 'owner@dentacare.test', 'Front desk'],
            ['Dr. Maya Chen', 'dentist@dentacare.test', 'Dentist'],
            ['Dr. Sam Lim', 'sam.lim@dentacare.test', 'Dentist'],
            ['Dr. Leah', 'leah@dentacare.test', 'Dentist'],
            ['Clinic Assistant', 'assistant@dentacare.test', 'Front desk'],
            ['Receptionist', 'receptionist@dentacare.test', 'Front desk'],
        ];

        foreach ($staff as [$name, $email, $title]) {
            User::updateOrCreate(
                ['email' => $email],
                ['name' => $name, 'password' => 'password123', 'role' => 'admin', 'staff_title' => $title],
            );
        }

        $patient = User::updateOrCreate(
            ['email' => 'patient@dentacare.test'],
            ['name' => 'Demo Patient', 'password' => 'password123', 'role' => 'patient', 'staff_title' => null],
        );

        Patient::updateOrCreate(
            ['user_id' => $patient->id],
            [
                'full_name' => $patient->name,
                'date_of_birth' => '1995-04-12',
                'sex' => 'Female',
                'contact_number' => '09123456789',
                'address' => 'DentaCare demo address',
                'email' => $patient->email,
            ],
        );

        $dentist = User::where('email', 'dentist@dentacare.test')->firstOrFail();
        $patient->patient()->update(['assigned_dentist_id' => $dentist->id]);
        $date = now()->addDays(5)->startOfDay();
        while ($date->isWeekend()) $date->addDay();
        $appointment = Appointment::firstOrCreate(
            ['patient_id' => $patient->patient->id, 'appointment_date' => $date->toDateString(), 'appointment_time' => '10:00:00'],
            ['dentist_id' => $dentist->id, 'service' => 'Routine check-up', 'status' => 'Booked', 'slot_key' => $dentist->id . '|' . $date->toDateString() . '|10:00'],
        );
        Reminder::firstOrCreate(['appointment_id' => $appointment->id, 'channel' => 'email'], ['scheduled_at' => $date->copy()->subDay()]);
    }
}
