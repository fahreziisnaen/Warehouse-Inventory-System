<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InboundRecord extends Model
{
    protected $primaryKey = 'inbound_id';
    
    protected $fillable = [
        'lpb_number',
        'receive_date',
        'po_id',
        'project_id',
        'part_number_id',
        'batch_quantity',
        'location'
    ];

    protected $casts = [
        'receive_date' => 'date',
    ];

    protected static ?string $label = 'Barang Masuk';

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class, 'po_id', 'po_id');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id', 'project_id');
    }

    public function inboundItems(): HasMany
    {
        return $this->hasMany(InboundItem::class, 'inbound_id');
    }

    public function batchItemHistories()
    {
        return $this->morphMany(BatchItemHistory::class, 'recordable');
    }

    public function partNumber(): BelongsTo
    {
        return $this->belongsTo(PartNumber::class, 'part_number_id');
    }

    public function unitFormat()
    {
        return $this->belongsTo(UnitFormat::class, 'format_id');
    }
} 