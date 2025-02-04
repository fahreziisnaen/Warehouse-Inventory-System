<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PartNumberResource\Pages;
use App\Models\PartNumber;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Actions\DeleteAction;

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
                DeleteAction::make()
                    ->visible(fn (PartNumber $record): bool => $record->items()->count() === 0)
                    ->before(function (PartNumber $record) {
                        if ($record->items()->count() > 0) {
                            return false;
                        }
                    }),
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
}
