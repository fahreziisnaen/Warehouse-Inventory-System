<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class BatchItemHistory extends Model
{
    protected $primaryKey = 'history_id';
    
    protected $fillable = [
        'batch_item_id',
        'type',
        'quantity',
        'recordable_type',
        'recordable_id'
    ];

    public function batchItem(): BelongsTo
    {
        return $this->belongsTo(BatchItem::class, 'batch_item_id');
    }

    public function recordable(): MorphTo
    {
        return $this->morphTo();
    }

    // Tambahkan accessor untuk nomor referensi
    public function getReferenceNumberAttribute()
    {
        if (!$this->recordable) {
            return 'Deleted Record';
        }

        return match($this->recordable_type) {
            'App\Models\InboundRecord' => $this->recordable->lpb_number,
            'App\Models\OutboundRecord' => $this->recordable->lkb_number,
            default => '-'
        };
    }

    // Tambahkan accessor untuk tanggal
    public function getTransactionDateAttribute()
    {
        // Jika recordable sudah dihapus, gunakan created_at
        if (!$this->recordable) {
            return $this->created_at;
        }

        return match($this->recordable_type) {
            'App\Models\InboundRecord' => $this->recordable->receive_date,
            'App\Models\OutboundRecord' => $this->recordable->delivery_date,
            default => $this->created_at
        };
    }

    // Tambahkan accessor untuk URL
    public function getTransactionUrlAttribute()
    {
        return match($this->recordable_type) {
            'App\Models\InboundRecord' => url("/admin/inbound-records/{$this->recordable_id}"),
            'App\Models\OutboundRecord' => url("/admin/outbound-records/{$this->recordable_id}"),
            default => null
        };
    }

    // Tambahkan juga accessor untuk sumber transaksi
    public function getTransactionSourceAttribute()
    {
        // Jika recordable sudah dihapus, berikan informasi "Deleted Record"
        if (!$this->recordable) {
            return 'Deleted ' . str_replace('App\\Models\\', '', $this->recordable_type);
        }

        return match($this->recordable_type) {
            'App\Models\InboundRecord' => 'Barang Masuk',
            'App\Models\OutboundRecord' => 'Barang Keluar',
            default => 'Unknown'
        };
    }
} 