<?php

namespace App\Http\Middleware;

use App\Services\StatsService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Terminable middleware that records HTTP 4xx/5xx errors in stats_daily.
 * For 5xx errors, also tracks the offending route in stats_error_routes.
 */
class TrackHttpErrors
{
    public function __construct(
        private StatsService $stats,
    ) {
    }

    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        return $next($request);
    }

    /** Record error counters after the response has been sent. */
    public function terminate(Request $request, Response $response): void
    {
        $status = $response->getStatusCode();

        if ($status < 400) {
            return;
        }

        if ($status >= 500) {
            $this->stats->increment(StatsService::HTTP_ERRORS_5XX);

            $route = $request->route()?->getName() ?? $request->getPathInfo();
            $this->stats->trackErrorRoute($status, mb_substr($route, 0, 100));
        } else {
            $this->stats->increment(StatsService::HTTP_ERRORS_4XX);
        }

        $this->stats->increment("http_errors_{$status}");
    }
}
