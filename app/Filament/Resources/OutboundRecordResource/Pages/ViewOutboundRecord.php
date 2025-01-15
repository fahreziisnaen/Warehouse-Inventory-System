<?php

namespace App\Filament\Resources\OutboundRecordResource\Pages;

use App\Filament\Resources\OutboundRecordResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Support\Enums\FontWeight;
use Filament\Actions\Action;
use App\Exports\OutboundRecordExport;
use Filament\Notifications\Notification;

class ViewOutboundRecord extends ViewRecord
{
    protected static string $resource = OutboundRecordResource::class;

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Informasi Barang Keluar')
                    ->schema([
                        TextEntry::make('lkb_number')
                            ->label('Nomor LKB')
                            ->weight(FontWeight::Bold),
                        TextEntry::make('delivery_note_number')
                            ->label('Nomor Surat Jalan')
                            ->weight(FontWeight::Bold),
                        TextEntry::make('delivery_date')
                            ->label('Tanggal Keluar')
                            ->date(),
                        TextEntry::make('vendor.vendor_name')
                            ->label('Customer')
                            ->url(fn ($record) => url("/admin/vendors/{$record->vendor_id}"))
                            ->openUrlInNewTab(),
                        TextEntry::make('project.project_id')
                            ->label('Project ID')
                            ->url(fn ($record) => url("/admin/projects/{$record->project_id}"))
                            ->openUrlInNewTab(),
                        TextEntry::make('purpose.name')
                            ->label('Tujuan')
                            ->badge(),
                    ])
                    ->columns(2),

                Section::make('Items')
                    ->schema([
                        RepeatableEntry::make('outboundItems')
                            ->schema([
                                TextEntry::make('item.partNumber.brand.brand_name')
                                    ->label('Brand'),
                                TextEntry::make('item.partNumber.part_number')
                                    ->label('Tipe Perangkat'),
                                TextEntry::make('item.serial_number')
                                    ->label('Serial Number')
                                    ->url(fn ($record) => url("/admin/items/{$record->item->item_id}"))
                                    ->openUrlInNewTab(),
                                TextEntry::make('inboundItem.inboundRecord.lpb_number')
                                    ->label('Nomor LPB')
                                    ->url(fn ($record) => $record->inboundItem ? 
                                        url("/admin/inbound-records/{$record->inboundItem->inbound_id}") : null)
                                    ->openUrlInNewTab(),
                                TextEntry::make('item.status')
                                    ->label('Status')
                                    ->badge()
                                    ->formatStateUsing(fn (string $state): string => ucfirst($state))
                                    ->color(fn (string $state): string => match ($state) {
                                        'masa_sewa' => 'purple',
                                        'terjual' => 'danger',
                                        'dipinjam' => 'warning',
                                        default => 'gray',
                                    }),
                                TextEntry::make('quantity')
                                    ->label('Quantity'),
                            ])
                            ->columns(6),
                    ])
                    ->visible(fn ($record) => $record->outboundItems->count() > 0),

                Section::make('Batch Items')
                    ->schema([
                        RepeatableEntry::make('batchItemHistories')
                            ->schema([
                                TextEntry::make('batchItem.partNumber.brand.brand_name')
                                    ->label('Brand'),
                                TextEntry::make('batchItem.partNumber.part_number')
                                    ->label('Tipe Perangkat'),
                                TextEntry::make('quantity')
                                    ->label('Quantity'),
                                TextEntry::make('batchItem.inboundHistories.first.recordable.lpb_number')
                                    ->label('Nomor LPB')
                                    ->url(fn ($record) => $record->batchItem->inboundHistories->first() ? 
                                        url("/admin/inbound-records/{$record->batchItem->inboundHistories->first()->recordable->inbound_id}") : null)
                                    ->openUrlInNewTab(),
                            ])
                            ->columns(4),
                    ])
                    ->visible(fn ($record) => $record->batchItemHistories->count() > 0),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('print')
                ->label('Print LKB')
                ->icon('heroicon-o-printer')
                ->action(function () {
                    try {
                        return (new OutboundRecordExport($this->record))->download();
                    } catch (\Exception $e) {
                        \Log::error('Export error: ' . $e->getMessage());
                        Notification::make()
                            ->title('Export Error')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                })
        ];
    }
} 