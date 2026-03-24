<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class CaptchaService
{
    private const CACHE_PREFIX = 'captcha:';

    private const TTL_SECONDS = 300; // 5 minutes

    /**
     * @return array{token: string, challenge: string}
     */
    public function generate(string $identifier): array
    {
        $operators = ['+', '-', '*'];
        $operator = $operators[array_rand($operators)];

        // Generate numbers based on operator to keep results reasonable
        if ($operator === '*') {
            $a = random_int(2, 9);
            $b = random_int(2, 9);
        } elseif ($operator === '-') {
            $a = random_int(10, 50);
            $b = random_int(1, $a - 1); // Ensure positive result
        } else {
            $a = random_int(1, 50);
            $b = random_int(1, 50);
        }

        $answer = match ($operator) {
            '+' => $a + $b,
            '-' => $a - $b,
            '*' => $a * $b,
        };

        $challenge = "{$a} {$operator} {$b}";
        $token = bin2hex(random_bytes(16));

        Cache::put(
            self::CACHE_PREFIX.$token,
            [
                'answer' => $answer,
                'identifier' => $identifier,
                'created_at' => now()->timestamp,
            ],
            self::TTL_SECONDS
        );

        return [
            'token' => $token,
            'challenge' => $challenge,
        ];
    }

    public function verify(string $token, int|string $answer, string $identifier): bool
    {
        $cacheKey = self::CACHE_PREFIX.$token;
        $data = Cache::get($cacheKey);

        if (! $data) {
            return false;
        }

        // Verify identifier matches (prevents token reuse across IPs)
        if ($data['identifier'] !== $identifier) {
            return false;
        }

        // Verify answer
        if ((int) $answer !== $data['answer']) {
            return false;
        }

        // Consume the token (one-time use)
        Cache::forget($cacheKey);

        return true;
    }

    public function getExpectedAnswer(string $token): ?int
    {
        $data = Cache::get(self::CACHE_PREFIX.$token);

        return $data['answer'] ?? null;
    }
}
