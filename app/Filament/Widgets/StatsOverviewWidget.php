<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\InboundRecord;
use App\Models\OutboundRecord;
use App\Models\Item;
use Carbon\Carbon;

class StatsOverviewWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $now = Carbon::now();
        $startOfMonth = $now->startOfMonth();
        $endOfMonth = $now->copy()->endOfMonth();

        return [
            // Total Barang Masuk Bulan Ini
            Stat::make('Total Barang Masuk Bulan Ini', 
                InboundRecord::whereBetween('receive_date', [$startOfMonth, $endOfMonth])->count())
                ->description('Jumlah barang masuk bulan ini')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->chart([7, 2, 10, 3, 15, 4, 17])
                ->color('success'),

            // Total Barang Keluar Bulan Ini    
            Stat::make('Total Barang Keluar Bulan Ini',
                OutboundRecord::whereBetween('delivery_date', [$startOfMonth, $endOfMonth])->count())
                ->description('Jumlah barang keluar bulan ini')
                ->descriptionIcon('heroicon-m-arrow-trending-down')
                ->chart([17, 16, 14, 15, 14, 13, 12])
                ->color('danger'),

            // Total Barang Di Gudang
            Stat::make('Total Barang Di Gudang',
                Item::where('status', 'diterima')->count())
                ->description('Jumlah barang tersedia')
                ->descriptionIcon('heroicon-m-cube')
                ->chart([15, 4, 17, 4, 8, 12, 9])
                ->color('primary'),

            // Total Barang dalam Masa Sewa
            Stat::make('Total Barang dalam Masa Sewa',
                Item::where('status', 'masa_sewa')->count())
                ->description('Jumlah barang dalam masa sewa')
                ->descriptionIcon('heroicon-m-clock')
                ->chart([9, 12, 15, 16, 17, 18, 19])
                ->color('warning'),

            // Total Barang dalam Non Sewa
            Stat::make('Total Barang dalam Non Sewa', 
                Item::where('status', 'non_sewa')->count())
                ->description('Jumlah barang non sewa')
                ->descriptionIcon('heroicon-m-shopping-cart')
                ->chart([5, 7, 9, 11, 13, 15, 17])
                ->color('success'),

            // Total Barang dalam Peminjaman
            Stat::make('Total Barang dalam Peminjaman',
                Item::where('status', 'dipinjam')->count())
                ->description('Jumlah barang dalam peminjaman')
                ->descriptionIcon('heroicon-m-hand-raised')
                ->chart([3, 5, 7, 9, 11, 13, 15])
                ->color('info'),
        ];
    }

    protected function getColumns(): int
    {
        return 3;
    }
} 