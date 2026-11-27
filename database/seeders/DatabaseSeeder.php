<?php

namespace Database\Seeders;

use App\Models\Appointment;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $accounts = [
            ['Clinic Owner',     'owner@dentacare.test',        'owner'],
            ['Dr. Maya Chen',    'dentist@dentacare.test',      'dentist'],
            ['Dr. Sam Lim',      'dentist2@dentacare.test',     'dentist'],
            ['Alex Assistant',   'assistant@dentacare.test',    'assistant'],
            ['Rina Receptionist','receptionist@dentacare.test', 'receptionist'],
            ['Sarah Collins',    'patient@dentacare.test',      'patient'],
        ];

        $users = [];

        foreach ($accounts as [$name, $email, $role]) {
            $user = User::firstOrNew(['email' => $email]);
            $user->forceFill([
                'name'              => $name,
                'role'              => $role,
                'password'          => Hash::make('password123'),
                'email_verified_at' => now(),
                'is_active'         => true,
            ])->save();

            $users[$role][] = $user;
        }

        $sarah = Patient::firstOrCreate(
            ['user_id' => $users['patient'][0]->id],
            [
                'full_name'      => 'Sarah Collins',
                'date_of_birth'  => '1998-05-14',
                'sex'            => 'Female',
                'contact_number' => '09171234567',
                'address'        => 'Lahug, Cebu City',
                'email'          => 'patient@dentacare.test',
            ]
        );

        $juan = Patient::firstOrCreate(
            ['full_name' => 'Juan Dela Cruz'],
            [
                'date_of_birth'  => '1990-02-03',
                'sex'            => 'Male',
                'contact_number' => '09181234567',
                'address'        => 'Mandaue City, Cebu',
                'email'          => 'juan@example.com',
            ]
        );

        $drChen = $users['dentist'][0];
        $drLim  = $users['dentist'][1];

        $slots = [
            [$sarah, $drChen, 'Routine check-up', 1, '09:00'],
            [$juan,  $drLim,  'Filling',          1, '10:00'],
            [$sarah, $drChen, 'Routine cleaning', 3, '11:00'],
        ];

        foreach ($slots as [$patient, $dentist, $service, $days, $time]) {
            Appointment::firstOrCreate([
                'patient_id'       => $patient->id,
                'dentist_id'       => $dentist->id,
                'appointment_date' => now()->addDays($days)->toDateString(),
                'appointment_time' => $time,
            ], ['service' => $service]);
        }
    }
}