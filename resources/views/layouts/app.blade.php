@php
    $user = auth()->user();
    $isPatient = $user?->role === 'patient';
    $initials = strtoupper(collect(explode(' ', $user->name ?? ''))->map(fn ($w) => mb_substr($w, 0, 1))->take(2)->implode(''));

    // [route, label, active pattern, allowed roles (null = everyone)]
    $staffNav = [
        ['dashboard', 'Dashboard', 'dashboard', null],
        ['appointments.index', 'Appointments', 'appointments.*', null],
        ['patients.index', 'Patients', 'patients.*', null],
        ['reminders.index', 'Reminders', 'reminders.*', null],
        ['reports.index', 'Reports', 'reports.*', ['owner', 'receptionist', 'dentist']],
        ['staff.index', 'Staff', 'staff.*', ['owner']],
        ['audit.index', 'Audit Log', 'audit.*', ['owner']],
        ['profile.edit', 'Profile', 'profile.*', null],
        ['privacy', 'Privacy Notice', 'privacy', null],
    ];
    $patientNav = [
        ['dashboard', 'Dashboard', 'dashboard', null],
        ['appointments.index', 'Appointments', 'appointments.*', null],
        ['symptoms.index', 'Symptom Checker', 'symptoms.*', null],
    ];
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>DentaCare</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="{{ asset('css/dentacare.css') }}">
</head>
<body>
@if ($isPatient)
    <div class="tb">
        <b>DENTACARE</b>
        <nav>
            @foreach ($patientNav as [$r, $l, $p, $roles])
                @if (Route::has($r))
                    <a href="{{ route($r) }}" class="{{ request()->routeIs($p) ? 'on' : '' }}">{{ $l }}</a>
                @endif
            @endforeach
            <a href="{{ route('profile.edit') }}" class="av" title="My profile">{{ $initials }}</a>
            <form method="POST" action="{{ route('logout') }}" style="display:inline">
                @csrf
                <a href="#" onclick="event.preventDefault(); this.closest('form').submit();">Log out</a>
            </form>
        </nav>
    </div>
    <div class="pg">
        @include('layouts.partials.flash')
        @isset($header) <div style="margin-bottom:12px">{{ $header }}</div> @endisset
        {{ $slot }}
    </div>
@else
    <div class="app">
        <aside>
            <h1>DENTACARE</h1>
            @foreach ($staffNav as [$r, $l, $p, $roles])
                @if (Route::has($r) && ($roles === null || in_array($user->role, $roles, true)))
                    <a href="{{ route($r) }}" class="{{ request()->routeIs($p) ? 'on' : '' }}">{{ $l }}</a>
                @endif
            @endforeach
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <a href="#" onclick="event.preventDefault(); this.closest('form').submit();">Log out</a>
            </form>
            <a class="acct" href="{{ route('profile.edit') }}" style="background:none">
                <span class="pic">{{ $initials }}</span>
                <div><b>{{ $user->name }}</b><small>{{ ucfirst($user->role) }}</small></div>
            </a>
        </aside>
        <main>
            @include('layouts.partials.flash')
            @isset($header) <div style="margin-bottom:12px">{{ $header }}</div> @endisset
            {{ $slot }}
        </main>
    </div>
@endif
</body>
</html>