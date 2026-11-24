@extends('layouts.admin')
@section('title', 'Reminders')
@section('content')
<div class="top"><div><h2>Reminders</h2><p class="sub">Send appointment and recall notices.</p></div><form method="post" action="{{ route('admin.reminders') }}">@csrf<button class="btn o" name="act" value="all">Send all</button></form></div>
<div class="card wrap"><table><tr><th>Patient</th><th>Message</th><th>Channel</th><th></th></tr>
@forelse ($rem as $i => $r)<tr><td>{{ $r['n'] }}</td><td>{{ $r['m'] }}</td><td>{{ $r['t'] }}</td><td><form method="post" action="{{ route('admin.reminders') }}">@csrf<input type="hidden" name="i" value="{{ $i }}"><button class="btn s">Send</button></form></td></tr>
@empty<tr><td colspan="4">All reminders sent.</td></tr>@endforelse</table></div>
@endsection
