<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\InboundRecord;
use App\Models\OutboundRecord;
use App\Models\InboundItem;
use App\Models\OutboundItem;
use App\Observers\InboundRecordObserver;
use App\Observers\InboundItemObserver;
use App\Observers\OutboundItemObserver;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        InboundRecord::observe(InboundRecordObserver::class);
        InboundItem::observe(InboundItemObserver::class);
        OutboundItem::observe(OutboundItemObserver::class);
    }
}
