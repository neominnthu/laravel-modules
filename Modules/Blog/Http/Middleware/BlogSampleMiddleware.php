<?php

declare(strict_types=1);

namespace Modules\Blog\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class BlogSampleMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        // Simple pass-through; marker for auto-registration test.
        return $next($request);
    }
}
