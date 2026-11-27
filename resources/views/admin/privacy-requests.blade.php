@extends('layouts.admin')
@section('title', 'Privacy requests')
@section('content')
<h2>Privacy requests</h2><p class="sub">Review patient access, correction, and deletion requests.</p>
<div class="card wrap"><table><thead><tr><th>Patient</th><th>Request</th><th>Message</th><th>Created</th><th>Status</th><th>Update</th></tr></thead><tbody>
@forelse ($requests as $request)
<tr><td>{{ $request->patient->full_name }}</td><td>{{ $request->request_type }}</td><td>{{ $request->message ?: '—' }}</td><td>{{ $request->created_at->format('M j, Y') }}</td><td>{{ $request->status }}</td><td>
<form method="post" action="{{ route('admin.privacy-requests.update', $request->id) }}">@csrf<select name="status"><option @selected($request->status === 'In review')>In review</option><option @selected($request->status === 'Completed')>Completed</option><option @selected($request->status === 'Declined')>Declined</option></select>@if ($request->request_type === 'Deletion')<small>Completing removes account access and anonymizes profile.</small>@endif<button class="btn s">Save</button></form></td></tr>
@empty<tr><td colspan="6">No open requests.</td></tr>@endforelse
</tbody></table></div>
{{ $requests->links() }}
@endsection
