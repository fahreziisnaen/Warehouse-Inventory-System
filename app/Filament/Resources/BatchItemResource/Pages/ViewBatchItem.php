<?php

namespace App\Filament\Resources\BatchItemResource\Pages;

use App\Filament\Resources\BatchItemResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\RepeatableEntry;

class ViewBatchItem extends ViewRecord
{
    protected static string $resource = BatchItemResource::class;

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Informasi Item')
                    ->schema([
                        TextEntry::make('partNumber.brand.brand_name')
                            ->label('Brand'),
                        TextEntry::make('partNumber.part_number')
                            ->label('Part Number'),
                        TextEntry::make('quantity')
                            ->label('Stock'),
                        TextEntry::make('unitFormat.name')
                            ->label('Satuan'),
                    ])
                    ->columns(2),

                Section::make('Riwayat Transaksi')
                    ->schema([
                        RepeatableEntry::make('histories')
                            ->schema([
                                TextEntry::make('transaction_date')
                                    ->label('Tanggal')
                                    ->date(),
                                TextEntry::make('type')
                                    ->label('Tipe')
                                    ->badge()
                                    ->color(fn (string $state): string => match ($state) {
                                        'inbound' => 'success',
                                        'outbound' => 'danger',
                                        default => 'warning',
                                    })
                                    ->formatStateUsing(fn (string $state): string => match ($state) {
                                        'inbound' => 'Masuk',
                                        'outbound' => 'Keluar',
                                        default => ucfirst($state),
                                    }),
                                TextEntry::make('quantity')
                                    ->label('Quantity'),
                                TextEntry::make('recordable_type')
                                    ->label('Sumber')
                                    ->formatStateUsing(fn (string $state): string => match ($state) {
                                        'App\Models\InboundRecord' => 'Barang Masuk',
                                        'App\Models\OutboundRecord' => 'Barang Keluar',
                                        default => $state,
                                    }),
                                TextEntry::make('recordable.lpb_number')
                                    ->label('No. Referensi')
                                    ->url(fn ($record) => $record->transaction_url)
                                    ->openUrlInNewTab(),
                            ])
                            ->columns(5),
                    ]),
            ]);
    }
} 