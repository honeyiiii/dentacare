<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Audit log</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow sm:rounded-lg p-6">
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="text-left border-b">
                                <th class="py-2 pr-4">When</th>
                                <th class="pr-4">Who</th>
                                <th class="pr-4">Action</th>
                                <th class="pr-4">Record</th>
                                <th class="pr-4">Details</th>
                                <th>IP address</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($logs as $log)
                                <tr class="border-b align-top">
                                    <td class="py-2 pr-4 whitespace-nowrap">{{ $log->created_at?->format('M j, Y g:i A') }}</td>
                                    <td class="pr-4">{{ $log->user?->name ?? 'System / deleted user' }}</td>
                                    <td class="pr-4">{{ $log->action }}</td>
                                    <td class="pr-4">{{ $log->subject_type ? class_basename($log->subject_type) . ' #' . $log->subject_id : '—' }}</td>
                                    <td class="pr-4">{{ $log->description ?? '—' }}</td>
                                    <td>{{ $log->ip_address ?? '—' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="py-4 text-gray-500">No activity has been recorded yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="mt-4">{{ $logs->links() }}</div>
            </div>
        </div>
    </div>
</x-app-layout>
