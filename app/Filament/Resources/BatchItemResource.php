<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BatchItemResource\Pages;
use App\Models\BatchItem;
use App\Models\UnitFormat;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Support\Enums\FontWeight;
use App\Models\InboundRecord;

class BatchItemResource extends Resource
{
    protected static ?string $model = BatchItem::class;

    protected static ?string $navigationGroup = 'Master Data';
    protected static ?string $navigationIcon = 'heroicon-o-squares-plus';
    protected static ?int $navigationSort = 4;
    protected static ?string $modelLabel = 'Item Batch';
    protected static ?string $pluralModelLabel = 'Item Batch';
    protected static ?string $navigationLabel = 'Item Batch';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('part_number_id')
                    ->relationship('partNumber', 'part_number')
                    ->required()
                    ->searchable()
                    ->disabled(),
                Forms\Components\Select::make('format_id')
                    ->label('Satuan')
                    ->options(UnitFormat::pluck('name', 'format_id'))
                    ->required(),
                Forms\Components\TextInput::make('quantity')
                    ->required()
                    ->numeric()
                    ->default(0)
                    ->disabled(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('partNumber.brand.brand_name')
                    ->label('Brand')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('partNumber.part_number')
                    ->label('Part Number')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('partNumber.description')
                    ->label('Deskripsi')
                    ->limit(50)
                    ->searchable(),
                Tables\Columns\TextColumn::make('quantity')
                    ->label('Stock')
                    ->sortable(),
                Tables\Columns\TextColumn::make('format.name')
                    ->label('Satuan')
                    ->sortable(),
                Tables\Columns\TextColumn::make('histories')
                    ->label('Lokasi')
                    ->badge()
                    ->color(fn ($state) => match($state) {
                        'Gudang Jakarta' => 'success',
                        'Gudang Surabaya' => 'warning',
                        default => 'gray'
                    })
                    ->getStateUsing(function ($record) {
                        $latestInbound = $record->histories()
                            ->where('type', 'inbound')
                            ->with('recordable')
                            ->whereHasMorph('recordable', [InboundRecord::class], function ($query) {
                                $query->orderBy('receive_date', 'desc');
                            })
                            ->first();
                        
                        return $latestInbound?->recordable?->location;
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('brand')
                    ->relationship('partNumber.brand', 'brand_name'),
                Tables\Filters\SelectFilter::make('format')
                    ->relationship('format', 'name')
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                //
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBatchItems::route('/'),
            'edit' => Pages\EditBatchItem::route('/{record}/edit'),
            'view' => Pages\ViewBatchItem::route('/{record}'),
        ];
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Informasi Item Batch')
                    ->schema([
                        TextEntry::make('partNumber.brand.brand_name')
                            ->label('Brand'),
                        TextEntry::make('partNumber.part_number')
                            ->label('Part Number'),
                        TextEntry::make('partNumber.description')
                            ->label('Deskripsi'),
                        TextEntry::make('quantity')
                            ->label('Current Stock'),
                        TextEntry::make('format.name')
                            ->label('Satuan'),
                        TextEntry::make('histories')
                            ->label('Lokasi')
                            ->getStateUsing(function ($record) {
                                $latestInbound = $record->histories()
                                    ->where('type', 'inbound')
                                    ->with('recordable')
                                    ->whereHasMorph('recordable', [InboundRecord::class], function ($query) {
                                        $query->orderBy('receive_date', 'desc');
                                    })
                                    ->first();
                                
                                return $latestInbound?->recordable?->location;
                            })
                            ->badge()
                            ->color(fn ($state) => match($state) {
                                'Gudang Jakarta' => 'success',
                                'Gudang Surabaya' => 'warning',
                                default => 'gray'
                            }),
                    ])
                    ->columns(2),

                Section::make('History')
                    ->schema([
                        RepeatableEntry::make('histories')
                            ->schema([
                                TextEntry::make('transaction_date')
                                    ->label('Tanggal')
                                    ->date(),
                                TextEntry::make('type')
                                    ->label('Barang')
                                    ->formatStateUsing(fn (string $state): string => match ($state) {
                                        'inbound' => 'Masuk',
                                        'outbound' => 'Keluar',
                                    })
                                    ->badge()
                                    ->color(fn (string $state): string => match ($state) {
                                        'inbound' => 'success',
                                        'outbound' => 'danger',
                                    }),
                                TextEntry::make('quantity')
                                    ->label('Jumlah'),
                                TextEntry::make('recordable.lpb_number')
                                    ->label('No. Referensi')
                                    ->getStateUsing(function ($record) {
                                        return $record->recordable_type === 'App\Models\InboundRecord'
                                            ? $record->recordable->lpb_number
                                            : $record->recordable->lkb_number;
                                    })
                                    ->url(fn ($record) => $record->transaction_url)
                                    ->openUrlInNewTab()
                                    ->weight(FontWeight::Bold)
                                    ->color('primary'),
                            ])
                            ->columns(4)
                    ]),
            ]);
    }
} 