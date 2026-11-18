@extends('layouts.base')
@section('body')
<div class="split"><div class="pan"></div><div class="ct"><div style="max-width:@yield('w', '360px')">@yield('content')</div></div></div>
@endsection
