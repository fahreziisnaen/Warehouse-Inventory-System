<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ItemResource\Pages;
use App\Filament\Resources\ItemResource\RelationManagers;
use App\Models\Item;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Tables\Actions\Action;
use Illuminate\Database\Eloquent\Collection;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Tables\Columns\TextColumn;
use Filament\Support\Enums\FontWeight;

class ItemResource extends Resource
{
    protected static ?string $model = Item::class;

    protected static ?string $navigationGroup = 'Master Data';
    protected static ?string $navigationIcon = 'heroicon-o-cube';
    protected static ?int $navigationSort = 4;
    protected static ?string $modelLabel = 'Item';
    protected static ?string $pluralModelLabel = 'Items';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Card::make()
                    ->schema([
                        Forms\Components\Select::make('part_number_id')
                            ->relationship('partNumber', 'part_number')
                            ->label('Part Number')
                            ->required()
                            ->preload()
                            ->searchable()
                            ->optionsLimit(15)
                            ->createOptionForm([
                                Forms\Components\Select::make('brand_id')
                                    ->relationship('brand', 'brand_name')
                                    ->required()
                                    ->preload(),
                                Forms\Components\TextInput::make('part_number')
                                    ->required()
                                    ->unique(),
                                Forms\Components\Textarea::make('description')
                                    ->columnSpanFull(),
                            ]),
                        Forms\Components\TextInput::make('serial_number')
                            ->required()
                            ->maxLength(255)
                            ->unique(),
                        Forms\Components\Select::make('status')
                            ->options(function (Forms\Get $get, ?Model $record) {
                                // Jika sedang create, hanya tampilkan status awal
                                if (!$record) {
                                    return Item::getInitialStatuses();
                                }
                                // Jika sedang edit, tampilkan semua status
                                return Item::getStatuses();
                            })
                            ->required(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->query(
                Item::query()
                    ->with([
                        'partNumber.brand',
                        'inboundItems.inboundRecord',
                        'outboundItems.outboundRecord.purpose'
                    ])
            )
            ->columns([
                Tables\Columns\TextColumn::make('partNumber.brand.brand_name')
                    ->label('Brand')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('partNumber.part_number')
                    ->label('Part Number')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('serial_number')
                    ->label('Serial Number')
                    ->searchable(),
                Tables\Columns\TextColumn::make('latest_location')
                    ->label('Lokasi')
                    ->badge()
                    ->state(function ($record) {
                        return $record->inboundItems()
                            ->join('inbound_records', 'inbound_items.inbound_id', '=', 'inbound_records.inbound_id')
                            ->orderBy('inbound_records.receive_date', 'desc')
                            ->value('inbound_records.location');
                    })
                    ->color(fn ($state) => match($state) {
                        'Gudang Jakarta' => 'success',
                        'Gudang Surabaya' => 'warning',
                        default => 'gray'
                    }),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => ucfirst($state))
                    ->colors([
                        'success' => fn ($state) => $state === 'baru',
                        'warning' => fn ($state) => $state === 'bekas',
                        'info' => fn ($state) => $state === 'diterima',
                        'danger' => fn ($state) => $state === 'terjual',
                        'purple' => fn ($state) => $state === 'masa_sewa',
                        'secondary' => fn ($state) => $state === 'dipinjam',
                        'rose' => fn ($state) => $state === 'sewa_habis',
                    ]),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'baru' => 'Baru',
                        'bekas' => 'Bekas',
                        'diterima' => 'Diterima',
                        'terjual' => 'Terjual',
                        'masa_sewa' => 'Masa Sewa',
                        'dipinjam' => 'Dipinjam',
                        'sewa_habis' => 'Sewa Habis',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()
                    ->hidden(fn ($record) => $record->status === 'terjual'),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->recordUrl(fn($record) => static::getUrl('view', ['record' => $record]))
            ->modifyQueryUsing(fn (Builder $query) => $query
                ->with(['inboundItems.inboundRecord', 'outboundItems.outboundRecord.purpose'])
            );
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
            'index' => Pages\ListItems::route('/'),
            'view' => Pages\ViewItem::route('/{record}'),
            'edit' => Pages\EditItem::route('/{record}/edit'),
        ];
    }

    public static function canEdit(Model $record): bool
    {
        return $record->status !== 'terjual';
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Informasi Item')
                    ->schema([
                        TextEntry::make('partNumber.brand.brand_name')
                            ->label('Brand'),
                        TextEntry::make('partNumber.part_number')
                            ->label('Part Number'),
                        TextEntry::make('serial_number')
                            ->label('Serial Number'),
                        TextEntry::make('status')
                            ->badge()
                            ->formatStateUsing(fn (string $state): string => ucfirst($state))
                            ->color(fn (string $state): string => match ($state) {
                                'baru' => 'success',
                                'bekas' => 'warning',
                                'diterima' => 'info',
                                'terjual' => 'danger',
                                'masa_sewa' => 'purple',
                                'dipinjam' => 'secondary',
                                'sewa_habis' => 'rose',
                                default => 'gray',
                            }),
                        TextEntry::make('latest_location')
                            ->label('Lokasi')
                            ->visible(fn ($record) => $record && $record->status === 'diterima')
                            ->badge()
                            ->getStateUsing(function ($record) {
                                if (!$record) return null;
                                
                                return $record->inboundItems()
                                    ->join('inbound_records', 'inbound_items.inbound_id', '=', 'inbound_records.inbound_id')
                                    ->orderBy('inbound_records.receive_date', 'desc')
                                    ->value('inbound_records.location');
                            })
                            ->color(fn ($state) => match($state) {
                                'Gudang Jakarta' => 'success',
                                'Gudang Surabaya' => 'warning',
                                default => 'gray'
                            }),
                    ])
                    ->columns(2),

                Section::make('Riwayat Inbound')
                    ->schema([
                        RepeatableEntry::make('inboundItems')
                            ->schema([
                                TextEntry::make('inboundRecord.lpb_number')
                                    ->label('No. LPB')
                                    ->url(fn ($record) => url("/admin/inbound-records/{$record->inbound_id}"))
                                    ->openUrlInNewTab()
                                    ->weight(FontWeight::Bold)
                                    ->color('primary'),
                                TextEntry::make('inboundRecord.receive_date')
                                    ->label('Tanggal Terima')
                                    ->date(),
                                TextEntry::make('inboundRecord.location')
                                    ->label('Lokasi')
                                    ->badge()
                                    ->color(fn ($state) => match($state) {
                                        'Gudang Jakarta' => 'success',
                                        'Gudang Surabaya' => 'warning',
                                        default => 'gray'
                                    }),
                            ])
                            ->columns(3),
                    ]),

                Section::make('Riwayat Outbound')
                    ->schema([
                        RepeatableEntry::make('outboundItems')
                            ->schema([
                                TextEntry::make('outboundRecord.lkb_number')
                                    ->label('No. LKB')
                                    ->url(fn ($record) => url("/admin/outbound-records/{$record->outbound_id}"))
                                    ->openUrlInNewTab()
                                    ->weight(FontWeight::Bold)
                                    ->color('primary'),
                                TextEntry::make('outboundRecord.delivery_date')
                                    ->label('Tanggal Keluar')
                                    ->date(),
                                TextEntry::make('outboundRecord.purpose.name')
                                    ->label('Tujuan')
                                    ->badge(),
                            ])
                            ->columns(3),
                    ]),
            ]);
    }
}
