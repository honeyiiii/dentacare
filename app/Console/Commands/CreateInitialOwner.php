<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class CreateInitialOwner extends Command
{
    protected $signature = 'clinic:create-owner';
    protected $description = 'Create the first DentaCare clinic owner account';

    public function handle(): int
    {
        if (User::where('role', 'owner')->exists()) {
            $this->error('An owner account already exists. Use owner-managed staff endpoints to add more staff.');
            return self::FAILURE;
        }

        $name = trim($this->ask('Owner name') ?? '');
        $email = strtolower(trim($this->ask('Owner email') ?? ''));
        $password = $this->secret('Owner password');
        $confirmation = $this->secret('Confirm password');

        $validator = Validator::make([
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'password_confirmation' => $confirmation,
        ], [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }
            return self::FAILURE;
        }

        User::forceCreate([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
            'role' => 'owner',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $this->info('Initial owner account created.');
        return self::SUCCESS;
    }
}
