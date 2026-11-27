@extends('layouts.auth')
@section('title', 'Create account')
@section('w', '460px')
@section('content')
<b style="font-size:24px">Create a DentaCare account</b>
<p class="sub">Create a patient account to book and track your care.</p>
@include('partials.errors')
<form method="post" action="{{ route('signup.submit') }}">@csrf
<input type="hidden" name="account_type" value="patient">
<label>Full name</label><input name="name" value="{{ old('name') }}" autocomplete="name" required>
<label>Email address</label><input name="email" type="email" value="{{ old('email') }}" autocomplete="email" required>
<div class="row"><div><label>Date of birth</label><input name="dob" type="date" value="{{ old('dob') }}" required></div><div><label>Sex</label><select name="sex" required><option value="Female" @selected(old('sex') === 'Female')>Female</option><option value="Male" @selected(old('sex') === 'Male')>Male</option></select></div></div>
<label>Contact number</label><input name="phone" value="{{ old('phone') }}" autocomplete="tel" required>
<label>Address</label><input name="addr" value="{{ old('addr') }}" placeholder="House/Unit No., Street, Barangay, City, Province" autocomplete="street-address" required>
<div class="row"><div><label>Password</label><input name="password" type="password" autocomplete="new-password" required></div><div><label>Confirm password</label><input name="password_confirmation" type="password" autocomplete="new-password" required></div></div>
<br><br><button class="btn f">Create account</button></form>
<p style="text-align:center" class="sub">Already have an account? <a class="l" href="{{ route('login') }}">Sign in</a></p>
<p style="text-align:center"><a class="l" href="{{ route('welcome') }}">&larr; Back to home</a></p>
@endsection
