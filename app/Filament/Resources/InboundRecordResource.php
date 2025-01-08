<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InboundRecordResource\Pages;
use App\Filament\Resources\InboundRecordResource\RelationManagers;
use App\Models\InboundRecord;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Support\Enums\FontWeight;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Textarea;
use Illuminate\Support\HtmlString;
use App\Models\Item;
use Filament\Forms\Components\Radio;
use Filament\Notifications\Notification;
use Filament\Forms\Components\Component;
use Illuminate\Database\Eloquent\Model;

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
                Forms\Components\Card::make()
                    ->schema([
                        TextInput::make('lpb_number')
                            ->label('No. LPB')
                            ->required()
                            ->unique(ignoreRecord: true),
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
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(function (Set $set) {
                                $set('brand_id', null);
                                $set('part_number_id', null);
                            }),
                    ])
                    ->columns(2),

                Forms\Components\Card::make()
                    ->schema([
                        Select::make('po_id')
                            ->relationship('purchaseOrder', 'po_number')
                            ->label('No. PO')
                            ->required()
                            ->searchable()
                            ->preload(),
                        Select::make('project_id')
                            ->relationship('project', 'project_name')
                            ->label('Project')
                            ->required()
                            ->searchable()
                            ->preload(),
                    ])
                    ->columns(2),

                // Section untuk Item dengan Serial Number
                Forms\Components\Section::make('Items dengan Serial Number')
                    ->schema([
                        Forms\Components\Repeater::make('inboundItems')
                            ->relationship()
                            ->schema([
                                Select::make('brand_id')
                                    ->label('Brand')
                                    ->options(fn () => \App\Models\Brand::pluck('brand_name', 'brand_id'))
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
                                    ->required()
                                    ->reactive()
                                    ->afterStateUpdated(function (Set $set) {
                                        $set('part_number_id', null);
                                        $set('multiple_item_ids', null);
                                        $set('bulk_serial_numbers', null);
                                    }),
                                
                                Select::make('part_number_id')
                                    ->label('Part Number')
                                    ->options(function (Get $get) {
                                        $brandId = $get('brand_id');
                                        if (!$brandId) return [];
                                        return \App\Models\PartNumber::where('brand_id', $brandId)
                                            ->pluck('part_number', 'part_number_id');
                                    })
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
                                    })
                                    ->required()
                                    ->reactive()
                                    ->afterStateUpdated(function (Set $set) {
                                        $set('multiple_item_ids', null);
                                        $set('bulk_serial_numbers', null);
                                    }),
                                
                                Radio::make('input_type')
                                    ->label('Tipe Input')
                                    ->options([
                                        'existing_multiple' => 'Pilih Serial Number yang ada',
                                        'bulk' => 'Input Multiple Serial Number Baru',
                                    ])
                                    ->default('existing_multiple')
                                    ->reactive(),

                                // Hanya untuk multiple select
                                Select::make('multiple_item_ids')
                                    ->label('Serial Numbers')
                                    ->multiple()
                                    ->searchable()
                                    ->preload()
                                    ->options(function (Get $get) {
                                        $partNumberId = $get('part_number_id');
                                        if (!$partNumberId) return [];
                                        return \App\Models\Item::where('part_number_id', $partNumberId)
                                            ->where('status', '!=', 'terjual')
                                            ->pluck('serial_number', 'item_id');
                                    })
                                    ->visible(fn (Get $get) => $get('input_type') === 'existing_multiple')
                                    ->required(fn (Get $get) => $get('input_type') === 'existing_multiple'),

                                // Untuk input bulk serial numbers baru
                                Textarea::make('bulk_serial_numbers')
                                    ->label('Serial Numbers')
                                    ->helperText('Masukkan Serial Number (satu per baris)')
                                    ->visible(fn (Get $get) => $get('input_type') === 'bulk')
                                    ->rules(['required_if:input_type,bulk']),

                                Hidden::make('quantity')
                                    ->default(1),
                            ])
                            ->columns(3),
                    ]),

                // Section untuk Batch Items
                Forms\Components\Section::make('Batch Items')
                    ->schema([
                        Select::make('brand_id')
                            ->label('Brand')
                            ->options(fn () => \App\Models\Brand::pluck('brand_name', 'brand_id'))
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
                            ->reactive()
                            ->afterStateUpdated(fn (Set $set) => $set('part_number_id', null)),
                        
                        Select::make('part_number_id')
                            ->label('Part Number')
                            ->options(function (Get $get) {
                                $brandId = $get('brand_id');
                                if (!$brandId) return [];
                                return \App\Models\PartNumber::where('brand_id', $brandId)
                                    ->pluck('part_number', 'part_number_id');
                            })
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
                            })
                            ->reactive(),
                        
                        Select::make('format_id')
                            ->label('Satuan')
                            ->options(fn () => \App\Models\UnitFormat::pluck('name', 'format_id'))
                            ->createOptionForm([
                                TextInput::make('name')
                                    ->label('Nama Satuan')
                                    ->required()
                                    ->unique('unit_formats', 'name')
                            ])
                            ->createOptionUsing(function (array $data) {
                                return \App\Models\UnitFormat::create([
                                    'name' => $data['name']
                                ])->format_id;
                            })
                            ->required()
                            ->visible(fn (Get $get) => $get('part_number_id')),
                        
                        TextInput::make('batch_quantity')
                            ->label('Quantity')
                            ->numeric()
                            ->required()
                            ->visible(fn (Get $get) => $get('part_number_id')),
                    ])
                    ->columns(4),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('lpb_number')
                    ->label('No. LPB')
                    ->searchable()
                    ->weight(FontWeight::Bold)
                    ->color('primary'),
                Tables\Columns\TextColumn::make('receive_date')
                    ->label('Tanggal Terima')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('purchaseOrder.po_number')
                    ->label('Purchase Order')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('project.project_id')
                    ->label('Project ID')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('inboundItems.item.serial_number')
                    ->label('Serial Numbers')
                    ->listWithLineBreaks()
                    ->limitList(3)
                    ->expandableLimitedList()
                    ->searchable(),
                Tables\Columns\TextColumn::make('location')
                    ->label('Lokasi')
                    ->badge()
                    ->color(fn ($state) => match($state) {
                        'Gudang Jakarta' => 'success',
                        'Gudang Surabaya' => 'warning',
                        default => 'gray'
                    }),
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

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Informasi Inbound')
                    ->schema([
                        TextEntry::make('lpb_number')
                            ->label('No. LPB')
                            ->weight(FontWeight::Bold)
                            ->color('primary'),
                        TextEntry::make('receive_date')
                            ->label('Tanggal Terima')
                            ->date(),
                        TextEntry::make('location')
                            ->label('Lokasi')
                            ->badge()
                            ->color(fn ($state) => match($state) {
                                'Gudang Jakarta' => 'success',
                                'Gudang Surabaya' => 'warning',
                                default => 'gray'
                            }),
                    ])
                    ->columns(2),
                Section::make('Items')
                    ->schema([
                        RepeatableEntry::make('inboundItems')
                            ->schema([
                                TextEntry::make('item.serial_number')
                                    ->label('Serial Number'),
                                TextEntry::make('item.status')
                                    ->label('Status')
                                    ->formatStateUsing(fn (string $state) => ucfirst($state)),
                                TextEntry::make('quantity')
                                    ->label('Quantity'),
                            ])
                            ->columns(3)
                    ]),
                Section::make('Batch Items')
                    ->schema([
                        TextEntry::make('part_number_id')
                            ->label('Part Number')
                            ->formatStateUsing(fn ($record) => $record->partNumber?->part_number ?? '-'),
                        TextEntry::make('format_id')
                            ->label('Satuan')
                            ->formatStateUsing(fn ($record) => $record->unitFormat?->name ?? '-'),
                        TextEntry::make('batch_quantity')
                            ->label('Quantity'),
                    ])
                    ->visible(fn ($record) => $record->part_number_id !== null)
                    ->columns(3),
            ]);
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (isset($data['inboundItems'])) {
            $newInboundItems = [];
            
            foreach ($data['inboundItems'] as $key => $itemData) {
                if ($itemData['input_type'] === 'existing_multiple') {
                    // Hapus item original dari array
                    unset($data['inboundItems'][$key]);
                    
                    // Buat inbound items baru untuk setiap item yang dipilih
                    foreach ($itemData['multiple_item_ids'] as $itemId) {
                        $newInboundItems[] = [
                            'part_number_id' => $itemData['part_number_id'],
                            'item_id' => $itemId,
                            'quantity' => 1
                        ];
                    }
                }
                elseif ($itemData['input_type'] === 'bulk') {
                    unset($data['inboundItems'][$key]);
                    
                    $serialNumbers = array_filter(
                        explode("\n", str_replace("\r", "", $itemData['bulk_serial_numbers']))
                    );
                    $serialNumbers = array_map('trim', $serialNumbers);
                    
                    // Cek duplikasi dan existing sebelum create
                    $existingSerials = Item::whereIn('serial_number', $serialNumbers)->pluck('serial_number')->toArray();
                    if (!empty($existingSerials)) {
                        Notification::make()
                            ->title('Error')
                            ->body("Beberapa Serial Number sudah ada di database: " . implode(', ', $existingSerials))
                            ->danger()
                            ->send();
                        continue;
                    }

                    foreach ($serialNumbers as $serialNumber) {
                        if (empty($serialNumber)) continue;
                        
                        $item = Item::create([
                            'part_number_id' => $itemData['part_number_id'],
                            'serial_number' => $serialNumber,
                            'status' => 'diterima'
                        ]);
                        
                        $newInboundItems[] = [
                            'part_number_id' => $itemData['part_number_id'],
                            'item_id' => $item->item_id,
                            'quantity' => 1
                        ];
                    }
                }
            }
            
            $data['inboundItems'] = array_merge($data['inboundItems'], $newInboundItems);
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
