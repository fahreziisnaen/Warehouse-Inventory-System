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
        // Validasi: harus ada minimal satu Item atau satu Batch Item
        $hasItems = false;
        $invalidSerials = [];
        
        if (!empty($this->data['outboundItems'])) {
            foreach ($this->data['outboundItems'] as $item) {
                if (!empty($item['brand_id']) && 
                    !empty($item['part_number_id']) && 
                    !empty($item['bulk_serial_numbers'])) {
                    
                    // Cek setiap serial number
                    $serialNumbers = array_filter(
                        explode("\n", str_replace("\r", "", $item['bulk_serial_numbers']))
                    );
                    $serialNumbers = array_map('trim', $serialNumbers);

                    foreach ($serialNumbers as $serialNumber) {
                        if (empty($serialNumber)) continue;

                        $existingItem = Item::where('serial_number', $serialNumber)
                            ->where('status', 'diterima')
                            ->first();
                        
                        if (!$existingItem) {
                            $invalidSerials[] = "Serial Number <strong class='text-primary'>{$serialNumber}</strong> tidak ditemukan atau tidak tersedia";
                        } else {
                            $hasItems = true;
                        }
                    }
                }
            }
        }

        // Cek Batch Items
        $hasBatchItems = !empty($this->data['part_number_id']) && !empty($this->data['batch_quantity']);
        
        // Jika ada serial number yang tidak valid
        if (!empty($invalidSerials)) {
            $errorMessage = implode("<br>", $invalidSerials);
            
            Notification::make()
                ->title('Validasi Gagal')
                ->body(new HtmlString($errorMessage))
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

        // Jika tidak ada item valid sama sekali
        if (!$hasItems && !$hasBatchItems) {
            Notification::make()
                ->title('Error')
                ->body('Minimal harus mengisi Item atau Batch Item')
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
                        ->where('status', 'diterima')
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

        // Proses Batch Items jika ada
        if (!empty($formData['part_number_id']) && !empty($formData['batch_quantity'])) {
            BatchItem::updateQuantity(
                $formData['part_number_id'],
                -$formData['batch_quantity'],
                'outbound',
                $record
            );
        }
    }
}
