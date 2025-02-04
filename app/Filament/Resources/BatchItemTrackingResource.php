<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BatchItemTrackingResource\Pages;
use App\Models\BatchItem;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Tables\Actions\Action;
use Filament\Support\Enums\MaxWidth;
use App\Models\Brand;
use App\Models\Vendor;
use App\Models\Purpose;

class BatchItemTrackingResource extends Resource
{
    protected static ?string $model = BatchItem::class;

    protected static ?string $navigationGroup = 'Tracking';
    protected static ?string $navigationIcon = 'heroicon-o-magnifying-glass';
    protected static ?int $navigationSort = 2;
    protected static ?string $modelLabel = 'Batch Item Tracking';
    protected static ?string $pluralModelLabel = 'Batch Item Tracking';
    protected static ?string $slug = 'batch-item-tracking';

    public static function table(Table $table): Table
    {
        $filter = session('batch_item_tracking_filter', []);
        $activeFilters = [];

        // Mengumpulkan informasi filter yang aktif
        if ($filter) {
            if ($filter['brand_id'] ?? null) {
                $brand = Brand::find($filter['brand_id']);
                $activeFilters[] = "Brand: {$brand->brand_name}";
            }
            if ($filter['location'] ?? null) {
                $activeFilters[] = "Lokasi: {$filter['location']}";
            }
            if ($filter['from'] ?? null || $filter['until'] ?? null) {
                $dateType = $filter['date_type'] ?? null;
                $dateRange = ($dateType ? "Tanggal " . ($dateType === 'inbound' ? 'Barang Masuk' : 'Barang Keluar') : 'Periode') . ": " .
                    ($filter['from'] ? date('d/m/Y', strtotime($filter['from'])) : '') .
                    ($filter['from'] && $filter['until'] ? ' - ' : '') .
                    ($filter['until'] ? date('d/m/Y', strtotime($filter['until'])) : '');
                $activeFilters[] = $dateRange;
            }
            if ($filter['project_id'] ?? null) {
                $activeFilters[] = "Project ID: {$filter['project_id']}";
            }
            if ($filter['vendor_id'] ?? null) {
                $vendor = Vendor::find($filter['vendor_id']);
                $activeFilters[] = "Customer: {$vendor->vendor_name}";
            }
        }

        return $table
            ->description(
                $activeFilters 
                    ? 'Menampilkan hasil filter: ' . implode(' | ', $activeFilters)
                    : 'Menampilkan semua batch item'
            )
            ->columns([
                Tables\Columns\TextColumn::make('partNumber.brand.brand_name')
                    ->label('Brand')
                    ->searchable()
                    ->sortable()
                    ->url(fn ($record): string => url("/admin/brands/{$record->partNumber->brand->brand_id}"))
                    ->openUrlInNewTab(),

                Tables\Columns\TextColumn::make('partNumber.part_number')
                    ->label('Part Number')
                    ->searchable()
                    ->sortable()
                    ->url(fn ($record): string => url("/admin/part-numbers/{$record->partNumber->part_number_id}"))
                    ->openUrlInNewTab(),

                Tables\Columns\TextColumn::make('quantity')
                    ->label('Quantity')
                    ->sortable(),

                Tables\Columns\TextColumn::make('unitFormat.name')
                    ->label('Satuan'),

                Tables\Columns\TextColumn::make('latest_inbound')
                    ->label('Terakhir Masuk')
                    ->getStateUsing(function ($record) {
                        $inbound = $record->inboundHistories()
                            ->with('recordable')
                            ->latest()
                            ->first();
                        
                        if (!$inbound) return '-';
                        
                        return new \Illuminate\Support\HtmlString(
                            '<a href="' . url("/admin/inbound-records/{$inbound->recordable->inbound_id}") . '" target="_blank">' .
                            $inbound->recordable->lpb_number . ' (' . 
                            $inbound->recordable->receive_date->format('d/m/Y') . ') - ' .
                            abs($inbound->quantity) . ' ' . $record->unitFormat->name .
                            '</a>'
                        );
                    })
                    ->html(),

                Tables\Columns\TextColumn::make('latest_outbound')
                    ->label('Terakhir Keluar')
                    ->getStateUsing(function ($record) {
                        $outbound = $record->outboundHistories()
                            ->with('recordable')
                            ->latest()
                            ->first();
                        
                        if (!$outbound) return '-';
                        
                        return new \Illuminate\Support\HtmlString(
                            '<a href="' . url("/admin/outbound-records/{$outbound->recordable->outbound_id}") . '" target="_blank">' .
                            $outbound->recordable->lkb_number . ' (' . 
                            $outbound->recordable->delivery_date->format('d/m/Y') . ') - ' .
                            abs($outbound->quantity) . ' ' . $record->unitFormat->name .
                            '</a>'
                        );
                    })
                    ->html(),
            ])
            ->headerActions([
                Action::make('filter')
                    ->label('Filter Items')
                    ->modalWidth(MaxWidth::SevenExtraLarge)
                    ->modalHeading('Filter Batch Items')
                    ->form([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\Section::make('Basic Information')
                                    ->schema([
                                        Forms\Components\Select::make('brand_id')
                                            ->relationship('partNumber.brand', 'brand_name')
                                            ->searchable()
                                            ->preload()
                                            ->label('Brand')
                                            ->default(fn() => session('batch_item_tracking_filter.brand_id')),

                                        Forms\Components\Select::make('location')
                                            ->options([
                                                'Gudang Jakarta' => 'Gudang Jakarta',
                                                'Gudang Surabaya' => 'Gudang Surabaya'
                                            ])
                                            ->label('Lokasi')
                                            ->default(fn() => session('batch_item_tracking_filter.location')),
                                    ]),

                                Forms\Components\Section::make('Date Range')
                                    ->schema([
                                        Forms\Components\Select::make('date_type')
                                            ->options([
                                                'inbound' => 'Barang Masuk',
                                                'outbound' => 'Barang Keluar'
                                            ])
                                            ->label('Tipe Transaksi')
                                            ->default(fn() => session('batch_item_tracking_filter.date_type')),

                                        Forms\Components\DatePicker::make('from')
                                            ->label('Dari Tanggal')
                                            ->default(fn() => session('batch_item_tracking_filter.from')),

                                        Forms\Components\DatePicker::make('until')
                                            ->label('Sampai Tanggal')
                                            ->default(fn() => session('batch_item_tracking_filter.until')),
                                    ]),

                                Forms\Components\Section::make('Additional Information')
                                    ->schema([
                                        Forms\Components\Select::make('project_id')
                                            ->relationship('inboundHistories.recordable.project', 'project_id')
                                            ->searchable()
                                            ->preload()
                                            ->label('Project')
                                            ->getOptionLabelUsing(fn ($value): string => $value)
                                            ->default(fn() => session('batch_item_tracking_filter.project_id')),

                                        Forms\Components\Select::make('vendor_id')
                                            ->relationship('outboundHistories.recordable.vendor', 'vendor_name')
                                            ->searchable()
                                            ->preload()
                                            ->label('Customer')
                                            ->default(fn() => session('batch_item_tracking_filter.vendor_id')),
                                    ]),
                            ]),
                    ])
                    ->action(function (array $data): void {
                        session(['batch_item_tracking_filter' => $data]);
                        redirect(request()->header('Referer'));
                    }),

                Action::make('reset_filter')
                    ->label('Reset Filter')
                    ->icon('heroicon-m-x-mark')
                    ->color('danger')
                    ->action(function (): void {
                        session()->forget('batch_item_tracking_filter');
                        redirect(request()->header('Referer'));
                    })
                    ->visible(fn() => session()->has('batch_item_tracking_filter')),
            ])
            ->modifyQueryUsing(function (Builder $query) {
                $filter = session('batch_item_tracking_filter', []);
                
                return $query
                    ->when(
                        $filter['brand_id'] ?? null,
                        fn (Builder $query, $brandId): Builder => $query->whereHas(
                            'partNumber',
                            fn ($q) => $q->where('brand_id', $brandId)
                        )
                    )
                    ->when(
                        $filter['location'] ?? null,
                        fn (Builder $query, $location): Builder => $query->whereHas(
                            'inboundHistories.recordable',
                            fn ($q) => $q->where('location', $location)
                        )
                    )
                    ->when(
                        $filter['from'] ?? null,
                        function (Builder $query) use ($filter) {
                            if ($filter['date_type'] === 'inbound') {
                                return $query->whereHas('inboundHistories.recordable', 
                                    fn ($q) => $q->whereDate('receive_date', '>=', $filter['from'])
                                );
                            } elseif ($filter['date_type'] === 'outbound') {
                                return $query->whereHas('outboundHistories.recordable', 
                                    fn ($q) => $q->whereDate('delivery_date', '>=', $filter['from'])
                                );
                            } else {
                                return $query->where(function ($q) use ($filter) {
                                    $q->whereHas('inboundHistories.recordable', 
                                        fn ($q) => $q->whereDate('receive_date', '>=', $filter['from'])
                                    )->orWhereHas('outboundHistories.recordable', 
                                        fn ($q) => $q->whereDate('delivery_date', '>=', $filter['from'])
                                    );
                                });
                            }
                        }
                    )
                    ->when(
                        $filter['until'] ?? null,
                        function (Builder $query) use ($filter) {
                            if ($filter['date_type'] === 'inbound') {
                                return $query->whereHas('inboundHistories.recordable', 
                                    fn ($q) => $q->whereDate('receive_date', '<=', $filter['until'])
                                );
                            } elseif ($filter['date_type'] === 'outbound') {
                                return $query->whereHas('outboundHistories.recordable', 
                                    fn ($q) => $q->whereDate('delivery_date', '<=', $filter['until'])
                                );
                            } else {
                                return $query->where(function ($q) use ($filter) {
                                    $q->whereHas('inboundHistories.recordable', 
                                        fn ($q) => $q->whereDate('receive_date', '<=', $filter['until'])
                                    )->orWhereHas('outboundHistories.recordable', 
                                        fn ($q) => $q->whereDate('delivery_date', '<=', $filter['until'])
                                    );
                                });
                            }
                        }
                    );
            });
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBatchItemTracking::route('/'),
        ];
    }
} 