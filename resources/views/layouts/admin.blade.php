@extends('layouts.base')
@section('body')
<div class="app"><aside><h1>DENTACARE</h1>
@foreach (['dashboard' => 'Dashboard', 'calendar' => 'Calendar', 'queue' => 'Patient Queue', 'reminders' => 'Reminders', 'reports' => 'Reports', 'profile' => 'Profile', 'privacy' => 'Privacy Notice'] as $k => $l)
<a href="{{ route('admin.' . $k) }}" class="{{ request()->routeIs('admin.' . $k) ? 'on' : '' }}">{{ $l }}</a>
@endforeach
<a href="{{ route('logout') }}">Log out</a>
<a class="acct" href="{{ route('admin.profile') }}" title="My profile">@include('partials.avatar', ['u' => session('admin'), 'cls' => 'pic'])<span><b>{{ session('admin.name') }}</b><small>{{ session('admin.role') }}</small></span></a></aside>
<main>@yield('content')</main></div>
@endsection
