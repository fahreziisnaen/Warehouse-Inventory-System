<?php

namespace App\Filament\Resources\ProjectResource\Pages;

use App\Filament\Resources\ProjectResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Support\Enums\FontWeight;

class ViewProject extends ViewRecord
{
    protected static string $resource = ProjectResource::class;

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Informasi Project')
                    ->schema([
                        TextEntry::make('project_id')
                            ->label('Project ID')
                            ->weight(FontWeight::Bold),
                        TextEntry::make('project_name')
                            ->label('Nama Project'),
                        TextEntry::make('vendor.vendor_name')
                            ->label('Customer')
                            ->url(fn ($record) => url("/admin/vendors/{$record->vendor_id}"))
                            ->openUrlInNewTab(),
                    ])
                    ->columns(2),

                Section::make('Purchase Orders')
                    ->schema([
                        RepeatableEntry::make('purchaseOrders')
                            ->schema([
                                TextEntry::make('po_number')
                                    ->label('Nomor PO')
                                    ->url(fn ($record) => url("/admin/purchase-orders/{$record->po_id}"))
                                    ->openUrlInNewTab(),
                                TextEntry::make('po_date')
                                    ->label('Tanggal PO')
                                    ->date(),
                                TextEntry::make('vendor.vendor_name')
                                    ->label('Supplier'),
                            ])
                            ->columns(3),
                    ]),

                Section::make('Transaksi')
                    ->schema([
                        RepeatableEntry::make('inboundRecords')
                            ->label('Barang Masuk')
                            ->schema([
                                TextEntry::make('lpb_number')
                                    ->label('Nomor LPB')
                                    ->url(fn ($record) => url("/admin/inbound-records/{$record->inbound_id}"))
                                    ->openUrlInNewTab(),
                                TextEntry::make('receive_date')
                                    ->label('Tanggal Terima')
                                    ->date(),
                            ])
                            ->columns(2),

                        RepeatableEntry::make('outboundRecords')
                            ->label('Barang Keluar')
                            ->schema([
                                TextEntry::make('lkb_number')
                                    ->label('Nomor LKB')
                                    ->url(fn ($record) => url("/admin/outbound-records/{$record->outbound_id}"))
                                    ->openUrlInNewTab(),
                                TextEntry::make('delivery_date')
                                    ->label('Tanggal Keluar')
                                    ->date(),
                                TextEntry::make('purpose.name')
                                    ->label('Tujuan')
                                    ->badge(),
                            ])
                            ->columns(3),
                    ]),
            ]);
    }
} 