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
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Form;
use Filament\Forms;
use Filament\Forms\Get;

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
                            } elseif (!in_array($existingItem->status, ['disewa', 'dipinjam', 'terjual', 'unknown'])) {
                                $invalidSerials[] = "Serial Number <strong class='text-primary'>{$serialNumber}</strong> memiliki status yang tidak valid";
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

        // Cek Batch Items
        $hasBatchItems = !empty($this->data['batchItems']) && collect($this->data['batchItems'])->some(function ($item) {
            return !empty($item['brand_id']) && 
                   !empty($item['part_number_id']) && 
                   !empty($item['batch_quantity']) &&
                   !empty($item['format_id']);
        });
        
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
        if (!empty($formData['batchItems'])) {
            foreach ($formData['batchItems'] as $batchItem) {
                if (empty($batchItem['brand_id']) || 
                    empty($batchItem['part_number_id']) || 
                    empty($batchItem['batch_quantity']) ||
                    empty($batchItem['format_id'])) {
                    continue;
                }

                try {
                    $existingBatchItem = BatchItem::firstOrCreate(
                        ['part_number_id' => $batchItem['part_number_id']],
                        ['quantity' => 0, 'format_id' => $batchItem['format_id']]
                    );

                    BatchItem::updateQuantity(
                        $batchItem['part_number_id'],
                        $batchItem['batch_quantity'],
                        'inbound',
                        $record
                    );
                } catch (\Exception $e) {
                    Notification::make()
                        ->title('Error')
                        ->body("Gagal memproses Batch Item: " . $e->getMessage())
                        ->danger()
                        ->send();
                }
            }
        }
    }

    public function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Informasi Dasar')
                ->schema([
                    TextInput::make('lpb_number')
                        ->label('No. LPB')
                        ->required()
                        ->unique(),
                    DatePicker::make('receive_date')
                        ->label('Tanggal Terima')
                        ->required(),
                    Select::make('location')
                        ->label('Lokasi')
                        ->options([
                            'Gudang Jakarta' => 'Gudang Jakarta',
                            'Gudang Surabaya' => 'Gudang Surabaya',
                        ])
                        ->required(),
                ])
                ->columns(2),

            Section::make('Informasi Referensi')
                ->schema([
                    Select::make('po_id')
                        ->relationship('purchaseOrder', 'po_number')
                        ->label('No. PO')
                        ->searchable()
                        ->preload(),
                    Select::make('project_id')
                        ->relationship('project', 'project_id')
                        ->label('Project ID')
                        ->required()
                        ->searchable()
                        ->preload(),
                ])
                ->columns(2),

            // Section untuk Item dengan Serial Number
            Section::make('Items dengan Serial Number')
                ->schema([
                    Forms\Components\Repeater::make('inboundItems')
                        ->schema([
                            Select::make('brand_id')
                                ->label('Brand')
                                ->options(fn () => \App\Models\Brand::pluck('brand_name', 'brand_id'))
                                ->reactive()
                                ->createOptionForm([
                                    TextInput::make('brand_name')
                                        ->required()
                                        ->unique('brands', 'brand_name')
                                ])
                                ->createOptionUsing(function (array $data) {
                                    return \App\Models\Brand::create([
                                        'brand_name' => $data['brand_name']
                                    ])->brand_id;
                                })
                                ->afterStateUpdated(fn (callable $set) => $set('part_number_id', null)),

                            Select::make('part_number_id')
                                ->label('Part Number')
                                ->options(function (callable $get) {
                                    $brandId = $get('brand_id');
                                    if (!$brandId) return [];
                                    return \App\Models\PartNumber::where('brand_id', $brandId)
                                        ->pluck('part_number', 'part_number_id');
                                })
                                ->required(fn (Get $get): bool => filled($get('brand_id')))
                                ->reactive()
                                ->createOptionForm([
                                    Select::make('brand_id')
                                        ->label('Brand')
                                        ->options(fn () => \App\Models\Brand::pluck('brand_name', 'brand_id'))
                                        ->required(),
                                    TextInput::make('part_number')
                                        ->required()
                                        ->unique('part_numbers', 'part_number'),
                                    Textarea::make('description')
                                        ->columnSpanFull(),
                                ])
                                ->createOptionUsing(function (array $data) {
                                    return \App\Models\PartNumber::create([
                                        'brand_id' => $data['brand_id'],
                                        'part_number' => $data['part_number'],
                                        'description' => $data['description'] ?? null,
                                    ])->part_number_id;
                                }),

                            Textarea::make('bulk_serial_numbers')
                                ->label('Serial Numbers')
                                ->required(fn (Get $get): bool => filled($get('brand_id')))
                                ->helperText('Satu serial number per baris'),
                        ])
                        ->columns(3)
                        ->defaultItems(0)
                        ->addActionLabel('Tambah Item')
                        ->reorderableWithButtons()
                        ->collapsible()
                        ->minItems(0)
                        ->live(),
                ]),

            // Section untuk Batch Items
            Section::make('Batch Items')
                ->schema([
                    Forms\Components\Repeater::make('batchItems')
                        ->schema([
                            Select::make('brand_id')
                                ->label('Brand')
                                ->options(fn () => \App\Models\Brand::pluck('brand_name', 'brand_id'))
                                ->reactive()
                                ->createOptionForm([
                                    TextInput::make('brand_name')
                                        ->required()
                                        ->unique('brands', 'brand_name')
                                ])
                                ->createOptionUsing(function (array $data) {
                                    return \App\Models\Brand::create([
                                        'brand_name' => $data['brand_name']
                                    ])->brand_id;
                                })
                                ->afterStateUpdated(fn (callable $set) => $set('part_number_id', null)),

                            Select::make('part_number_id')
                                ->label('Part Number')
                                ->options(function (callable $get) {
                                    $brandId = $get('brand_id');
                                    if (!$brandId) return [];
                                    return \App\Models\PartNumber::where('brand_id', $brandId)
                                        ->pluck('part_number', 'part_number_id');
                                })
                                ->required(fn (Get $get): bool => filled($get('brand_id')))
                                ->reactive(),

                            TextInput::make('batch_quantity')
                                ->label('Jumlah')
                                ->numeric()
                                ->minValue(1)
                                ->validationMessages([
                                    'min' => 'Jumlah minimal 1',
                                    'required' => 'Jumlah harus diisi',
                                    'numeric' => 'Jumlah harus berupa angka'
                                ]),

                            Select::make('format_id')
                                ->label('Satuan')
                                ->options(fn () => \App\Models\UnitFormat::pluck('name', 'format_id'))
                                ->required(fn (Get $get): bool => filled($get('brand_id')))
                                ->createOptionForm([
                                    TextInput::make('name')
                                        ->required()
                                        ->unique('unit_formats', 'name')
                                ])
                                ->createOptionUsing(function (array $data) {
                                    return \App\Models\UnitFormat::create([
                                        'name' => $data['name']
                                    ])->format_id;
                                }),
                        ])
                        ->columns(4)
                        ->defaultItems(0)
                        ->addActionLabel('Tambah Batch Item')
                        ->reorderableWithButtons()
                        ->collapsible()
                        ->minItems(0)
                        ->live(),
                ]),
        ]);
    }
}
