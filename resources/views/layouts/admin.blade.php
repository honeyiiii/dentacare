@extends('layouts.base')
@section('body')
<div class="app"><aside><h1>DENTACARE</h1>
@foreach (['dashboard' => 'Dashboard', 'patients' => 'Patients', 'calendar' => 'Calendar', 'queue' => 'Patient Queue', 'reminders' => 'Reminders', 'reports' => 'Reports', 'profile' => 'Profile', 'privacy' => 'Privacy Notice'] as $k => $l)
<a href="{{ route('admin.' . $k) }}" class="{{ request()->routeIs('admin.' . $k) ? 'on' : '' }}">{{ $l }}</a>
@endforeach
<form method="post" action="{{ route('logout') }}">@csrf<button type="submit" class="btn o" style="width:100%;margin:8px 0">Log out</button></form>
<a class="acct" href="{{ route('admin.profile') }}" title="My profile">@include('partials.avatar', ['u' => session('admin'), 'cls' => 'pic'])<span><b>{{ session('admin.name') }}</b><small>{{ session('admin.role') }}</small></span></a></aside>
<main>@yield('content')</main></div>
@endsection
