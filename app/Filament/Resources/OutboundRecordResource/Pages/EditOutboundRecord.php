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
use App\Models\InboundItem;

class EditOutboundRecord extends EditRecord
{
    protected static string $resource = OutboundRecordResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('addItem')
                ->label('Tambah Item')
                ->icon('heroicon-o-plus')
                ->action(function (array $data): void {
                    $this->addItem($data);
                })
                ->form([
                    Forms\Components\Select::make('brand_id')
                        ->label('Brand')
                        ->options(fn () => \App\Models\Brand::pluck('brand_name', 'brand_id'))
                        ->searchable(true)
                        ->preload()
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
                        ->searchable(true)
                        ->preload()
                        ->required(),

                    Forms\Components\Textarea::make('bulk_serial_numbers')
                        ->label('Serial Numbers')
                        ->required()
                        ->helperText('Satu serial number per baris'),

                    Forms\Components\Select::make('purpose_id')
                        ->label('Tujuan')
                        ->options(fn () => \App\Models\Purpose::pluck('name', 'purpose_id'))
                        ->searchable(true)
                        ->preload()
                        ->required(),
                ]),

            Action::make('addBatchItem')
                ->label('Tambah Batch Item')
                ->icon('heroicon-o-plus')
                ->action(function (array $data): void {
                    $this->addBatchItem($data);
                })
                ->form([
                    Forms\Components\Select::make('brand_id')
                        ->label('Brand')
                        ->options(fn () => \App\Models\Brand::pluck('brand_name', 'brand_id'))
                        ->searchable(true)
                        ->preload()
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
                        ->searchable(true)
                        ->preload()
                        ->required(),

                    Forms\Components\TextInput::make('batch_quantity')
                        ->label('Quantity')
                        ->numeric()
                        ->required()
                        ->minValue(1),

                    Forms\Components\Select::make('purpose_id')
                        ->label('Tujuan')
                        ->options(fn () => \App\Models\Purpose::pluck('name', 'purpose_id'))
                        ->searchable(true)
                        ->preload()
                        ->required(),
                ]),
        ];
    }

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Informasi Dasar')
                ->schema([
                    Forms\Components\TextInput::make('lkb_number')
                        ->label('Nomor LKB')
                        ->required()
                        ->unique(ignoreRecord: true),
                    Forms\Components\DatePicker::make('delivery_date')
                        ->label('Tanggal Keluar')
                        ->required(),
                    Forms\Components\Select::make('vendor_id')
                        ->relationship('vendor', 'vendor_name')
                        ->label('Vendor')
                        ->required()
                        ->searchable()
                        ->preload(),
                    Forms\Components\Select::make('project_id')
                        ->relationship('project', 'project_id')
                        ->label('Project ID')
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
                    OutboundItem::create([
                        'outbound_id' => $this->record->outbound_id,
                        'item_id' => $item->item_id,
                        'quantity' => 1,
                        'purpose_id' => $data['purpose_id']
                    ]);

                    // Update status berdasarkan purpose yang baru ditambahkan
                    $outboundItem = OutboundItem::where('outbound_id', $this->record->outbound_id)
                        ->where('item_id', $item->item_id)
                        ->first();

                    $newStatus = match($outboundItem->purpose->name) {
                        'Sewa' => Item::STATUS_MASA_SEWA,
                        'Non Sewa' => Item::STATUS_NON_SEWA,
                        'Peminjaman' => Item::STATUS_DIPINJAM,
                        default => $item->status
                    };
                    
                    $item->update(['status' => $newStatus]);
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
                ->title('Batch item berhasil ditambahkan')
                ->success()
                ->send();
        } catch (\Exception $e) {
            Notification::make()
                ->title('Gagal menambahkan batch item')
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
            $item = $outboundItem->item;
            
            // Cek apakah ini transaksi terakhir untuk item ini
            $lastOutbound = OutboundItem::where('item_id', $item->item_id)
                ->join('outbound_records', 'outbound_items.outbound_id', '=', 'outbound_records.outbound_id')
                ->orderBy('outbound_records.delivery_date', 'desc')
                ->first();
                
            $lastInbound = InboundItem::where('item_id', $item->item_id)
                ->join('inbound_records', 'inbound_items.inbound_id', '=', 'inbound_records.inbound_id')
                ->orderBy('inbound_records.receive_date', 'desc')
                ->first();

            // Jika ini bukan transaksi terakhir, tidak boleh dihapus
            if ($lastOutbound->outbound_item_id !== $outboundItem->outbound_item_id) {
                Notification::make()
                    ->title('Item tidak dapat dihapus')
                    ->body('Item ini memiliki transaksi yang lebih baru. Hanya transaksi terakhir yang dapat dihapus.')
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

            // Update status item berdasarkan transaksi sebelumnya
            if ($lastInbound && $lastInbound->inbound_record->receive_date > $outboundItem->outboundRecord->delivery_date) {
                // Jika ada inbound yang lebih baru, kembalikan ke status diterima
                $item->update(['status' => Item::STATUS_DITERIMA]);
            } else {
                // Cari outbound sebelumnya
                $previousOutbound = OutboundItem::where('item_id', $item->item_id)
                    ->join('outbound_records', 'outbound_items.outbound_id', '=', 'outbound_records.outbound_id')
                    ->where('outbound_records.delivery_date', '<', $outboundItem->outboundRecord->delivery_date)
                    ->orderBy('outbound_records.delivery_date', 'desc')
                    ->first();

                if ($previousOutbound) {
                    // Jika ada outbound sebelumnya, kembalikan ke status sesuai purpose sebelumnya
                    $newStatus = match($previousOutbound->purpose->name) {
                        'Sewa' => Item::STATUS_MASA_SEWA,
                        'Non Sewa' => Item::STATUS_NON_SEWA,
                        'Peminjaman' => Item::STATUS_DIPINJAM,
                        default => Item::STATUS_UNKNOWN
                    };
                    $item->update(['status' => $newStatus]);
                } else {
                    // Jika tidak ada transaksi sebelumnya, set ke unknown
                    $item->update(['status' => Item::STATUS_UNKNOWN]);
                }
            }

            // Delete outbound item
            $outboundItem->delete();

            Notification::make()
                ->title('Item berhasil dihapus')
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
