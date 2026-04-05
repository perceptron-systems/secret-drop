<?php

namespace App\Http\Middleware;

use App\Services\PageviewService;
use App\Support\LocaleConfig;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Fires a pageview tracking event after successful GET responses using the named route as page identifier. */
class TrackPageView
{
    public function __construct(
        private PageviewService $pageviewService,
    ) {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $request->isMethod('GET') || $response->getStatusCode() !== 200 || $request->ajax()) {
            return $response;
        }

        $page = $this->identifyPage($request);

        if (! $page) {
            return $response;
        }

        $this->pageviewService->track(
            $page,
            $request->userAgent() ?? '',
            $request->header('Accept-Language', ''),
            (int) $request->cookie('tz_offset', '0'),
            $this->extractLocale($request),
            $request->header('Referer', '')
        );

        return $response;
    }

    private function identifyPage(Request $request): ?string
    {
        $route = $request->route();

        $name = $route?->getName();

        if (! $name || str_starts_with($name, 'generated::')) {
            return null;
        }

        if ($name === 'page.show') {
            $slug = $route->parameter('pageSlug', 'unknown');
            $locale = $route->parameter('locale', LocaleConfig::DEFAULT_LOCALE);
            $canonical = LocaleConfig::findRouteBySlug($slug, $locale);

            return $canonical ?? $slug;
        }

        return $name;
    }

    private function extractLocale(Request $request): string
    {
        $route = $request->route();
        $locale = $route?->parameter('locale');

        if ($locale && LocaleConfig::isSupported($locale)) {
            return $locale;
        }

        return app()->getLocale();
    }
}
