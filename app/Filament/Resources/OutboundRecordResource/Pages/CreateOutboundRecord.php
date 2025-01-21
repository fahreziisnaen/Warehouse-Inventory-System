<?php

namespace App\Filament\Resources\OutboundRecordResource\Pages;

use App\Filament\Resources\OutboundRecordResource;
use Filament\Resources\Pages\CreateRecord;
use App\Models\Item;
use App\Models\OutboundItem;
use Filament\Notifications\Notification;
use Filament\Notifications\Actions\Action;
use App\Models\BatchItem;
use Filament\Forms\Components\Select;

class CreateOutboundRecord extends CreateRecord
{
    protected static string $resource = OutboundRecordResource::class;

    protected function beforeCreate(): void
    {
        $errors = [];
        $validItems = 0;

        // Validasi Items dengan Serial Number
        if (!empty($this->data['outboundItems'])) {
            foreach ($this->data['outboundItems'] as $itemData) {
                if (empty($itemData['brand_id']) || 
                    empty($itemData['part_number_id']) || 
                    empty($itemData['bulk_serial_numbers']) ||
                    empty($itemData['purpose_id'])) {
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

                    if (!$item) {
                        $errors[] = "Serial Number '{$serialNumber}' tidak ditemukan dalam database";
                        continue;
                    }

                    // Validasi Part Number sesuai
                    if ($item->part_number_id != $itemData['part_number_id']) {
                        $errors[] = "Serial Number '{$serialNumber}' tidak sesuai dengan Part Number yang dipilih";
                        continue;
                    }

                    $validItems++;
                }
            }
        }

        // Validasi Batch Items
        $hasBatchItems = !empty($this->data['batchItems']) && 
            collect($this->data['batchItems'])->some(fn ($item) => 
                !empty($item['part_number_id']) && 
                !empty($item['batch_quantity']) && 
                $item['batch_quantity'] > 0
            );

        // Jika tidak ada item valid dan tidak ada batch items
        if ($validItems === 0 && !$hasBatchItems) {
            if (!empty($errors)) {
                Notification::make()
                    ->title('Validasi Gagal')
                    ->body(implode("\n", $errors))
                    ->danger()
                    ->persistent()
                    ->actions([
                        Action::make('close')
                            ->label('Tutup')
                            ->color('danger')
                            ->close()
                    ])
                    ->send();
            } else {
                Notification::make()
                    ->title('Error')
                    ->body('Minimal harus mengisi salah satu: Items atau Batch Item yang valid')
                    ->danger()
                    ->persistent()
                    ->actions([
                        Action::make('close')
                            ->label('Tutup')
                            ->color('danger')
                            ->close()
                    ])
                    ->send();
            }
            
            $this->halt();
        }
    }

    protected function afterCreate(): void
    {
        $record = $this->record;
        $formData = $this->data;
        $errors = [];
        $addedCount = 0;

        // Proses Items dengan Serial Number
        if (!empty($formData['outboundItems'])) {
            foreach ($formData['outboundItems'] as $itemData) {
                if (empty($itemData['brand_id']) || 
                    empty($itemData['part_number_id']) || 
                    empty($itemData['bulk_serial_numbers']) ||
                    empty($itemData['purpose_id'])) {
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

                    if (!$item) {
                        $errors[] = "Serial Number '{$serialNumber}' tidak ditemukan dalam database";
                        continue;
                    }

                    // Validasi Part Number sesuai
                    if ($item->part_number_id != $itemData['part_number_id']) {
                        $errors[] = "Serial Number '{$serialNumber}' tidak sesuai dengan Part Number yang dipilih";
                        continue;
                    }

                    OutboundItem::create([
                        'outbound_id' => $record->outbound_id,
                        'item_id' => $item->item_id,
                        'quantity' => 1,
                        'purpose_id' => $itemData['purpose_id']
                    ]);

                    $addedCount++;
                }
            }
        }

        if (!empty($errors)) {
            Notification::make()
                ->title('Beberapa item tidak dapat ditambahkan')
                ->body(implode("\n", $errors))
                ->danger()
                ->persistent()
                ->actions([
                    Action::make('close')
                        ->label('Tutup')
                        ->color('danger')
                        ->close()
                ])
                ->send();
        }

        if ($addedCount > 0) {
            Notification::make()
                ->title("{$addedCount} item berhasil ditambahkan")
                ->success()
                ->send();
        }

        // Proses Batch Items
        if (!empty($formData['batchItems'])) {
            foreach ($formData['batchItems'] as $batchItem) {
                if (empty($batchItem['part_number_id']) || empty($batchItem['batch_quantity'])) {
                    continue;
                }

                BatchItem::updateQuantity(
                    $batchItem['part_number_id'],
                    -$batchItem['batch_quantity'],
                    'outbound',
                    $record
                );
            }
        }
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if ($data['lkb_type'] === 'new') {
            $data['lkb_number'] = \App\Models\OutboundRecord::generateLkbNumber($data['location']);
        }
        
        unset($data['lkb_type']);
        return $data;
    }
}
