<?php

namespace App\Filament\Resources\OutboundRecordResource\Pages;

use App\Filament\Resources\OutboundRecordResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use App\Models\Item;

class CreateOutboundRecord extends CreateRecord
{
    protected static string $resource = OutboundRecordResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Proses outbound items dan update status berdasarkan purpose
        if (isset($data['outboundItems'])) {
            foreach ($data['outboundItems'] as $key => $itemData) {
                if (!empty($itemData['item_id'])) {
                    $item = Item::find($itemData['item_id']);
                    if ($item) {
                        // Set status berdasarkan purpose
                        $newStatus = match($data['purpose_id']) {
                            1 => 'masa_sewa',     // Asumsi ID 1 untuk Sewa
                            2 => 'terjual',       // Asumsi ID 2 untuk Pembelian
                            3 => 'dipinjam',      // Asumsi ID 3 untuk Peminjaman
                            default => $item->status
                        };
                        $item->update(['status' => $newStatus]);
                    }
                }
            }
        }

        return $data;
    }
}
