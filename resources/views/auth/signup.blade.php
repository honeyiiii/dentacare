@extends('layouts.auth')
@section('title', 'Create account')
@section('w', '420px')
@section('content')
<b style="font-size:24px">{{ $role === 'admin' ? 'Create staff account' : 'Create your account' }}</b>
<p class="sub">{{ $role === 'admin' ? 'For front desk staff and dentists' : 'Book and track your care' }}</p>
@include('partials.errors')
<form method="post" action="{{ route('signup', $role) }}">@csrf
<label>Full name</label><input name="name" value="{{ old('name') }}">
@if ($role === 'admin')
<div class="row"><div><label>Role</label><select name="role"><option>Front desk</option><option>Dentist</option></select></div><div><label>Contact number</label><input name="phone" value="{{ old('phone') }}"></div></div>
<label>Email address</label><input name="email" type="email" value="{{ old('email') }}">
<label>Clinic invite code</label><input name="code" placeholder="Given by your clinic owner">
<div class="row"><div><label>Password</label><input name="password" type="password"></div><div><label>Confirm password</label><input name="password_confirmation" type="password"></div></div>
@else
<div class="row"><div><label>Date of birth</label><input name="dob" type="date" value="{{ old('dob') }}"></div><div><label>Sex</label><select name="sex"><option>Female</option><option>Male</option></select></div></div>
<label>Contact number</label><input name="phone" value="{{ old('phone') }}">
<label>Address</label><input name="addr" placeholder="House/Unit No., Street, Barangay, City, Province" value="{{ old('addr') }}">
<label>Email address</label><input name="email" type="email" value="{{ old('email') }}">
<label>Password</label><input name="password" type="password">
@endif
<br><br><button class="btn f">Create account</button></form>
<p style="text-align:center" class="sub">Already have an account? <a class="l" href="{{ route('login', $role) }}">Sign in</a></p>
@endsection
