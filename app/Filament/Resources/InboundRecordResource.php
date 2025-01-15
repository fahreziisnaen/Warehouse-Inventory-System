<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InboundRecordResource\Pages;
use App\Models\InboundRecord;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Get;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use App\Models\Item;
use Illuminate\Database\Eloquent\Model;
use Filament\Forms\Components\Radio;
use Filament\Forms\Set;

class InboundRecordResource extends Resource
{
    protected static ?string $model = InboundRecord::class;

    protected static ?string $navigationGroup = 'Transaksi';
    protected static ?string $navigationIcon = 'heroicon-o-arrow-down-tray';
    protected static ?int $navigationSort = 2;
    protected static ?string $modelLabel = 'Barang Masuk';
    protected static ?string $pluralModelLabel = 'Barang Masuk';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Dasar')
                    ->schema([
                        Forms\Components\Radio::make('lpb_type')
                            ->label('Tipe Nomor LPB')
                            ->options([
                                'new' => 'No LPB Baru',
                                'custom' => 'No LPB Lama',
                            ])
                            ->default('new')
                            ->inline()
                            ->live()
                            ->afterStateUpdated(function (Set $set, Get $get) {
                                if ($get('lpb_type') === 'new') {
                                    $set('lpb_number', \App\Models\InboundRecord::generateLpbNumber());
                                } else {
                                    $set('lpb_number', '');
                                }
                            }),

                        Forms\Components\TextInput::make('lpb_number')
                            ->label('Nomor LPB')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->disabled(fn (Get $get): bool => $get('lpb_type') === 'new')
                            ->dehydrated()
                            ->default(fn () => \App\Models\InboundRecord::generateLpbNumber())
                            ->visible(fn (Get $get): bool => $get('lpb_type') !== null),

                        DatePicker::make('receive_date')
                            ->label('Tanggal Terima')
                            ->required()
                            ->default(now()),
                        Select::make('location')
                            ->label('Lokasi')
                            ->options([
                                'Gudang Jakarta' => 'Gudang Jakarta',
                                'Gudang Surabaya' => 'Gudang Surabaya',
                            ])
                            ->required(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Informasi Referensi')
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
                Forms\Components\Section::make('Items dengan Serial Number')
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
                                    ->live()
                            ->hiddenOn('edit'),
                    ]),

                // Section untuk Batch Items
                Forms\Components\Section::make('Batch Items')
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
                            ->live()
                            ->hiddenOn('edit'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('lpb_number')
                    ->label('No. LPB')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('receive_date')
                    ->label('Tanggal Terima')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('location')
                    ->label('Lokasi')
                    ->badge(),
                Tables\Columns\TextColumn::make('project.project_id')
                    ->label('Project ID')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('project.project_name')
                    ->label('Nama Project')
                    ->searchable(),
                Tables\Columns\TextColumn::make('purchaseOrder.po_number')
                    ->label('PO Number')
                    ->searchable()
                    ->sortable(),
            ])
            ->recordUrl(fn($record) => static::getUrl('view', ['record' => $record]))
            ->filters([
                Filter::make('receive_date')
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->label('Dari Tanggal'),
                        Forms\Components\DatePicker::make('until')
                            ->label('Sampai Tanggal'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('receive_date', '>=', $date),
                            )
                            ->when(
                                $data['until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('receive_date', '<=', $date),
                            );
                    }),
                SelectFilter::make('location')
                    ->label('Lokasi')
                    ->options([
                        'Gudang Jakarta' => 'Gudang Jakarta',
                        'Gudang Surabaya' => 'Gudang Surabaya',
                    ])
                    ->searchable(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInboundRecords::route('/'),
            'create' => Pages\CreateInboundRecord::route('/create'),
            'view' => Pages\ViewInboundRecord::route('/{record}'),
            'edit' => Pages\EditInboundRecord::route('/{record}/edit'),
        ];
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (isset($data['inboundItems'])) {
            $newInboundItems = [];
            
            foreach ($data['inboundItems'] as $key => $itemData) {
                // Skip jika data tidak lengkap
                if (empty($itemData['brand_id']) || 
                    empty($itemData['part_number_id']) || 
                    empty($itemData['bulk_serial_numbers'])) {
                    continue;  // Skip iterasi ini dan lanjut ke data berikutnya
                }

                // Proses serial numbers
                $serialNumbers = array_filter(
                    explode("\n", str_replace("\r", "", $itemData['bulk_serial_numbers']))
                );
                $serialNumbers = array_map('trim', $serialNumbers);
                
                // Skip jika tidak ada serial number valid
                if (empty($serialNumbers)) {
                    continue;
                }

                foreach ($serialNumbers as $serialNumber) {
                    if (empty($serialNumber)) continue;

                    // Cek apakah serial number sudah ada
                    $existingItem = Item::where('serial_number', $serialNumber)->first();

                    if ($existingItem) {
                        // Jika sudah ada, update status dan gunakan item yang ada
                        $existingItem->update(['status' => 'diterima']);
                        $newInboundItems[] = [
                            'item_id' => $existingItem->item_id,
                            'quantity' => 1
                        ];
                    } else {
                        // Jika belum ada, buat item baru
                        $newItem = Item::create([
                            'part_number_id' => $itemData['part_number_id'],
                            'serial_number' => $serialNumber,
                            'status' => 'diterima'
                        ]);
                        
                        if ($newItem && $newItem->item_id) {
                            $newInboundItems[] = [
                                'item_id' => $newItem->item_id,
                                'quantity' => 1
                            ];
                        }
                    }
                }
            }
            
            // Hanya set data jika ada item valid
            if (!empty($newInboundItems)) {
                $data['inboundItems'] = $newInboundItems;
            } else {
                // Hapus inboundItems jika tidak ada data valid
                unset($data['inboundItems']);
            }
        }

        return $data;
    }

    public static function getRecordWithRelations($record): Model
    {
        return static::getModel()::with([
            'project.vendor',
            'purchaseOrder',
            'inboundItems.item.partNumber.brand'
        ])->find($record->getKey());
    }
}
