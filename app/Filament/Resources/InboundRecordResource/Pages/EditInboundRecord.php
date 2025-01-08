<?php

namespace App\Filament\Resources\InboundRecordResource\Pages;

use App\Filament\Resources\InboundRecordResource;
use Filament\Resources\Pages\EditRecord;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Infolist;
use Filament\Tables;
use Filament\Tables\Table;
use App\Models\InboundItem;
use Filament\Notifications\Notification;

class EditInboundRecord extends EditRecord
{
    protected static string $resource = InboundRecordResource::class;

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Card::make()
                ->schema([
                    Forms\Components\TextInput::make('lpb_number')
                        ->label('No. LPB')
                        ->required()
                        ->unique(ignoreRecord: true),
                    Forms\Components\DatePicker::make('receive_date')
                        ->label('Tanggal Terima')
                        ->required(),
                    Forms\Components\Select::make('location')
                        ->label('Lokasi')
                        ->options([
                            'Gudang Jakarta' => 'Gudang Jakarta',
                            'Gudang Surabaya' => 'Gudang Surabaya',
                        ])
                        ->required(),
                ])
                ->columns(2),

            Forms\Components\Card::make()
                ->schema([
                    Forms\Components\Select::make('po_id')
                        ->relationship('purchaseOrder', 'po_number')
                        ->label('No. PO')
                        ->searchable()
                        ->preload(),
                    Forms\Components\Select::make('project_id')
                        ->relationship('project', 'project_id')
                        ->label('Project ID')
                        ->required()
                        ->searchable()
                        ->preload(),
                ])
                ->columns(2),

            // Tabel untuk Items dengan Serial Number
            Forms\Components\Section::make('Items dengan Serial Number')
                ->schema([
                    Forms\Components\View::make('filament.forms.components.inbound-items-table')
                        ->viewData([
                            'inboundItems' => $this->record->inboundItems()
                                ->with(['item.partNumber.brand'])
                                ->get()
                                ->map(function ($item) {
                                    return [
                                        'id' => $item->inbound_item_id,
                                        'brand' => $item->item->partNumber->brand->brand_name ?? '-',
                                        'part_number' => $item->item->partNumber->part_number ?? '-',
                                        'serial_number' => $item->item->serial_number ?? '-',
                                        'quantity' => $item->quantity,
                                        'status' => $item->item->status ?? '-',
                                    ];
                                }),
                            'record' => $this->record,
                        ]),
                ]),
        ]);
    }

    public function deleteInboundItem(int $inboundItemId): void
    {
        $inboundItem = InboundItem::find($inboundItemId);
        
        if ($inboundItem) {
            // Update item status jika perlu
            if ($inboundItem->item) {
                $inboundItem->item->update(['status' => 'unknown']);
            }
            
            $inboundItem->delete();

            Notification::make()
                ->title('Item deleted successfully')
                ->success()
                ->send();

            $this->redirect(EditInboundRecord::getUrl(['record' => $this->record]));
        }
    }
}
