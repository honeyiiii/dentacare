<!DOCTYPE html>
<html lang="en"><head><meta charset="utf-8"><title>@yield('title', 'DentaCare') | DentaCare</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="{{ asset('css/dentacare.css') }}"><link rel="stylesheet" href="{{ asset('css/homepage.css') }}"></head><body>
@yield('body')
<div class="toast" id="t">{{ session('flash') }}</div>
<script src="{{ asset('js/dentacare.js') }}"></script></body></html>
