<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PurchaseOrderResource\Pages;
use App\Filament\Resources\PurchaseOrderResource\RelationManagers;
use App\Models\PurchaseOrder;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;

class PurchaseOrderResource extends Resource
{
    protected static ?string $model = PurchaseOrder::class;

    protected static ?string $navigationGroup = 'Transaksi';
    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';
    protected static ?int $navigationSort = 1;
    protected static ?string $modelLabel = 'Purchase Order';
    protected static ?string $pluralModelLabel = 'Purchase Orders';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Card::make()
                    ->schema([
                        Forms\Components\TextInput::make('po_number')
                            ->label('PO Number')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->regex('/^PO-\d{6}$/')
                            ->validationMessages([
                                'regex' => 'Format harus PO-XXXXXX (X=angka)',
                            ]),
                        Forms\Components\DatePicker::make('po_date')
                            ->label('PO Date')
                            ->required(),
                        Forms\Components\Select::make('supplier_id')
                            ->relationship('supplier', 'supplier_name')
                            ->required()
                            ->searchable(),
                        Forms\Components\Select::make('project_id')
                            ->relationship('project', 'project_name')
                            ->required()
                            ->searchable(),
                        Forms\Components\TextInput::make('total_amount')
                            ->required()
                            ->numeric()
                            ->prefix('Rp')
                            ->maxValue(42949672.95),
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
                Tables\Columns\TextColumn::make('supplier.supplier_name')
                    ->label('Supplier')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('project.project_name')
                    ->label('Project')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('total_amount')
                    ->money('idr')
                    ->sortable(),
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
                SelectFilter::make('supplier')
                    ->relationship('supplier', 'supplier_name'),
                SelectFilter::make('project')
                    ->relationship('project', 'project_name'),
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
            'index' => Pages\ListPurchaseOrders::route('/'),
            'create' => Pages\CreatePurchaseOrder::route('/create'),
            'edit' => Pages\EditPurchaseOrder::route('/{record}/edit'),
        ];
    }
}
