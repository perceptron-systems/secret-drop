<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class PageviewService
{
    /** @var array<int, string> */
    private const BOT_PATTERNS = [
        'bot', 'crawl', 'spider', 'slurp', 'mediapartners',
        'lighthouse', 'pagespeed', 'headlesschrome', 'phantomjs',
        'curl', 'wget', 'python-', 'go-http', 'java/', 'ruby',
        'perl', 'libwww', 'apache-http', 'node-fetch', 'axios',
        'postman', 'insomnia', 'httpclient', 'scrapy', 'semrush',
        'ahrefs', 'mj12bot', 'dotbot', 'yandex', 'baidu',
    ];

    public function track(string $page, string $userAgent, string $acceptLanguage, int $tzOffset = 0, string $locale = '', string $referrer = ''): void
    {
        $now = now();
        $isBot = $this->isBot($userAgent);

        DB::table('stats_pageviews')->upsert(
            [
                'date' => $now->toDateString(),
                'page' => $page,
                'is_bot' => $isBot,
                'hour' => $now->hour,
                'country' => $this->detectCountry($acceptLanguage),
                'locale' => $locale,
                'count' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            ['date', 'page', 'is_bot', 'hour', 'country', 'locale'],
            ['count' => DB::raw('count + 1'), 'updated_at' => $now]
        );

        if (! $isBot) {
            $localHour = $this->getLocalHour($now, $tzOffset);

            DB::table('stats_local_hours')->upsert(
                [
                    'date' => $now->toDateString(),
                    'local_hour' => $localHour,
                    'count' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                ['date', 'local_hour'],
                ['count' => DB::raw('count + 1'), 'updated_at' => $now]
            );
        }

        $domain = $this->extractReferrerDomain($referrer);

        if ($domain !== '') {
            DB::table('stats_referrers')->upsert(
                [
                    'date' => $now->toDateString(),
                    'referrer_domain' => $domain,
                    'is_bot' => $isBot,
                    'count' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                ['date', 'referrer_domain', 'is_bot'],
                ['count' => DB::raw('count + 1'), 'updated_at' => $now]
            );
        }
    }

    public function isBot(string $userAgent): bool
    {
        $userAgent = strtolower($userAgent);

        if ($userAgent === '') {
            return true;
        }

        foreach (self::BOT_PATTERNS as $pattern) {
            if (str_contains($userAgent, $pattern)) {
                return true;
            }
        }

        return false;
    }

    private function detectCountry(string $acceptLanguage): string
    {
        if (empty($acceptLanguage)) {
            return 'XX';
        }

        $first = explode(',', $acceptLanguage)[0];
        $locale = trim(explode(';', $first)[0]);

        if (str_contains($locale, '-')) {
            $parts = explode('-', $locale);

            return strtoupper($parts[1]);
        }

        return strtoupper(substr($locale, 0, 2));
    }

    private function getLocalHour(Carbon $now, int $tzOffset): int
    {
        $localHour = ($now->hour - (int) ($tzOffset / 60)) % 24;

        return ($localHour + 24) % 24;
    }

    private function extractReferrerDomain(string $referrer): string
    {
        if ($referrer === '') {
            return '';
        }

        $host = parse_url($referrer, PHP_URL_HOST);

        if (! $host) {
            return '';
        }

        $host = strtolower($host);

        if (str_starts_with($host, 'www.')) {
            $host = substr($host, 4);
        }

        $appHost = parse_url(config('app.url', ''), PHP_URL_HOST);

        if ($appHost) {
            $appHost = strtolower($appHost);

            if (str_starts_with($appHost, 'www.')) {
                $appHost = substr($appHost, 4);
            }

            if ($host === $appHost) {
                return '';
            }
        }

        return mb_substr($host, 0, 100);
    }
}
