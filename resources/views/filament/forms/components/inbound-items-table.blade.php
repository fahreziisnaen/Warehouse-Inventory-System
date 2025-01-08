<x-filament::card>
    <table class="w-full divide-y divide-gray-200">
        <thead>
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Brand</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Part Number</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Serial Number</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Quantity</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
            @foreach($inboundItems as $item)
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap">{{ $item['brand'] }}</td>
                    <td class="px-6 py-4 whitespace-nowrap">{{ $item['part_number'] }}</td>
                    <td class="px-6 py-4 whitespace-nowrap">{{ $item['serial_number'] }}</td>
                    <td class="px-6 py-4 whitespace-nowrap">{{ $item['quantity'] }}</td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                            {{ ucfirst($item['status']) }}
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                        <button
                            type="button"
                            wire:click="deleteInboundItem({{ $item['id'] }})"
                            class="text-red-600 hover:text-red-900"
                            onclick="return confirm('Are you sure you want to delete this item?')"
                        >
                            Delete
                        </button>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</x-filament::card> 