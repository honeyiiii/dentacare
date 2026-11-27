@extends('layouts.admin')
@section('title', 'Patients')
@section('content')
<h2>Patients</h2>
<p class="sub">Patient records created through DentaCare sign-up.</p>

<div class="card">
    <form method="get" action="{{ route('admin.patients') }}" class="row" style="align-items:center;gap:10px;margin:0 0 16px">
        <input name="search" value="{{ $search }}" type="search" placeholder="Search name, email, or phone" aria-label="Search patients" style="flex:1;min-width:220px">
        <button class="btn" type="submit">Search</button>
        @if ($search !== '')<a class="btn o" href="{{ route('admin.patients') }}">Clear</a>@endif
    </form>

    <div class="wrap">
        <table>
            <thead><tr><th>Name</th><th>Email</th><th>Date of birth</th><th>Sex</th><th>Contact</th></tr></thead>
            <tbody>
                @forelse ($patients as $patient)
                    <tr>
                        <td>{{ $patient->full_name }}</td>
                        <td>{{ $patient->email ?: '—' }}</td>
                        <td>{{ $patient->date_of_birth?->format('M j, Y') ?: '—' }}</td>
                        <td>{{ $patient->sex }}</td>
                        <td>{{ $patient->contact_number }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5">{{ $search !== '' ? 'No patients match your search.' : 'No patient records yet.' }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($patients->hasPages())<div style="margin-top:16px">{{ $patients->links() }}</div>@endif
</div>
@endsection
