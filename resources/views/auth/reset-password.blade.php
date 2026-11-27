@extends('layouts.auth')
@section('title', 'Reset password')
@section('content')
<b style="font-size:24px">Choose a new password</b>
<p class="sub">Use a password with at least 8 characters.</p>
@include('partials.errors')
<form method="post" action="{{ route('password.store') }}">@csrf
    <input type="hidden" name="token" value="{{ $request->route('token') }}">
    <label>Email address</label>
    <input name="email" type="email" autocomplete="email" value="{{ old('email', $request->email) }}" required>
    <label>New password</label>
    <input name="password" type="password" autocomplete="new-password" required>
    <label>Confirm new password</label>
    <input name="password_confirmation" type="password" autocomplete="new-password" required>
    <br><br><button class="btn f" type="submit">Reset password</button>
</form>
@endsection
