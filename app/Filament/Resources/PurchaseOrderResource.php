<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PurchaseOrderResource\Pages;
use App\Models\PurchaseOrder;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\RepeatableEntry;

class PurchaseOrderResource extends Resource
{
    protected static ?string $model = PurchaseOrder::class;

    protected static ?string $navigationGroup = 'Transaksi';
    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';
    protected static ?int $navigationSort = 1;
    protected static ?string $modelLabel = 'Purchase Order';
    protected static ?string $pluralModelLabel = 'Purchase Orders';
    protected static ?string $createButtonLabel = 'Buat Purchase Order';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Purchase Order')
                    ->schema([
                        Forms\Components\TextInput::make('po_number')
                            ->label('Nomor PO')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),
                        Forms\Components\DatePicker::make('po_date')
                            ->label('Tanggal PO')
                            ->required(),
                        Forms\Components\Select::make('vendor_id')
                            ->relationship('vendor', 'vendor_name')
                            ->label('Supplier')
                            ->required()
                            ->preload()
                            ->searchable(),
                        Forms\Components\Select::make('project_id')
                            ->relationship('project', 'project_id')
                            ->label('Project ID')
                            ->required()
                            ->preload()
                            ->searchable(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('po_number')
                    ->label('PO Number')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('po_date')
                    ->label('PO Date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('vendor.vendor_name')
                    ->label('Supplier')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('project.project_id')
                    ->label('Project ID')
                    ->sortable()
                    ->searchable(),
            ])
            ->filters([
                Filter::make('po_date')
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->label('From Date'),
                        Forms\Components\DatePicker::make('until')
                            ->label('Until Date'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('po_date', '>=', $date),
                            )
                            ->when(
                                $data['until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('po_date', '<=', $date),
                            );
                    }),
                SelectFilter::make('vendor')
                    ->relationship('vendor', 'vendor_name'),
                SelectFilter::make('project')
                    ->relationship('project', 'project_id'),
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
            'index' => Pages\ListPurchaseOrders::route('/'),
            'create' => Pages\CreatePurchaseOrder::route('/create'),
            'view' => Pages\ViewPurchaseOrder::route('/{record}'),
            'edit' => Pages\EditPurchaseOrder::route('/{record}/edit'),
        ];
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Informasi Purchase Order')
                    ->schema([
                        TextEntry::make('po_number')
                            ->label('Nomor PO'),
                        TextEntry::make('po_date')
                            ->label('Tanggal PO')
                            ->date(),
                        TextEntry::make('vendor.vendor_name')
                            ->label('Supplier')
                            ->url(fn ($record) => url("/admin/vendors/{$record->vendor_id}"))
                            ->openUrlInNewTab(),
                        TextEntry::make('project.project_id')
                            ->label('Project ID')
                            ->url(fn ($record) => url("/admin/projects/{$record->project_id}"))
                            ->openUrlInNewTab(),
                    ])
                    ->columns(2),
                Section::make('Barang Masuk')
                    ->schema([
                        RepeatableEntry::make('inboundRecords')
                            ->schema([
                                TextEntry::make('lpb_number')
                                    ->label('Nomor LPB')
                                    ->url(fn ($record) => url("/admin/inbound-records/{$record->inbound_id}"))
                                    ->openUrlInNewTab(),
                                TextEntry::make('receive_date')
                                    ->label('Tanggal Terima')
                                    ->date(),
                                TextEntry::make('inboundItems_count')
                                    ->label('Jumlah Item')
                                    ->state(function ($record) {
                                        return $record->inboundItems->count();
                                    }),
                            ])
                            ->columns(3)
                    ]),
            ]);
    }
}
