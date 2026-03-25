<?php

namespace Database\Seeders;

use App\Models\Secret;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class StatsSeeder extends Seeder
{
    /** @var array<string, array{min: int, max: int}> */
    private const BOTS = [
        'Googlebot' => ['min' => 20, 'max' => 80],
        'Bingbot' => ['min' => 5, 'max' => 30],
        'Ahrefs' => ['min' => 3, 'max' => 15],
        'SEMrush' => ['min' => 2, 'max' => 10],
        'Facebook' => ['min' => 1, 'max' => 8],
        'Twitter' => ['min' => 0, 'max' => 5],
        'LinkedIn' => ['min' => 0, 'max' => 4],
        'DuckDuckGo' => ['min' => 1, 'max' => 6],
        'Yandex' => ['min' => 1, 'max' => 5],
        'OpenAI' => ['min' => 0, 'max' => 8],
        'Anthropic' => ['min' => 0, 'max' => 3],
        'Perplexity' => ['min' => 0, 'max' => 4],
        'Apple' => ['min' => 0, 'max' => 3],
        'WhatsApp' => ['min' => 0, 'max' => 6],
        'Discord' => ['min' => 0, 'max' => 3],
        'Majestic' => ['min' => 0, 'max' => 2],
        'Moz' => ['min' => 0, 'max' => 2],
        'ByteDance' => ['min' => 0, 'max' => 5],
        'Other' => ['min' => 2, 'max' => 10],
    ];

    /** @var array<string, array{min: int, max: int}> */
    private const PAGES = [
        'home' => ['min' => 10, 'max' => 40],
        'how-it-works' => ['min' => 3, 'max' => 12],
        'use-cases' => ['min' => 2, 'max' => 8],
        'legal' => ['min' => 1, 'max' => 5],
        'secrets.show' => ['min' => 5, 'max' => 20],
        'admin.index' => ['min' => 1, 'max' => 4],
    ];

    /** @var array<string, array{min: int, max: int}> */
    private const COUNTRIES = [
        'FR' => ['min' => 15, 'max' => 50],
        'US' => ['min' => 3, 'max' => 15],
        'DE' => ['min' => 2, 'max' => 10],
        'GB' => ['min' => 1, 'max' => 8],
        'ES' => ['min' => 1, 'max' => 6],
        'IT' => ['min' => 0, 'max' => 5],
        'BE' => ['min' => 1, 'max' => 5],
        'CH' => ['min' => 0, 'max' => 4],
        'CA' => ['min' => 0, 'max' => 3],
        'NL' => ['min' => 0, 'max' => 3],
        'JP' => ['min' => 0, 'max' => 2],
        'BR' => ['min' => 0, 'max' => 2],
    ];

    /** @var array<string, array{min: int, max: int}> */
    private const REFERRERS = [
        'google.com' => ['min' => 5, 'max' => 25],
        'google.fr' => ['min' => 3, 'max' => 15],
        'bing.com' => ['min' => 0, 'max' => 5],
        'duckduckgo.com' => ['min' => 0, 'max' => 3],
        'github.com' => ['min' => 1, 'max' => 8],
        'reddit.com' => ['min' => 0, 'max' => 4],
        'linkedin.com' => ['min' => 0, 'max' => 3],
        't.co' => ['min' => 0, 'max' => 3],
    ];

    public function run(): void
    {
        $days = 90;
        $now = Carbon::now();

        for ($i = $days; $i >= 0; $i--) {
            $date = $now->copy()->subDays($i);
            $dateStr = $date->toDateString();
            $dayOfWeek = (int) $date->dayOfWeek;
            $isWeekend = in_array($dayOfWeek, [0, 6]);
            $trafficMultiplier = $isWeekend ? 0.4 : 1.0;

            $this->seedBots($dateStr, $trafficMultiplier);
            $this->seedDevices($dateStr, $trafficMultiplier);
            $this->seedSecretMetrics($dateStr, $trafficMultiplier);
            $this->seedPageviews($dateStr, $trafficMultiplier, $isWeekend);
            $this->seedHeatmap($dateStr, $dayOfWeek, $trafficMultiplier);
            $this->seedReferrers($dateStr, $trafficMultiplier);
            $this->seedSecrets($date, $trafficMultiplier);
        }

        $this->command->info("Seeded {$days} days of stats data.");
    }

    private function seedBots(string $date, float $multiplier): void
    {
        foreach (self::BOTS as $bot => $range) {
            $count = (int) (rand($range['min'], $range['max']) * $multiplier);

            if ($count <= 0) {
                continue;
            }

            DB::table('stats_bots')->upsert([
                'date' => $date,
                'bot_name' => $bot,
                'count' => $count,
                'created_at' => $date,
                'updated_at' => $date,
            ], ['date', 'bot_name'], ['count', 'updated_at']);
        }
    }

    private function seedDevices(string $date, float $multiplier): void
    {
        $devices = [
            'desktop' => ['min' => 15, 'max' => 45],
            'mobile' => ['min' => 8, 'max' => 25],
            'tablet' => ['min' => 0, 'max' => 5],
        ];

        foreach ($devices as $device => $range) {
            $count = (int) (rand($range['min'], $range['max']) * $multiplier);

            if ($count <= 0) {
                continue;
            }

            DB::table('stats_devices')->upsert([
                'date' => $date,
                'device_type' => $device,
                'count' => $count,
                'created_at' => $date,
                'updated_at' => $date,
            ], ['date', 'device_type'], ['count', 'updated_at']);
        }
    }

    private function seedSecretMetrics(string $date, float $multiplier): void
    {
        $textCreated = (int) (rand(2, 12) * $multiplier);
        $fileCreated = (int) (rand(0, 4) * $multiplier);
        $read = (int) (($textCreated + $fileCreated) * rand(40, 90) / 100);

        $metrics = [
            'secrets_created_text' => $textCreated,
            'secrets_created_file' => $fileCreated,
            'secrets_read' => $read,
            'secrets_expired_unread' => (int) (rand(0, 3) * $multiplier),
            'secrets_revoked' => rand(0, 1),
            'secrets_with_passphrase' => (int) ($textCreated * rand(10, 30) / 100),
            'secrets_with_max_views' => (int) ($textCreated * rand(5, 20) / 100),
            'secrets_split_mode' => rand(0, 1),
            'magic_links_requested' => (int) (rand(1, 5) * $multiplier),
            'magic_links_used' => (int) (rand(1, 4) * $multiplier),
            'total_file_size_bytes' => $fileCreated * rand(50000, 5000000),
        ];

        if ($read > 0) {
            $metrics['first_read_delay_total'] = $read * rand(300, 7200);
            $metrics['first_read_delay_count'] = $read;
        }

        foreach ($metrics as $metric => $count) {
            if ($count <= 0) {
                continue;
            }

            DB::table('stats_daily')->upsert([
                'date' => $date,
                'metric' => $metric,
                'count' => $count,
                'created_at' => $date,
                'updated_at' => $date,
            ], ['date', 'metric'], ['count', 'updated_at']);
        }
    }

    private function seedPageviews(string $date, float $multiplier, bool $isWeekend): void
    {
        $locales = ['fr', 'en', 'de', 'es'];

        foreach (self::PAGES as $page => $range) {
            $humanCount = (int) (rand($range['min'], $range['max']) * $multiplier);
            $botCount = (int) (rand(1, 5) * $multiplier);

            if ($humanCount <= 0 && $botCount <= 0) {
                continue;
            }

            $hour = rand(8, 22);
            $country = array_rand(self::COUNTRIES);
            $locale = $locales[array_rand($locales)];

            if ($humanCount > 0) {
                DB::table('stats_pageviews')->upsert([
                    'date' => $date,
                    'page' => $page,
                    'is_bot' => false,
                    'hour' => $hour,
                    'country' => $country,
                    'locale' => $locale,
                    'count' => $humanCount,
                    'created_at' => $date,
                    'updated_at' => $date,
                ], ['date', 'page', 'is_bot', 'hour', 'country', 'locale'], ['count', 'updated_at']);

                DB::table('stats_local_hours')->upsert(
                    [
                        'date' => $date,
                        'local_hour' => ($hour + rand(-2, 2) + 24) % 24,
                        'count' => $humanCount,
                        'created_at' => $date,
                        'updated_at' => $date,
                    ],
                    ['date', 'local_hour'],
                    ['count' => DB::raw("count + {$humanCount}")]
                );
            }

            if ($botCount > 0) {
                DB::table('stats_pageviews')->upsert([
                    'date' => $date,
                    'page' => $page,
                    'is_bot' => true,
                    'hour' => $hour,
                    'country' => 'US',
                    'locale' => '',
                    'count' => $botCount,
                    'created_at' => $date,
                    'updated_at' => $date,
                ], ['date', 'page', 'is_bot', 'hour', 'country', 'locale'], ['count', 'updated_at']);
            }
        }
    }

    private function seedHeatmap(string $date, int $dayOfWeek, float $multiplier): void
    {
        foreach (['secrets_created', 'secrets_read'] as $metric) {
            $hoursToSeed = rand(4, 10);

            for ($j = 0; $j < $hoursToSeed; $j++) {
                $hour = rand(7, 22);
                $count = (int) (rand(1, 5) * $multiplier);

                if ($count <= 0) {
                    continue;
                }

                DB::table('stats_heatmap')->upsert(
                    [
                        'date' => $date,
                        'day_of_week' => $dayOfWeek,
                        'hour' => $hour,
                        'metric' => $metric,
                        'count' => $count,
                        'created_at' => $date,
                        'updated_at' => $date,
                    ],
                    ['date', 'day_of_week', 'hour', 'metric'],
                    ['count' => DB::raw("count + {$count}")]
                );
            }
        }
    }

    private function seedReferrers(string $date, float $multiplier): void
    {
        foreach (self::REFERRERS as $domain => $range) {
            $count = (int) (rand($range['min'], $range['max']) * $multiplier);

            if ($count <= 0) {
                continue;
            }

            DB::table('stats_referrers')->upsert([
                'date' => $date,
                'referrer_domain' => $domain,
                'is_bot' => false,
                'count' => $count,
                'created_at' => $date,
                'updated_at' => $date,
            ], ['date', 'referrer_domain', 'is_bot'], ['count', 'updated_at']);
        }
    }

    private function seedSecrets(Carbon $date, float $multiplier): void
    {
        $emails = [
            'alice@example.com',
            'bob@example.com',
            'charlie@example.com',
            'diana@example.com',
            'eve@example.com',
        ];

        $count = (int) (rand(2, 8) * $multiplier);

        for ($j = 0; $j < $count; $j++) {
            $isRead = rand(1, 100) <= 65;
            $isExpired = $date->lt(now()->subDays(7)) && rand(1, 100) <= 40;
            $isRevoked = ! $isExpired && rand(1, 100) <= 10;
            $isFile = rand(1, 100) <= 25;
            $hasMaxViews = rand(1, 100) <= 20;
            $maxViews = $hasMaxViews ? rand(1, 5) : null;
            $readCount = $isRead ? rand(1, $maxViews ?? 3) : 0;
            $maxReached = $hasMaxViews && $readCount >= $maxViews;
            $email = $emails[array_rand($emails)];

            $factory = Secret::factory()
                ->withCreatorEmail($email);

            if ($isFile) {
                $factory = $factory->file();
            }

            if ($hasMaxViews) {
                $factory = $factory->withMaxViews($maxViews);
            }

            if ($isRead) {
                $factory = $factory->read($readCount);
            }

            if ($isRevoked) {
                $factory = $factory->revoked();
            }

            if ($isExpired) {
                $factory = $factory->expired();
            } else {
                $factory = $factory->expiresIn(rand(24, 720));
            }

            $secret = $factory->create([
                'created_at' => $date->copy()->setTimezone('UTC')->addMinutes(rand(0, 1440)),
                'updated_at' => $date->copy()->setTimezone('UTC')->addMinutes(rand(0, 1440)),
            ]);

            if ($maxReached) {
                $secret->update(['ciphertext' => null, 'file_path' => null]);
            }
        }
    }
}
