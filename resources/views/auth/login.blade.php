@extends('layouts.auth')
@section('title', 'Sign in')
@section('content')
<b style="font-size:24px">Sign in</b><p class="sub">Welcome to DentaCare! {{ $role === 'admin' ? 'Staff' : 'Patient' }} account</p>
@include('partials.errors')
<form method="post" action="{{ route('login', $role) }}">@csrf
<label>Email address</label><input name="email" type="email" value="{{ old('email') }}">
<label>Password</label><input name="password" type="password"><br><br><button class="btn f">Sign in</button></form>
<p style="text-align:center"><a class="l" href="{{ route('forgot', $role) }}">Forgot password?</a></p>
<p style="text-align:center" class="sub">No account yet? <a class="l" href="{{ route('signup', $role) }}">Create account</a></p>
<p style="text-align:center"><a class="l" href="{{ route('welcome') }}">← Back</a></p>
@endsection
