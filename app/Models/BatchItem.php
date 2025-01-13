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
        return $this->hasMany(BatchItemHistory::class, 'batch_item_id')
            ->orderByDesc(
                \DB::raw("COALESCE(
                    CASE 
                        WHEN recordable_type = 'App\\Models\\InboundRecord' THEN (SELECT receive_date FROM inbound_records WHERE inbound_id = recordable_id)
                        WHEN recordable_type = 'App\\Models\\OutboundRecord' THEN (SELECT delivery_date FROM outbound_records WHERE outbound_id = recordable_id)
                    END,
                    created_at
                )")
            );
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
        } elseif ($type === 'outbound') {
            $batchItem->quantity -= abs($quantity);
        }

        $batchItem->save();

        // Create history record
        BatchItemHistory::create([
            'batch_item_id' => $batchItem->batch_item_id,
            'type' => $type,
            'quantity' => $type === 'outbound' ? -abs($quantity) : $quantity,
            'recordable_type' => get_class($record),
            'recordable_id' => $record->getKey()
        ]);

        return $batchItem;
    }
} 