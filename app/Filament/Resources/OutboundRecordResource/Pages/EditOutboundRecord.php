<?php

namespace App\Filament\Resources\OutboundRecordResource\Pages;

use App\Filament\Resources\OutboundRecordResource;
use Filament\Resources\Pages\EditRecord;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Actions\Action;
use App\Models\Item;
use App\Models\OutboundItem;
use App\Models\BatchItem;
use Filament\Notifications\Notification;
use Illuminate\Support\HtmlString;

class EditOutboundRecord extends EditRecord
{
    protected static string $resource = OutboundRecordResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('addItem')
                ->label('Add Item')
                ->icon('heroicon-o-plus')
                ->action(function (array $data): void {
                    $this->addItem($data);
                })
                ->form([
                    Forms\Components\Select::make('brand_id')
                        ->label('Brand')
                        ->options(fn () => \App\Models\Brand::pluck('brand_name', 'brand_id'))
                        ->required()
                        ->reactive()
                        ->afterStateUpdated(fn (callable $set) => $set('part_number_id', null)),

                    Forms\Components\Select::make('part_number_id')
                        ->label('Part Number')
                        ->options(function (callable $get) {
                            $brandId = $get('brand_id');
                            if (!$brandId) return [];
                            return \App\Models\PartNumber::where('brand_id', $brandId)
                                ->pluck('part_number', 'part_number_id');
                        })
                        ->required(),

                    Forms\Components\Textarea::make('bulk_serial_numbers')
                        ->label('Serial Numbers')
                        ->required()
                        ->helperText('Satu serial number per baris'),
                ]),

