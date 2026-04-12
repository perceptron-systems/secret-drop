<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

/** Generates and verifies SHA-256 proof-of-work challenges to gate rate-limited requests. */
class ProofOfWorkService
{
    private const CACHE_PREFIX = 'pow:';

    /**
     * @return array{token: string, challenge: string, difficulty: int}
     */
    public function generate(string $identifier): array
    {
        $challenge = bin2hex(random_bytes(16));
        $token = bin2hex(random_bytes(16));
        $difficulty = (int) config('pow.difficulty', 20);

        Cache::put(
            self::CACHE_PREFIX.$token,
            [
                'challenge' => $challenge,
                'difficulty' => $difficulty,
                'identifier' => $identifier,
                'created_at' => now()->timestamp,
            ],
            (int) config('pow.ttl_seconds', 300)
        );

        return [
            'token' => $token,
            'challenge' => $challenge,
            'difficulty' => $difficulty,
        ];
    }

    public function verify(string $token, string $nonce, string $identifier): bool
    {
        $cacheKey = self::CACHE_PREFIX.$token;
        $data = Cache::get($cacheKey);

        if (! $data) {
            return false;
        }

        if ($data['identifier'] !== $identifier) {
            return false;
        }

        // Validate nonce format: hex string, max 32 chars
        if (! preg_match('/^[0-9a-f]{1,32}$/i', $nonce)) {
            return false;
        }

        $hash = hash('sha256', hex2bin($data['challenge']).hex2bin($nonce), true);

        if (! $this->hasLeadingZeroBits($hash, $data['difficulty'])) {
            return false;
        }

        Cache::forget($cacheKey);

        return true;
    }

    /**
     * Check that the first $bits bits of a binary hash string are zero.
     */
    private function hasLeadingZeroBits(string $hashBinary, int $bits): bool
    {
        $fullBytes = intdiv($bits, 8);
        $remainingBits = $bits % 8;

        // Check full zero bytes
        for ($i = 0; $i < $fullBytes; $i++) {
            if (ord($hashBinary[$i]) !== 0) {
                return false;
            }
        }

        // Check remaining bits in the next byte
        if ($remainingBits > 0) {
            $mask = 0xFF << (8 - $remainingBits);

            if ((ord($hashBinary[$fullBytes]) & $mask) !== 0) {
                return false;
            }
        }

        return true;
    }

    /**
     * Brute-force solve a challenge (for tests only).
     */
    public function solve(string $challenge, int $difficulty): string
    {
        $challengeBytes = hex2bin($challenge);

        for ($nonce = 0; $nonce < PHP_INT_MAX; $nonce++) {
            $nonceHex = str_pad(dechex($nonce), 8, '0', STR_PAD_LEFT);
            $hash = hash('sha256', $challengeBytes.hex2bin($nonceHex), true);

            if ($this->hasLeadingZeroBits($hash, $difficulty)) {
                return $nonceHex;
            }
        }

        throw new \RuntimeException('Could not solve PoW challenge');
    }
}
