<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    public function create(): View
    {
        return view('auth.register');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name'           => ['required', 'string', 'max:255'],
            'date_of_birth'  => ['required', 'date', 'before:today'],
            'sex'            => ['required', 'in:Female,Male'],
            'contact_number' => ['required', 'string', 'max:30'],
            'address'        => ['required', 'string', 'max:255'],
            'email'          => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
            'password'       => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = DB::transaction(function () use ($data) {
            $user = new User();
            $user->forceFill([
                'name'              => $data['name'],
                'email'             => $data['email'],
                'password'          => Hash::make($data['password']),
                'role'              => 'patient',
                'is_active'         => true,
                'email_verified_at' => now(),
            ])->save();

            Patient::create([
                'user_id'        => $user->id,
                'full_name'      => $data['name'],
                'date_of_birth'  => $data['date_of_birth'],
                'sex'            => $data['sex'],
                'contact_number' => $data['contact_number'],
                'address'        => $data['address'],
                'email'          => $data['email'],
            ]);

            return $user;
        });

        event(new Registered($user));
        Auth::login($user);

        return redirect(route('dashboard'));
    }
}
