<?php

namespace App\Filament\Resources\BrandResource\Pages;

use App\Filament\Resources\BrandResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Support\Enums\FontWeight;

class ViewBrand extends ViewRecord
{
    protected static string $resource = BrandResource::class;

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Informasi Brand')
                    ->schema([
                        TextEntry::make('brand_name')
                            ->label('Nama Brand')
                            ->weight(FontWeight::Bold),
                        TextEntry::make('total_part_numbers')
                            ->label('Total Perangkat')
                            ->state(fn ($record) => $record->partNumbers->count())
                            ->badge()
                            ->color('info'),
                    ])
                    ->columns(2),

                Section::make('Data Perangkat')
                    ->schema([
                        RepeatableEntry::make('partNumbers')
                            ->schema([
                                TextEntry::make('part_number')
                                    ->label('Tipe Perangkat')
                                    ->weight(FontWeight::Bold)
                                    ->url(fn ($record) => url("/admin/part-numbers/{$record->part_number_id}"))
                                    ->openUrlInNewTab()
                                    ->copyable(),
                                TextEntry::make('description')
                                    ->label('Deskripsi')
                                    ->limit(50),
                                TextEntry::make('items_count')
                                    ->label('Total Perangkat')
                                    ->badge()
                                    ->color('info')
                                    ->state(fn ($record) => $record->items->count()),
                                TextEntry::make('available_items')
                                    ->label('Perangkat di Gudang')
                                    ->badge()
                                    ->color('success')
                                    ->state(fn ($record) => $record->items->whereIn('status', ['baru', 'bekas', 'diterima'])->count()),
                            ])
                            ->columns(4),
                    ]),
            ]);
    }
} 