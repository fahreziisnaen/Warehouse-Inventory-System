<?php

namespace App\Filament\Resources\InboundRecordResource\Pages;

use App\Filament\Resources\InboundRecordResource;
use Filament\Resources\Pages\EditRecord;
use Filament\Forms;
use Filament\Forms\Form;
use App\Models\InboundItem;
use Filament\Notifications\Notification;
use Filament\Actions\Action;
use App\Models\Item;
use App\Models\BatchItem;
use Illuminate\Support\HtmlString;
use Illuminate\Database\Eloquent\Model;
use App\Models\OutboundItem;

class EditInboundRecord extends EditRecord
{
    protected static string $resource = InboundRecordResource::class;

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
                        ->required()
                        ->reactive()
                        ->createOptionForm([
                            Forms\Components\TextInput::make('brand_name')
                                ->required()
                                ->unique('brands', 'brand_name')
                        ])
                        ->createOptionUsing(function (array $data) {
                            return \App\Models\Brand::create([
                                'brand_name' => $data['brand_name']
                            ])->brand_id;
                        })
                        ->afterStateUpdated(fn (callable $set) => $set('part_number_id', null)),

                    Forms\Components\Select::make('part_number_id')
                        ->label('Part Number')
                        ->options(function (callable $get) {
                            $brandId = $get('brand_id');
                            if (!$brandId) return [];
                            return \App\Models\PartNumber::where('brand_id', $brandId)
                                ->pluck('part_number', 'part_number_id');
                        })
                        ->required()
                        ->createOptionForm([
                            Forms\Components\Select::make('brand_id')
                                ->label('Brand')
                                ->options(fn () => \App\Models\Brand::pluck('brand_name', 'brand_id'))
                                ->required(),
                            Forms\Components\TextInput::make('part_number')
                                ->required()
                                ->unique('part_numbers', 'part_number'),
                            Forms\Components\Textarea::make('description')
                                ->columnSpanFull(),
                        ])
                        ->createOptionUsing(function (array $data) {
                            return \App\Models\PartNumber::create([
                                'brand_id' => $data['brand_id'],
                                'part_number' => $data['part_number'],
                                'description' => $data['description'] ?? null,
                            ])->part_number_id;
                        }),

                    Forms\Components\Textarea::make('bulk_serial_numbers')
                        ->label('Serial Numbers')
                        ->required()
                        ->helperText('Satu serial number per baris'),
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
                        ->required()
                        ->reactive()
                        ->createOptionForm([
                            Forms\Components\TextInput::make('brand_name')
                                ->required()
                                ->unique('brands', 'brand_name')
                        ])
                        ->createOptionUsing(function (array $data) {
                            return \App\Models\Brand::create([
                                'brand_name' => $data['brand_name']
                            ])->brand_id;
                        })
                        ->afterStateUpdated(fn (callable $set) => $set('part_number_id', null)),

                    Forms\Components\Select::make('part_number_id')
                        ->label('Part Number')
                        ->options(function (callable $get) {
                            $brandId = $get('brand_id');
                            if (!$brandId) return [];
                            return \App\Models\PartNumber::where('brand_id', $brandId)
                                ->pluck('part_number', 'part_number_id');
                        })
                        ->required()
                        ->createOptionForm([
                            Forms\Components\Select::make('brand_id')
                                ->label('Brand')
                                ->options(fn () => \App\Models\Brand::pluck('brand_name', 'brand_id'))
                                ->required(),
                            Forms\Components\TextInput::make('part_number')
                                ->required()
                                ->unique('part_numbers', 'part_number'),
                            Forms\Components\Textarea::make('description')
                                ->columnSpanFull(),
                        ])
                        ->createOptionUsing(function (array $data) {
                            return \App\Models\PartNumber::create([
                                'brand_id' => $data['brand_id'],
                                'part_number' => $data['part_number'],
                                'description' => $data['description'] ?? null,
                            ])->part_number_id;
                        }),

                    Forms\Components\TextInput::make('batch_quantity')
                        ->label('Quantity')
                        ->numeric()
                        ->required()
                        ->minValue(1),

