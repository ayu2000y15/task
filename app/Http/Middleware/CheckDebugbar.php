<?php

namespace App\Http\Middleware;

use Closure;
use Barryvdh\Debugbar\Facades\Debugbar;

class CheckDebugbar
{
    public function handle($request, Closure $next)
    {
        if (auth()->check() && in_array(auth()->id(), [1, 2, 3])) { // 特定のユーザーID
            Debugbar::enable();
        } else {
            Debugbar::disable();
        }
        return $next($request);
    }
}
