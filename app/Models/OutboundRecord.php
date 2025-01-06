<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OutboundRecord extends Model
{
    protected $primaryKey = 'outbound_id';
    
    protected $fillable = [
        'lkb_number',
        'delivery_date',
        'vendor_id',
        'project_id',
        'purpose_id',
        'part_number_id',
        'batch_quantity'
    ];

    protected $casts = [
        'delivery_date' => 'datetime',
    ];

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class, 'vendor_id', 'vendor_id');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id', 'project_id');
    }

    public function outboundItems(): HasMany
    {
        return $this->hasMany(OutboundItem::class, 'outbound_id', 'outbound_id');
    }

    public function purpose(): BelongsTo
    {
        return $this->belongsTo(Purpose::class, 'purpose_id', 'purpose_id');
    }

    public function batchItemHistories()
    {
        return $this->morphMany(BatchItemHistory::class, 'recordable');
    }

    public function partNumber(): BelongsTo
    {
        return $this->belongsTo(PartNumber::class, 'part_number_id');
    }

    protected static function booted()
    {
        static::created(function ($outboundRecord) {
            // Update status item berdasarkan tujuan saat create
            $purpose = $outboundRecord->purpose;
            foreach ($outboundRecord->outboundItems as $outboundItem) {
                $newStatus = match($purpose->name) {
                    'Sewa' => 'masa_sewa',
                    'Pembelian' => 'terjual',
                    'Peminjaman' => 'dipinjam',
                    default => $outboundItem->item->status
                };
                $outboundItem->item->update(['status' => $newStatus]);
            }
        });

        static::updated(function ($outboundRecord) {
            // Update status item berdasarkan tujuan saat update
            $purpose = $outboundRecord->purpose;
            foreach ($outboundRecord->outboundItems as $outboundItem) {
                $newStatus = match($purpose->name) {
                    'Sewa' => 'masa_sewa',
                    'Pembelian' => 'terjual',
                    'Peminjaman' => 'dipinjam',
                    default => $outboundItem->item->status
                };
                $outboundItem->item->update(['status' => $newStatus]);
            }
        });
    }
} 