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
        'delivery_note_number',
        'delivery_date',
        'vendor_id',
        'project_id',
        'purpose_id',
        'part_number_id',
        'batch_quantity'
    ];

    protected $casts = [
        'delivery_date' => 'date',
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
        return $this->hasMany(OutboundItem::class, 'outbound_id')
            ->with(['inboundItem.inboundRecord']);
    }

    public function purpose(): BelongsTo
    {
        return $this->belongsTo(Purpose::class, 'purpose_id', 'purpose_id');
    }

    public function batchItemHistories()
    {
        return $this->morphMany(BatchItemHistory::class, 'recordable', 'recordable_type', 'recordable_id', 'outbound_id')
            ->with(['batchItem.inboundHistories.recordable']);
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

            // Update batch item quantity jika ada
            if ($outboundRecord->part_number_id && $outboundRecord->batch_quantity) {
                BatchItem::updateQuantity(
                    $outboundRecord->part_number_id,
                    -$outboundRecord->batch_quantity,
                    'outbound',
                    $outboundRecord
                );
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

        static::deleting(function ($outboundRecord) {
            // Update status items menjadi unknown
            foreach ($outboundRecord->outboundItems as $outboundItem) {
                $outboundItem->item->update(['status' => Item::STATUS_UNKNOWN]);
            }

            // Proses batch items
            $histories = BatchItemHistory::where('recordable_type', OutboundRecord::class)
                ->where('recordable_id', $outboundRecord->outbound_id)
                ->get();

            foreach ($histories as $history) {
                // Kembalikan quantity ke batch item
                if ($history->type === 'outbound') {
                    BatchItem::where('batch_item_id', $history->batch_item_id)
                        ->increment('quantity', abs($history->quantity));
                }
                
                // Hapus history
                $history->delete();
            }

            // Hapus semua history yang terkait
            BatchItemHistory::where('recordable_type', OutboundRecord::class)
                ->where('recordable_id', $outboundRecord->outbound_id)
                ->delete();
        });
    }
} 