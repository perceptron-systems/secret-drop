<?php

namespace App\Http\Middleware;

use App\Services\StatsService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
            $this->trackErrorRoute($status, $request);
        } else {
            $this->stats->increment(StatsService::HTTP_ERRORS_4XX);
        }

        $this->stats->increment("http_errors_{$status}");
    }

    private function trackErrorRoute(int $status, Request $request): void
    {
        $route = $request->route()?->getName() ?? $request->getPathInfo();
        $route = mb_substr($route, 0, 100);
        $now = now();

        DB::table('stats_error_routes')->upsert(
            [
                'date' => $now->toDateString(),
                'status' => $status,
                'route' => $route,
                'count' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            ['date', 'status', 'route'],
            ['count' => DB::raw('count + 1'), 'updated_at' => $now]
        );
    }
}
