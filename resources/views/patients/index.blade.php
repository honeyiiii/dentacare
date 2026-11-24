<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Patients</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            @if (session('success'))
                <div class="mb-4 p-3 bg-green-100 text-green-800 rounded">
                    {{ session('success') }}
                </div>
            @endif

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <div class="flex justify-between items-center mb-4">
                    <form method="GET" action="{{ route('patients.index') }}" class="flex gap-2">
                        <input type="text" name="search" value="{{ $search }}"
                               placeholder="Search name or contact number"
                               class="border-gray-300 rounded-md shadow-sm">
                        <x-primary-button>Search</x-primary-button>
                    </form>

                    <a href="{{ route('patients.create') }}"
                       class="px-4 py-2 bg-blue-600 text-white rounded-md text-sm font-semibold">
                        Register patient
                    </a>
                </div>

                <table class="w-full text-left">
                    <thead>
                        <tr class="border-b">
                            <th class="py-2">ID</th>
                            <th>Name</th>
                            <th>Date of birth</th>
                            <th>Sex</th>
                            <th>Contact</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($patients as $patient)
                            <tr class="border-b">
                                <td class="py-2">{{ $patient->id }}</td>
                                <td>{{ $patient->full_name }}</td>
                                <td>{{ $patient->date_of_birth }}</td>
                                <td>{{ $patient->sex }}</td>
                                <td>{{ $patient->contact_number }}</td>
                                <td class="py-2 text-right whitespace-nowrap">
                                    <a class="text-blue-700 hover:underline" href="{{ route('patients.show', $patient) }}">View</a>
                                    <a class="ml-3 text-blue-700 hover:underline" href="{{ route('patients.edit', $patient) }}">Edit</a>
                                    <form method="POST" action="{{ route('patients.destroy', $patient) }}" class="inline ml-3"
                                          onsubmit="return confirm('Archive this patient record? Its clinical history will be retained.');">
                                        @csrf
                                        @method('DELETE')
                                        <button class="text-red-700 hover:underline" type="submit">Archive</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-4 text-gray-500">No patients found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

                <div class="mt-4">
                    {{ $patients->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
