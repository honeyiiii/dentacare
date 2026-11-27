@extends('layouts.auth')
@section('title', 'Verify your email')
@section('w', '460px')
@section('content')
<b style="font-size:24px">Verify your email</b>
<p class="sub">We sent a verification link to your email address. Open that link to continue to DentaCare.</p>
<p class="sub">If you use the local development mailer, find the link in <code>storage/logs/laravel.log</code>.</p>
@if (session('flash'))<div class="note" role="status">{{ session('flash') }}</div>@endif
@if (session('status') === 'verification-link-sent')<div class="note" role="status">A fresh verification link has been sent.</div>@endif
<form method="post" action="{{ route('verification.send') }}">@csrf<button class="btn f">Resend verification email</button></form>
<form method="post" action="{{ route('logout') }}" style="margin-top:12px">@csrf<button class="btn o">Log out</button></form>
@endsection
