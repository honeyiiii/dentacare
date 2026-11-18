@extends('layouts.base')
@section('body')
<div class="tb"><b>DENTACARE</b><nav>
@foreach (['dashboard' => 'Dashboard', 'appointments' => 'Appointments', 'symptoms' => 'Symptom Checker'] as $k => $l)
<a href="{{ route('patient.' . $k) }}" class="{{ request()->routeIs('patient.' . $k) ? 'on' : '' }}">{{ $l }}</a>
@endforeach
<a href="{{ route('patient.profile') }}" title="My profile">@include('partials.avatar', ['u' => session('user'), 'cls' => 'av'])</a></nav></div>
<div class="pg">@yield('content')</div>
@endsection
