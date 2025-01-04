<?php

namespace App\Filament\Resources\OutboundRecordResource\Pages;

use App\Filament\Resources\OutboundRecordResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditOutboundRecord extends EditRecord
{
    protected static string $resource = OutboundRecordResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
