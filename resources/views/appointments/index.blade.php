<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Appointments</h2>
            <a href="{{ route('appointments.create') }}" class="text-indigo-600 hover:underline">+ Book appointment</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="mb-4 p-4 bg-green-100 text-green-800 rounded">{{ session('success') }}</div>
            @endif

            <div class="bg-white shadow sm:rounded-lg p-6">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="text-left border-b">
                            <th class="py-2">Date</th><th>Time</th><th>Patient</th>
                            <th>Dentist</th><th>Service</th><th>Status</th><th>Reminder</th><th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($appointments as $a)
                            <tr class="border-b">
                                <td class="py-2">{{ \Illuminate\Support\Carbon::parse($a->appointment_date)->format('M j, Y') }}</td>
                                <td>{{ \Illuminate\Support\Carbon::parse($a->appointment_time)->format('g:i A') }}</td>
                                <td>{{ $a->patient?->full_name }}</td>
                                <td>{{ $a->dentist?->name }}</td>
                                <td>{{ $a->service }}</td>
                                <td>{{ ucfirst($a->status) }}</td>
                                <td>{{ $a->reminder_sent_at ? 'Sent' : '-' }}</td>
                                <td class="text-right">
                                    @if ($a->status === 'scheduled' && (in_array(auth()->user()->role, ['owner', 'receptionist'], true) || (auth()->user()->role === 'dentist' && $a->dentist_id === auth()->id())))
                                        <form method="POST" action="{{ route('appointments.cancel', $a) }}">
                                            @csrf @method('PATCH')
                                            <button class="text-red-600 hover:underline" onclick="return confirm('Cancel this appointment?')">Cancel</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="py-4 text-gray-500">No appointments yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
                <div class="mt-4">{{ $appointments->links() }}</div>
            </div>
        </div>
    </div>
</x-app-layout>
