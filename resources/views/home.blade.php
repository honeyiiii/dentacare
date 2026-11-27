@extends('layouts.base')
@section('title', 'Friendly dental care')
@section('body')
<header class="hp-nav"><b>DENTACARE</b>
<nav><a href="#services">Services</a><a href="#how">How it works</a><a href="#dentists">Our dentists</a><a href="#contact">Contact</a></nav>
<div class="hp-act"><a class="btn g s" href="{{ route('login', 'patient') }}">Sign in</a><a class="btn w s" href="{{ route('signup', 'patient') }}">Create account</a></div></header>

<section class="hp-hero"><div class="hp-wrap"><div>
<small>FAMILY DENTAL CLINIC</small><h1>Healthy smiles, booked in minutes.</h1>
<p>Book your visit online, check your symptoms before you come in, and keep track of your dental care in one place.</p>
<a class="btn w" href="{{ route('signup', 'patient') }}">+ Book appointment</a> <a class="btn g" href="#services">See our services</a></div>
<div class="hp-card"><b>Next available today</b><p class="sub" style="margin:2px 0 8px">Pick a dentist and a time that works for you</p>
@foreach ($C::DENTISTS as $i => $dr)<div class="row"><span class="pic">{{ $C::initials($dr) }}</span><div><b style="font-size:13px">{{ $dr }}</b><br><span class="sub" style="margin:0">{{ ['General dentistry', 'Fillings and extractions', 'Cleaning and check-ups'][$i] ?? 'Dentist' }}</span></div></div>@endforeach</div></div></section>

<section class="hp-stats"><div class="hp-wrap">
<div><div class="big">3</div><span class="sub">Dentists</span></div><div><div class="big">6</div><span class="sub">Services</span></div>
<div><div class="big">Mon–Fri</div><span class="sub">Open weekdays</span></div><div><div class="big">24/7</div><span class="sub">Online booking</span></div></div></section>

<section class="hp-sec" id="services"><div class="hp-wrap"><h2>Our services</h2><p class="sub">Everything your family needs, under one roof</p>
<div class="hp-grid">
@foreach ([['🦷', 'Routine check-up', 'A full exam to catch problems early and keep your teeth healthy.'], ['✨', 'Routine cleaning', 'Remove plaque and tartar for a fresher, healthier mouth.'], ['🛠️', 'Filling', 'Repair cavities and restore the strength of your tooth.'], ['🩻', 'Check-up and X-ray', 'Digital X-rays to see what an exam alone can’t.'], ['🪥', 'Tooth extraction', 'Gentle removal of damaged or problem teeth.'], ['💬', 'Consultation', 'Talk to a dentist about pain, concerns or treatment options.']] as [$ic, $t, $d])
<div class="card"><div class="hp-ic">{{ $ic }}</div><b>{{ $t }}</b><p class="sub" style="text-align:left;margin:6px 0 0">{{ $d }}</p></div>
@endforeach</div></div></section>

<section class="hp-sec alt" id="how"><div class="hp-wrap"><h2>How it works</h2><p class="sub">Three simple steps to your next visit</p>
<div class="hp-grid">
<div class="hp-step"><div class="n">1</div><b>Create your account</b><p class="sub" style="margin-top:6px">Sign up with your details in under a minute.</p></div>
<div class="hp-step"><div class="n">2</div><b>Check your symptoms</b><p class="sub" style="margin-top:6px">Use the Symptom Checker to see how urgent it may be. It is not a diagnosis.</p></div>
<div class="hp-step"><div class="n">3</div><b>Book your visit</b><p class="sub" style="margin-top:6px">Choose a dentist, date and time, then get reminders.</p></div></div></div></section>

<section class="hp-sec" id="dentists"><div class="hp-wrap"><h2>Meet our dentists</h2><p class="sub">Caring, experienced and easy to talk to</p>
<div class="hp-grid">
@foreach ($C::DENTISTS as $i => $dr)
<div class="card hp-doc"><span class="pic">{{ $C::initials($dr) }}</span><b>{{ $dr }}</b><p class="sub" style="margin:4px 0 12px">{{ ['General dentistry', 'Fillings and extractions', 'Cleaning and check-ups'][$i] ?? 'Dentist' }}</p><a class="btn s o" href="{{ route('signup', 'patient') }}">Book with {{ explode(' ', $dr)[1] }}</a></div>
@endforeach</div></div></section>

<section class="hp-cta"><h2>Ready for a healthier smile?</h2><p>Create a free account and book your first visit today.</p>
<a class="btn w" href="{{ route('signup', 'patient') }}">+ Book appointment</a></section>

<footer class="hp-foot" id="contact"><div class="hp-wrap">
<div><b>DENTACARE</b>Friendly dental care, made simple.</div>
<div><b>Clinic hours</b>Monday to Friday, 9:00 AM to 3:00 PM<br>Closed on weekends</div>
<div><b>Contact</b>Your clinic address, City<br>hello@dentacare.ph<br><a href="{{ route('welcome') }}" style="text-decoration:underline">Staff and patient sign-in</a></div></div></footer>
@endsection
