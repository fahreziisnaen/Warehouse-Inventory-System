<?php

namespace App\Filament\Widgets;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Spatie\Activitylog\Models\Activity;

class LatestActivities extends TableWidget
{
    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 2;

    public function table(Table $table): Table
    {
        return $table
            ->query(Activity::query()->latest()->limit(10))
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Waktu')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('causer.name')
                    ->label('User')
                    ->sortable(),
                Tables\Columns\TextColumn::make('description')
                    ->label('Aktivitas')
                    ->searchable(),
                Tables\Columns\TextColumn::make('subject_type')
                    ->label('Tipe')
                    ->formatStateUsing(fn ($state) => match(class_basename($state)) {
                        'InboundRecord' => 'Barang Masuk',
                        'OutboundRecord' => 'Barang Keluar',
                        'Item' => 'Item',
                        'BatchItem' => 'Batch Item',
                        'PartNumber' => 'Part Number',
                        'Brand' => 'Brand',
                        'Project' => 'Project',
                        'Vendor' => 'Vendor',
                        'PurchaseOrder' => 'Purchase Order',
                        default => class_basename($state)
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('properties')
                    ->label('Detail Perubahan')
                    ->formatStateUsing(function ($state) {
                        if (!$state || !is_array($state)) {
                            return '';
                        }

                        $changes = [];
                        
                        // Handle properties yang berisi attributes dan old values
                        if (isset($state['attributes'])) {
                            $oldValues = $state['old'] ?? [];
                            $newValues = $state['attributes'];
                            
                            foreach ($newValues as $key => $newValue) {
                                $oldValue = $oldValues[$key] ?? null;
                                if ($oldValue !== $newValue) {
                                    $changes[] = "{$key}: " . ($oldValue ?: '(kosong)') . " → {$newValue}";
                                }
                            }
                        }
                        
                        return implode("\n", $changes);
                    })
                    ->wrap(),
            ]);
    }
} 