<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PartNumberResource\Pages;
use App\Filament\Resources\PartNumberResource\RelationManagers;
use App\Models\PartNumber;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\RepeatableEntry;

class PartNumberResource extends Resource
{
    protected static ?string $model = PartNumber::class;

    protected static ?string $navigationGroup = 'Master Data';
    protected static ?string $navigationIcon = 'heroicon-o-tag';
    protected static ?int $navigationSort = 3;
    protected static ?string $modelLabel = 'Part Number';
    protected static ?string $pluralModelLabel = 'Part Numbers';
    protected static ?string $createButtonLabel = 'Buat Part Number';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Part Number')
                    ->schema([
                        Forms\Components\Select::make('brand_id')
                            ->relationship('brand', 'brand_name')
                            ->label('Brand')
                            ->required()
                            ->preload()
                            ->searchable(),
                        Forms\Components\TextInput::make('part_number')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),
                        Forms\Components\Textarea::make('description')
                            ->maxLength(65535)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('brand.brand_name')
                    ->label('Brand')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('part_number')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('description')
                    ->limit(50)
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('brand')
                    ->relationship('brand', 'brand_name')
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
            ])
            ->recordUrl(fn($record) => static::getUrl('view', ['record' => $record]));
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
            'index' => Pages\ListPartNumbers::route('/'),
            'create' => Pages\CreatePartNumber::route('/create'),
            'view' => Pages\ViewPartNumber::route('/{record}'),
            'edit' => Pages\EditPartNumber::route('/{record}/edit'),
        ];
    }

    public static function getCreateButtonLabel(): string
    {
        return static::$createButtonLabel;
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Informasi Part Number')
                    ->schema([
                        TextEntry::make('part_number')
                            ->label('Part Number'),
                        TextEntry::make('brand.brand_name')
                            ->label('Brand'),
                        TextEntry::make('description')
                            ->label('Deskripsi')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Section::make('Items')
                    ->schema([
                        RepeatableEntry::make('items')
                            ->schema([
                                TextEntry::make('serial_number')
                                    ->label('Serial Number'),
                                TextEntry::make('status')
                                    ->label('Status')
                                    ->badge()
                                    ->formatStateUsing(fn (string $state) => ucfirst($state))
                                    ->color(fn (string $state): string => match ($state) {
                                        'baru' => 'success',
                                        'bekas' => 'warning',
                                        'diterima' => 'info',
                                        'terjual' => 'danger',
                                        'masa_sewa' => 'purple',
                                        'dipinjam' => 'secondary',
                                        'sewa_habis' => 'rose',
                                    }),
                                TextEntry::make('inboundItems_count')
                                    ->label('Jumlah Inbound')
                                    ->state(function ($record) {
                                        return $record->inboundItems->count();
                                    }),
                                TextEntry::make('outboundItems_count')
                                    ->label('Jumlah Outbound')
                                    ->state(function ($record) {
                                        return $record->outboundItems->count();
                                    }),
                            ])
                            ->columns(4)
                    ]),

                Section::make('Statistik')
                    ->schema([
                        TextEntry::make('items_count')
                            ->label('Total Perangkat')
                            ->state(function ($record) {
                                return $record->items->count();
                            }),
                        TextEntry::make('in_warehouse_count')
                            ->label('Jumlah di Gudang')
                            ->state(function ($record) {
                                return $record->items->where('status', 'diterima')->count();
                            }),
                        TextEntry::make('rented_count')
                            ->label('Jumlah Disewa')
                            ->state(function ($record) {
                                return $record->items->where('status', 'masa_sewa')->count();
                            }),
                        TextEntry::make('sold_count')
                            ->label('Jumlah Terjual')
                            ->state(function ($record) {
                                return $record->items->where('status', 'terjual')->count();
                            }),
                        TextEntry::make('borrowed_count')
                            ->label('Jumlah Dipinjam')
                            ->state(function ($record) {
                                return $record->items->where('status', 'dipinjam')->count();
                            }),
                        TextEntry::make('unclear_count')
                            ->label('Belum Masuk Laporan')
                            ->state(function ($record) {
                                return $record->items->whereIn('status', ['baru', 'bekas'])->count();
                            }),
                    ])
                    ->columns(6),
            ]);
    }
}
