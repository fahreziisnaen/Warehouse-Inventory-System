<?php

namespace App\Filament\Pages\Reports;

use App\Models\Item;
use Filament\Pages\Page;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;

class InventoryReport extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationGroup = 'Reports';
    protected static ?string $title = 'Inventory Report';
    protected static ?int $navigationSort = 1;

    protected static string $view = 'filament.pages.reports.inventory-report';

    public function table(Table $table): Table
    {
        return $table
            ->query(Item::query())
            ->columns([
                TextColumn::make('partNumber.part_number')
                    ->label('Part Number')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('serial_number')
                    ->searchable(),
                TextColumn::make('partNumber.brand.brand_name')
                    ->label('Brand')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'available' => 'success',
                        'in_use' => 'warning',
                        'maintenance' => 'danger',
                        'disposed' => 'gray',
                    }),
                TextColumn::make('manufacture_date')
                    ->date()
                    ->sortable(),
            ])
            ->filters([
                // 
            ])
            ->actions([
                //
            ])
            ->bulkActions([
                //
            ]);
    }
}
