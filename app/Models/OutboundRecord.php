<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OutboundRecord extends Model
{
    protected $primaryKey = 'outbound_id';
    
    protected static ?string $label = 'Barang Keluar';

    protected $fillable = [
        'lkb_number',
        'delivery_note_number',
        'outbound_date',
        'vendor_id',
        'project_id',
        'purpose'
    ];

    protected $casts = [
        'outbound_date' => 'date'
    ];

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class, 'vendor_id');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    public function outboundItems(): HasMany
    {
        return $this->hasMany(OutboundItem::class, 'outbound_id');
    }
} 