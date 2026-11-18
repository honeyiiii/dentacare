@extends('layouts.patient')
@section('title', 'Book an appointment')
@section('content')
<h2>{{ $edit !== null ? 'Reschedule appointment' : 'Book an appointment' }}</h2><p class="sub">Pick a dentist and a time that works for you</p>
<div class="card" style="max-width:520px">@include('partials.errors')
<form method="post" action="{{ route('patient.book', $edit !== null ? ['edit' => $a['id']] : []) }}">@csrf
<label>Service</label><select name="svc">@foreach ($C::SERVICES as $x)<option @selected(old('svc', $a['svc']) === $x)>{{ $x }}</option>@endforeach</select>
<label>Dentist</label><select name="dr">@foreach ($C::DENTISTS as $x)<option @selected(old('dr', $a['dr']) === $x)>{{ $x }}</option>@endforeach</select>
<div class="row"><div><label>Preferred date</label><input type="date" name="date" min="{{ date('Y-m-d') }}" value="{{ old('date', $a['date']) }}" required></div>
<div><label>Preferred time</label><select name="time">@foreach ($C::SLOTS as $x)<option value="{{ $x }}" @selected(old('time', $a['time']) === $x)>{{ $C::ftime($x) }}</option>@endforeach</select></div></div>
<label>Additional notes</label><textarea name="notes" rows="4" maxlength="500">{{ old('notes', $a['notes'] ?? '') }}</textarea><br><br><button class="btn cta f">Confirm appointment</button></form></div>
@endsection
