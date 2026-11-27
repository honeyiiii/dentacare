<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>DentaCare</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="{{ asset('css/dentacare.css') }}">
</head>
<body>
    <div class="split">
        <div class="pan"></div>
        <div class="ct">
            <div style="max-width:400px">
                {{ $slot }}
            </div>
        </div>
    </div>
</body>
</html>