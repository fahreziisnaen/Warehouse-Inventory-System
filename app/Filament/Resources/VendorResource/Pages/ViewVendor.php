<?php

namespace App\Filament\Resources\VendorResource\Pages;

use App\Filament\Resources\VendorResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Support\Enums\FontWeight;

class ViewVendor extends ViewRecord
{
    protected static string $resource = VendorResource::class;

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Informasi Vendor')
                    ->schema([
                        TextEntry::make('vendor_name')
                            ->label('Nama Vendor')
                            ->weight(FontWeight::Bold),
                        TextEntry::make('vendorType.type_name')
                            ->label('Tipe')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'Customer' => 'success',
                                'Supplier' => 'warning',
                                default => 'gray',
                            }),
                        TextEntry::make('address')
                            ->label('Alamat')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Section::make('Projects')
                    ->schema([
                        RepeatableEntry::make('projects')
                            ->schema([
                                TextEntry::make('project_id')
                                    ->label('Project ID')
                                    ->url(fn ($record) => url("/admin/projects/{$record->project_id}"))
                                    ->openUrlInNewTab(),
                                TextEntry::make('project_name')
                                    ->label('Nama Project'),
                            ])
                            ->columns(2),
                    ])
                    ->visible(fn ($record) => $record->vendorType->type_name === 'Customer'),

                Section::make('Purchase Orders')
                    ->schema([
                        RepeatableEntry::make('purchaseOrders')
                            ->schema([
                                TextEntry::make('po_number')
                                    ->label('Nomor PO')
                                    ->url(fn ($record) => url("/admin/purchase-orders/{$record->po_id}"))
                                    ->openUrlInNewTab(),
                                TextEntry::make('po_date')
                                    ->label('Tanggal PO')
                                    ->date(),
                                TextEntry::make('project.project_name')
                                    ->label('Project'),
                            ])
                            ->columns(3),
                    ])
                    ->visible(fn ($record) => $record->vendorType->type_name === 'Supplier'),
            ]);
    }
} 