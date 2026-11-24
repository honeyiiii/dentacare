<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Book Appointment</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow sm:rounded-lg p-6">
                @if ($errors->any())
                    <div class="mb-4 p-4 bg-red-100 text-red-800 rounded">
                        <ul class="list-disc ms-5">
                            @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('appointments.store') }}" class="space-y-4">
                    @csrf
                    <div>
                        <x-input-label for="patient_id" value="Patient" />
                        <select id="patient_id" name="patient_id" class="block mt-1 w-full border-gray-300 rounded-md" required>
                            <option value="">Select patient</option>
                            @foreach ($patients as $p)
                                <option value="{{ $p->id }}" @selected(old('patient_id') == $p->id)>{{ $p->full_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <x-input-label for="dentist_id" value="Dentist" />
                        <select id="dentist_id" name="dentist_id" class="block mt-1 w-full border-gray-300 rounded-md" required>
                            <option value="">Select dentist</option>
                            @foreach ($dentists as $d)
                                <option value="{{ $d->id }}" @selected(old('dentist_id') == $d->id)>{{ $d->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <x-input-label for="service" value="Service" />
                        <x-text-input id="service" name="service" class="block mt-1 w-full" :value="old('service')" required />
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <x-input-label for="appointment_date" value="Date" />
                            <x-text-input id="appointment_date" name="appointment_date" type="date" class="block mt-1 w-full" :value="old('appointment_date')" required />
                        </div>
                        <div>
                            <x-input-label for="appointment_time" value="Time" />
                            <x-text-input id="appointment_time" name="appointment_time" type="time" class="block mt-1 w-full" :value="old('appointment_time')" required />
                        </div>
                    </div>
                    <x-primary-button>Book appointment</x-primary-button>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>