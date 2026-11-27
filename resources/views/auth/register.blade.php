<x-guest-layout>
    <b style="font-size:24px">Create your account</b>
    <p class="sub">Book and track your care</p>

    @if ($errors->any())
        <div class="note" style="background:#fee2e2;color:#991b1b">
            @foreach ($errors->all() as $e) <div>{{ $e }}</div> @endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('register') }}">
        @csrf
        <label>Full name</label>
        <input name="name" value="{{ old('name') }}" required>

        <div class="row">
            <div><label>Date of birth</label><input type="date" name="date_of_birth" value="{{ old('date_of_birth') }}" required></div>
            <div><label>Sex</label>
                <select name="sex">
                    <option @selected(old('sex') === 'Female')>Female</option>
                    <option @selected(old('sex') === 'Male')>Male</option>
                </select>
            </div>
        </div>

        <label>Contact number</label>
        <input name="contact_number" value="{{ old('contact_number') }}" required>

        <label>Address</label>
        <input name="address" value="{{ old('address') }}" placeholder="House/Unit No., Street, Barangay, City, Province" required>

        <label>Email address</label>
        <input type="email" name="email" value="{{ old('email') }}" required>

        <div class="row">
            <div><label>Password</label><input type="password" name="password" required></div>
            <div><label>Confirm password</label><input type="password" name="password_confirmation" required></div>
        </div>
        <br>
        <button class="btn f">Create account</button>
    </form>

    <p style="text-align:center" class="sub">Already have an account? <a class="l" href="{{ route('login') }}">Sign in</a></p>
</x-guest-layout>