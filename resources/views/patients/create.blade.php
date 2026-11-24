<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Register patient</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <form method="POST" action="{{ route('patients.store') }}" class="space-y-4">
                    @csrf

                    @if ($accounts->isNotEmpty())
                        <div>
                            <x-input-label for="user_id" value="Link patient login (optional)" />
                            <select id="user_id" name="user_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                                <option value="">No login account</option>
                                @foreach ($accounts as $account)
                                    <option value="{{ $account->id }}" @selected(old('user_id') == $account->id)>{{ $account->name }} — {{ $account->email }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('user_id')" class="mt-1" />
                        </div>
                    @endif

                    <div>
                        <x-input-label for="full_name" value="Full name" />
                        <x-text-input id="full_name" name="full_name" type="text"
                                      class="mt-1 block w-full" :value="old('full_name')" required />
                        <x-input-error :messages="$errors->get('full_name')" class="mt-1" />
                    </div>

                    <div>
                        <x-input-label for="date_of_birth" value="Date of birth" />
                        <x-text-input id="date_of_birth" name="date_of_birth" type="date"
                                      class="mt-1 block w-full" :value="old('date_of_birth')" required />
                        <x-input-error :messages="$errors->get('date_of_birth')" class="mt-1" />
                    </div>

                    <div>
                        <x-input-label for="sex" value="Sex" />
                        <select id="sex" name="sex" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required>
                            <option value="">Select</option>
                            <option value="Male" @selected(old('sex') === 'Male')>Male</option>
                            <option value="Female" @selected(old('sex') === 'Female')>Female</option>
                        </select>
                        <x-input-error :messages="$errors->get('sex')" class="mt-1" />
                    </div>

                    <div>
                        <x-input-label for="contact_number" value="Contact number" />
                        <x-text-input id="contact_number" name="contact_number" type="text"
                                      class="mt-1 block w-full" :value="old('contact_number')" required />
                        <x-input-error :messages="$errors->get('contact_number')" class="mt-1" />
                    </div>

                    <div>
                        <x-input-label for="address" value="Address" />
                        <x-text-input id="address" name="address" type="text"
                                      class="mt-1 block w-full" :value="old('address')" required />
                        <x-input-error :messages="$errors->get('address')" class="mt-1" />
                    </div>

                    <div>
                        <x-input-label for="email" value="Email (optional)" />
                        <x-text-input id="email" name="email" type="email"
                                      class="mt-1 block w-full" :value="old('email')" />
                        <x-input-error :messages="$errors->get('email')" class="mt-1" />
                    </div>

                    <div class="flex gap-3">
                        <x-primary-button>Save patient</x-primary-button>
                        <a href="{{ route('patients.index') }}" class="px-4 py-2 text-sm text-gray-600">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
