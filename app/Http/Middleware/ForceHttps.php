<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

/** Redirects HTTP requests to HTTPS and forces the URL scheme in production. */
class ForceHttps
{
    public function handle(Request $request, Closure $next): Response
    {
        if (app()->environment('production')) {
            URL::forceScheme('https');

            if (! $request->secure()) {
                return redirect()->secure($request->getRequestUri(), 301);
            }
        }

        return $next($request);
    }
}
