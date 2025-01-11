<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BatchItemResource\Pages;
use App\Models\BatchItem;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\TextEntry;
use Filament\Forms\Components\RepeatableEntry;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Section;

class BatchItemResource extends Resource
{
    protected static ?string $model = BatchItem::class;

    protected static ?string $navigationIcon = 'heroicon-o-cube';
    protected static ?string $navigationLabel = 'Item Batch';
    protected static ?string $navigationGroup = 'Master Data';
    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('part_number_id')
                    ->relationship('partNumber', 'part_number')
                    ->required(),
                Forms\Components\TextInput::make('quantity')
                    ->required()
                    ->numeric(),
                Forms\Components\Select::make('format_id')
                    ->relationship('unitFormat', 'name')
                    ->required(),
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
                Tables\Columns\TextColumn::make('quantity')
                    ->label('Quantity')
                    ->sortable(),
                Tables\Columns\TextColumn::make('unitFormat.name')
                    ->label('Satuan')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
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
            'index' => Pages\ListBatchItems::route('/'),
            'create' => Pages\CreateBatchItem::route('/create'),
            'view' => Pages\ViewBatchItem::route('/{record}'),
            'edit' => Pages\EditBatchItem::route('/{record}/edit'),
        ];
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
                        TextEntry::make('quantity')
                            ->label('Stock'),
                        TextEntry::make('unitFormat.name')
                            ->label('Satuan'),
                    ])
                    ->columns(2),

                Section::make('Riwayat Transaksi')
                    ->schema([
                        RepeatableEntry::make('histories')
                            ->schema([
                                TextEntry::make('transaction_date')
                                    ->label('Tanggal')
                                    ->date(),
                                TextEntry::make('type')
                                    ->label('Tipe'),
                                TextEntry::make('quantity')
                                    ->label('Quantity'),
                                TextEntry::make('transaction_source')
                                    ->label('Sumber'),
                                TextEntry::make('reference_number')
                                    ->label('No. Referensi')
                                    ->url(fn ($record) => $record->transaction_url),
                            ])
                            ->columns(5)
                    ]),
            ]);
    }
} 