<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public function login(Request $r, string $role)
    {
        if ($r->isMethod('post')) {
            $d = $r->validate(['email' => 'required|email', 'password' => 'required']);
            session(['role' => $role]);
            if ($role === 'patient') session(['user.email' => $d['email']]);
            return redirect()->route($role . '.dashboard');
        }
        return view('auth.login', compact('role'));
    }

    public function signup(Request $r, string $role)
    {
        if ($r->isMethod('post')) {
            if ($role === 'admin') {
                $d = $r->validate(['name' => 'required|max:80', 'role' => 'required|in:Front desk,Dentist', 'phone' => 'required', 'email' => 'required|email', 'code' => 'required', 'password' => 'required|min:6|confirmed']);
                session(['admin' => ['name' => $d['name'], 'role' => $d['role'], 'email' => $d['email'], 'phone' => $d['phone'], 'photo' => '']]);
            } else {
                $d = $r->validate(['name' => 'required|max:80', 'dob' => 'required|date', 'sex' => 'required|in:Female,Male', 'phone' => 'required', 'addr' => 'required', 'email' => 'required|email', 'password' => 'required|min:6']);
                session(['user' => ['name' => $d['name'], 'email' => $d['email'], 'dob' => $d['dob'], 'sex' => $d['sex'], 'phone' => $d['phone'], 'addr' => $d['addr'], 'photo' => '']]);
            }
            session(['role' => $role]);
            return redirect()->route($role . '.dashboard')->with('flash', 'Account created');
        }
        return view('auth.signup', compact('role'));
    }

    public function forgot(Request $r, string $role)
    {
        $step = 1; $email = session('reset_email');
        if ($r->isMethod('post')) {
            if ($r->input('step') === '1') {
                $d = $r->validate(['email' => 'required|email']);
                session(['reset_email' => $email = $d['email']]); $step = 2;
            } else {
                $step = 2;
                $v = Validator::make($r->all(), ['code' => ['required', 'regex:/^\d{6}$/'], 'password' => 'required|min:6|confirmed']);
                if ($v->fails()) return view('auth.forgot', compact('role', 'step', 'email'))->withErrors($v);
                session()->forget('reset_email');
                return redirect()->route('login', $role)->with('flash', 'Password updated. Please sign in.');
            }
        }
        return view('auth.forgot', compact('role', 'step', 'email'));
    }

    public function logout()
    {
        session()->forget('role');
        return redirect()->route('welcome');
    }
}
