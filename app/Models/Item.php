<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Item extends Model
{
    use LogsActivity;

    protected $primaryKey = 'item_id';
    
    protected $fillable = [
        'serial_number',
        'status',
        'condition',
        'part_number_id'
    ];

    protected $casts = [
        'manufacture_date' => 'date',
        'status' => 'string',
        'condition' => 'string'
    ];

    protected $appends = ['status_label'];

    const STATUS_DITERIMA = 'diterima';
    const STATUS_NON_SEWA = 'non_sewa';
    const STATUS_MASA_SEWA = 'masa_sewa';
    const STATUS_DIPINJAM = 'dipinjam';
    const STATUS_UNKNOWN = 'unknown';

    const CONDITION_NEW = 'Baru';
    const CONDITION_USED = 'Bekas';

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
            self::STATUS_NON_SEWA => 'Non Sewa',
            self::STATUS_MASA_SEWA => 'Masa Sewa',
            self::STATUS_DIPINJAM => 'Dipinjam',
            self::STATUS_UNKNOWN => 'Unknown',
        ];
    }

    public static function getConditions(): array
    {
        return [
            self::CONDITION_NEW => 'Baru',
            self::CONDITION_USED => 'Bekas',
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
            ->join('inbound_records', 'inbound_items.inbound_id', '=', 'inbound_records.inbound_id')
            ->orderBy('inbound_records.receive_date', 'desc')
            ->first();

        // Ambil transaksi Outbound terbaru
        $latestOutbound = $this->outboundItems()
            ->join('outbound_records', 'outbound_items.outbound_id', '=', 'outbound_records.outbound_id')
            ->orderBy('outbound_records.delivery_date', 'desc')
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
            'Non Sewa' => 'non_sewa',
            'Peminjaman' => 'dipinjam',
            default => $this->status
        };
        $this->update(['status' => $newStatus]);
    }

    public function getRouteKeyName()
    {
        return 'serial_number';
    }

    protected static function booted()
    {
        static::deleting(function ($item) {
            // Hapus semua inbound items terkait
            $item->inboundItems()->delete();
            
            // Hapus semua outbound items terkait
            $item->outboundItems()->delete();
        });
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['serial_number', 'status', 'condition', 'part_number_id'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => match($eventName) {
                'created' => 'membuat Item baru',
                'updated' => 'mengubah data Item',
                'deleted' => 'menghapus Item',
                default => $eventName
            });
    }
} 