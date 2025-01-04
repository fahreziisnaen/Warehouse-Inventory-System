<?php

namespace App\Filament\Widgets;

use App\Models\Item;
use App\Models\InboundRecord;
use App\Models\OutboundRecord;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Total Items', Item::count())
                ->description('Total inventory items')
                ->descriptionIcon('heroicon-m-cube')
                ->chart([7, 3, 4, 5, 6, 3, 5, 3])
                ->color('success'),

            Stat::make('Barang Masuk Bulan Ini', InboundRecord::whereMonth('receive_date', now()->month)->count())
                ->description('Total barang masuk bulan ini')
                ->descriptionIcon('heroicon-m-arrow-down-tray')
                ->chart([4, 7, 3, 5, 6, 3, 3, 4])
                ->color('info'),

            Stat::make('Barang Keluar Bulan Ini', OutboundRecord::whereMonth('outbound_date', now()->month)->count())
                ->description('Total barang keluar bulan ini')
                ->descriptionIcon('heroicon-m-arrow-up-tray')
                ->chart([3, 5, 7, 4, 5, 3, 4, 3])
                ->color('warning'),
        ];
    }
} 