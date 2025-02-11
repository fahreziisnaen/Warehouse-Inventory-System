<?php

namespace App\Filament\Resources\InboundRecordResource\Pages;

use App\Filament\Resources\InboundRecordResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Support\Enums\FontWeight;
use Filament\Actions\Action;
use App\Exports\InboundRecordExport;
use Filament\Notifications\Notification;

class ViewInboundRecord extends ViewRecord
{
    protected static string $resource = InboundRecordResource::class;

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Informasi Inbound')
                    ->schema([
                        TextEntry::make('lpb_number')
                            ->label('No. LPB')
                            ->weight(FontWeight::Bold)
                            ->color('primary'),
                        TextEntry::make('receive_date')
                            ->label('Tanggal Terima')
                            ->date(),
                        TextEntry::make('location')
                            ->label('Lokasi')
                            ->badge(),
                        TextEntry::make('project.project_id')
                            ->label('Project ID')
                            ->weight(FontWeight::Bold)
                            ->url(fn ($record) => url("/admin/projects/{$record->project_id}"))
                            ->openUrlInNewTab(),
                        TextEntry::make('project.project_name')
                            ->label('Nama Project'),
                        TextEntry::make('project.vendor.vendor_name')
                            ->label('Customer')
                            ->url(fn ($record) => url("/admin/vendors/{$record->project->vendor_id}"))
                            ->openUrlInNewTab(),
                        TextEntry::make('purchaseOrder.vendor.vendor_name')
                            ->label('Supplier')
                            ->url(fn ($record) => url("/admin/vendors/{$record->purchaseOrder->vendor_id}"))
                            ->openUrlInNewTab()
                            ->visible(fn ($record) => $record->purchaseOrder),
                        TextEntry::make('purchaseOrder.po_number')
                            ->label('PO Number')
                            ->weight(FontWeight::Bold)
                            ->color('success')
                            ->url(fn ($record) => url("/admin/purchase-orders/{$record->po_id}"))
                            ->openUrlInNewTab(),
                        TextEntry::make('note')
                            ->label('Catatan')
                            ->columnSpanFull()
                            ->visible(fn ($record) => filled($record->note)),
                    ])
                    ->columns(2),

                Section::make('Items dengan Serial Number')
                    ->schema([
                        RepeatableEntry::make('validInboundItems')
                            ->schema([
                                TextEntry::make('item.partNumber.brand.brand_name')
                                    ->label('Brand'),
                                TextEntry::make('item.partNumber.part_number')
                                    ->label('Part Number'),
                                TextEntry::make('item.serial_number')
                                    ->label('Serial Number'),
                                TextEntry::make('quantity')
                                    ->label('Quantity'),
                                TextEntry::make('item.status')
                                    ->label('Status')
                                    ->badge()
                                    ->formatStateUsing(fn (string $state): string => ucfirst($state))
                            ])
                            ->columns(5)
                    ])
                    ->hidden(fn ($record) => !$record->validInboundItems()->exists()),

                Section::make('Batch Items')
                    ->schema([
                        RepeatableEntry::make('batchItemHistories')
                            ->schema([
                                TextEntry::make('batchItem.partNumber.brand.brand_name')
                                    ->label('Brand'),
                                TextEntry::make('batchItem.partNumber.part_number')
                                    ->label('Part Number'),
                                TextEntry::make('quantity')
                                    ->label('Quantity'),
                                TextEntry::make('batchItem.unitFormat.name')
                                    ->label('Satuan'),
                            ])
                            ->columns(4),
                    ])
                    ->hidden(fn ($record) => !$record->batchItemHistories()->exists()),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('print')
                ->label('Print LPB')
                ->icon('heroicon-o-printer')
                ->action(function () {
                    try {
                        return (new InboundRecordExport($this->record))->download();
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