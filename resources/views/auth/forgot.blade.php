@extends('layouts.auth')
@section('title', 'Forgot password')
@section('content')
<b style="font-size:24px">Forgot password?</b>
@include('partials.errors')
@if ($step === 1)
<p class="sub">Enter the email address linked to your account.</p>
<form method="post" action="{{ route('forgot') }}">@csrf<input type="hidden" name="step" value="1"><label>Email address</label><input name="email" type="email" value="{{ old('email') }}" required><br><br><button class="btn f">Continue</button></form>
@else
<p class="sub">Enter the 6-digit reset code and your new password. (Demo: any 6 digits work.)</p>
<form method="post" action="{{ route('forgot') }}">@csrf<input type="hidden" name="step" value="2"><label>Reset code</label><input name="code" inputmode="numeric" maxlength="6" required>
<label>New password</label><input name="password" type="password" required><label>Confirm new password</label><input name="password_confirmation" type="password" required><br><br><button class="btn f">Reset password</button></form>
<p style="text-align:center"><a class="l" href="{{ route('forgot') }}">Use a different email</a></p>
@endif
<p style="text-align:center"><a class="l" href="{{ route('login') }}">&larr; Back to sign in</a></p>
@endsection
