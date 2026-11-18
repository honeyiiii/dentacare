@extends('layouts.patient')
@section('title', 'Dashboard')
@section('content')
<div class="top"><div><h2>Welcome {{ explode(' ', session('user.name'))[0] }}!</h2><p class="sub">Here is what is happening with your dental care</p></div><a class="btn cta" href="{{ route('patient.book') }}">+ Book appointment</a></div>
<div class="two"><div class="hero"><small>YOUR NEXT VISIT</small>
@if ($next)<h3>{{ $next['svc'] }} with {{ $next['dr'] }}</h3><p style="margin:-8px 0 14px;opacity:.85">{{ $C::fdate($next['date']) }} at {{ $C::ftime($next['time']) }}</p>
<a class="btn w" href="{{ route('patient.appointments') }}">View details</a> <a class="btn g" href="{{ route('patient.book', ['edit' => $next['id']]) }}">Reschedule</a>
@else<h3>No upcoming visit</h3><a class="btn w" href="{{ route('patient.book') }}">Book one now</a>@endif</div>
<div class="card"><b>Your care plan</b><p class="sub" style="margin:8px 0 4px">Annual care progress: 75%</p><div class="bar"><i style="width:75%"></i></div><span class="sub">Next: six-month cleaning</span></div></div>
<div class="card wrap"><div class="top"><b>Visit history</b><a class="l" href="{{ route('patient.appointments') }}">See all</a></div>
<table><tr><th>Date</th><th>Visit</th><th>Dentist</th></tr>
@forelse ($hist as $a)<tr><td>{{ $C::fdate($a['date']) }}</td><td>{{ $a['svc'] }}</td><td>{{ $a['dr'] }}</td></tr>@empty<tr><td colspan="3">No visits yet.</td></tr>@endforelse</table></div>
@endsection
