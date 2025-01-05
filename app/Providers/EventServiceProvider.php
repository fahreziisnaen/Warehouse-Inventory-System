<?php

namespace App\Providers;

use App\Models\InboundItem;
use App\Models\OutboundItem;
use App\Observers\InboundItemObserver;
use App\Observers\OutboundItemObserver;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $observers = [
        InboundItem::class => [InboundItemObserver::class],
        OutboundItem::class => [OutboundItemObserver::class],
    ];

    public function boot(): void
    {
        InboundItem::observe(InboundItemObserver::class);
        OutboundItem::observe(OutboundItemObserver::class);
    }
} 