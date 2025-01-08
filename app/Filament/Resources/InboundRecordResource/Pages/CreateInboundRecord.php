<?php

namespace App\Filament\Resources\InboundRecordResource\Pages;

use App\Filament\Resources\InboundRecordResource;
use Filament\Resources\Pages\CreateRecord;
use App\Models\Item;
use App\Models\InboundItem;
use App\Models\BatchItem;
use Filament\Notifications\Notification;
use Filament\Notifications\Actions\Action;
use Illuminate\Support\HtmlString;

class CreateInboundRecord extends CreateRecord
{
    protected static string $resource = InboundRecordResource::class;

    protected function beforeCreate(): void
    {
        // Validasi: harus ada minimal satu Item atau satu Batch Item
        $hasItems = false;
        $invalidSerials = [];
        
        if (!empty($this->data['inboundItems'])) {
            foreach ($this->data['inboundItems'] as $item) {
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

                        $existingItem = Item::where('serial_number', $serialNumber)->first();
                        
                        if ($existingItem) {
                            if ($existingItem->status === 'diterima') {
                                $invalidSerials[] = "Serial Number <strong class='text-primary'>{$serialNumber}</strong> masih berada di Gudang";
                            } elseif (!in_array($existingItem->status, ['disewa', 'dipinjam', 'terjual'])) {
                                $invalidSerials[] = "Serial Number <strong class='text-primary'>{$serialNumber}</strong> memiliki status yang tidak valid ({$existingItem->status})";
                            } else {
                                $hasItems = true;
                            }
                        } else {
                            $hasItems = true;
                        }
                    }
                }
            }
        }

        $hasBatchItems = !empty($this->data['part_number_id']) && 
            !empty($this->data['batch_quantity']);
        
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

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (isset($data['inboundItems'])) {
            $validItems = [];
            
            foreach ($data['inboundItems'] as $item) {
                if (!empty($item['brand_id']) && 
                    !empty($item['part_number_id']) && 
                    !empty($item['bulk_serial_numbers'])) {
                    $validItems[] = [
                        'brand_id' => $item['brand_id'],
                        'part_number_id' => $item['part_number_id'],
                        'bulk_serial_numbers' => $item['bulk_serial_numbers']
                    ];
                }
            }
            
            if (!empty($validItems)) {
                $data['inboundItems'] = $validItems;
            } else {
                unset($data['inboundItems']);
            }
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        $record = $this->record;
        $formData = $this->data;

        // Proses Items dengan Serial Number
        if (!empty($formData['inboundItems'])) {
            foreach ($formData['inboundItems'] as $itemData) {
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

                    try {
                        $existingItem = Item::where('serial_number', $serialNumber)->first();

                        if ($existingItem) {
                            // Cek status item
                            if ($existingItem->status === 'diterima') {
                                Notification::make()
                                    ->title('Error')
                                    ->body("Serial Number {$serialNumber} masih berstatus 'diterima'. Tidak bisa diinbound ulang.")
                                    ->danger()
                                    ->send();
                                continue;
                            }

                            // Hanya proses jika status adalah disewa/dipinjam/terjual
                            if (in_array($existingItem->status, ['disewa', 'dipinjam', 'terjual'])) {
                                $existingItem->update(['status' => 'diterima']);
                                InboundItem::create([
                                    'inbound_id' => $record->inbound_id,
                                    'item_id' => $existingItem->item_id,
                                    'quantity' => 1
                                ]);
                            } else {
                                Notification::make()
                                    ->title('Error')
                                    ->body("Serial Number {$serialNumber} memiliki status yang tidak valid untuk inbound.")
                                    ->danger()
                                    ->send();
                            }
                        } else {
                            // Jika item baru, buat record baru
                            $newItem = Item::create([
                                'part_number_id' => $itemData['part_number_id'],
                                'serial_number' => $serialNumber,
                                'status' => 'diterima'
                            ]);

                            if ($newItem) {
                                InboundItem::create([
                                    'inbound_id' => $record->inbound_id,
                                    'item_id' => $newItem->item_id,
                                    'quantity' => 1
                                ]);
                            }
                        }
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Error')
                            ->body("Gagal memproses Serial Number {$serialNumber}: " . $e->getMessage())
                            ->danger()
                            ->send();
                    }
                }
            }
        }

        // Proses Batch Items
        if (!empty($formData['part_number_id']) && !empty($formData['batch_quantity'])) {
            $batchItem = BatchItem::firstOrCreate(
                ['part_number_id' => $formData['part_number_id']],
                ['quantity' => 0, 'format_id' => $formData['format_id'] ?? null]
            );

            BatchItem::updateQuantity(
                $formData['part_number_id'],
                $formData['batch_quantity'],
                'inbound',
                $record
            );
        }
    }
}
