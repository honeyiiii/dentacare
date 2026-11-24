@extends('layouts.auth')
@section('title', 'Forgot password')
@section('content')
<b style="font-size:24px">Forgot password?</b>
@include('partials.errors')
@if ($step === 1)
<p class="sub">Enter your email and we’ll send a reset code.</p>
<form method="post" action="{{ route('forgot', $role) }}">@csrf<input type="hidden" name="step" value="1"><label>Email address</label><input name="email" type="email"><br><br><button class="btn f">Send reset code</button></form>
@else
<p class="sub">We sent a 6-digit code to {{ $email }}. (Demo: any 6 digits work.)</p>
<form method="post" action="{{ route('forgot', $role) }}">@csrf<input type="hidden" name="step" value="2"><label>Reset code</label><input name="code" inputmode="numeric" maxlength="6">
<label>New password</label><input name="password" type="password"><label>Confirm new password</label><input name="password_confirmation" type="password"><br><br><button class="btn f">Reset password</button></form>
<p style="text-align:center"><a class="l" href="{{ route('forgot', $role) }}">Use a different email</a></p>
@endif
<p style="text-align:center"><a class="l" href="{{ route('login', $role) }}">← Back to sign in</a></p>
@endsection
