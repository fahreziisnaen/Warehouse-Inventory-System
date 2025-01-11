<?php

namespace App\Filament\Widgets;

use App\Models\InboundRecord;
use App\Models\OutboundRecord;
use App\Models\Item;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Support\Colors\Color;

class StatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $currentMonth = now();
        $startOfMonth = $currentMonth->format('Y-m-01');
        $endOfMonth = $currentMonth->format('Y-m-t');

        return [
            Stat::make('Total Barang Masuk Bulan Ini', InboundRecord::whereMonth('receive_date', now()->month)->count())
                ->description('Jumlah barang masuk bulan ini')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success')
                ->url(route('filament.admin.resources.inbound-records.index', [
                    'tableFilters' => [
                        'receive_date' => [
                            'from' => $startOfMonth,
                            'until' => $endOfMonth
                        ]
                    ]
                ])),

            Stat::make('Total Barang Keluar Bulan Ini', OutboundRecord::whereMonth('delivery_date', now()->month)->count())
                ->description('Jumlah barang keluar bulan ini')
                ->descriptionIcon('heroicon-m-arrow-trending-down')
                ->color('danger')
                ->url(route('filament.admin.resources.outbound-records.index', [
                    'tableFilters' => [
                        'delivery_date' => [
                            'from' => $startOfMonth,
                            'until' => $endOfMonth
                        ]
                    ]
                ])),

            Stat::make('Total Barang Di Gudang', Item::where('status', 'diterima')->count())
                ->description('Jumlah barang tersedia')
                ->descriptionIcon('heroicon-m-cube')
                ->color('info')
                ->url(route('filament.admin.resources.items.index', [
                    'tableFilters' => ['status' => ['value' => 'diterima']]
                ])),

            Stat::make('Total Barang Disewa', Item::where('status', 'masa_sewa')->count())
                ->description('Jumlah barang dalam masa sewa')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning')
                ->url(route('filament.admin.resources.items.index', [
                    'tableFilters' => ['status' => ['value' => 'masa_sewa']]
                ])),

            Stat::make('Total Barang Dibeli', Item::where('status', 'terjual')->count())
                ->description('Jumlah barang terjual')
                ->descriptionIcon('heroicon-m-shopping-cart')
                ->color('success')
                ->url(route('filament.admin.resources.items.index', [
                    'tableFilters' => ['status' => ['value' => 'terjual']]
                ])),

            Stat::make('Total Barang Dipinjam', Item::where('status', 'dipinjam')->count())
                ->description('Jumlah barang dipinjam')
                ->descriptionIcon('heroicon-m-hand-raised')
                ->color('purple')
                ->url(route('filament.admin.resources.items.index', [
                    'tableFilters' => ['status' => ['value' => 'dipinjam']]
                ])),
        ];
    }
} 