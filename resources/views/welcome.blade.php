@extends('layouts.auth')
@section('title', 'Welcome')
@section('w', '460px')
@section('content')
<div style="text-align:center"><h2 style="font-size:30px">Welcome to DentaCare!</h2><p class="sub">Select account type</p>
<div class="row"><div class="card"><b>Admin Account</b><p class="sub">Front desk and dentists</p><a class="btn o f" href="{{ route('login', 'admin') }}">Open Admin</a></div>
<div class="card"><b>Patient Account</b><p class="sub">Book and track your care</p><a class="btn f" href="{{ route('login', 'patient') }}">Open Patient</a></div></div><p><a class="l" href="{{ route('home') }}">← Back to home</a></p></div>
@endsection
