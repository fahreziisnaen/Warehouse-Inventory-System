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
                        Forms\Components\TextInput::make('lpb_number')
                            ->label('Nomor LPB')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->disabled(fn ($context) => $context === 'view'),
                        Forms\Components\DatePicker::make('receive_date')
                            ->label('Tanggal Penerimaan Barang')
                            ->required()
                            ->disabled(fn ($context) => $context === 'view'),
                        Forms\Components\Select::make('po_id')
                            ->relationship('purchaseOrder', 'po_number')
                            ->label('Purchase Order')
                            ->required()
                            ->preload()
                            ->searchable(),
                        Forms\Components\Select::make('project_id')
                            ->relationship('project', 'project_id')
                            ->label('Project ID')
                            ->required()
                            ->preload()
                            ->searchable()
                            ->disabled(fn ($context) => $context === 'view'),
                        Select::make('location')
                            ->label('Lokasi')
                            ->options([
                                'Gudang Jakarta' => 'Gudang Jakarta',
                                'Gudang Surabaya' => 'Gudang Surabaya',
                            ])
                            ->required(),
                    ])
                    ->columns(2),
                Forms\Components\Section::make('Items')
                    ->schema([
                        Forms\Components\Repeater::make('inboundItems')
                            ->relationship()
                            ->schema([
                                Forms\Components\Select::make('item_id')
                                    ->relationship(
                                        'item', 
                                        'serial_number',
                                        fn (Builder $query, $record) => $query
                                            ->whereIn('status', ['baru', 'bekas', 'sewa_habis'])
                                            ->when(
                                                $record?->inboundItems,
                                                fn (Builder $query) => $query->orWhereIn('item_id', $record->inboundItems->pluck('item_id'))
                                            )
                                    )
                                    ->getOptionLabelFromRecordUsing(fn ($record) => $record->serial_number)
                                    ->formatStateUsing(function ($state, $record) {
                                        if ($state) {
                                            $item = \App\Models\Item::find($state);
                                            return $item ? $item->serial_number : $state;
                                        }
                                        return $state;
                                    })
                                    ->label('Serial Number')
                                    ->required()
                                    ->preload()
                                    ->searchable(['serial_number'])
                                    ->live()
                                    ->afterStateUpdated(function ($state, Forms\Set $set) {
                                        if ($state) {
                                            $item = \App\Models\Item::find($state);
                                            $set('current_status', $item?->status);
                                        }
                                    })
                                    ->disabled(fn ($context) => $context === 'view'),
                                Forms\Components\TextInput::make('current_status')
                                    ->label('Status')
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->formatStateUsing(function ($state, $record) {
                                        if ($record && $record->item) {
                                            return ucfirst($record->item->status);
                                        }
                                        return ucfirst($state);
                                    })
                                    ->extraAttributes(['class' => 'text-center']),
                                Forms\Components\TextInput::make('quantity')
                                    ->label('Quantity')
                                    ->required()
                                    ->numeric()
                                    ->default(1)
                                    ->disabled()
                                    ->minValue(1)
                                    ->maxValue(1),
                            ])
                            ->columns(3)
                            ->defaultItems(1)
                            ->disabled(fn ($context) => $context === 'view'),
                    ]),
                Forms\Components\Section::make('Batch Items')
                    ->schema([
                        Forms\Components\Select::make('part_number_id')
                            ->relationship('partNumber', 'part_number')
                            ->label('Part Number (Batch)')
                            ->searchable()
                            ->preload()
                            ->live(),
                        Forms\Components\TextInput::make('batch_quantity')
                            ->label('Quantity')
                            ->numeric()
                            ->minValue(1)
                            ->visible(fn ($get) => $get('part_number_id')),
                    ])
                    ->columns(2),
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
