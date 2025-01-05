<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CanEditItem
{
    public function handle(Request $request, Closure $next): Response
    {
        $item = $request->route('record');

        if ($item && $item->status === 'terjual') {
            return redirect()->back()->with('error', 'Item yang sudah terjual tidak dapat diedit.');
        }

        return $next($request);
    }
} 