<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ItemTrackingResource\Pages;
use App\Models\Item;
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

class ItemTrackingResource extends Resource
{
    protected static ?string $model = Item::class;

    protected static ?string $navigationGroup = 'Tracking';
    protected static ?string $navigationIcon = 'heroicon-o-magnifying-glass';
    protected static ?int $navigationSort = 1;
    protected static ?string $modelLabel = 'Item Tracking';
    protected static ?string $pluralModelLabel = 'Item Tracking';
    protected static ?string $slug = 'item-tracking';

    public static function table(Table $table): Table
    {
        $filter = session('item_tracking_filter', []);
        $activeFilters = [];

        // Mengumpulkan informasi filter yang aktif
        if ($filter) {
            if ($filter['brand_id'] ?? null) {
                $brand = Brand::find($filter['brand_id']);
                $activeFilters[] = "Brand: {$brand->brand_name}";
            }
            if ($filter['status'] ?? null) {
                $activeFilters[] = "Status: " . match($filter['status']) {
                    'diterima' => 'Diterima',
                    'masa_sewa' => 'Masa Sewa',
                    'non_sewa' => 'Non Sewa',
                    'dipinjam' => 'Dipinjam',
                };
            }
            if (($filter['location'] ?? null) && ($filter['status'] === 'diterima')) {
                $activeFilters[] = "Lokasi: {$filter['location']}";
            }
            if ($filter['condition'] ?? null) {
                $activeFilters[] = "Kondisi: {$filter['condition']}";
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
            if ($filter['purpose_id'] ?? null) {
                $purpose = Purpose::find($filter['purpose_id']);
                $activeFilters[] = "Tujuan: {$purpose->name}";
            }
        }

        return $table
            ->description(
                $activeFilters 
                    ? 'Menampilkan hasil filter: ' . implode(' | ', $activeFilters)
                    : 'Menampilkan semua item'
            )
            ->columns([
                Tables\Columns\TextColumn::make('serial_number')
                    ->label('Serial Number')
                    ->searchable()
                    ->sortable()
                    ->url(fn ($record): string => url("/admin/items/{$record->serial_number}"))
                    ->openUrlInNewTab(),
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
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => ucfirst($state))
                    ->color(fn (string $state): string => match ($state) {
                        'diterima' => 'success',
                        'masa_sewa' => 'warning',
                        'non_sewa' => 'danger',
                        'dipinjam' => 'info',
                        default => 'gray'
                    }),
                Tables\Columns\TextColumn::make('condition')
                    ->label('Kondisi')
                    ->badge(),
                Tables\Columns\TextColumn::make('latest_inbound')
                    ->label('Terakhir Masuk')
                    ->getStateUsing(function ($record) {
                        $inbound = $record->inboundItems()
                            ->with('inboundRecord')
                            ->latest()
                            ->first();
                        
                        if (!$inbound) return '-';
                        
                        return new \Illuminate\Support\HtmlString(
                            '<a href="' . url("/admin/inbound-records/{$inbound->inboundRecord->inbound_id}") . '" target="_blank">' .
                            $inbound->inboundRecord->lpb_number . ' (' . 
                            $inbound->inboundRecord->receive_date->format('d/m/Y') . ')' .
                            '</a>'
                        );
                    })
                    ->html(),
                Tables\Columns\TextColumn::make('latest_outbound')
                    ->label('Terakhir Keluar')
                    ->getStateUsing(function ($record) {
                        $outbound = $record->outboundItems()
                            ->with(['outboundRecord', 'purpose'])
                            ->latest()
                            ->first();
                        
                        if (!$outbound) return '-';
                        
                        return new \Illuminate\Support\HtmlString(
                            '<a href="' . url("/admin/outbound-records/{$outbound->outboundRecord->outbound_id}") . '" target="_blank">' .
                            $outbound->outboundRecord->lkb_number . ' (' . 
                            $outbound->outboundRecord->delivery_date->format('d/m/Y') . ') - ' .
                            $outbound->purpose->name .
                            '</a>'
                        );
                    })
                    ->html(),
            ])
            ->headerActions([
                Action::make('filter')
                    ->label('Filter Items')
                    ->modalWidth(MaxWidth::SevenExtraLarge)
                    ->modalHeading('Filter Items')
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
                                            ->default(fn() => session('item_tracking_filter.brand_id')),

                                        Forms\Components\Select::make('status')
                                            ->options([
                                                'diterima' => 'Diterima',
                                                'masa_sewa' => 'Masa Sewa',
                                                'non_sewa' => 'Non Sewa', 
                                                'dipinjam' => 'Dipinjam'
                                            ])
                                            ->live()
                                            ->afterStateUpdated(fn (Set $set) => $set('location', null))
                                            ->label('Status')
                                            ->default(fn() => session('item_tracking_filter.status')),

                                        Forms\Components\Select::make('location')
                                            ->options([
                                                'Gudang Jakarta' => 'Gudang Jakarta',
                                                'Gudang Surabaya' => 'Gudang Surabaya'
                                            ])
                                            ->visible(fn (Get $get): bool => $get('status') === 'diterima')
                                            ->label('Lokasi')
                                            ->default(fn() => session('item_tracking_filter.location')),

                                        Forms\Components\Select::make('condition')
                                            ->options([
                                                'Baru' => 'Baru',
                                                'Bekas' => 'Bekas'
                                            ])
                                            ->label('Kondisi')
                                            ->default(fn() => session('item_tracking_filter.condition')),
                                    ]),

                                Forms\Components\Section::make('Date Range')
                                    ->schema([
                                        Forms\Components\Select::make('date_type')
                                            ->options([
                                                'inbound' => 'Barang Masuk',
                                                'outbound' => 'Barang Keluar'
                                            ])
                                            ->label('Tipe Transaksi')
                                            ->default(fn() => session('item_tracking_filter.date_type')),

                                        Forms\Components\DatePicker::make('from')
                                            ->label('Dari Tanggal')
                                            ->default(fn() => session('item_tracking_filter.from')),

                                        Forms\Components\DatePicker::make('until')
                                            ->label('Sampai Tanggal')
                                            ->default(fn() => session('item_tracking_filter.until')),
                                    ]),

                                Forms\Components\Section::make('Additional Information')
                                    ->schema([
                                        Forms\Components\Select::make('project_id')
                                            ->relationship('inboundItems.inboundRecord.project', 'project_id')
                                            ->searchable()
                                            ->preload()
                                            ->label('Project')
                                            ->getOptionLabelUsing(fn ($value): string => $value)
                                            ->default(fn() => session('item_tracking_filter.project_id')),

                                        Forms\Components\Select::make('vendor_id')
                                            ->relationship('outboundItems.outboundRecord.vendor', 'vendor_name')
                                            ->searchable()
                                            ->preload()
                                            ->label('Customer')
                                            ->default(fn() => session('item_tracking_filter.vendor_id')),

                                        Forms\Components\Select::make('purpose_id')
                                            ->relationship('outboundItems.purpose', 'name')
                                            ->searchable()
                                            ->preload()
                                            ->label('Tujuan')
                                            ->default(fn() => session('item_tracking_filter.purpose_id')),
                                    ]),
                            ]),
                    ])
                    ->action(function (array $data): void {
                        session(['item_tracking_filter' => $data]);
                        redirect(request()->header('Referer'));
                    }),

                Action::make('reset_filter')
                    ->label('Reset Filter')
                    ->icon('heroicon-m-x-mark')
                    ->color('danger')
                    ->action(function (): void {
                        session()->forget('item_tracking_filter');
                        redirect(request()->header('Referer'));
                    })
                    ->visible(fn() => session()->has('item_tracking_filter')),
            ])
            ->modifyQueryUsing(function (Builder $query) {
                $filter = session('item_tracking_filter', []);
                
                return $query
                    ->when(
                        $filter['brand_id'] ?? null,
                        fn (Builder $query, $brandId): Builder => $query->whereHas(
                            'partNumber',
                            fn ($q) => $q->where('brand_id', $brandId)
                        )
                    )
                    ->when(
                        $filter['status'] ?? null,
                        fn (Builder $query, $status): Builder => $query->where('status', $status)
                    )
                    ->when(
                        ($filter['location'] ?? null) && ($filter['status'] === 'diterima'),
                        fn (Builder $query) => $query->whereHas(
                            'inboundItems.inboundRecord',
                            fn ($q) => $q->where('location', $filter['location'])
                        )
                    )
                    ->when(
                        $filter['from'] ?? null,
                        function (Builder $query) use ($filter) {
                            if ($filter['date_type'] === 'inbound') {
                                return $query->whereHas('inboundItems.inboundRecord', 
                                    fn ($q) => $q->whereDate('receive_date', '>=', $filter['from'])
                                );
                            } elseif ($filter['date_type'] === 'outbound') {
                                return $query->whereHas('outboundItems.outboundRecord', 
                                    fn ($q) => $q->whereDate('delivery_date', '>=', $filter['from'])
                                );
                            } else {
                                // Jika tidak ada date_type, cari di kedua tabel
                                return $query->where(function ($q) use ($filter) {
                                    $q->whereHas('inboundItems.inboundRecord', 
                                        fn ($q) => $q->whereDate('receive_date', '>=', $filter['from'])
                                    )->orWhereHas('outboundItems.outboundRecord', 
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
                                return $query->whereHas('inboundItems.inboundRecord', 
                                    fn ($q) => $q->whereDate('receive_date', '<=', $filter['until'])
                                );
                            } elseif ($filter['date_type'] === 'outbound') {
                                return $query->whereHas('outboundItems.outboundRecord', 
                                    fn ($q) => $q->whereDate('delivery_date', '<=', $filter['until'])
                                );
                            } else {
                                // Jika tidak ada date_type, cari di kedua tabel
                                return $query->where(function ($q) use ($filter) {
                                    $q->whereHas('inboundItems.inboundRecord', 
                                        fn ($q) => $q->whereDate('receive_date', '<=', $filter['until'])
                                    )->orWhereHas('outboundItems.outboundRecord', 
                                        fn ($q) => $q->whereDate('delivery_date', '<=', $filter['until'])
                                    );
                                });
                            }
                        }
                    )
                    ;
            });
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListItemTracking::route('/'),
        ];
    }
} 