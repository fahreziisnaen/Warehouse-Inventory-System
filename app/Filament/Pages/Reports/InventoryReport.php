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
            ->query(
                Item::query()
                    ->with([
                        'partNumber.brand',
                        'inboundItems.inboundRecord',
                        'outboundItems.outboundRecord.purpose'
                    ])
                    ->leftJoin('outbound_items', 'items.item_id', '=', 'outbound_items.item_id')
                    ->leftJoin('outbound_records', 'outbound_items.outbound_id', '=', 'outbound_records.outbound_id')
                    ->select('items.*')
                    ->orderBy('outbound_records.delivery_date', 'desc')
            )
            ->columns([
                // Inbound Information (Left)
                TextColumn::make('inboundItems.inboundRecord.lpb_number')
                    ->label('No. LPB')
                    ->searchable(),
                TextColumn::make('inboundItems.inboundRecord.receive_date')
                    ->label('Tanggal Terima')
                    ->date()
                    ->sortable(),

                // Outbound Information (Middle-Left)
                TextColumn::make('outboundItems.outboundRecord.lkb_number')
                    ->label('No. LKB')
                    ->searchable(),
                TextColumn::make('outboundItems.outboundRecord.delivery_date')
                    ->label('Tanggal Keluar')
                    ->date()
                    ->sortable(),
                TextColumn::make('outboundItems.outboundRecord.purpose.name')
                    ->label('Tujuan')
                    ->searchable(),
                
                // Item Status (Middle)
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => ucfirst($state))
                    ->colors([
                        'success' => fn ($state) => $state === 'baru',
                        'warning' => fn ($state) => $state === 'bekas',
                        'info' => fn ($state) => $state === 'diterima',
                        'danger' => fn ($state) => $state === 'terjual',
                        'purple' => fn ($state) => $state === 'masa_sewa',
                        'secondary' => fn ($state) => $state === 'dipinjam',
                        'rose' => fn ($state) => $state === 'sewa_habis',
                    ]),

                // Item Details (Right)
                TextColumn::make('partNumber.brand.brand_name')
                    ->label('Brand')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('partNumber.part_number')
                    ->label('Part Number')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('serial_number')
                    ->label('Serial Number')
                    ->searchable(),
            ])
            ->defaultSort('outboundItems.outboundRecord.delivery_date', 'desc')
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
