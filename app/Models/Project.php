<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    protected $primaryKey = 'project_id';
    public $incrementing = false;
    protected $keyType = 'string';
    
    protected $fillable = [
        'project_id',
        'project_name',
        'customer_id',
        'start_date',
        'end_date',
        'status',
        'description'
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date'
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class, 'project_id');
    }

    public function inboundRecords(): HasMany
    {
        return $this->hasMany(InboundRecord::class, 'project_id');
    }

    public function outboundRecords(): HasMany
    {
        return $this->hasMany(OutboundRecord::class, 'project_id');
    }
} 