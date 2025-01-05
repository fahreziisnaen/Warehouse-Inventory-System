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
        $currentMonth = now()->month;
        
        return [
            Stat::make('Total Inbound This Month', 
                InboundRecord::whereMonth('receive_date', $currentMonth)->count()
            )
                ->description('Jumlah barang masuk bulan ini')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success'),
            
            Stat::make('Total Outbound This Month', 
                OutboundRecord::whereMonth('delivery_date', $currentMonth)->count()
            )
                ->description('Jumlah barang keluar bulan ini')
                ->descriptionIcon('heroicon-m-arrow-trending-down')
                ->color('danger'),
            
            Stat::make('Available Items', 
                Item::whereIn('status', ['baru', 'bekas', 'diterima'])->count()
            )
                ->description('Jumlah barang tersedia')
                ->descriptionIcon('heroicon-m-cube')
                ->color('info'),
        ];
    }
} 