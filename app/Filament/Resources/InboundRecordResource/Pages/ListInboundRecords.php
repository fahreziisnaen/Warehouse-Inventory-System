<?php

namespace App\Filament\Resources\InboundRecordResource\Pages;

use App\Filament\Resources\InboundRecordResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListInboundRecords extends ListRecords
{
    protected static string $resource = InboundRecordResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Buat Barang Masuk'),
        ];
    }
}
