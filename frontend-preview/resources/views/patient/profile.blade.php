@extends('layouts.patient')
@section('title', 'My Profile')
@section('content')
<h2>My Profile</h2><p class="sub">Keep your details up to date.</p>
<div class="card" style="max-width:520px">@include('partials.errors')
<form method="post" action="{{ route('patient.profile') }}" enctype="multipart/form-data">@csrf
<div class="ph">@include('partials.avatar', ['u' => $u, 'cls' => 'pic'])<div><input type="file" name="photo" accept="image/*" style="width:auto"><p class="sub" style="margin:6px 0 0">JPG or PNG, up to 2 MB. Press Save changes to upload.</p>
@if ($u['photo'])<button class="btn s o" name="remove" value="1">Remove photo</button>@endif</div></div>
<label>Full name</label><input name="name" value="{{ $u['name'] }}">
<div class="row"><div><label>Date of birth</label><input type="date" name="dob" value="{{ $u['dob'] }}"></div><div><label>Sex</label><select name="sex">@foreach (['Female', 'Male'] as $x)<option @selected($u['sex'] === $x)>{{ $x }}</option>@endforeach</select></div></div>
<label>Contact number</label><input name="phone" value="{{ $u['phone'] }}">
<label>Address</label><input name="addr" placeholder="House/Unit No., Street, Barangay, City, Province" value="{{ $u['addr'] }}">
<label>Email</label><input name="email" value="{{ $u['email'] }}"><br><br>
<button class="btn">Save changes</button> <a class="btn o" href="{{ route('logout') }}">Log out</a></form></div>
@endsection
