<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ForceJsonResponse
{
    public function handle(Request $request, Closure $next)
    {
        // File downloads (e.g. the accounting export) keep their own content type; only a missing or
        // browser-default Accept header is replaced.
        $accept = $request->headers->get('Accept');
        if ($accept === null || $accept === '' || $accept === '*/*' || str_contains($accept, 'text/html')) {
            $request->headers->set('Accept', 'application/json');
        }

        return $next($request);
    }
}
