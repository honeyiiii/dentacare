@extends('layouts.admin')
@section('title', 'Calendar')
@section('content')
<div class="top"><div><h2>Clinic calendar</h2><p class="sub">Week of {{ date('M j', strtotime($W[0])) }}. Click an empty slot to book, or a booking to change it.</p></div><a class="btn cta" href="{{ route('admin.calendar', ['book' => $W[0] . '|09:00']) }}">+ Book appointment</a></div>
@if ($view)<div class="card" style="max-width:420px"><b>{{ $view['pat'] }}</b><p>{{ $view['svc'] }}<br>{{ $view['dr'] }}<br>{{ $C::fdate($view['date']) }} at {{ $C::ftime($view['time']) }}</p>@if (!empty($view['notes']))<p class="sub">Notes: {{ $view['notes'] }}</p>@endif
<form method="post" action="{{ route('admin.calendar') }}">@csrf<input type="hidden" name="id" value="{{ $view['id'] }}"><button class="btn" name="act" value="checkin">Check in</button> @if (in_array(session('admin.role'), ['Owner', 'Front desk', 'Dentist'], true))<button class="btn o" name="act" value="cancel">Cancel booking</button>@endif <a class="btn o" href="{{ route('admin.calendar') }}">Close</a></form></div>@endif
@if ($sel)<div class="card" style="max-width:420px"><b>Book appointment</b>@include('partials.errors')
<form method="post" action="{{ route('admin.calendar') }}">@csrf<input type="hidden" name="act" value="book"><label>Patient</label><select name="patient_id" required>@foreach ($patients as $patient)<option value="{{ $patient->id }}">{{ $patient->full_name }}</option>@endforeach</select>
<label>Service</label><select name="service">@foreach ($C::SERVICES as $x)<option>{{ $x }}</option>@endforeach</select>
<label>Dentist</label><select name="dentist_id" required>@foreach ($dentists as $dentist)<option value="{{ $dentist->id }}">{{ $dentist->name }}</option>@endforeach</select>
<div class="row"><div><label>Day</label><select name="date">@foreach ($W as $x)<option value="{{ $x }}" @selected($sel['d'] === $x)>{{ date('D j', strtotime($x)) }}</option>@endforeach</select></div>
<div><label>Time</label><select name="time">@foreach ($C::SLOTS as $x)<option value="{{ $x }}" @selected($sel['t'] === $x)>{{ $C::ftime($x) }}</option>@endforeach</select></div></div><br>
<button class="btn">Book</button> <a class="btn o" href="{{ route('admin.calendar') }}">Close</a></form></div>@endif
<div class="card wrap"><table class="cal"><tr><th></th>@foreach ($W as $d)<th>{{ date('D j', strtotime($d)) }}</th>@endforeach</tr>
@foreach ($C::SLOTS as $t)<tr><td class="t">{{ $C::ftime($t) }}</td>
@foreach ($W as $d)
@php $b = collect($appts)->filter(fn($a) => $a['date'] === $d && $a['time'] === $t && $a['st'] === 'Booked')->values(); @endphp
<td>@if ($b->count())<a href="{{ route('admin.calendar', ['view' => $b[0]['id']]) }}">@foreach ($b as $x)<div class="bk">{{ $x['pat'] }}</div>@endforeach</a>
@else<a href="{{ route('admin.calendar', ['book' => $d . '|' . $t]) }}"><span class="plus">+</span></a>@endif</td>
@endforeach</tr>@endforeach</table></div>
@endsection
