<?php

namespace App\Services;

use App\Models\Secret;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class StatsService
{
    public const SECRETS_CREATED_TEXT = 'secrets_created_text';

    public const SECRETS_CREATED_FILE = 'secrets_created_file';

    public const SECRETS_WITH_PASSPHRASE = 'secrets_with_passphrase';

    public const SECRETS_WITH_MAX_VIEWS = 'secrets_with_max_views';

    public const SECRETS_SPLIT_MODE = 'secrets_split_mode';

    public const TOTAL_FILE_SIZE_BYTES = 'total_file_size_bytes';

    public const SECRETS_READ = 'secrets_read';

    public const SECRETS_EXPIRED_UNREAD = 'secrets_expired_unread';

    public const SECRETS_REVOKED = 'secrets_revoked';

    public const SECRETS_MAX_VIEWS_REACHED = 'secrets_max_views_reached';

    public const MAGIC_LINKS_REQUESTED = 'magic_links_requested';

    public const MAGIC_LINKS_USED = 'magic_links_used';

    public const SECRETS_EXTENDED = 'secrets_extended';

    public const FIRST_READ_DELAY_TOTAL = 'first_read_delay_total';

    public const FIRST_READ_DELAY_COUNT = 'first_read_delay_count';

    public const HEATMAP_SECRETS_CREATED = 'secrets_created';

    public const HEATMAP_SECRETS_READ = 'secrets_read';

    public function increment(string $metric, int $amount = 1): void
    {
        $date = now()->toDateString();

        DB::table('stats_daily')->upsert(
            [
                'date' => $date,
                'metric' => $metric,
                'count' => $amount,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            ['date', 'metric'],
            ['count' => DB::raw("count + {$amount}"), 'updated_at' => now()]
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function getStats(string $period = '30d'): array
    {
        $days = match ($period) {
            '7d' => 7,
            '30d' => 30,
            '90d' => 90,
            '1y' => 365,
            'all' => null,
            default => 30,
        };

        $startDate = $days ? now()->subDays($days)->toDateString() : null;

        $query = DB::table('stats_daily')
            ->select('metric', 'date', 'count')
            ->orderBy('date');

        if ($startDate) {
            $query->where('date', '>=', $startDate);
        }

        $stats = [];
        foreach ($query->cursor() as $row) {
            $stats[$row->metric][$row->date] = $row->count;
        }

        $firstDate = $startDate ?? DB::table('stats_daily')->min('date') ?? now()->toDateString();

        return [
            'period' => $period,
            'days' => $days,
            'start_date' => $firstDate,
            'end_date' => now()->toDateString(),
            'metrics' => $stats,
            'totals' => $this->getTotals($startDate),
        ];
    }

    /**
     * @return array<string, int>
     */
    public function getTotals(?string $startDate = null): array
    {
        $query = DB::table('stats_daily')
            ->select('metric', DB::raw('SUM(count) as total'))
            ->groupBy('metric');

        if ($startDate) {
            $query->where('date', '>=', $startDate);
        }

        return $query->pluck('total', 'metric')->map(fn ($v) => (int) $v)->toArray();
    }

    /**
     * @return array<string, int>
     */
    public function getAllTimeTotals(): array
    {
        return $this->getTotals(null);
    }

    public function incrementHeatmap(string $metric): void
    {
        $now = now();
        $date = $now->toDateString();
        $dayOfWeek = (int) $now->dayOfWeek;
        $hour = (int) $now->hour;

        DB::table('stats_heatmap')->upsert(
            [
                'date' => $date,
                'day_of_week' => $dayOfWeek,
                'hour' => $hour,
                'metric' => $metric,
                'count' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            ['date', 'day_of_week', 'hour', 'metric'],
            ['count' => DB::raw('count + 1'), 'updated_at' => $now]
        );
    }

    /**
     * @return array<int, array<int, int>>
     */
    public function getHeatmap(string $metric, ?string $startDate = null): array
    {
        $query = DB::table('stats_heatmap')
            ->select('day_of_week', 'hour', DB::raw('SUM(count) as total'))
            ->where('metric', $metric)
            ->groupBy('day_of_week', 'hour');

        if ($startDate) {
            $query->where('date', '>=', $startDate);
        }

        $data = $query->get()
            ->keyBy(fn ($row) => "{$row->day_of_week}-{$row->hour}");

        $heatmap = [];
        for ($day = 0; $day < 7; $day++) {
            $heatmap[$day] = [];
            for ($hour = 0; $hour < 24; $hour++) {
                $key = "{$day}-{$hour}";
                $heatmap[$day][$hour] = isset($data[$key]) ? (int) $data[$key]->total : 0;
            }
        }

        return $heatmap;
    }

    public function trackFirstReadDelay(int $delaySeconds): void
    {
        $this->increment(self::FIRST_READ_DELAY_TOTAL, $delaySeconds);
        $this->increment(self::FIRST_READ_DELAY_COUNT);
    }

    public function getAverageFirstReadDelay(?string $startDate = null): ?float
    {
        $totals = $this->getTotals($startDate);
        $total = $totals[self::FIRST_READ_DELAY_TOTAL] ?? 0;
        $count = $totals[self::FIRST_READ_DELAY_COUNT] ?? 0;

        if ($count === 0) {
            return null;
        }

        return $total / $count;
    }

    public function getActiveSecretsCount(): int
    {
        return Secret::query()
            ->whereNull('revoked_at')
            ->where(function ($q) {
                $q->whereNull('expire_at')
                    ->orWhere('expire_at', '>', now());
            })
            ->where(function ($q) {
                $q->whereNull('max_views')
                    ->orWhereColumn('read_count', '<', 'max_views');
            })
            ->count();
    }

    public function getReadRate(?string $startDate = null): ?float
    {
        $query = Secret::query();

        if ($startDate) {
            $query->where('created_at', '>=', $startDate);
        }

        $total = $query->count();

        if ($total === 0) {
            return null;
        }

        $read = (clone $query)->whereNotNull('first_read_at')->count();

        return ($read / $total) * 100;
    }

    /**
     * @return array{unique_creators: int, gini: float}
     */
    public function getCreatorConcentration(): array
    {
        $counts = Secret::query()
            ->whereNotNull('creator_email_hash')
            ->select('creator_email_hash', DB::raw('COUNT(*) as total'))
            ->groupBy('creator_email_hash')
            ->orderBy('total')
            ->pluck('total')
            ->toArray();

        $uniqueCreators = count($counts);

        if ($uniqueCreators <= 1) {
            return ['unique_creators' => $uniqueCreators, 'gini' => 0.0];
        }

        $n = count($counts);
        $sum = array_sum($counts);
        $weightedSum = 0;

        foreach ($counts as $i => $value) {
            $weightedSum += ($i + 1) * $value;
        }

        $gini = (2 * $weightedSum) / ($n * $sum) - ($n + 1) / $n;

        return ['unique_creators' => $uniqueCreators, 'gini' => round($gini, 2)];
    }

    /**
     * @return array{active_secrets: int, total_files: int, pending_cleanup: int}
     */
    public function getSystemHealth(): array
    {
        $activeSecrets = $this->getActiveSecretsCount();

        $totalFiles = count(Storage::disk('secrets')->allFiles());

        $pendingCleanup = Secret::query()
            ->where(function ($q) {
                $q->where(function ($q2) {
                    $q2->whereNotNull('revoked_at');
                })
                ->orWhere(function ($q2) {
                    $q2->whereNotNull('expire_at')
                        ->where('expire_at', '<=', now());
                })
                ->orWhere(function ($q2) {
                    $q2->whereNotNull('max_views')
                        ->whereColumn('read_count', '>=', 'max_views');
                });
            })
            ->where(function ($q) {
                $q->whereNotNull('ciphertext')
                    ->orWhereNotNull('file_path');
            })
            ->count();

        return [
            'active_secrets' => $activeSecrets,
            'total_files' => $totalFiles,
            'pending_cleanup' => $pendingCleanup,
        ];
    }

    /**
     * Get the current disk usage of stored secret files in bytes.
     */
    public function getCurrentDiskUsage(): int
    {
        return Cache::remember('disk_usage_secrets', 3600, function () {
            $disk = Storage::disk('secrets');
            $files = $disk->allFiles();
            $totalSize = 0;

            foreach ($files as $file) {
                $totalSize += $disk->size($file);
            }

            return $totalSize;
        });
    }

    /**
     * @return array{
     *     total_human: int,
     *     total_bot: int,
     *     by_page: array<string, array{human: int, bot: int}>,
     *     by_country: array<string, int>,
     *     by_language: array<string, int>,
     *     by_hour: array<int, int>,
     *     daily: array<string, array{human: int, bot: int}>
     * }
     */
    public function getPageviews(?string $startDate = null): array
    {
        $query = DB::table('stats_pageviews');

        if ($startDate) {
            $query->where('date', '>=', $startDate);
        }

        $rows = $query->get();

        $totalHuman = 0;
        $totalBot = 0;
        $byPage = [];
        $byCountry = [];
        $byLanguage = [];
        $byHour = array_fill(0, 24, 0);
        $daily = [];

        foreach ($rows as $row) {
            if ($row->is_bot) {
                $totalBot += $row->count;
            } else {
                $totalHuman += $row->count;
            }

            $byPage[$row->page] ??= ['human' => 0, 'bot' => 0];
            $byPage[$row->page][$row->is_bot ? 'bot' : 'human'] += $row->count;

            if (! $row->is_bot) {
                $byCountry[$row->country] = ($byCountry[$row->country] ?? 0) + $row->count;
                $byHour[$row->hour] += $row->count;

                if ($row->locale !== '') {
                    $byLanguage[$row->locale] = ($byLanguage[$row->locale] ?? 0) + $row->count;
                }
            }

            $daily[$row->date] ??= ['human' => 0, 'bot' => 0];
            $daily[$row->date][$row->is_bot ? 'bot' : 'human'] += $row->count;
        }

        arsort($byCountry);
        arsort($byLanguage);
        uasort($byPage, fn ($a, $b) => $b['human'] <=> $a['human']);

        return [
            'total_human' => $totalHuman,
            'total_bot' => $totalBot,
            'by_page' => $byPage,
            'by_country' => $byCountry,
            'by_language' => $byLanguage,
            'by_hour' => $byHour,
            'by_local_hour' => $this->getLocalHours($startDate),
            'daily' => $daily,
        ];
    }

    /**
     * @return array<string, array{human: int, bot: int}>
     */
    public function getReferrers(?string $startDate = null): array
    {
        $query = DB::table('stats_referrers');

        if ($startDate) {
            $query->where('date', '>=', $startDate);
        }

        $rows = $query->get();
        $byDomain = [];

        foreach ($rows as $row) {
            $byDomain[$row->referrer_domain] ??= ['human' => 0, 'bot' => 0];
            $byDomain[$row->referrer_domain][$row->is_bot ? 'bot' : 'human'] += $row->count;
        }

        uasort($byDomain, fn ($a, $b) => $b['human'] <=> $a['human']);

        return $byDomain;
    }

    /**
     * @return array<string, int>
     */
    public function getBotStats(?string $startDate = null): array
    {
        $query = DB::table('stats_bots')
            ->select('bot_name', DB::raw('SUM(count) as total'))
            ->groupBy('bot_name')
            ->orderByDesc('total');

        if ($startDate) {
            $query->where('date', '>=', $startDate);
        }

        return $query->pluck('total', 'bot_name')->map(fn ($v) => (int) $v)->toArray();
    }

    /**
     * @return array<string, int>
     */
    public function getDeviceStats(?string $startDate = null): array
    {
        $query = DB::table('stats_devices')
            ->select('device_type', DB::raw('SUM(count) as total'))
            ->groupBy('device_type')
            ->orderByDesc('total');

        if ($startDate) {
            $query->where('date', '>=', $startDate);
        }

        return $query->pluck('total', 'device_type')->map(fn ($v) => (int) $v)->toArray();
    }

    /**
     * @return array<int, int>
     */
    private function getLocalHours(?string $startDate = null): array
    {
        $query = DB::table('stats_local_hours')
            ->select('local_hour', DB::raw('SUM(count) as total'))
            ->groupBy('local_hour');

        if ($startDate) {
            $query->where('date', '>=', $startDate);
        }

        $data = $query->pluck('total', 'local_hour');
        $hours = array_fill(0, 24, 0);

        foreach ($data as $hour => $total) {
            $hours[$hour] = (int) $total;
        }

        return $hours;
    }
}
