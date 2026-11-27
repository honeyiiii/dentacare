<span class="{{ $cls ?? 'pic' }}">@if (!empty($u['photo']))<img src="{{ $u['photo'] }}" alt="">@else{{ $C::initials($u['name']) }}@endif</span>
