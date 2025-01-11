<?php

namespace App\Filament\Resources\OutboundRecordResource\Pages;

use App\Filament\Resources\OutboundRecordResource;
use Filament\Resources\Pages\CreateRecord;
use App\Models\Item;
use App\Models\OutboundItem;
use App\Models\BatchItem;
use Filament\Notifications\Notification;
use Filament\Notifications\Actions\Action;
use Illuminate\Support\HtmlString;

class CreateOutboundRecord extends CreateRecord
{
    protected static string $resource = OutboundRecordResource::class;

    protected function beforeCreate(): void
    {
        // Cek apakah ada Item yang valid
        $hasItems = false;
        if (!empty($this->data['outboundItems'])) {
            foreach ($this->data['outboundItems'] as $item) {
                if (!empty($item['brand_id']) && 
                    !empty($item['part_number_id']) && 
                    !empty($item['bulk_serial_numbers'])) {
                    $hasItems = true;
                    break;
                }
            }
        }

        // Cek apakah ada Batch Item yang valid
        $hasBatchItems = !empty($this->data['part_number_id']) && 
                        !empty($this->data['batch_quantity']) && 
                        $this->data['batch_quantity'] > 0;

        // Jika tidak ada item sama sekali
        if (!$hasItems && !$hasBatchItems) {
            Notification::make()
                ->title('Error')
                ->body('Minimal harus mengisi salah satu: Items atau Batch Item')
                ->danger()
                ->persistent()
                ->actions([
                    Action::make('close')
                        ->label('Tutup')
                        ->color('danger')
                        ->close()
                ])
                ->send();
            
            $this->halt();
        }
    }

    protected function afterCreate(): void
    {
        $record = $this->record;
        $formData = $this->data;

        // Proses Items dengan Serial Number
        if (!empty($formData['outboundItems'])) {
            foreach ($formData['outboundItems'] as $itemData) {
                if (empty($itemData['brand_id']) || 
                    empty($itemData['part_number_id']) || 
                    empty($itemData['bulk_serial_numbers'])) {
                    continue;
                }

                $serialNumbers = array_filter(
                    explode("\n", str_replace("\r", "", $itemData['bulk_serial_numbers']))
                );
                $serialNumbers = array_map('trim', $serialNumbers);

                foreach ($serialNumbers as $serialNumber) {
                    if (empty($serialNumber)) continue;

                    $item = Item::where('serial_number', $serialNumber)
                        ->whereIn('status', ['diterima', 'unknown'])
                        ->first();

                    if ($item) {
                        // Update status berdasarkan purpose
                        $purpose = $record->purpose;
                        $newStatus = match($purpose->name) {
                            'Sewa' => 'masa_sewa',
                            'Pembelian' => 'terjual',
                            'Peminjaman' => 'dipinjam',
                            default => $item->status
                        };
                        
                        $item->update(['status' => $newStatus]);
                        
                        OutboundItem::create([
                            'outbound_id' => $record->outbound_id,
                            'item_id' => $item->item_id,
                            'quantity' => 1
                        ]);
                    }
                }
            }
        }
    }
}
