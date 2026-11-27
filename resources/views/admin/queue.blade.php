@extends('layouts.admin')
@section('title', 'Patient Queue')
@section('content')
<h2>Patient Queue</h2><p class="sub">Today’s walk-ins and booked patients, in order.</p>
<div class="card"><form method="post" action="{{ route('admin.queue') }}" class="row" style="align-items:flex-end">@csrf<input type="hidden" name="act" value="walkin"><div><label>Walk-in patient</label><input name="n" placeholder="Patient name" required></div>
<div><label>Service</label><select name="s">@foreach ($C::SERVICES as $x)<option>{{ $x }}</option>@endforeach</select></div><div><label>Dentist</label><select name="dentist_id"><option value="">Assign when calling</option>@foreach ($dentists as $dentist)<option value="{{ $dentist->id }}">{{ $dentist->name }}</option>@endforeach</select></div><div style="flex:0"><button class="btn">+ Add walk-in</button></div></form></div>
<div class="card wrap"><table><tr><th>#</th><th>Patient</th><th>Service</th><th>Dentist</th><th>Status</th><th>Action</th></tr>
@foreach ($queue as $i => $q)<tr><td>{{ $i + 1 }}</td><td>{{ $q['n'] }}</td><td>{{ $q['s'] }}</td><td>{{ $q['d'] }}</td><td>{!! $C::pill($q['st']) !!}</td>
<td>@if ($q['st'] !== 'Done')<form class="inl" method="post" action="{{ route('admin.queue') }}">@csrf<input type="hidden" name="id" value="{{ $q['id'] }}">
@if ($q['st'] === 'Waiting')<button class="btn s" name="act" value="call">Call in</button>@else<button class="btn s o" name="act" value="done">Mark done</button>@endif</form>@else —@endif</td></tr>@endforeach</table></div>
@endsection
