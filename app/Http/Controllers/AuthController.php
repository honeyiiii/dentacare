<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
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

            if (! Auth::attempt($credentials)) {
                return back()->withErrors(['email' => 'These credentials do not match our records.'])->onlyInput('email');
            }

            $request->session()->regenerate();
            $user = Auth::user();

            if (! in_array($user->role, ['patient', 'admin'], true)) {
                Auth::logout();
                return back()->withErrors(['email' => 'This account type cannot access the clinic portal.'])->onlyInput('email');
            }

            $request->session()->put('role', $user->role);

            if ($user->role === 'patient') {
                $patient = Patient::where('user_id', $user->id)->first();
                $request->session()->put('user', [
                    'name' => $user->name,
                    'email' => $user->email,
                    'dob' => $patient?->date_of_birth?->format('Y-m-d') ?? '',
                    'sex' => $patient?->sex ?? 'Female',
                    'phone' => $patient?->contact_number ?? '',
                    'addr' => $patient?->address ?? '',
                    'photo' => '',
                ]);
                return redirect()->route('patient.dashboard');
            }

            $request->session()->put('admin', [
                'name' => $user->name,
                'role' => $user->staff_title ?: 'Front desk',
                'email' => $user->email,
                'phone' => '',
                'photo' => '',
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
                'account_type' => ['required', Rule::in(['patient', 'staff'])],
                'name' => 'required|string|max:80',
                'email' => 'required|string|email|max:255|unique:users,email',
                'password' => 'required|string|min:8|confirmed',
            ];

            if ($type === 'staff') {
                $rules['staff_title'] = ['required', Rule::in(['Front desk', 'Dentist'])];
                $rules['invite_code'] = 'required|string';
            } else {
                $rules += [
                    'dob' => 'required|date',
                    'sex' => ['required', Rule::in(['Female', 'Male'])],
                    'phone' => 'required|string|max:30',
                    'addr' => 'required|string|max:200',
                ];
            }

            $data = $request->validate($rules);

            if ($type === 'staff') {
                $expectedCode = (string) config('clinic.staff_invite_code');
                if ($expectedCode === '' || ! hash_equals($expectedCode, (string) $data['invite_code'])) {
                    return back()->withErrors(['invite_code' => 'The clinic invite code is invalid.'])->withInput($request->except('password', 'password_confirmation', 'invite_code'));
                }
            }

            $user = User::create([
                'name' => $data['name'],
                'email' => strtolower($data['email']),
                'password' => Hash::make($data['password']),
                'role' => $type === 'staff' ? 'admin' : 'patient',
                'staff_title' => $type === 'staff' ? $data['staff_title'] : null,
            ]);

            if ($type === 'patient') {
                Patient::create([
                    'user_id' => $user->id,
                    'full_name' => $data['name'],
                    'date_of_birth' => $data['dob'],
                    'sex' => $data['sex'],
                    'contact_number' => $data['phone'],
                    'address' => $data['addr'],
                    'email' => $user->email,
                ]);
            }

            Auth::login($user);
            $request->session()->regenerate();
            $request->session()->put('role', $user->role);

            if ($user->role === 'patient') {
                $request->session()->put('user', [
                    'name' => $user->name, 'email' => $user->email, 'dob' => $data['dob'],
                    'sex' => $data['sex'], 'phone' => $data['phone'], 'addr' => $data['addr'], 'photo' => '',
                ]);
                return redirect()->route('patient.dashboard')->with('flash', 'Account created');
            }

            $request->session()->put('admin', [
                'name' => $user->name, 'role' => $user->staff_title, 'email' => $user->email, 'phone' => '', 'photo' => '',
            ]);

            return redirect()->route('admin.dashboard')->with('flash', 'Staff account created');
        }

        return view('auth.signup');
    }

    public function forgot(Request $request, ?string $role = null)
    {
        $step = 1;
        $email = $request->session()->get('reset_email');

        if ($request->isMethod('post')) {
            if ($request->input('step') === '1') {
                $data = $request->validate(['email' => 'required|email']);
                $email = strtolower($data['email']);
                if (! User::where('email', $email)->exists()) {
                    return back()->withErrors(['email' => 'No account was found for that email address.'])->withInput();
                }
                $request->session()->put('reset_email', $email);
                $step = 2;
            } else {
                $validator = Validator::make($request->all(), [
                    'code' => ['required', 'regex:/^\d{6}$/'],
                    'password' => 'required|min:8|confirmed',
                ]);
                if ($validator->fails()) {
                    return view('auth.forgot', compact('step', 'email'))->withErrors($validator);
                }

                $user = User::where('email', $request->session()->get('reset_email'))->first();
                if (! $user) {
                    return redirect()->route('forgot')->withErrors(['email' => 'Please start the password reset again.']);
                }

                $user->update(['password' => Hash::make($request->input('password'))]);
                $request->session()->forget('reset_email');
                return redirect()->route('login')->with('flash', 'Password updated. Please sign in.');
            }
        }

        return view('auth.forgot', compact('step', 'email'));
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('welcome');
    }
}
