@extends('layouts.admin')
@section('title', 'Staff accounts')
@section('content')
<h2>Staff accounts</h2><p class="sub">Only authorized front-desk staff can provision clinic accounts.</p>
@if (session('flash'))<div class="note" role="status">{{ session('flash') }}</div>@endif
<div class="card" style="max-width:540px">
    @include('partials.errors')
    <form method="post" action="{{ route('admin.staff.create') }}">@csrf
        <label>Full name</label><input name="name" value="{{ old('name') }}" required>
        <label>Email address</label><input name="email" type="email" value="{{ old('email') }}" required>
        <label>Clinic role</label><select name="staff_title" required><option value="Front desk">Receptionist / Front desk</option><option value="Assistant">Clinic assistant</option><option value="Dentist">Dentist</option></select>
        <br><br><button class="btn" type="submit">Create staff account</button>
    </form>
</div>
<div class="card wrap"><table><thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Account</th><th></th></tr></thead><tbody>
@forelse ($staff as $member)<tr><td>{{ $member->name }}</td><td>{{ $member->email }}</td><td>{{ $member->staff_title }}</td><td>{{ $member->is_active ? 'Active' : 'Deactivated' }}</td><td>
@if ($member->id !== auth()->id())<form method="post" action="{{ route('admin.staff.status', $member->id) }}">@csrf @method('PATCH')<input type="hidden" name="is_active" value="{{ $member->is_active ? 0 : 1 }}"><button class="btn s {{ $member->is_active ? 'o' : '' }}">{{ $member->is_active ? 'Deactivate' : 'Activate' }}</button></form>@endif
</td></tr>
@empty<tr><td colspan="5">No staff accounts found.</td></tr>@endforelse
</tbody></table></div>
@endsection
