@extends('layouts.admin')
@section('title', 'Reports')
@section('content')
<h2>Reports</h2><p class="sub">Clinic activity.</p>
<div class="grid"><div class="card">Booked<div class="big">{{ $booked }}</div></div><div class="card">Completed<div class="big">{{ $done }}</div></div><div class="card">Cancelled<div class="big">{{ $cancelled }}</div></div><div class="card">Queue today<div class="big">{{ $queueN }}</div></div></div>
<div class="two e"><div class="card"><b>Appointments per day</b><p class="sub" style="margin:2px 0 0">Number of booked appointments this week</p>
<div class="chart"><div class="ax">@for ($v = $m; $v >= 0; $v--)<span>{{ $v }}</span>@endfor</div>
<div class="plot">@for ($v = 0; $v <= $m; $v++)<i class="gl" style="bottom:{{ $v / $m * 100 }}%"></i>@endfor
<div class="cols">@foreach ($n as $i => $v)<div style="height:{{ $v / $m * 100 }}%"><em>{{ $v }}</em><span>{{ date('D', strtotime($W[$i])) }}</span></div>@endforeach</div></div></div></div>
<div class="card"><b>Top services</b><br><br>@foreach ($top as $k => $v){{ $k }} {{ $v }}%<div class="bar"><i style="width:{{ $v }}%"></i></div>@endforeach</div></div>
@endsection
