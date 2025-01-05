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

class OutboundRecordResource extends Resource
{
    protected static ?string $model = OutboundRecord::class;

    protected static ?string $navigationGroup = 'Transaksi';
    protected static ?string $navigationIcon = 'heroicon-o-arrow-up-tray';
    protected static ?int $navigationSort = 3;
    protected static ?string $modelLabel = 'Barang Keluar';
    protected static ?string $pluralModelLabel = 'Barang Keluar';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Card::make()
                    ->schema([
                        Forms\Components\TextInput::make('lkb_number')
                            ->label('Nomor LKB')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->disabled(fn ($context) => $context === 'view'),
                        Forms\Components\DatePicker::make('delivery_date')
                            ->label('Delivery Date')
                            ->required()
                            ->disabled(fn ($context) => $context === 'view'),
                        Forms\Components\Select::make('vendor_id')
                            ->relationship(
                                'vendor', 
                                'vendor_name',
                                fn (Builder $query) => $query
                                    ->whereHas('vendorType', fn($q) => 
                                        $q->where('type_name', 'Customer')
                                    )
                            )
                            ->label('Customer')
                            ->required()
                            ->preload()
                            ->searchable()
                            ->disabled(fn ($context) => $context === 'view'),
                        Forms\Components\Select::make('project_id')
                            ->relationship('project', 'project_id')
                            ->label('Project ID')
                            ->required()
                            ->preload()
                            ->searchable()
                            ->disabled(fn ($context) => $context === 'view'),
                        Forms\Components\Select::make('purpose_id')
                            ->relationship('purpose', 'name')
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
                            ->relationship()
                            ->schema([
                                Forms\Components\Select::make('item_id')
                                    ->relationship(
                                        'item', 
                                        'serial_number',
                                        fn (Builder $query, $record) => $query
                                            ->when(
                                                !$record,  // Jika create baru
                                                fn ($query) => $query->where('status', 'diterima'),
                                                fn ($query) => $query  // Jika edit
                                                    ->where('status', 'diterima')
                                                    ->when(
                                                        $record?->outboundItems?->count(),
                                                        fn ($query) => $query->orWhereIn('item_id', $record->outboundItems->pluck('item_id'))
                                                    )
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
                                    ->searchable()
                                    ->live()
                                    ->afterStateUpdated(function ($state, Forms\Set $set, $get) {
                                        if ($state) {
                                            $item = \App\Models\Item::find($state);
                                            $set('current_status', $item?->status);
                                            
                                            // Update status berdasarkan tujuan
                                            $purposeId = $get('../../purpose_id');
                                            if ($purposeId) {
                                                $purpose = \App\Models\Purpose::find($purposeId);
                                                $newStatus = match($purpose->name) {
                                                    'Sewa' => 'masa_sewa',
                                                    'Pembelian' => 'terjual',
                                                    'Peminjaman' => 'dipinjam',
                                                    default => $item?->status
                                                };
                                                $item->update(['status' => $newStatus]);
                                            }
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
                            ->reorderable(false)
                            ->cloneable(false)
                            ->disabled(fn ($context) => $context === 'view')
                            ->disableItemCreation(fn ($context) => $context === 'view')
                            ->disableItemDeletion(fn ($context) => $context === 'view')
                            ->disableItemMovement(fn ($context) => $context === 'view'),
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

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Informasi Barang Keluar')
                    ->schema([
                        TextEntry::make('lkb_number')
                            ->label('Nomor LKB'),
                        TextEntry::make('delivery_date')
                            ->label('Tanggal Keluar')
                            ->date(),
                        TextEntry::make('vendor.vendor_name')
                            ->label('Customer'),
                        TextEntry::make('project.project_id')
                            ->label('Project ID'),
                        TextEntry::make('purpose.name')
                            ->label('Tujuan'),
                    ])
                    ->columns(2),
                Section::make('Items')
                    ->schema([
                        RepeatableEntry::make('outboundItems')
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
            ]);
    }
}
