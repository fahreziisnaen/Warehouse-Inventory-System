<?php

namespace App\Filament\Resources\PartNumberResource\Pages;

use App\Filament\Resources\PartNumberResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPartNumber extends EditRecord
{
    protected static string $resource = PartNumberResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
