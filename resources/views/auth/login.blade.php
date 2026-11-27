@extends('layouts.auth')
@section('title', 'Sign in')
@section('content')
<b style="font-size:24px">Sign in</b><p class="sub">Welcome back to DentaCare</p>
@if (session('flash'))<p class="sub">{{ session('flash') }}</p>@endif
@include('partials.errors')
<form method="post" action="{{ route('login.submit') }}">@csrf
<label>Email address</label><input name="email" type="email" autocomplete="email" value="{{ old('email') }}" required>
<label>Password</label><input name="password" type="password" autocomplete="current-password" required><br><br><button class="btn f">Sign in</button></form>
<p style="text-align:center"><a class="l" href="{{ route('forgot') }}">Forgot password?</a></p>
<p style="text-align:center" class="sub">New to DentaCare? <a class="l" href="{{ route('signup') }}">Create an account</a></p>
<p style="text-align:center"><a class="l" href="{{ route('welcome') }}">&larr; Back to home</a></p>
@endsection
