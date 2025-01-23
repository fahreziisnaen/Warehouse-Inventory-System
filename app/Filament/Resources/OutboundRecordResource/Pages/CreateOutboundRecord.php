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
use Illuminate\Support\HtmlString;

class CreateOutboundRecord extends CreateRecord
{
    protected static string $resource = OutboundRecordResource::class;

    protected function beforeCreate(): void
    {
        // Validasi: harus ada minimal satu Item atau satu Batch Item
        $hasItems = false;
        $invalidSerials = [];
        $unavailableSerials = [];
        $wrongPartNumberSerials = [];
        
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

                    // Cek ketersediaan item
                    $item = Item::where('serial_number', $serialNumber)->first();
                    
                    if (!$item) {
                        $unavailableSerials[] = $serialNumber;
                        continue;
                    }

                    // Validasi Part Number sesuai
                    if ($item->part_number_id != $itemData['part_number_id']) {
                        $wrongPartNumberSerials[] = $serialNumber;
                        continue;
                    }

                    // Cek status item
                    if ($item->status !== 'diterima') {
                        $invalidSerials[] = "Serial Number <strong>{$serialNumber}</strong> memiliki status <strong>{$item->status}</strong>";
                        continue;
                    }

                    $hasItems = true;
                }
            }
        }

        // Validasi Batch Items
        $hasBatchItems = !empty($this->data['batchItems']) && collect($this->data['batchItems'])->some(function ($item) {
            return !empty($item['part_number_id']) && 
                   !empty($item['batch_quantity']);
        });

        if (!$hasItems && !$hasBatchItems) {
            throw new \Exception('Minimal harus ada satu item atau batch item yang valid');
        }

        // Tampilkan error dialog jika ada serial number yang tidak valid
        if (!empty($unavailableSerials) || !empty($wrongPartNumberSerials) || !empty($invalidSerials)) {
            $errorMessages = [];
            
            if (!empty($unavailableSerials)) {
                $errorMessages[] = "<div class='font-semibold mb-2'>Serial Number tidak tersedia:</div>" .
                    "<ul class='list-disc pl-4 mb-2'><li>" . 
                    implode("</li><li>", $unavailableSerials) . 
                    "</li></ul>";
            }
            
            if (!empty($wrongPartNumberSerials)) {
                $errorMessages[] = "<div class='font-semibold mb-2'>Serial Number tidak sesuai dengan Part Number:</div>" .
                    "<ul class='list-disc pl-4 mb-2'><li>" . 
                    implode("</li><li>", $wrongPartNumberSerials) . 
                    "</li></ul>";
            }
            
            if (!empty($invalidSerials)) {
                $errorMessages[] = "<div class='font-semibold mb-2'>Serial Number dengan status tidak valid:</div>" .
                    "<ul class='list-disc pl-4'><li>" . 
                    implode("</li><li>", $invalidSerials) . 
                    "</li></ul>";
            }

            Notification::make()
                ->title('Validasi Serial Number Gagal')
                ->body(new HtmlString(implode("", $errorMessages)))
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
        $addedCount = 0;
        $errors = [];

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
                        ->where('status', 'diterima')
                        ->first();

                    if (!$item) continue;

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

        // Proses Batch Items
        if (!empty($formData['batchItems'])) {
            foreach ($formData['batchItems'] as $batchItem) {
                if (empty($batchItem['part_number_id']) || 
                    empty($batchItem['batch_quantity'])) {
                    continue;
                }

                $availableBatch = BatchItem::where('part_number_id', $batchItem['part_number_id'])
                    ->where('quantity', '>=', $batchItem['batch_quantity'])
                    ->first();

                if ($availableBatch) {
                    BatchItem::updateQuantity(
                        $batchItem['part_number_id'],
                        -$batchItem['batch_quantity'],
                        'outbound',
                        $record
                    );
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
