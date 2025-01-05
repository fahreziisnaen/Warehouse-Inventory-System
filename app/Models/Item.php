<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Item extends Model
{
    protected $primaryKey = 'item_id';
    
    protected $fillable = [
        'part_number_id',
        'serial_number',
        'status',
        'manufacture_date'
    ];

    protected $casts = [
        'manufacture_date' => 'date',
        'status' => 'string'
    ];

    protected $appends = ['status_label'];

    const STATUS_BARU = 'baru';
    const STATUS_BEKAS = 'bekas';
    const STATUS_DITERIMA = 'diterima';
    const STATUS_TERJUAL = 'terjual';
    const STATUS_MASA_SEWA = 'masa_sewa';
    const STATUS_DIPINJAM = 'dipinjam';
    const STATUS_SEWA_HABIS = 'sewa_habis';

    public static function getInitialStatuses(): array
    {
        return [
            self::STATUS_BARU => 'Baru',
            self::STATUS_BEKAS => 'Bekas',
        ];
    }

    public static function getStatuses(): array
    {
        return [
            self::STATUS_BARU => 'Baru',
            self::STATUS_BEKAS => 'Bekas',
            self::STATUS_DITERIMA => 'Diterima',
            self::STATUS_TERJUAL => 'Terjual',
            self::STATUS_MASA_SEWA => 'Masa Sewa',
            self::STATUS_DIPINJAM => 'Dipinjam',
            self::STATUS_SEWA_HABIS => 'Sewa Habis',
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
} 