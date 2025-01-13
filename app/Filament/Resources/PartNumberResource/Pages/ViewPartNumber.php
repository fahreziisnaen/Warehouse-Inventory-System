<?php

namespace App\Filament\Resources\PartNumberResource\Pages;

use App\Filament\Resources\PartNumberResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Support\Enums\FontWeight;

class ViewPartNumber extends ViewRecord
{
    protected static string $resource = PartNumberResource::class;

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Informasi Perangkat')
                    ->schema([
                        TextEntry::make('brand.brand_name')
                            ->label('Brand')
                            ->weight(FontWeight::Bold),
                        TextEntry::make('part_number')
                            ->label('Tipe Perangkat')
                            ->weight(FontWeight::Bold)
                            ->copyable(),
                        TextEntry::make('description')
                            ->label('Deskripsi')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Section::make('Statistik')
                    ->schema([
                        TextEntry::make('total_items')
                            ->label('Total Perangkat')
                            ->state(fn ($record) => $record->items->count())
                            ->badge()
                            ->color('info'),
                        TextEntry::make('available_items')
                            ->label('Perangkat di Gudang')
                            ->state(fn ($record) => $record->items->whereIn('status', ['diterima'])->count())
                            ->badge()
                            ->color('success'),
                        TextEntry::make('rented_items')
                            ->label('Disewakan')
                            ->state(fn ($record) => $record->items->where('status', 'masa_sewa')->count())
                            ->badge()
                            ->color('warning'),
                        TextEntry::make('sold_items')
                            ->label('Terjual')
                            ->state(fn ($record) => $record->items->where('status', 'terjual')->count())
                            ->badge()
                            ->color('danger'),
                    ])
                    ->columns(4),

                Section::make('Data Serial Number')
                    ->schema([
                        RepeatableEntry::make('items')
                            ->schema([
                                TextEntry::make('serial_number')
                                    ->label('Serial Number')
                                    ->weight(FontWeight::Bold)
                                    ->url(fn ($record) => url("/admin/items/{$record->serial_number}"))
                                    ->openUrlInNewTab()
                                    ->copyable(),
                                TextEntry::make('status')
                                    ->label('Status')
                                    ->badge()
                                    ->formatStateUsing(fn (string $state): string => ucfirst($state))
                                    ->color(fn (string $state): string => match ($state) {
                                        'diterima' => 'success',
                                        'masa_sewa' => 'warning',
                                        'terjual' => 'danger',
                                        'dipinjam' => 'info',
                                        default => 'gray',
                                    }),
                                TextEntry::make('latest_inbound')
                                    ->label('Terakhir Masuk')
                                    ->state(fn ($record) => $record->inboundItems()
                                        ->whereHas('inboundRecord')
                                        ->latest('created_at')
                                        ->first()?->inboundRecord->lpb_number . ' (' . 
                                            $record->inboundItems()
                                                ->whereHas('inboundRecord')
                                                ->latest('created_at')
                                                ->first()?->inboundRecord->receive_date->format('d/m/Y') . 
                                        ')' ?? '-')
                                    ->url(fn ($record) => $record->inboundItems()
                                        ->whereHas('inboundRecord')
                                        ->latest('created_at')
                                        ->first()
                                            ? url("/admin/inbound-records/{$record->inboundItems()->latest('created_at')->first()->inbound_id}")
                                            : null)
                                    ->openUrlInNewTab(),
                                TextEntry::make('latest_outbound')
                                    ->label('Terakhir Keluar')
                                    ->state(fn ($record) => $record->outboundItems()
                                        ->whereHas('outboundRecord')
                                        ->latest('created_at')
                                        ->first()?->outboundRecord->lkb_number . ' (' .
                                            $record->outboundItems()
                                                ->whereHas('outboundRecord')
                                                ->latest('created_at')
                                                ->first()?->outboundRecord->delivery_date->format('d/m/Y') .
                                        ')' ?? '-')
                                    ->url(fn ($record) => $record->outboundItems()
                                        ->whereHas('outboundRecord')
                                        ->latest('created_at')
                                        ->first()
                                            ? url("/admin/outbound-records/{$record->outboundItems()->latest('created_at')->first()->outbound_id}")
                                            : null)
                                    ->openUrlInNewTab(),
                            ])
                            ->columns(4),
                    ]),

                Section::make('Data Batch')
                    ->schema([
                        RepeatableEntry::make('batchItems')
                            ->schema([
                                TextEntry::make('quantity')
                                    ->label('Quantity')
                                    ->badge()
                                    ->color('success'),
                                TextEntry::make('unitFormat.name')
                                    ->label('Satuan'),
                            ])
                            ->columns(2),
                    ])
                    ->visible(fn ($record) => $record->batchItems->count() > 0),
            ]);
    }
} 