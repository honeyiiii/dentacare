<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Reports</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 bg-white shadow-sm sm:rounded-lg p-6">

            <form method="GET" class="flex flex-wrap gap-3 items-end mb-6 print:hidden">
                <div>
                    <label class="block text-sm text-gray-600">Report</label>
                    <select name="type" class="border-gray-300 rounded">
                        @foreach ($types as $key => $label)
                            <option value="{{ $key }}" @selected($type === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm text-gray-600">From</label>
                    <input type="date" name="from" value="{{ $from }}" class="border-gray-300 rounded">
                </div>
                <div>
                    <label class="block text-sm text-gray-600">To</label>
                    <input type="date" name="to" value="{{ $to }}" class="border-gray-300 rounded">
                </div>
                <button class="px-4 py-2 bg-blue-600 text-white rounded">Generate</button>
                <button type="button" onclick="window.print()" class="px-4 py-2 bg-gray-700 text-white rounded">Print</button>
            </form>

            <h3 class="text-lg font-semibold mb-1">{{ $types[$type] }}</h3>
            <p class="text-sm text-gray-500 mb-4">{{ $from }} to {{ $to }} — {{ count($rows) }} record(s)</p>

            <div class="overflow-x-auto">
                <table class="min-w-full text-sm text-left border">
                    <thead class="bg-gray-100">
                        <tr>
                            @foreach ($headings as $h)
                                <th class="px-3 py-2 border">{{ $h }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($rows as $row)
                            <tr>
                                @foreach ($row as $cell)
                                    <td class="px-3 py-2 border">{{ $cell }}</td>
                                @endforeach
                            </tr>
                        @empty
                            <tr><td colspan="{{ count($headings) }}" class="px-3 py-4 text-center text-gray-500">No records for this period.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>