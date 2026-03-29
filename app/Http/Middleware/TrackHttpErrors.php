<?php

namespace App\Http\Middleware;

use App\Services\StatsService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

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

    public function terminate(Request $request, Response $response): void
    {
        $status = $response->getStatusCode();

        if ($status < 400) {
            return;
        }

        if ($status >= 500) {
            $this->stats->increment(StatsService::HTTP_ERRORS_5XX);
        } else {
            $this->stats->increment(StatsService::HTTP_ERRORS_4XX);
        }

        $this->stats->increment("http_errors_{$status}");
    }
}
