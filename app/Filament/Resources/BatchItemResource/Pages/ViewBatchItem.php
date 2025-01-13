<?php

namespace App\Filament\Resources\BatchItemResource\Pages;

use App\Filament\Resources\BatchItemResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Support\Enums\FontWeight;

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
                            ->label('Brand')
                            ->weight(FontWeight::Bold),
                        TextEntry::make('partNumber.part_number')
                            ->label('Part Number')
                            ->weight(FontWeight::Bold),
                        TextEntry::make('quantity')
                            ->label('Stock')
                            ->badge()
                            ->color('success'),
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
                                    ->label('Quantity')
                                    ->formatStateUsing(fn ($record) => 
                                        ($record->type === 'outbound' ? '-' : '+') . abs($record->quantity)
                                    )
                                    ->color(fn ($record) => 
                                        $record->type === 'outbound' ? 'danger' : 'success'
                                    ),
                                TextEntry::make('transaction_source')
                                    ->label('Sumber'),
                                TextEntry::make('reference_number')
                                    ->label('No. Referensi')
                                    ->url(fn ($record) => $record->transaction_url)
                                    ->openUrlInNewTab()
                                    ->weight(FontWeight::Bold)
                                    ->badge()
                                    ->color(fn ($record) => match ($record->type) {
                                        'inbound' => 'success',
                                        'outbound' => 'warning',
                                        default => 'primary',
                                    })
                                    ->copyable(),
                            ])
                            ->columns(5),
                    ]),
            ]);
    }
} 