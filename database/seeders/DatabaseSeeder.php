<?php

namespace Database\Seeders;

use App\Models\Patient;
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
    }
}
