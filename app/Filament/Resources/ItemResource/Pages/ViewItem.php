<?php

namespace App\Filament\Resources\ItemResource\Pages;

use App\Filament\Resources\ItemResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Support\Enums\FontWeight;
use Filament\Infolists\Components\RepeatableEntry;

class ViewItem extends ViewRecord
{
    protected static string $resource = ItemResource::class;

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Informasi Perangkat')
                    ->schema([
                        TextEntry::make('serial_number')
                            ->label('Serial Number')
                            ->weight(FontWeight::Bold)
                            ->copyable(),
                        TextEntry::make('partNumber.brand.brand_name')
                            ->label('Brand'),
                        TextEntry::make('partNumber.part_number')
                            ->label('Tipe Perangkat'),
                        TextEntry::make('status')
                            ->label('Status')
                            ->badge()
                            ->formatStateUsing(fn (string $state): string => ucfirst($state))
                            ->color(fn (string $state): string => match ($state) {
                                'baru' => 'success',
                                'bekas' => 'warning',
                                'diterima' => 'info',
                                'non_sewa' => 'danger',
                                'masa_sewa' => 'purple',
                                'dipinjam' => 'secondary',
                                default => 'gray',
                            }),
                        TextEntry::make('condition')
                            ->label('Kondisi')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'Baru' => 'success',
                                'Bekas' => 'warning',
                                default => 'gray',
                            }),
                    ])
                    ->columns(2),

                Section::make('Riwayat Transaksi')
                    ->schema([
                        RepeatableEntry::make('inboundItems')
                            ->label('Barang Masuk')
                            ->schema([
                                TextEntry::make('inboundRecord.lpb_number')
                                    ->label('No. LPB')
                                    ->url(fn ($record) => url("/admin/inbound-records/{$record->inbound_id}"))
                                    ->openUrlInNewTab(),
                                TextEntry::make('inboundRecord.receive_date')
                                    ->label('Tanggal Terima')
                                    ->date(),
                                TextEntry::make('quantity')
                                    ->label('Quantity'),
                            ])
                            ->columns(3),

                        RepeatableEntry::make('outboundItems')
                            ->label('Barang Keluar')
                            ->schema([
                                TextEntry::make('outboundRecord.lkb_number')
                                    ->label('No. LKB')
                                    ->url(fn ($record) => url("/admin/outbound-records/{$record->outbound_id}"))
                                    ->openUrlInNewTab(),
                                TextEntry::make('outboundRecord.delivery_date')
                                    ->label('Tanggal Keluar')
                                    ->date(),
                                TextEntry::make('outboundRecord.purpose.name')
                                    ->label('Tujuan'),
                                TextEntry::make('quantity')
                                    ->label('Quantity'),
                            ])
                            ->columns(4),
                    ]),
            ]);
    }
} 