                    Forms\Components\Select::make('format_id')
                        ->label('Satuan')
                        ->options(fn () => \App\Models\UnitFormat::pluck('name', 'format_id'))
                        ->required()
                        ->createOptionForm([
                            Forms\Components\TextInput::make('name')
                                ->required()
                                ->unique('unit_formats', 'name')
                        ])
                        ->createOptionUsing(function (array $data) {
                            return \App\Models\UnitFormat::create([
                                'name' => $data['name']
                            ])->format_id;
                        }),
                ]),
        ];
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
                $existingItem = Item::where('serial_number', $serialNumber)->first();

                if ($existingItem) {
                    if ($existingItem->status === 'diterima') {
                        $errors[] = "Serial Number <strong class='text-primary'>{$serialNumber}</strong> masih berada di Gudang";
                        continue;
                    }

                    if (!in_array($existingItem->status, ['disewa', 'dipinjam', 'terjual', 'unknown'])) {
                        $errors[] = "Serial Number <strong class='text-primary'>{$serialNumber}</strong> memiliki status yang tidak valid";
                        continue;
                    }

                    $existingItem->update(['status' => 'diterima']);
                    InboundItem::create([
                        'inbound_id' => $this->record->inbound_id,
                        'item_id' => $existingItem->item_id,
                        'quantity' => 1
                    ]);
                    $addedCount++;
                } else {
                    $newItem = Item::create([
                        'part_number_id' => $data['part_number_id'],
                        'serial_number' => $serialNumber,
                        'status' => 'diterima'
                    ]);

                    if ($newItem) {
                        InboundItem::create([
                            'inbound_id' => $this->record->inbound_id,
                            'item_id' => $newItem->item_id,
                            'quantity' => 1
                        ]);
                        $addedCount++;
                    }
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
                    Action::make('close')
                        ->label('Tutup')
                        ->color('danger')
                        ->close()
                ])
                ->send();
        }

        $this->redirect(EditInboundRecord::getUrl(['record' => $this->record]));
    }

    protected function addBatchItem(array $data): void
    {
        try {
            $existingBatchItem = BatchItem::firstOrCreate(
                ['part_number_id' => $data['part_number_id']],
                ['quantity' => 0, 'format_id' => $data['format_id']]
            );

            BatchItem::updateQuantity(
                $data['part_number_id'],
                $data['batch_quantity'],
                'inbound',
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

        $this->redirect(EditInboundRecord::getUrl(['record' => $this->record]));
    }

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Informasi Dasar')
                ->schema([
                    Forms\Components\TextInput::make('lpb_number')
                        ->label('No. LPB')
                        ->required()
                        ->unique(ignoreRecord: true),
                    Forms\Components\DatePicker::make('receive_date')
                        ->label('Tanggal Terima')
                        ->required(),
                    Forms\Components\Select::make('location')
                        ->label('Lokasi')
                        ->options([
                            'Gudang Jakarta' => 'Gudang Jakarta',
                            'Gudang Surabaya' => 'Gudang Surabaya',
                        ])
                        ->required(),
                    Forms\Components\Textarea::make('note')
                        ->label('Catatan')
                        ->nullable()
                        ->columnSpanFull(),
                ])
                ->columns(2),

            Forms\Components\Section::make('Informasi Referensi')
                ->schema([
                    Forms\Components\Select::make('po_id')
                        ->relationship('purchaseOrder', 'po_number')
                        ->label('No. PO')
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

            // Tabel untuk Items dengan Serial Number
            Forms\Components\Section::make('Items dengan Serial Number')
                ->schema([
                    Forms\Components\View::make('filament.forms.components.inbound-items-table')
                        ->viewData([
                            'inboundItems' => $this->record->inboundItems()
                                ->with(['item.partNumber.brand'])
                                ->get()
                                ->map(function ($item) {
                                    return [
                                        'id' => $item->inbound_item_id,
                                        'brand' => $item->item->partNumber->brand->brand_name ?? '-',
                                        'part_number' => $item->item->partNumber->part_number ?? '-',
                                        'serial_number' => $item->item->serial_number ?? '-',
                                        'quantity' => $item->quantity,
                                        'status' => $item->item->status ?? '-',
                                        'condition' => $item->item->condition ?? '-',
                                    ];
                                }),
                            'record' => $this->record,
                        ]),
                ]),

            // Tambahkan Section untuk Batch Items
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
                            'record' => $this->record,
                        ]),
                ]),
        ]);
    }

    public function deleteInboundItem(int $inboundItemId): void
    {
        $inboundItem = InboundItem::find($inboundItemId);
        
        if ($inboundItem) {
            $item = $inboundItem->item;
            
            // Cek transaksi terakhir
            $lastInbound = InboundItem::where('item_id', $item->item_id)
                ->join('inbound_records', 'inbound_items.inbound_id', '=', 'inbound_records.inbound_id')
                ->orderBy('inbound_records.receive_date', 'desc')
                ->first();

            $lastOutbound = OutboundItem::where('item_id', $item->item_id)
                ->join('outbound_records', 'outbound_items.outbound_id', '=', 'outbound_records.outbound_id')
                ->orderBy('outbound_records.delivery_date', 'desc')
                ->first();

            // Validasi transaksi terakhir
            if ($lastInbound->inbound_item_id !== $inboundItem->inbound_item_id) {
                Notification::make()
                    ->title('Item tidak dapat dihapus')
                    ->body('Item ini memiliki transaksi inbound yang lebih baru.')
                    ->danger()
                    ->persistent()
                    ->send();
                return;
            }

            // Validasi outbound yang lebih baru
            if ($lastOutbound && $lastOutbound->outboundRecord->delivery_date > $inboundItem->inboundRecord->receive_date) {
                Notification::make()
                    ->title('Item tidak dapat dihapus')
                    ->body('Item ini sudah memiliki transaksi outbound yang lebih baru.')
                    ->danger()
                    ->persistent()
                    ->send();
                return;
            }

            // Cari outbound sebelumnya
            $previousOutbound = OutboundItem::where('item_id', $item->item_id)
                ->join('outbound_records', 'outbound_items.outbound_id', '=', 'outbound_records.outbound_id')
                ->where('outbound_records.delivery_date', '<', $inboundItem->inboundRecord->receive_date)
                ->orderBy('outbound_records.delivery_date', 'desc')
                ->first();

            if ($previousOutbound) {
                // Kembalikan ke status outbound sebelumnya
                $newStatus = match($previousOutbound->purpose->name) {
                    'Sewa' => Item::STATUS_MASA_SEWA,
                    'Non Sewa' => Item::STATUS_NON_SEWA,
                    'Peminjaman' => Item::STATUS_DIPINJAM,
                    default => Item::STATUS_UNKNOWN
                };
                $item->update(['status' => $newStatus]);
            } else {
                // Jika tidak ada transaksi sebelumnya
                $item->update(['status' => Item::STATUS_UNKNOWN]);
            }

            // Delete inbound item
            $inboundItem->delete();

            Notification::make()
                ->title('Item berhasil dihapus')
                ->success()
                ->send();

            $this->redirect(EditInboundRecord::getUrl(['record' => $this->record]));
        }
    }

    public function deleteBatchItem(int $historyId): void
    {
        $history = \App\Models\BatchItemHistory::find($historyId);
        
        if ($history) {
            // Reverse the quantity change
            $batchItem = $history->batchItem;
            if ($batchItem) {
                if ($history->type === 'inbound') {
                    $batchItem->quantity -= $history->quantity;
                } else {
                    $batchItem->quantity += $history->quantity;
                }
                $batchItem->save();
            }

            // Delete the history record
            $history->delete();

            Notification::make()
                ->title('Batch item berhasil dihapus')
                ->success()
                ->send();

            $this->redirect(EditInboundRecord::getUrl(['record' => $this->record]));
        }
    }

    protected function afterSave(): void
    {
        Notification::make()
            ->success()
            ->title('Berhasil disimpan')
            ->send();

        $this->redirect(static::getResource()::getUrl('view', ['record' => $this->record]));
    }
}
