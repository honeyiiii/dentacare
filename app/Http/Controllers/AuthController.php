<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class AuthController extends Controller
{
    public function login(Request $request, ?string $role = null)
    {
        if ($request->isMethod('post')) {
            $request->merge(['email' => strtolower(trim((string) $request->input('email')))]);
            $credentials = $request->validate([
                'email' => 'required|email',
                'password' => 'required|string',
            ]);

            $account = User::where('email', $credentials['email'])->first();
            if (! Auth::attempt($credentials) || ! $account?->is_active) {
                Auth::logout();
                return back()->withErrors(['email' => 'These credentials do not match our records.'])->onlyInput('email');
            }

            $request->session()->regenerate();
            $user = Auth::user();

            if (! in_array($user->role, ['patient', 'admin'], true)) {
                Auth::logout();
                return back()->withErrors(['email' => 'This account type cannot access the clinic portal.'])->onlyInput('email');
            }

            $request->session()->put('role', $user->role);

            if (! $user->hasVerifiedEmail()) return redirect()->route('verification.notice');

            if ($user->role === 'patient') {
                $patient = Patient::where('user_id', $user->id)->first();
                $request->session()->put('user', [
                    'name' => $user->name,
                    'email' => $user->email,
                    'dob' => $patient?->date_of_birth?->format('Y-m-d') ?? '',
                    'sex' => $patient?->sex ?? 'Female',
                    'phone' => $patient?->contact_number ?? '',
                    'addr' => $patient?->address ?? '',
                    'photo' => $patient?->avatar_path ? route('patient.avatar') : '',
                ]);
                return redirect()->route('patient.dashboard');
            }

            $request->session()->put('admin', [
                'name' => $user->name,
                'role' => $user->staff_title ?: 'Front desk',
                'email' => $user->email,
                'phone' => '',
                'photo' => $user->avatar_path ? route('admin.avatar') : '',
            ]);

            return redirect()->route('admin.dashboard');
        }

        return view('auth.login');
    }

    public function signup(Request $request, ?string $role = null)
    {
        if ($request->isMethod('post')) {
            $request->merge(['email' => strtolower(trim((string) $request->input('email')))]);
            $type = $request->input('account_type', 'patient');
            $rules = [
                'account_type' => ['required', Rule::in(['patient'])],
                'name' => 'required|string|max:80',
                'email' => 'required|string|email|max:255|unique:users,email',
                'password' => 'required|string|min:8|confirmed',
            ];

            $rules += [
                'dob' => 'required|date',
                'sex' => ['required', Rule::in(['Female', 'Male'])],
                'phone' => 'required|string|max:30',
                'addr' => 'required|string|max:200',
            ];

            $data = $request->validate($rules);

            [$user, $patient] = DB::transaction(function () use ($data) {
                $user = User::create([
                    'name' => $data['name'],
                    'email' => strtolower($data['email']),
                    'password' => Hash::make($data['password']),
                    'role' => 'patient',
                    'staff_title' => null,
                ]);
                $patient = Patient::create([
                    'user_id' => $user->id,
                    'full_name' => $data['name'],
                    'date_of_birth' => $data['dob'],
                    'sex' => $data['sex'],
                    'contact_number' => $data['phone'],
                    'address' => $data['addr'],
                    'email' => $user->email,
                ]);
                AuditLog::create(['actor_id' => $user->id, 'action' => 'patient.account_created', 'subject_type' => 'Patient', 'subject_id' => $patient->id]);
                return [$user, $patient];
            });

            event(new Registered($user));
            Auth::login($user);
            $request->session()->regenerate();
            $request->session()->put('role', $user->role);

            $request->session()->put('user', [
                'name' => $user->name, 'email' => $user->email, 'dob' => $data['dob'],
                'sex' => $data['sex'], 'phone' => $data['phone'], 'addr' => $data['addr'], 'photo' => $patient->avatar_path ? route('patient.avatar') : '',
            ]);
            return redirect()->route('verification.notice')->with('flash', 'Account created. Verify your email to continue.');
        }

        return view('auth.signup');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('welcome');
    }
}
