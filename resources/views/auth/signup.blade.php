@extends('layouts.auth')
@section('title', 'Create account')
@section('w', '460px')
@section('content')
<b style="font-size:24px">Create a DentaCare account</b>
<p class="sub">Choose your account type to get started.</p>
@include('partials.errors')
<form method="post" action="{{ route('signup') }}">@csrf
<label>Account type</label>
<select name="account_type" id="account-type" required>
    <option value="patient" @selected(old('account_type', 'patient') === 'patient')>Patient</option>
    <option value="staff" @selected(old('account_type') === 'staff')>Clinic staff (dentist or receptionist)</option>
</select>
<label>Full name</label><input name="name" value="{{ old('name') }}" autocomplete="name" required>
<label>Email address</label><input name="email" type="email" value="{{ old('email') }}" autocomplete="email" required>

<div id="patient-fields">
    <div class="row"><div><label>Date of birth</label><input name="dob" type="date" value="{{ old('dob') }}"></div><div><label>Sex</label><select name="sex"><option value="Female">Female</option><option value="Male">Male</option></select></div></div>
    <label>Contact number</label><input name="phone" value="{{ old('phone') }}" autocomplete="tel">
    <label>Address</label><input name="addr" value="{{ old('addr') }}" placeholder="House/Unit No., Street, Barangay, City, Province" autocomplete="street-address">
</div>

<div id="staff-fields" hidden>
    <label>Clinic role</label><select name="staff_title"><option value="Front desk">Receptionist / Front desk</option><option value="Dentist">Dentist</option></select>
    <label>Clinic invite code</label><input name="invite_code" autocomplete="off" placeholder="Provided by your clinic administrator">
    <p class="sub">Staff accounts require a valid invitation from the clinic.</p>
</div>

<div class="row"><div><label>Password</label><input name="password" type="password" autocomplete="new-password" required></div><div><label>Confirm password</label><input name="password_confirmation" type="password" autocomplete="new-password" required></div></div>
<br><br><button class="btn f">Create account</button></form>
<p style="text-align:center" class="sub">Already have an account? <a class="l" href="{{ route('login') }}">Sign in</a></p>
<p style="text-align:center"><a class="l" href="{{ route('welcome') }}">&larr; Back to home</a></p>
<script>
const typeSelect = document.getElementById('account-type');
const patientFields = document.getElementById('patient-fields');
const staffFields = document.getElementById('staff-fields');
function toggleAccountFields() {
    const isStaff = typeSelect.value === 'staff';
    patientFields.hidden = isStaff;
    staffFields.hidden = !isStaff;
    patientFields.querySelectorAll('input, select').forEach(field => field.required = !isStaff);
    staffFields.querySelectorAll('input, select').forEach(field => field.required = isStaff);
}
typeSelect.addEventListener('change', toggleAccountFields);
toggleAccountFields();
</script>
@endsection
