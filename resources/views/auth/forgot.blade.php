<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Forgot password | DentaCare</title>
    <style>
        :root{--navy:#0f2b5b;--blue:#1d4ed8;--bg:#f4f6fb;--ink:#0b1630;--muted:#5b6b8a;--line:#e3e8f2}
        *{box-sizing:border-box;margin:0}
        body{font-family:Inter,"Segoe UI",system-ui,sans-serif;color:var(--ink);min-height:100vh;display:grid;grid-template-columns:minmax(220px,500px) 1fr;background:#fff}
        .side{background:var(--navy);color:#fff;padding:32px;font-weight:700;letter-spacing:.12em;font-size:20px}
        main{display:grid;place-items:center;padding:32px}
        .box{width:100%;max-width:540px}
        h1{font-size:34px;margin-bottom:6px}
        .sub{color:var(--muted);margin-bottom:24px}
        label{display:block;font-size:13px;color:var(--muted);text-transform:uppercase;margin:0 0 8px}
        input{width:100%;padding:18px;border:1px solid var(--line);border-radius:12px;font:inherit;margin-bottom:24px;color:var(--ink)}
        input:focus{outline:3px solid #93b4ff;outline-offset:1px}
        button{width:100%;padding:16px;border:0;border-radius:12px;background:var(--blue);color:#fff;font:inherit;font-weight:600;cursor:pointer}
        button:hover{background:#1742b8}
        .links{text-align:center;margin-top:24px;color:var(--muted)}
        .links p{margin-bottom:16px}
        a{color:var(--blue)}
        .ok{background:#e6f7ec;color:#166534;border-radius:12px;padding:16px;margin-bottom:24px;line-height:1.5}
        .err{color:#b91c1c;font-size:14px;margin:-16px 0 20px;line-height:1.5}
        @media(max-width:720px){body{grid-template-columns:1fr}.side{padding:20px}main{padding:28px 20px}}
    </style>
</head>
<body>
    <div class="side">DENTACARE</div>
    <main>
        <div class="box">
            <h1>Forgot password?</h1>
            <p class="sub">Enter your email and we will send you a link to reset it.</p>

            @if (session('status'))
                <div class="ok" role="status">{{ session('status') }}</div>
                <div class="links" style="margin-top:0;margin-bottom:24px">
                    <p>Didn’t receive a link? <a href="{{ route('forgot') }}">Try again</a></p>
                </div>
            @else
                @if ($errors->has('email'))
                    <p class="err" role="alert">{{ $errors->first('email') }}</p>
                @endif
                <form method="post" action="{{ route('password.email') }}">
                    @csrf
                    <label for="email">Email address</label>
                    <input id="email" name="email" type="email" autocomplete="email" value="{{ old('email') }}" required autofocus>
                    <button type="submit">Send reset link</button>
                </form>
            @endif

            <div class="links">
                <p>Remembered it? <a href="{{ route('login') }}">Sign in</a></p>
                <a href="{{ route('welcome') }}">&larr; Back to DentaCare</a>
            </div>
        </div>
    </main>
</body>
</html>
