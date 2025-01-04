<?php

namespace App\Filament\Resources\PartNumberResource\Pages;

use App\Filament\Resources\PartNumberResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPartNumbers extends ListRecords
{
    protected static string $resource = PartNumberResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Buat Part Number'),
        ];
    }
}
