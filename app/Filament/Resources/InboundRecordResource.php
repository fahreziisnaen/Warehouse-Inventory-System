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
                                    ->options(function () {
                                        // Hanya ambil brand yang memiliki Item dengan Serial Number
                                        return \App\Models\Brand::whereHas('partNumbers', function ($query) {
                                            $query->whereHas('items', function ($q) {
                                                $q->whereIn('status', ['baru', 'bekas', 'sewa_habis']);
                                            });
                                        })->pluck('brand_name', 'brand_id');
                                    })
                                    ->required()
                                    ->reactive()
                                    ->afterStateUpdated(fn (Set $set) => $set('part_number_id', null)),
                                
                                Select::make('part_number_id')
                                    ->label('Part Number')
                                    ->options(function (Get $get) {
                                        $brandId = $get('brand_id');
                                        if (!$brandId) return [];
                                        // Hanya ambil Part Number yang memiliki Item dengan Serial Number
                                        return \App\Models\PartNumber::where('brand_id', $brandId)
                                            ->whereHas('items', function ($query) {
                                                $query->whereIn('status', ['baru', 'bekas', 'sewa_habis']);
                                            })
                                            ->pluck('part_number', 'part_number_id');
                                    })
                                    ->required()
                                    ->reactive()
                                    ->afterStateUpdated(fn (Set $set) => $set('item_id', null)),
                                
                                Select::make('item_id')
                                    ->label('Serial Number')
                                    ->options(function (Get $get) {
                                        $partNumberId = $get('part_number_id');
                                        if (!$partNumberId) return [];
                                        return \App\Models\Item::where('part_number_id', $partNumberId)
                                            ->whereIn('status', ['baru', 'bekas', 'sewa_habis'])
                                            ->pluck('serial_number', 'item_id');
                                    })
                                    ->required()
                                    ->searchable(),
                                
                                TextInput::make('quantity')
                                    ->label('Quantity')
                                    ->default(1)
                                    ->disabled(),
                            ])
                            ->columns(4),
                    ]),

                // Section untuk Batch Items
                Forms\Components\Section::make('Batch Items')
                    ->schema([
                        Select::make('brand_id')
                            ->label('Brand')
                            ->options(function (Get $get) {
                                $location = $get('location');
                                if (!$location) return [];

                                // Filter brand berdasarkan lokasi
                                return \App\Models\Brand::whereHas('partNumbers', function ($query) use ($location) {
                                    $query->whereHas('batchItems', function ($batchQuery) use ($location) {
                                        $batchQuery->whereHas('histories', function ($historyQuery) use ($location) {
                                            $historyQuery->where('type', 'inbound')
                                                ->whereHasMorph('recordable', 
                                                    [\App\Models\InboundRecord::class],
                                                    function ($recordQuery) use ($location) {
                                                        $recordQuery->where('location', $location);
                                                    }
                                                );
                                        });
                                    });
                                })->pluck('brand_name', 'brand_id');
                            })
                            ->reactive()
                            ->afterStateUpdated(fn (Set $set) => $set('part_number_id', null))
                            ->searchable(),
                        
                        Select::make('part_number_id')
                            ->label('Part Number')
                            ->options(function (Get $get) {
                                $brandId = $get('brand_id');
                                $location = $get('location');
                                if (!$brandId || !$location) return [];

                                // Filter part number berdasarkan brand dan lokasi
                                return \App\Models\PartNumber::where('brand_id', $brandId)
                                    ->whereHas('batchItems', function ($batchQuery) use ($location) {
                                        $batchQuery->whereHas('histories', function ($historyQuery) use ($location) {
                                            $historyQuery->where('type', 'inbound')
                                                ->whereHasMorph('recordable', 
                                                    [\App\Models\InboundRecord::class],
                                                    function ($recordQuery) use ($location) {
                                                        $recordQuery->where('location', $location);
                                                    }
                                                );
                                        });
                                    })
                                    ->pluck('part_number', 'part_number_id');
                            })
                            ->reactive()
                            ->searchable(),
                        
                        TextInput::make('batch_quantity')
                            ->label('Quantity')
                            ->numeric()
                            ->visible(fn (Get $get) => $get('part_number_id')),
                    ])
                    ->columns(3),
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
                        TextEntry::make('batch_quantity')
                            ->label('Quantity'),
                    ])
                    ->visible(fn ($record) => $record->part_number_id !== null)
                    ->columns(2),
            ]);
    }
}
