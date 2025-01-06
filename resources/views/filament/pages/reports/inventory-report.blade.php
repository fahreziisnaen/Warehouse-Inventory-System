<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Serial Number Items Section --}}
        <div class="p-6 bg-white rounded-lg shadow">
            <h2 class="text-xl font-bold mb-4">Items dengan Serial Number</h2>
            {{ $this->table }}
        </div>

        {{-- Batch Items Section --}}
        <div class="p-6 bg-white rounded-lg shadow">
            <h2 class="text-xl font-bold mb-4">Batch Items</h2>
            {{ $this->getBatchItemsTable() }}
        </div>
    </div>
</x-filament-panels::page>
