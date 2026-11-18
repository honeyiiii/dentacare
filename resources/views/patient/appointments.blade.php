@extends('layouts.patient')
@section('title', 'Appointments')
@section('content')
<div class="top"><div><h2>My Appointments</h2><p class="sub">Upcoming and past visits.</p></div><a class="btn cta" href="{{ route('patient.book') }}">+ Book appointment</a></div>
<div class="card wrap"><table><tr><th>Date</th><th>Service</th><th>Dentist</th><th>Status</th><th></th></tr>
@forelse ($list as $a)<tr><td>{{ $C::fdate($a['date']) . ' ' . $C::ftime($a['time']) }}</td><td>{{ $a['svc'] }}</td><td>{{ $a['dr'] }}</td><td>{!! $C::pill($a['st']) !!}</td>
<td>@if ($a['st'] === 'Booked')<a class="btn s o" href="{{ route('patient.book', ['edit' => $a['id']]) }}">Reschedule</a>
<form class="inl" method="post" action="{{ route('patient.appointments') }}" onsubmit="return confirm('Cancel this appointment?')">@csrf<input type="hidden" name="id" value="{{ $a['id'] }}"><button class="btn s o">Cancel</button></form>@endif</td></tr>
@empty<tr><td colspan="5">No appointments yet.</td></tr>@endforelse</table></div>
@endsection
