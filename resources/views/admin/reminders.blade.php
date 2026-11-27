@extends('layouts.admin')
@section('title', 'Reminders')
@section('content')
<div class="top"><div><h2>Reminders</h2><p class="sub">Send pending appointment notices by email. A development mailer only records messages; it does not deliver them.</p></div></div>
@include('partials.errors')
<div class="card wrap"><table><tr><th>Patient</th><th>Message</th><th>Channel</th><th></th></tr>
@forelse ($rem as $r)<tr><td>{{ $r['n'] }}</td><td>{{ $r['m'] }}</td><td>{{ $r['t'] }} @if ($r['status'] === 'failed')<span class="pill bad">Delivery failed</span>@elseif ($r['status'] === 'logged')<span class="pill">Logged only</span>@endif</td><td><form method="post" action="{{ route('admin.reminders') }}">@csrf<input type="hidden" name="id" value="{{ $r['id'] }}"><button class="btn s">Retry / Send</button></form></td></tr>
@empty<tr><td colspan="4">All reminders sent.</td></tr>@endforelse</table></div>
@endsection
