@extends('layouts.admin')
@section('title', 'Profile')
@section('content')
<h2>Profile</h2><p class="sub">Your staff account.</p>
<div class="card" style="max-width:520px">@include('partials.errors')
<form method="post" action="{{ route('admin.profile') }}" enctype="multipart/form-data">@csrf
<div class="ph">@include('partials.avatar', ['u' => $u, 'cls' => 'pic'])<div><input type="file" name="photo" accept="image/*" style="width:auto"><p class="sub" style="margin:6px 0 0">JPG or PNG, up to 2 MB. Press Save changes to upload.</p>
@if ($u['photo'])<button class="btn s o" name="remove" value="1">Remove photo</button>@endif</div></div>
<label>Full name</label><input name="name" value="{{ $u['name'] }}"><label>Role</label><input value="{{ $u['role'] }}" disabled>
<label>Contact number</label><input name="phone" value="{{ $u['phone'] }}"><label>Email</label><input name="email" value="{{ $u['email'] }}"><br><br><button class="btn">Save changes</button></form></div>
@endsection
