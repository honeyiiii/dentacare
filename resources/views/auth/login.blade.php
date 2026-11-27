<x-guest-layout>
    <b style="font-size:24px">Sign in</b>
    <p class="sub">Welcome to DentaCare! {{ request('type') === 'staff' ? 'Staff' : 'Patient' }} account</p>

    @if (session('status'))
        <div class="note" style="background:#dcfce7;color:#166534">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="note" style="background:#fee2e2;color:#991b1b">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('login') }}">
        @csrf
        <label for="email">Email address</label>
        <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="username">

        <label for="password">Password</label>
        <input id="password" name="password" type="password" required autocomplete="current-password">

        <label style="display:flex;gap:8px;align-items:center;text-transform:none;font-size:13px">
            <input type="checkbox" name="remember" style="width:auto"> Remember me
        </label>
        <br>
        <button class="btn f">Sign in</button>
    </form>

    @if (Route::has('password.request'))
        <p style="text-align:center"><a class="l" href="{{ route('password.request') }}">Forgot password?</a></p>
    @endif
    <p style="text-align:center" class="sub">No account yet? <a class="l" href="{{ route('register') }}">Create account</a></p>
    <p style="text-align:center"><a class="l" href="{{ url('/') }}">← Back</a></p>
</x-guest-layout>   