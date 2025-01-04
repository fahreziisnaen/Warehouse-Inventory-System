<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PurchaseOrder extends Model
{
    protected $primaryKey = 'po_id';
    
    protected $fillable = [
        'po_number',
        'po_date',
        'vendor_id',
        'project_id',
        'total_amount'
    ];

    protected $casts = [
        'po_date' => 'date',
        'total_amount' => 'decimal:2'
    ];

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class, 'vendor_id');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    public function inboundRecord(): HasOne
    {
        return $this->hasOne(InboundRecord::class, 'po_id');
    }
} 