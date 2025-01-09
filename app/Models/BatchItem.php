<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BatchItem extends Model
{
    protected $primaryKey = 'batch_item_id';
    
    protected $fillable = [
        'part_number_id',
        'quantity',
        'format_id'
    ];

    public function partNumber(): BelongsTo
    {
        return $this->belongsTo(PartNumber::class, 'part_number_id');
    }

    public function histories(): HasMany
    {
        return $this->hasMany(BatchItemHistory::class, 'batch_item_id');
    }

    public function unitFormat(): BelongsTo
    {
        return $this->belongsTo(UnitFormat::class, 'format_id', 'format_id');
    }

    public static function updateQuantity($partNumberId, $quantity, $type, $record = null)
    {
        $batchItem = self::where('part_number_id', $partNumberId)->first();
        
        if (!$batchItem) {
            return null;
        }

        if ($type === 'inbound') {
            $batchItem->quantity += $quantity;
        } else {
            $batchItem->quantity -= $quantity;
        }

        $batchItem->save();

        // Create history record
        BatchItemHistory::create([
            'batch_item_id' => $batchItem->batch_item_id,
            'type' => $type,
            'quantity' => $quantity,
            'recordable_type' => get_class($record),
            'recordable_id' => $record->getKey()
        ]);

        return $batchItem;
    }
} 