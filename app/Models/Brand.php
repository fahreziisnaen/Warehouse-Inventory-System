<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Brand extends Model
{
    use LogsActivity;

    protected $primaryKey = 'brand_id';
    
    protected $fillable = [
        'brand_name'
    ];

    public function partNumbers(): HasMany
    {
        return $this->hasMany(PartNumber::class, 'brand_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['brand_name'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => match($eventName) {
                'created' => 'membuat Brand baru',
                'updated' => 'mengubah data Brand',
                'deleted' => 'menghapus Brand',
                default => $eventName
            });
    }
} 