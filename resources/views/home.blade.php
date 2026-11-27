<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>DentaCare</title>
    <link rel="stylesheet" href="{{ asset('css/dentacare.css') }}">
</head>
<body>
    <div class="split">
        <div class="pan"></div>
        <div class="ct">
            <div style="text-align:center">
                <h2 style="font-size:30px">Welcome to DentaCare!</h2>
                <p class="sub">Select account type</p>
                <div class="row" style="max-width:420px;margin:0 auto">
                    <div class="card">
                        <b>Admin Account</b>
                        <p class="sub">Front desk and dentists</p>
                        <a class="btn o f" style="display:block;text-align:center;text-decoration:none"
                           href="{{ auth()->check() ? route('dashboard') : route('login', ['type' => 'staff']) }}">Open Admin</a>
                    </div>
                    <div class="card">
                        <b>Patient Account</b>
                        <p class="sub">Book and track your care</p>
                        <a class="btn f" style="display:block;text-align:center;text-decoration:none"
                           href="{{ auth()->check() ? route('dashboard') : route('login', ['type' => 'patient']) }}">Open Patient</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>