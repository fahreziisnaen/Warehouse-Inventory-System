<?php

namespace App\Filament\Widgets;

use App\Models\InboundRecord;
use App\Models\OutboundRecord;
use App\Models\Item;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Total Barang Masuk Bulan Ini', InboundRecord::whereMonth('receive_date', now()->month)->count())
                ->description('Jumlah barang masuk bulan ini')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success'),

            Stat::make('Total Barang Keluar Bulan Ini', OutboundRecord::whereMonth('delivery_date', now()->month)->count())
                ->description('Jumlah barang keluar bulan ini')
                ->descriptionIcon('heroicon-m-arrow-trending-down')
                ->color('danger'),

            Stat::make('Total Barang Di Gudang', Item::where('status', 'diterima')->count())
                ->description('Jumlah barang tersedia')
                ->descriptionIcon('heroicon-m-cube')
                ->color('info'),
        ];
    }
} 