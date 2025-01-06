<?php

namespace App\Filament\Resources\OutboundRecordResource\Pages;

use App\Filament\Resources\OutboundRecordResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListOutboundRecords extends ListRecords
{
    protected static string $resource = OutboundRecordResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Buat Barang Keluar')
                ->icon('heroicon-o-plus'),
        ];
    }
}
