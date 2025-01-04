<?php

namespace App\Filament\Resources\InboundRecordResource\Pages;

use App\Filament\Resources\InboundRecordResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditInboundRecord extends EditRecord
{
    protected static string $resource = InboundRecordResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
