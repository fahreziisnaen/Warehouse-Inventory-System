<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OutboundRecordResource\Pages;
use App\Models\OutboundRecord;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Filters\Filter;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\RepeatableEntry;
use App\Models\BatchItem;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Get;
use Filament\Forms\Set;

class OutboundRecordResource extends Resource
{
    protected static ?string $model = OutboundRecord::class;

    protected static ?string $navigationGroup = 'Transaksi';
    protected static ?string $navigationIcon = 'heroicon-o-arrow-up-tray';
    protected static ?int $navigationSort = 3;
    protected static ?string $modelLabel = 'Barang Keluar';
    protected static ?string $pluralModelLabel = 'Barang Keluar';
    protected static ?string $createButtonLabel = 'Buat Barang Keluar';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Dasar')
                    ->schema([
                        Forms\Components\TextInput::make('lkb_number')
                            ->label('Nomor LKB')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->disabled(fn ($context) => $context === 'view'),
                        Forms\Components\TextInput::make('delivery_note_number')
                            ->label('Nomor Surat Jalan')
                            ->required()
                            ->unique(ignoreRecord: true),
                        Forms\Components\DatePicker::make('delivery_date')
                            ->label('Delivery Date')
                            ->required()
                            ->disabled(fn ($context) => $context === 'view'),
                        Forms\Components\Select::make('vendor_id')
                            ->options(function () {
                                return \App\Models\Vendor::whereHas('vendorType', fn($q) => 
                                        $q->where('type_name', 'Customer')
                                )->pluck('vendor_name', 'vendor_id');
                            })
                            ->label('Customer')
                            ->required()
                            ->preload()
                            ->searchable()
                            ->disabled(fn ($context) => $context === 'view'),
                        Forms\Components\Select::make('project_id')
                            ->options(fn () => \App\Models\Project::pluck('project_id', 'project_id'))
                            ->label('Project ID')
                            ->required()
                            ->preload()
                            ->searchable()
                            ->disabled(fn ($context) => $context === 'view'),
                        Forms\Components\Select::make('purpose_id')
                            ->options(fn () => \App\Models\Purpose::pluck('name', 'purpose_id'))
                            ->label('Tujuan')
                            ->required()
                            ->preload()
                            ->searchable()
                            ->disabled(fn ($context) => $context === 'view'),
                    ])
                    ->columns(2),
                Forms\Components\Section::make('Items')
                    ->schema([
                        Forms\Components\Repeater::make('outboundItems')
                            ->schema([
                                Forms\Components\Select::make('brand_id')
                                    ->label('Brand')
                                    ->options(fn () => \App\Models\Brand::pluck('brand_name', 'brand_id'))
                                    ->reactive()
                                    ->afterStateUpdated(function (Set $set) {
                                        $set('part_number_id', null);
                                        $set('bulk_serial_numbers', null);
                                        $set('validation_error', null);
                                    }),

                                Forms\Components\Select::make('part_number_id')
                                    ->label('Part Number')
                                    ->options(function (Get $get) {
                                        $brandId = $get('brand_id');
                                        if (!$brandId) return [];
                                        return \App\Models\PartNumber::where('brand_id', $brandId)
                                            ->pluck('part_number', 'part_number_id');
                                    })
                                    ->required(fn (Get $get): bool => filled($get('brand_id')))
                                    ->disabled(fn (Get $get): bool => !filled($get('brand_id')))
                                    ->reactive(),

                                Forms\Components\Textarea::make('bulk_serial_numbers')
                                    ->label('Serial Numbers')
                                    ->required(fn (Get $get): bool => filled($get('brand_id')))
                                    ->disabled(fn (Get $get): bool => !filled($get('part_number_id')))
                                    ->helperText('Satu serial number per baris')
                                    ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                        if (!$state || !$get('brand_id')) return;
                                        
                                        $serialNumbers = array_filter(
                                            explode("\n", str_replace("\r", "", $state))
                                        );
                                        $serialNumbers = array_map('trim', $serialNumbers);
                                        
                                        foreach ($serialNumbers as $serialNumber) {
                                            $item = \App\Models\Item::where('serial_number', $serialNumber)
                                                ->whereIn('status', ['diterima', 'unknown'])
                                                ->first();
                                            
                                            if (!$item) {
                                                $set('validation_error', "Serial Number {$serialNumber} tidak valid atau tidak tersedia");
                                                return;
                                            }
                                        }
                                        $set('validation_error', null);
                                    }),

                                TextInput::make('validation_error')
                                    ->label('Status')
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->visible(fn ($get) => !empty($get('validation_error')))
                                    ->extraAttributes(['class' => 'text-red-500']),
                            ])
                            ->columns(3)
                            ->defaultItems(0)
                            ->addActionLabel('Tambah Item')
                            ->reorderableWithButtons()
                            ->collapsible()
                            ->minItems(0)
                            ->live()
                    ]),
                Forms\Components\Section::make('Batch Items')
                    ->schema([
                        Forms\Components\Repeater::make('batchItems')
                            ->schema([
                                Forms\Components\Select::make('brand_id')
                                    ->label('Brand')
                                    ->options(fn () => \App\Models\Brand::pluck('brand_name', 'brand_id'))
                                    ->reactive()
                                    ->afterStateUpdated(function (Set $set) {
                                        $set('part_number_id', null);
                                        $set('batch_quantity', null);
                                        $set('available_quantity', null);
                                    }),

                                Forms\Components\Select::make('part_number_id')
                                    ->label('Part Number')
                                    ->options(function (Get $get) {
                                        $brandId = $get('brand_id');
                                        if (!$brandId) return [];
                                        return \App\Models\PartNumber::where('brand_id', $brandId)
                                            ->pluck('part_number', 'part_number_id');
                                    })
                                    ->searchable()
                                    ->preload()
                                    ->live()
                                    ->required(fn (Get $get): bool => filled($get('brand_id')))
                                    ->disabled(fn (Get $get): bool => !filled($get('brand_id')))
                                    ->afterStateUpdated(function (Set $set, $state) {
                                        if (!$state) {
                                            $set('batch_quantity', null);
                                            $set('available_quantity', null);
                                            return;
                                        }
                                        
                                        $batchItem = BatchItem::where('part_number_id', $state)->first();
                                        $set('available_quantity', $batchItem ? $batchItem->quantity : 0);
                                    }),

                                Forms\Components\TextInput::make('batch_quantity')
                                    ->label('Quantity')
                                    ->numeric()
                                    ->minValue(1)
                                    ->visible(fn ($get) => $get('part_number_id'))
                                    ->live()
                                    ->required(fn ($get) => filled($get('part_number_id')))
                                    ->disabled(fn (Get $get): bool => !filled($get('part_number_id')))
                                    ->rules([
                                        'required',
                                        'numeric',
                                        'min:1',
                                        function (Get $get) {
                                            return function (string $attribute, $value, \Closure $fail) use ($get) {
                                                $availableQty = $get('available_quantity');
                                                if ($value > $availableQty) {
                                                    $fail("Quantity tidak boleh melebihi stock yang tersedia ({$availableQty})");
                                                }
                                            };
                                        },
                                    ])
                                    ->validationMessages([
                                        'min' => 'Jumlah minimal 1',
                                        'required' => 'Jumlah harus diisi',
                                        'numeric' => 'Jumlah harus berupa angka'
                                    ]),

                                Forms\Components\TextInput::make('available_quantity')
                                    ->label('Available Stock')
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->visible(fn ($get) => $get('part_number_id')),
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

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('lkb_number')
                    ->label('Nomor LKB')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('delivery_note_number')
                    ->label('Nomor Surat Jalan')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('delivery_date')
                    ->label('Delivery Date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('vendor.vendor_name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('project.project_id')
                    ->label('Project ID')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('outboundItems.item.serial_number')
                    ->label('Serial Numbers')
                    ->listWithLineBreaks()
                    ->limitList(3)
                    ->expandableLimitedList()
                    ->searchable(),
                Tables\Columns\TextColumn::make('purpose.name')
                    ->label('Tujuan')
                    ->sortable()
                    ->searchable(),
            ])
            ->recordUrl(fn($record) => static::getUrl('view', ['record' => $record]))
            ->filters([
                Filter::make('delivery_date')
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
                                fn (Builder $query, $date): Builder => $query->whereDate('delivery_date', '>=', $date),
                            )
                            ->when(
                                $data['until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('delivery_date', '<=', $date),
                            );
                    }),
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
            'index' => Pages\ListOutboundRecords::route('/'),
            'create' => Pages\CreateOutboundRecord::route('/create'),
            'view' => Pages\ViewOutboundRecord::route('/{record}'),
            'edit' => Pages\EditOutboundRecord::route('/{record}/edit'),
        ];
    }
}
