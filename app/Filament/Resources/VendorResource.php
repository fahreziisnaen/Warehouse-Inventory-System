<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VendorResource\Pages;
use App\Models\Vendor;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class VendorResource extends Resource
{
    protected static ?string $model = Vendor::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';

    protected static ?int $navigationSort = 4;

    protected static ?string $modelLabel = 'Vendor';
    protected static ?string $pluralModelLabel = 'Vendor';

    public static function getNavigationLabel(): string
    {
        return 'Vendor';
    }

    public static function getCreateButtonLabel(): string
    {
        return 'Buat Vendor';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('vendor_type_id')
                    ->relationship('vendorType', 'type_name')
                    ->label('Customer/Supplier')
                    ->required(),
                Forms\Components\TextInput::make('vendor_name')
                    ->label('Nama Perusahaan')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Textarea::make('address')
                    ->label('Alamat')
                    ->required()
                    ->maxLength(65535)
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\BadgeColumn::make('vendorType.type_name')
                    ->label('Customer/Supplier')
                    ->colors([
                        'warning' => fn ($state) => $state === 'Supplier',
                        'success' => fn ($state) => $state === 'Customer',
                    ])
                    ->sortable(),
                Tables\Columns\TextColumn::make('vendor_name')
                    ->label('Nama Perusahaan')
                    ->searchable(),
                Tables\Columns\TextColumn::make('address')
                    ->label('Alamat')
                    ->searchable()
                    ->limit(50),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('vendor_type')
                    ->relationship('vendorType', 'type_name')
                    ->label('Customer/Supplier'),
            ])
            ->actions([
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
            'index' => Pages\ListVendors::route('/'),
            'create' => Pages\CreateVendor::route('/create'),
            'edit' => Pages\EditVendor::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery();
    }

    public static function getModelLabel(): string
    {
        return 'Vendor';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Vendor';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Master Data';
    }
} 