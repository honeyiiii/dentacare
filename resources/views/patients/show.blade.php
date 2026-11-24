<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Patient record</h2>
            <a href="{{ route('patients.index') }}" class="text-sm text-blue-700 hover:underline">Back to patients</a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="mb-4 p-3 bg-green-100 text-green-800 rounded">{{ session('success') }}</div>
            @endif

            <section class="bg-white shadow-sm sm:rounded-lg p-6">
                <div class="flex justify-between items-start gap-4 mb-6">
                    <div>
                        <p class="text-sm text-gray-500">Patient #{{ $patient->id }}</p>
                        <h3 class="text-2xl font-semibold text-gray-900">{{ $patient->full_name }}</h3>
                    </div>
                    <a href="{{ route('patients.edit', $patient) }}" class="px-4 py-2 bg-blue-600 text-white rounded-md text-sm font-semibold">Edit record</a>
                </div>

                <dl class="grid sm:grid-cols-2 gap-x-8 gap-y-5">
                    <div><dt class="text-sm text-gray-500">Date of birth</dt><dd class="mt-1">{{ $patient->date_of_birth }}</dd></div>
                    <div><dt class="text-sm text-gray-500">Sex</dt><dd class="mt-1">{{ $patient->sex }}</dd></div>
                    <div><dt class="text-sm text-gray-500">Contact number</dt><dd class="mt-1">{{ $patient->contact_number }}</dd></div>
                    <div><dt class="text-sm text-gray-500">Email</dt><dd class="mt-1">{{ $patient->email ?: 'Not provided' }}</dd></div>
                    <div class="sm:col-span-2"><dt class="text-sm text-gray-500">Address</dt><dd class="mt-1">{{ $patient->address }}</dd></div>
                </dl>
            </section>
        </div>
    </div>
</x-app-layout>