            Action::make('addBatchItem')
                ->label('Add Batch Item')
                ->icon('heroicon-o-plus')
                ->action(function (array $data): void {
                    $this->addBatchItem($data);
                })
                ->form([
                    Forms\Components\Select::make('brand_id')
                        ->label('Brand')
                        ->options(fn () => \App\Models\Brand::pluck('brand_name', 'brand_id'))
                        ->required()
                        ->reactive()
                        ->afterStateUpdated(fn (callable $set) => $set('part_number_id', null)),

                    Forms\Components\Select::make('part_number_id')
                        ->label('Part Number')
                        ->options(function (callable $get) {
                            $brandId = $get('brand_id');
                            if (!$brandId) return [];
                            return \App\Models\PartNumber::where('brand_id', $brandId)
                                ->pluck('part_number', 'part_number_id');
                        })
                        ->required(),

                    Forms\Components\TextInput::make('batch_quantity')
                        ->label('Quantity')
                        ->numeric()
                        ->required()
                        ->minValue(1),
                ]),
        ];
    }

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Card::make()
                ->schema([
                    Forms\Components\TextInput::make('lkb_number')
                        ->label('Nomor LKB')
                        ->required()
                        ->unique(ignoreRecord: true),
                    Forms\Components\DatePicker::make('delivery_date')
                        ->label('Delivery Date')
                        ->required(),
                    Forms\Components\Select::make('vendor_id')
                        ->relationship(
                            'vendor', 
                            'vendor_name',
                            fn ($query) => $query->whereHas('vendorType', fn($q) => 
                                $q->where('type_name', 'Customer')
                            )
                        )
                        ->label('Customer')
                        ->required()
                        ->searchable()
                        ->preload(),
                    Forms\Components\Select::make('project_id')
                        ->relationship('project', 'project_id')
                        ->label('Project ID')
                        ->required()
                        ->searchable()
                        ->preload(),
                    Forms\Components\Select::make('purpose_id')
                        ->relationship('purpose', 'name')
                        ->label('Tujuan')
                        ->required()
                        ->searchable()
                        ->preload(),
                ])
                ->columns(2),

            Forms\Components\Section::make('Items')
                ->schema([
                    Forms\Components\View::make('filament.forms.components.outbound-items-table')
                        ->viewData([
                            'outboundItems' => $this->record->outboundItems()
                                ->with(['item.partNumber.brand'])
                                ->get()
                                ->map(function ($item) {
                                    return [
                                        'id' => $item->outbound_item_id,
                                        'brand' => $item->item->partNumber->brand->brand_name ?? '-',
                                        'part_number' => $item->item->partNumber->part_number ?? '-',
                                        'serial_number' => $item->item->serial_number ?? '-',
                                        'quantity' => $item->quantity,
                                        'status' => $item->item->status ?? '-',
                                    ];
                                }),
                        ]),
                ]),

            Forms\Components\Section::make('Batch Items')
                ->schema([
                    Forms\Components\View::make('filament.forms.components.batch-items-table')
                        ->viewData([
                            'batchItems' => $this->record->batchItemHistories()
                                ->with(['batchItem.partNumber.brand', 'batchItem.unitFormat'])
                                ->get()
                                ->map(function ($history) {
                                    return [
                                        'id' => $history->history_id,
                                        'brand' => $history->batchItem->partNumber->brand->brand_name ?? '-',
                                        'part_number' => $history->batchItem->partNumber->part_number ?? '-',
                                        'quantity' => $history->quantity,
                                        'satuan' => $history->batchItem->unitFormat->name ?? '-',
                                    ];
                                }),
                        ]),
                ]),
        ]);
    }

    protected function addItem(array $data): void
    {
        $serialNumbers = array_filter(
            explode("\n", str_replace("\r", "", $data['bulk_serial_numbers']))
        );
        $serialNumbers = array_map('trim', $serialNumbers);

        $addedCount = 0;
        $errors = [];

        foreach ($serialNumbers as $serialNumber) {
            if (empty($serialNumber)) continue;

            try {
                $item = Item::where('serial_number', $serialNumber)
                    ->whereIn('status', ['diterima', 'unknown'])
                    ->first();

                if ($item) {
                    // Update status berdasarkan purpose
                    $purpose = $this->record->purpose;
                    $newStatus = match($purpose->name) {
                        'Sewa' => 'masa_sewa',
                        'Pembelian' => 'terjual',
                        'Peminjaman' => 'dipinjam',
                        default => $item->status
                    };
                    
                    $item->update(['status' => $newStatus]);
                    
                    OutboundItem::create([
                        'outbound_id' => $this->record->outbound_id,
                        'item_id' => $item->item_id,
                        'quantity' => 1
                    ]);
                    $addedCount++;
                } else {
                    $errors[] = "Serial Number <strong class='text-primary'>{$serialNumber}</strong> tidak ditemukan atau tidak tersedia";
                }
            } catch (\Exception $e) {
                $errors[] = "Error processing <strong class='text-primary'>{$serialNumber}</strong>: " . $e->getMessage();
            }
        }

        if ($addedCount > 0) {
            Notification::make()
                ->title("{$addedCount} item(s) added successfully")
                ->success()
                ->send();
        }

        if (!empty($errors)) {
            Notification::make()
                ->title('Some items could not be added')
                ->body(new HtmlString(implode("<br>", $errors)))
                ->danger()
                ->persistent()
                ->actions([
                    \Filament\Notifications\Actions\Action::make('close')
                        ->label('Tutup')
                        ->color('danger')
                        ->close()
                ])
                ->send();
        }

        $this->redirect(EditOutboundRecord::getUrl(['record' => $this->record]));
    }

    protected function addBatchItem(array $data): void
    {
        try {
            $batchItem = BatchItem::where('part_number_id', $data['part_number_id'])->first();
            
            if (!$batchItem || $batchItem->quantity < $data['batch_quantity']) {
                throw new \Exception('Quantity melebihi stock yang tersedia');
            }

            BatchItem::updateQuantity(
                $data['part_number_id'],
                -$data['batch_quantity'],
                'outbound',
                $this->record
            );

            Notification::make()
                ->title('Batch item added successfully')
                ->success()
                ->send();
        } catch (\Exception $e) {
            Notification::make()
                ->title('Error adding batch item')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }

        $this->redirect(EditOutboundRecord::getUrl(['record' => $this->record]));
    }

    public function deleteOutboundItem(int $outboundItemId): void
    {
        $outboundItem = OutboundItem::find($outboundItemId);
        
        if ($outboundItem) {
            // Cek status item
            $validStatuses = ['masa_sewa', 'terjual', 'dipinjam'];
            if ($outboundItem->item && !in_array($outboundItem->item->status, $validStatuses)) {
                Notification::make()
                    ->title('Item tidak dapat dihapus')
                    ->body("Item dengan status '{$outboundItem->item->status}' tidak dapat dihapus karena status tidak sesuai. Item harus berstatus Disewa, Terjual, atau Dipinjam.")
                    ->danger()
                    ->persistent()
                    ->actions([
                        \Filament\Notifications\Actions\Action::make('close')
                            ->label('Tutup')
                            ->color('danger')
                            ->close()
                    ])
                    ->send();
                    
                return;
            }

            // Jika status valid, lanjutkan proses delete
            if ($outboundItem->item) {
                $outboundItem->item->update([
                    'status' => 'unknown'
                ]);
            }

            // Delete outbound item
            $outboundItem->delete();

            Notification::make()
                ->title('Item deleted successfully')
                ->success()
                ->send();

            $this->redirect(EditOutboundRecord::getUrl(['record' => $this->record]));
        }
    }

    public function deleteBatchItem(int $historyId): void
    {
        $history = \App\Models\BatchItemHistory::find($historyId);
        
        if ($history) {
            // Reverse the quantity change
            $batchItem = $history->batchItem;
            if ($batchItem) {
                // Untuk outbound, ketika dihapus maka stock harus bertambah
                // karena sebelumnya stock berkurang saat outbound
                if ($history->type === 'outbound') {
                    $batchItem->increment('quantity', abs($history->quantity));
                } else {
                    $batchItem->decrement('quantity', abs($history->quantity));
                }
            }

            // Delete the history record
            $history->delete();

            Notification::make()
                ->title('Batch item deleted successfully')
                ->success()
                ->send();

            $this->redirect(EditOutboundRecord::getUrl(['record' => $this->record]));
        }
    }
}
