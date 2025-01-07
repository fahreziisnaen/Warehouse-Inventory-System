<?php

namespace App\Filament\Pages\Reports;

use App\Models\Item;
use App\Models\BatchItem;
use Filament\Pages\Page;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Support\Enums\FontWeight;
use Illuminate\Support\HtmlString;
use Illuminate\Database\Eloquent\Builder;

class InventoryReport extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationGroup = 'Reports';
    protected static ?string $navigationLabel = 'Inventory Report';
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
            )
            ->columns([
                // Inbound Information
                TextColumn::make('inboundItems.inboundRecord.lpb_number')
                    ->label('No. LPB')
                    ->searchable()
                    ->url(fn ($record) => $record->inboundItems->first() 
                        ? url('/admin/inbound-records/'.$record->inboundItems->first()->inbound_id)
                        : null)
                    ->weight(FontWeight::Bold)
                    ->color('primary'),
                TextColumn::make('inboundItems.inboundRecord.receive_date')
                    ->label('Tanggal Terima')
                    ->date()
                    ->sortable(),

                // Outbound Information
                TextColumn::make('outboundItems.outboundRecord.lkb_number')
                    ->label('No. LKB')
                    ->searchable()
                    ->url(fn ($record) => $record->outboundItems->first() 
                        ? url('/admin/outbound-records/'.$record->outboundItems->first()->outbound_id)
                        : null)
                    ->weight(FontWeight::Bold)
                    ->color('primary'),
                TextColumn::make('outboundItems.outboundRecord.delivery_date')
                    ->label('Tanggal Keluar')
                    ->date()
                    ->sortable(),
                TextColumn::make('outboundItems.outboundRecord.purpose.name')
                    ->label('Tujuan')
                    ->badge(),

                // Status dan Lokasi
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => ucfirst($state))
                    ->colors([
                        'success' => fn ($state) => $state === 'baru',
                        'warning' => fn ($state) => $state === 'bekas',
                        'info' => fn ($state) => $state === 'diterima',
                        'danger' => fn ($state) => $state === 'terjual',
                        'purple' => fn ($state) => $state === 'masa_sewa',
                        'secondary' => fn ($state) => $state === 'dipinjam',
                        'rose' => fn ($state) => $state === 'sewa_habis',
                    ]),
                TextColumn::make('latest_location')
                    ->label('Lokasi')
                    ->badge()
                    ->color(fn ($state) => $state ? match($state) {
                        'Gudang Jakarta' => 'success',
                        'Gudang Surabaya' => 'warning',
                        default => 'gray'
                    } : 'gray')
                    ->visible(fn ($record) => $record->status === 'diterima')
                    ->getStateUsing(function ($record) {
                        return $record->inboundItems()
                            ->join('inbound_records', 'inbound_items.inbound_id', '=', 'inbound_records.inbound_id')
                            ->orderBy('inbound_records.receive_date', 'desc')
                            ->value('inbound_records.location');
                    }),
            ]);
    }

    public function getBatchItemsTable(Table $table): Table
    {
        return $table
            ->query(BatchItem::query()->with(['partNumber.brand', 'format']))
            ->columns([
                TextColumn::make('partNumber.brand.brand_name')
                    ->label('Brand')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('partNumber.part_number')
                    ->label('Part Number')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('partNumber.description')
                    ->label('Description')
                    ->searchable(),
                TextColumn::make('format.name')
                    ->label('Satuan')
                    ->sortable(),
                TextColumn::make('quantity')
                    ->label('Stock')
                    ->sortable()
                    ->alignRight(),
            ])
            ->defaultSort('partNumber.brand.brand_name');
    }

    protected function getHeaderWidgets(): array
    {
        return [
            // Jika ingin menambahkan widget di atas tabel
        ];
    }

    protected function getFooterWidgets(): array
    {
        return [
            // Jika ingin menambahkan widget di bawah tabel
        ];
    }
}
