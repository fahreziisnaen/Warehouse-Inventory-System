<?php

namespace App\Filament\Resources\PurchaseOrderResource\Pages;

use App\Filament\Resources\PurchaseOrderResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Support\Enums\FontWeight;

class ViewPurchaseOrder extends ViewRecord
{
    protected static string $resource = PurchaseOrderResource::class;

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Informasi Purchase Order')
                    ->schema([
                        TextEntry::make('po_number')
                            ->label('Nomor PO')
                            ->weight(FontWeight::Bold),
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
                                    ->state(fn ($record) => $record->inboundItems->count()),
                            ])
                            ->columns(3),
                    ]),
            ]);
    }
} 