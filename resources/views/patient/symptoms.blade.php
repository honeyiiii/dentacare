@extends('layouts.patient')
@section('title', 'Symptom Checker')
@section('content')
<h2>Symptom Checker</h2><p class="sub">Describe how you feel to get a predicted result. This is NOT a diagnosis.</p>
<div id="sc" data-book="{{ route('patient.book') }}" data-check="{{ route('patient.symptoms') }}"></div><dialog id="dm"></dialog>
@endsection
