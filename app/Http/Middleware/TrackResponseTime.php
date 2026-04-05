<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

/**
 * Terminable middleware that records response time per route group in histogram buckets.
 * Buckets represent upper bounds in milliseconds (e.g. a 120 ms response lands in the 200 bucket).
 */
class TrackResponseTime
{
    private const BUCKETS = [50, 100, 200, 500, 1000, 2000, 5000, 10000];

    private const ROUTE_GROUPS = [
        'secrets.store' => 'create',
        'secrets.show' => 'read',
        'secrets.fetch' => 'read',
        'secrets.confirmRead' => 'read',
        'secrets.download' => 'read',
        'admin.index' => 'admin',
        'admin.dashboard' => 'admin',
        'admin.poll' => 'admin',
        'admin.requestAccess' => 'admin',
        'admin.verify' => 'admin',
        'admin.extend' => 'admin',
        'admin.revoke' => 'admin',
        'superadmin.index' => 'superadmin',
        'superadmin.dashboard' => 'superadmin',
        'superadmin.poll' => 'superadmin',
    ];

    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $request->attributes->set('_rt_start', microtime(true));

        return $next($request);
    }

    public function terminate(Request $request, Response $response): void
    {
        $start = $request->attributes->get('_rt_start');

        if (! $start) {
            return;
        }

        $durationMs = (microtime(true) - $start) * 1000;
        $routeName = $request->route()?->getName() ?? '';
        $group = self::ROUTE_GROUPS[$routeName] ?? 'pages';
        $bucket = $this->resolveBucket($durationMs);
        $now = now();

        DB::table('stats_response_times')->upsert(
            [
                'date' => $now->toDateString(),
                'route_group' => $group,
                'bucket' => $bucket,
                'count' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            ['date', 'route_group', 'bucket'],
            ['count' => DB::raw('count + 1'), 'updated_at' => $now]
        );
    }

    private function resolveBucket(float $ms): int
    {
        foreach (self::BUCKETS as $bucket) {
            if ($ms <= $bucket) {
                return $bucket;
            }
        }

        return self::BUCKETS[array_key_last(self::BUCKETS)];
    }
}
