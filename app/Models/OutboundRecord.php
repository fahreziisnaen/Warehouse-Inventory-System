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
        'part_number_id',
        'batch_quantity',
        'note'
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

    public function batchItemHistories()
    {
        return $this->morphMany(BatchItemHistory::class, 'recordable', 'recordable_type', 'recordable_id', 'outbound_id')
            ->with(['batchItem.inboundHistories.recordable']);
    }

    public function partNumber(): BelongsTo
    {
        return $this->belongsTo(PartNumber::class, 'part_number_id');
    }

    public function purpose(): BelongsTo
    {
        return $this->belongsTo(Purpose::class, 'purpose_id');
    }

    protected static function booted()
    {
        static::created(function ($outboundRecord) {
            // Update status item berdasarkan tujuan saat create
            foreach ($outboundRecord->outboundItems as $outboundItem) {
                $newStatus = match($outboundItem->purpose->name) {
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
            foreach ($outboundRecord->outboundItems as $outboundItem) {
                $newStatus = match($outboundItem->purpose->name) {
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

    public static function generateLkbNumber(string $location): string
    {
        $currentMonth = now()->format('m');
        $currentYear = now()->format('Y');
        
        $locationCode = match($location) {
            'Gudang Surabaya' => 'SBY',
            'Gudang Jakarta' => 'JKT',
            default => 'JKT'
        };
        
        // Cari nomor urut terakhir untuk tahun dan lokasi yang sama
        $lastNumber = static::where('lkb_number', 'LIKE', "%-{$currentMonth}.{$currentYear}-{$locationCode}-K")
            ->orderByRaw('CAST(SUBSTRING_INDEX(lkb_number, "-", 1) AS UNSIGNED) DESC')
            ->first();
        
        if ($lastNumber) {
            // Ambil nomor urut terakhir dan tambah 1
            $lastSequence = (int) explode('-', $lastNumber->lkb_number)[0];
            $newSequence = $lastSequence + 1;
        } else {
            // Jika belum ada nomor untuk tahun ini, mulai dari 1
            $newSequence = 1;
        }
        
        // Format nomor dengan padding 2 digit (01, 02, dst)
        $paddedSequence = str_pad($newSequence, 2, '0', STR_PAD_LEFT);
        
        return sprintf('%s-%s.%s-%s-K', $paddedSequence, $currentMonth, $currentYear, $locationCode);
    }
} 