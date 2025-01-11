<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BrandResource\Pages;
use App\Filament\Resources\BrandResource\RelationManagers;
use App\Models\Brand;
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

class BrandResource extends Resource
{
    protected static ?string $model = Brand::class;

    protected static ?string $navigationGroup = 'Master Data';
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?int $navigationSort = 2;
    protected static ?string $modelLabel = 'Brand';
    protected static ?string $pluralModelLabel = 'Brands';
    protected static ?string $createButtonLabel = 'Buat Brand';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Card::make()
                    ->schema([
                        Forms\Components\TextInput::make('brand_name')
                            ->label('Brand')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('brand_name')
                    ->label('Brand')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
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
            'index' => Pages\ListBrands::route('/'),
            'create' => Pages\CreateBrand::route('/create'),
            'view' => Pages\ViewBrand::route('/{record}'),
            'edit' => Pages\EditBrand::route('/{record}/edit'),
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
                Section::make('Informasi Brand')
                    ->schema([
                        TextEntry::make('brand_name')
                            ->label('Brand'),
                    ])
                    ->columns(2),

                Section::make('Part Numbers')
                    ->schema([
                        RepeatableEntry::make('partNumbers')
                            ->schema([
                                TextEntry::make('part_number')
                                    ->label('Part Number'),
                                TextEntry::make('description')
                                    ->label('Deskripsi')
                                    ->limit(50),
                                TextEntry::make('items_count')
                                    ->label('Jumlah Item')
                                    ->state(function ($record) {
                                        return $record->items->count();
                                    }),
                                TextEntry::make('available_items_count')
                                    ->label('Item Tersedia')
                                    ->state(function ($record) {
                                        return $record->items->whereIn('status', ['baru', 'bekas', 'diterima'])->count();
                                    }),
                            ])
                            ->columns(4)
                    ]),

                Section::make('Statistik')
                    ->schema([
                        TextEntry::make('total_part_numbers')
                            ->label('Total Part Numbers')
                            ->state(function ($record) {
                                return $record->partNumbers->count();
                            }),
                        TextEntry::make('total_items')
                            ->label('Total Items')
                            ->state(function ($record) {
                                return $record->partNumbers->sum(function ($partNumber) {
                                    return $partNumber->items->count();
                                });
                            }),
                        TextEntry::make('available_items')
                            ->label('Items Tersedia')
                            ->state(function ($record) {
                                return $record->partNumbers->sum(function ($partNumber) {
                                    return $partNumber->items->whereIn('status', ['baru', 'bekas', 'diterima'])->count();
                                });
                            }),
                    ])
                    ->columns(3),
            ]);
    }
}
