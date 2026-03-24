<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=(), usb=()');
        $response->headers->set('Cross-Origin-Opener-Policy', 'same-origin');
        $response->headers->set('X-Permitted-Cross-Domain-Policies', 'none');

        if (app()->environment('production')) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains; preload');
        }

        if (! $request->is('sitemap.xml', 'sitemap.xsl')) {
            $nonce = csp_nonce();
            $csp = $this->buildCsp($nonce);
            $response->headers->set('Content-Security-Policy', $csp);
        }

        return $response;
    }

    private function buildCsp(string $nonce): string
    {
        $isLocal = app()->environment('local');

        // unsafe-eval only needed in local for Vite hot reload
        $scriptSrc = $isLocal
            ? "script-src 'self' 'nonce-{$nonce}' 'unsafe-eval'"
            : "script-src 'self' 'nonce-{$nonce}'";

        // style-src: nonce for <style> tags
        // style-src-attr: allows inline style="" attributes (required by Alpine.js x-collapse, transitions)
        $styleSrc = $isLocal
            ? "style-src 'self' 'nonce-{$nonce}' 'unsafe-inline'"
            : "style-src 'self' 'nonce-{$nonce}'";

        $connectSrc = $isLocal
            ? "connect-src 'self' ws://localhost:* http://localhost:*"
            : "connect-src 'self'";

        $directives = [
            "default-src 'self'",
            $scriptSrc,
            $styleSrc,
            // Alpine.js x-transition/x-collapse inject inline style attributes
            "style-src-attr 'unsafe-inline'",
            "img-src 'self' data:",
            "font-src 'self'",
            $connectSrc,
            "frame-ancestors 'none'",
            "form-action 'self'",
            "base-uri 'self'",
            "object-src 'none'",
        ];

        return implode('; ', $directives);
    }
}
