<?php

namespace App\Filament\Resources\BatchItemResource\Pages;

use App\Filament\Resources\BatchItemResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditBatchItem extends EditRecord
{
    protected static string $resource = BatchItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
} 