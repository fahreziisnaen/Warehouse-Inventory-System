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
        'location',
        'format_id'
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

    public function validInboundItems(): HasMany
    {
        return $this->hasMany(InboundItem::class, 'inbound_id')
            ->whereHas('item', function ($query) {
                $query->whereNotNull('serial_number')
                    ->whereNotNull('part_number_id')
                    ->whereHas('partNumber', function ($q) {
                        $q->whereHas('brand');
                    });
            });
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

    public function unitFormat(): BelongsTo
    {
        return $this->belongsTo(UnitFormat::class, 'format_id');
    }

    public static function generateLpbNumber(): string
    {
        $currentMonth = now()->format('m');
        $currentYear = now()->format('Y');
        
        // Cari nomor urut terakhir untuk tahun ini saja
        $lastNumber = static::where('lpb_number', 'LIKE', "%-__.{$currentYear}-P")
            ->orderByRaw('CAST(SUBSTRING_INDEX(lpb_number, "-", 1) AS UNSIGNED) DESC')
            ->first();
        
        if ($lastNumber) {
            // Ambil nomor urut terakhir dan tambah 1
            $lastSequence = (int) explode('-', $lastNumber->lpb_number)[0];
            $newSequence = $lastSequence + 1;
        } else {
            // Jika belum ada nomor untuk tahun ini, mulai dari 20 khusus untuk tahun 2025
            $newSequence = ($currentYear === '2025') ? 20 : 1;
        }
        
        return sprintf('%d-%s.%s-P', $newSequence, $currentMonth, $currentYear);
    }
} 