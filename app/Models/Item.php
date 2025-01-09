<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Item extends Model
{
    protected $primaryKey = 'item_id';
    
    protected $fillable = [
        'serial_number',
        'status',
        'part_number_id'
    ];

    protected $casts = [
        'manufacture_date' => 'date',
        'status' => 'string'
    ];

    protected $appends = ['status_label'];

    const STATUS_DITERIMA = 'diterima';
    const STATUS_TERJUAL = 'terjual';
    const STATUS_MASA_SEWA = 'masa_sewa';
    const STATUS_DIPINJAM = 'dipinjam';

    public static function getInitialStatuses(): array
    {
        return [
            self::STATUS_DITERIMA => 'Diterima',
        ];
    }

    public static function getStatuses(): array
    {
        return [
            self::STATUS_DITERIMA => 'Diterima',
            self::STATUS_TERJUAL => 'Terjual',
            self::STATUS_MASA_SEWA => 'Masa Sewa',
            self::STATUS_DIPINJAM => 'Dipinjam',
        ];
    }

    public function partNumber(): BelongsTo
    {
        return $this->belongsTo(PartNumber::class, 'part_number_id');
    }

    public function inboundItems(): HasMany
    {
        return $this->hasMany(InboundItem::class, 'item_id');
    }

    public function outboundItems(): HasMany
    {
        return $this->hasMany(OutboundItem::class, 'item_id');
    }

    public function getStatusLabelAttribute(): string
    {
        return ucfirst($this->status);
    }

    public function updateLatestStatus()
    {
        // Ambil transaksi Inbound terbaru
        $latestInbound = $this->inboundItems()
            ->whereHas('inboundRecord', function ($query) {
                $query->orderBy('receive_date', 'desc');
            })
            ->first();

        // Ambil transaksi Outbound terbaru
        $latestOutbound = $this->outboundItems()
            ->whereHas('outboundRecord', function ($query) {
                $query->orderBy('delivery_date', 'desc');
            })
            ->first();

        // Bandingkan tanggal
        if (!$latestInbound && !$latestOutbound) {
            return; // Tidak ada transaksi
        }

        if (!$latestOutbound) {
            $this->update(['status' => 'diterima']);
            return;
        }

        if (!$latestInbound) {
            $this->updateStatusFromOutbound($latestOutbound);
            return;
        }

        $inboundDate = $latestInbound->inboundRecord->receive_date;
        $outboundDate = $latestOutbound->outboundRecord->delivery_date;

        if ($outboundDate > $inboundDate) {
            // Jika outbound lebih baru, update status sesuai purpose
            $this->updateStatusFromOutbound($latestOutbound);
        } else {
            // Jika inbound lebih baru, status = diterima
            $this->update(['status' => 'diterima']);
        }
    }

    private function updateStatusFromOutbound($outboundItem)
    {
        $purpose = $outboundItem->outboundRecord->purpose->name;
        $newStatus = match($purpose) {
            'Sewa' => 'masa_sewa',
            'Pembelian' => 'terjual',
            'Peminjaman' => 'dipinjam',
            default => $this->status
        };
        $this->update(['status' => $newStatus]);
    }

    public function getRouteKeyName()
    {
        return 'serial_number';
    }
} 