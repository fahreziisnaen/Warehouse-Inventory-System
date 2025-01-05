<?php

namespace App\Http;

use Illuminate\Foundation\Http\Kernel as HttpKernel;

class Kernel extends HttpKernel
{
    /**
     * The application's route middleware aliases.
     *
     * Aliases may be used instead of class names to assign middleware to groups or to give
     * middleware an alias that is easier to assign.
     *
     * @var array<string, class-string|string>
     */
    protected $middlewareAliases = [
        // ... middleware lainnya
        'can-edit-item' => \App\Http\Middleware\CanEditItem::class,
    ];
} 