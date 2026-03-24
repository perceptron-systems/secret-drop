<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware to prevent sensitive data from leaking into logs.
 *
 * Zero-knowledge principle: URLs containing tokens or fragments
 * must never be logged in their complete form.
 */
class SanitizeRequestLogging
{
    /**
     * Route patterns containing sensitive tokens.
     */
    private const SENSITIVE_ROUTE_PATTERNS = [
        '#^/s/[^/]+#',           // /s/{token} - secret view
        '#^/api/secrets/[^/]+#', // /api/secrets/{token} - API endpoints
        '#^/admin/verify/[^/]+#', // /admin/verify/{token} - magic links
        '#^/superadmin/verify/[^/]+#', // /superadmin/verify/{token}
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $this->sanitizeServerVars($request);

        return $next($request);
    }

    private function sanitizeServerVars(Request $request): void
    {
        $uri = $request->getRequestUri();
        $sanitizedUri = $this->sanitizeUri($uri);

        if ($uri !== $sanitizedUri) {
            $request->server->set('REQUEST_URI', $sanitizedUri);
            $request->server->set('ORIGINAL_REQUEST_URI', '[REDACTED]');
        }

        $queryString = $request->server->get('QUERY_STRING', '');
        if ($queryString && $this->containsSensitiveParams($queryString)) {
            $request->server->set('QUERY_STRING', '[REDACTED]');
        }

        if ($request->server->has('HTTP_REFERER')) {
            $referer = $request->server->get('HTTP_REFERER');
            $sanitizedReferer = $this->sanitizeFullUrl($referer);
            $request->server->set('HTTP_REFERER', $sanitizedReferer);
        }
    }

    private function sanitizeUri(string $uri): string
    {
        foreach (self::SENSITIVE_ROUTE_PATTERNS as $pattern) {
            if (preg_match($pattern, $uri)) {
                return preg_replace('#(/[^/]+)/[A-Za-z0-9_-]{20,}#', '$1/[TOKEN]', $uri) ?? $uri;
            }
        }

        return $uri;
    }

    private function sanitizeFullUrl(string $url): string
    {
        if (str_contains($url, '#')) {
            $url = preg_replace('/#.*$/', '#[REDACTED]', $url) ?? $url;
        }

        foreach (self::SENSITIVE_ROUTE_PATTERNS as $pattern) {
            if (preg_match($pattern, $url)) {
                return preg_replace('#(/[^/]+)/[A-Za-z0-9_-]{20,}#', '$1/[TOKEN]', $url) ?? $url;
            }
        }

        return $url;
    }

    private function containsSensitiveParams(string $queryString): bool
    {
        $sensitiveParams = ['token', 'key', 'secret', 'admin_token'];

        foreach ($sensitiveParams as $param) {
            if (str_contains(strtolower($queryString), $param.'=')) {
                return true;
            }
        }

        return false;
    }
}
