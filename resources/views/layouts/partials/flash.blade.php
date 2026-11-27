@if (session('success'))
    <div class="note" style="background:#dcfce7;color:#166534;margin-bottom:14px">{{ session('success') }}</div>
@endif
@if ($errors->any() && ! request()->routeIs('profile.*'))
    <div class="note" style="background:#fee2e2;color:#991b1b;margin-bottom:14px">
        @foreach ($errors->all() as $e) <div>{{ $e }}</div> @endforeach
    </div>
@endif