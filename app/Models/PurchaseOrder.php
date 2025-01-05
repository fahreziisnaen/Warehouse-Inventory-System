<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseOrder extends Model
{
    protected $primaryKey = 'po_id';
    
    protected $fillable = [
        'po_number',
        'po_date',
        'vendor_id',
        'project_id'
    ];

    protected $casts = [
        'po_date' => 'date'
    ];

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class, 'vendor_id', 'vendor_id');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id', 'project_id');
    }

    public function inboundRecords(): HasMany
    {
        return $this->hasMany(InboundRecord::class, 'po_id', 'po_id');
    }
} 