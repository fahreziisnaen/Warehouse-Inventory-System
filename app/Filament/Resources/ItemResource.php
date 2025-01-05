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
                            ->options([
                                'baru' => 'Baru',
                                'bekas' => 'Bekas',
                                'diterima' => 'Diterima',
                                'terjual' => 'Terjual',
                                'dipinjam' => 'Dipinjam',
                                'masa_sewa' => 'Masa Sewa',
                            ])
                            ->required(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('partNumber.part_number')
                    ->label('Part Number')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('serial_number')
                    ->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => ucfirst($state))
                    ->colors([
                        'success' => fn ($state) => $state === 'baru',
                        'warning' => fn ($state) => $state === 'bekas',
                        'info' => fn ($state) => $state === 'diterima',
                        'danger' => fn ($state) => $state === 'terjual',
                        'gray' => fn ($state) => $state === 'dipinjam',
                        'purple' => fn ($state) => $state === 'masa_sewa',
                    ]),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'baru' => 'Baru',
                        'bekas' => 'Bekas',
                        'diterima' => 'Diterima',
                        'terjual' => 'Terjual',
                        'dipinjam' => 'Dipinjam',
                        'masa_sewa' => 'Masa Sewa',
                    ]),
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
            'index' => Pages\ListItems::route('/'),
            'create' => Pages\CreateItem::route('/create'),
            'edit' => Pages\EditItem::route('/{record}/edit'),
        ];
    }
}
