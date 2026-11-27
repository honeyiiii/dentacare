<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Dashboard — {{ ucfirst($user->role) }}
        </h2>
    </x-slot>

    <div class="py-8">

        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            {{-- Welcome --}}
            <p class="mb-6 text-gray-600">
                Welcome, {{ $user->name }}.
            </p>


            {{-- Statistics --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">

                @foreach ($stats as $label => $value)

                    <div class="bg-white shadow-sm sm:rounded-lg p-6">

                        <div class="text-sm text-gray-500">
                            {{ $label }}
                        </div>

                        <div class="text-3xl font-bold text-gray-900 mt-1">
                            {{ $value }}
                        </div>

                    </div>

                @endforeach

            </div>


            {{-- Recent Appointments --}}
            <div class="mt-8">

                <div class="bg-white shadow-sm sm:rounded-lg">

                    <div class="p-6 border-b">

                        <div class="flex justify-between items-center">

                            <div>
                                <h3 class="text-lg font-semibold text-gray-900">
                                    Recent Appointments
                                </h3>

                                <p class="text-sm text-gray-500 mt-1">
                                    Latest appointments in the system.
                                </p>
                            </div>

                            <a
                                href="{{ route('appointments.index') }}"
                                class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700"
                            >
                                View all
                            </a>

                        </div>

                    </div>


                    <div class="overflow-x-auto">

                        <table class="min-w-full text-sm">

                            <thead class="bg-gray-50">

                                <tr class="text-left border-b">

                                    <th class="px-6 py-3">
                                        Date
                                    </th>

                                    <th class="px-6 py-3">
                                        Time
                                    </th>

                                    <th class="px-6 py-3">
                                        Patient
                                    </th>

                                    <th class="px-6 py-3">
                                        Dentist
                                    </th>

                                    <th class="px-6 py-3">
                                        Service
                                    </th>

                                    <th class="px-6 py-3">
                                        Status
                                    </th>

                                </tr>

                            </thead>


                            <tbody>

                                @forelse ($appointments as $appointment)

                                    <tr class="border-b hover:bg-gray-50">

                                        {{-- Date --}}
                                        <td class="px-6 py-4">

                                            {{ \Illuminate\Support\Carbon::parse(
                                                $appointment->appointment_date
                                            )->format('M j, Y') }}

                                        </td>


                                        {{-- Time --}}
                                        <td class="px-6 py-4">

                                            {{ \Illuminate\Support\Carbon::parse(
                                                $appointment->appointment_time
                                            )->format('g:i A') }}

                                        </td>


                                        {{-- Patient --}}
                                        <td class="px-6 py-4 font-medium">

                                            {{ $appointment->patient?->full_name ?? 'Unknown patient' }}

                                        </td>


                                        {{-- Dentist --}}
                                        <td class="px-6 py-4">

                                            {{ $appointment->dentist?->name ?? 'Not assigned' }}

                                        </td>


                                        {{-- Service --}}
                                        <td class="px-6 py-4">

                                            {{ $appointment->service }}

                                        </td>


                                        {{-- Status --}}
                                        <td class="px-6 py-4">

                                            @if ($appointment->status === 'scheduled')

                                                <span class="px-2 py-1 text-xs rounded bg-green-100 text-green-800">
                                                    Scheduled
                                                </span>

                                            @elseif ($appointment->status === 'cancelled')

                                                <span class="px-2 py-1 text-xs rounded bg-red-100 text-red-800">
                                                    Cancelled
                                                </span>

                                            @else

                                                <span class="px-2 py-1 text-xs rounded bg-gray-100 text-gray-800">
                                                    {{ ucfirst($appointment->status) }}
                                                </span>

                                            @endif

                                        </td>

                                    </tr>

                                @empty

                                    <tr>

                                        <td
                                            colspan="6"
                                            class="px-6 py-8 text-center text-gray-500"
                                        >
                                            No appointments yet.
                                        </td>

                                    </tr>

                                @endforelse

                            </tbody>

                        </table>

                    </div>

                </div>

            </div>


            {{-- Navigation --}}
            <div class="mt-8 flex flex-wrap gap-3">

                <a
                    href="{{ route('appointments.index') }}"
                    class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700"
                >
                    Appointments
                </a>


                @if (in_array($user->role, [
                    'owner',
                    'receptionist',
                    'dentist',
                    'assistant'
                ]))

                    <a
                        href="{{ route('patients.index') }}"
                        class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700"
                    >
                        Patients
                    </a>

                @endif


                @if (in_array($user->role, [
                    'owner',
                    'receptionist',
                    'dentist'
                ]))

                    <a
                        href="{{ route('reports.index') }}"
                        class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700"
                    >
                        Reports
                    </a>

                @endif


                @if ($user->role === 'owner')

                    <a
                        href="{{ route('staff.index') }}"
                        class="px-4 py-2 bg-gray-700 text-white rounded hover:bg-gray-800"
                    >
                        Staff
                    </a>

                    <a
                        href="{{ route('audit.index') }}"
                        class="px-4 py-2 bg-gray-700 text-white rounded hover:bg-gray-800"
                    >
                        Audit log
                    </a>

                @endif

            </div>

        </div>

    </div>
</x-app-layout>