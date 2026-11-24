<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Staff Accounts</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('success'))
                <div class="p-4 bg-green-100 text-green-800 rounded">{{ session('success') }}</div>
            @endif
            @if ($errors->any())
                <div class="p-4 bg-red-100 text-red-800 rounded">
                    <ul class="list-disc ms-5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- Add staff --}}
            <div class="bg-white shadow sm:rounded-lg p-6">
                <h3 class="font-semibold text-lg mb-4">Add staff member</h3>
                <form method="POST" action="{{ route('staff.store') }}" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @csrf
                    <div>
                        <x-input-label for="name" value="Name" />
                        <x-text-input id="name" name="name" class="block mt-1 w-full" :value="old('name')" required />
                    </div>
                    <div>
                        <x-input-label for="email" value="Email" />
                        <x-text-input id="email" name="email" type="email" class="block mt-1 w-full" :value="old('email')" required />
                    </div>
                    <div>
                        <x-input-label for="role" value="Role" />
                        <select id="role" name="role" class="block mt-1 w-full border-gray-300 rounded-md shadow-sm" required>
                            @foreach (['dentist', 'assistant', 'receptionist', 'owner'] as $r)
                                <option value="{{ $r }}" @selected(old('role') === $r)>{{ ucfirst($r) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div></div>
                    <div>
                        <x-input-label for="password" value="Temporary password" />
                        <x-text-input id="password" name="password" type="password" class="block mt-1 w-full" required />
                    </div>
                    <div>
                        <x-input-label for="password_confirmation" value="Confirm password" />
                        <x-text-input id="password_confirmation" name="password_confirmation" type="password" class="block mt-1 w-full" required />
                    </div>
                    <div class="md:col-span-2">
                        <x-primary-button>Create account</x-primary-button>
                    </div>
                </form>
            </div>

            {{-- Staff list --}}
            <div class="bg-white shadow sm:rounded-lg p-6">
                <form method="GET" action="{{ route('staff.index') }}" class="flex gap-2 mb-4">
                    <x-text-input name="search" :value="$search" placeholder="Search name or email" class="w-full md:w-1/3" />
                    <x-secondary-button type="submit">Search</x-secondary-button>
                </form>

                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="text-left border-b">
                            <th class="py-2">Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($staff as $member)
                            <tr class="border-b">
                                <td class="py-2">{{ $member->name }}</td>
                                <td>{{ $member->email }}</td>
                                <td>
                                    @if ($member->id === auth()->id())
                                        {{ ucfirst($member->role) }} (you)
                                    @else
                                        <form method="POST" action="{{ route('staff.role', $member) }}">
                                            @csrf @method('PATCH')
                                            <select name="role" onchange="this.form.submit()" class="border-gray-300 rounded-md text-sm">
                                                @foreach (['owner', 'dentist', 'assistant', 'receptionist'] as $r)
                                                    <option value="{{ $r }}" @selected($member->role === $r)>{{ ucfirst($r) }}</option>
                                                @endforeach
                                            </select>
                                        </form>
                                    @endif
                                </td>
                                <td>
                                    <span class="{{ $member->is_active ? 'text-green-700' : 'text-red-700' }}">
                                        {{ $member->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td class="text-right">
                                    @if ($member->id !== auth()->id())
                                        <form method="POST" action="{{ route('staff.toggle', $member) }}">
                                            @csrf @method('PATCH')
                                            <button class="text-indigo-600 hover:underline">
                                                {{ $member->is_active ? 'Deactivate' : 'Reactivate' }}
                                            </button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="py-4 text-gray-500">No staff found.</td></tr>
                        @endforelse
                    </tbody>
                </table>

                <div class="mt-4">{{ $staff->links() }}</div>
            </div>
        </div>
    </div>
</x-app-layout>