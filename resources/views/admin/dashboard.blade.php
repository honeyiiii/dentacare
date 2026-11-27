@extends('layouts.admin')
@section('title', 'Front Desk')
@section('content')
<h2>Front Desk</h2><p class="sub">Welcome back.</p>
<div class="grid"><div class="card">Today’s appointments<div class="big">{{ $today }}</div></div><div class="card">In queue<div class="big">{{ $inq }}</div></div>
<div class="card">Reminders due<div class="big">{{ $reminders }}</div></div><div class="card">Upcoming bookings<div class="big">{{ $upcoming }}</div></div></div>
<div class="card wrap"><b>Next up</b><table><tr><th>When</th><th>Patient</th><th>Dentist</th></tr>
@foreach ($next as $a)<tr><td>{{ $C::fdate($a['date']) . ' ' . $C::ftime($a['time']) }}</td><td>{{ $a['pat'] }}</td><td>{{ $a['dr'] }}</td></tr>@endforeach</table></div>
@endsection
