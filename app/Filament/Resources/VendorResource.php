<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VendorResource\Pages;
use App\Models\Vendor;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\RepeatableEntry;

class VendorResource extends Resource
{
    protected static ?string $model = Vendor::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';

    protected static ?string $navigationGroup = 'Master Data';

    protected static ?int $navigationSort = 5;

    protected static ?string $modelLabel = 'Vendor';
    protected static ?string $pluralModelLabel = 'Vendor';

    protected static ?string $createButtonLabel = 'Buat Vendor';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Vendor')
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
                    ])
                    ->columns(2),
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
            'index' => Pages\ListVendors::route('/'),
            'create' => Pages\CreateVendor::route('/create'),
            'view' => Pages\ViewVendor::route('/{record}'),
            'edit' => Pages\EditVendor::route('/{record}/edit'),
        ];
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Informasi Vendor')
                    ->schema([
                        TextEntry::make('vendor_name')
                            ->label('Nama Vendor'),
                        TextEntry::make('vendorType.type_name')
                            ->label('Tipe'),
                        TextEntry::make('address')
                            ->label('Alamat')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Section::make('Projects')
                    ->schema([
                        RepeatableEntry::make('projects')
                            ->schema([
                                TextEntry::make('project_id')
                                    ->label('Project ID'),
                                TextEntry::make('project_name')
                                    ->label('Nama Project'),
                                TextEntry::make('status.name')
                                    ->label('Status'),
                            ])
                            ->columns(3)
                            ->visible(fn ($record) => $record->vendorType->type_name === 'Customer')
                    ]),

                Section::make('Purchase Orders')
                    ->schema([
                        RepeatableEntry::make('purchaseOrders')
                            ->schema([
                                TextEntry::make('po_number')
                                    ->label('Nomor PO'),
                                TextEntry::make('po_date')
                                    ->label('Tanggal PO')
                                    ->date(),
                                TextEntry::make('project.project_name')
                                    ->label('Project'),
                            ])
                            ->columns(3)
                            ->visible(fn ($record) => $record->vendorType->type_name === 'Supplier')
                    ]),

                Section::make('Barang Keluar')
                    ->schema([
                        RepeatableEntry::make('outboundRecords')
                            ->schema([
                                TextEntry::make('lkb_number')
                                    ->label('Nomor LKB'),
                                TextEntry::make('delivery_date')
                                    ->label('Tanggal Keluar')
                                    ->date(),
                                TextEntry::make('project.project_name')
                                    ->label('Project'),
                                TextEntry::make('purpose.name')
                                    ->label('Tujuan'),
                            ])
                            ->columns(4)
                            ->visible(fn ($record) => $record->vendorType->type_name === 'Customer')
                    ]),
            ]);
    }
} 