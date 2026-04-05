<?php

namespace App\Services;

/** Generates cryptographically secure tokens and their SHA-256 hashes for URLs, admin access, and magic links. */
class TokenService
{
    private const TOKEN_BYTES = 16; // 128 bits

    /**
     * Generate a secure public token for secret URLs.
     * Format: 32 hex characters (128 bits of entropy)
     */
    public function generatePublicToken(): string
    {
        return bin2hex(random_bytes(self::TOKEN_BYTES));
    }

    /**
     * Generate a secure admin token and its hash.
     * The plain token is returned to the client, only the hash is stored.
     *
     * @return array{token: string, hash: string}
     */
    public function generateAdminToken(): array
    {
        $token = bin2hex(random_bytes(self::TOKEN_BYTES));

        return [
            'token' => $token,
            'hash' => $this->hashToken($token),
        ];
    }

    /**
     * Generate a magic link token and its hash.
     * The plain token is sent via email, only the hash is stored.
     *
     * @return array{token: string, hash: string}
     */
    public function generateMagicLinkToken(): array
    {
        $token = bin2hex(random_bytes(self::TOKEN_BYTES));

        return [
            'token' => $token,
            'hash' => $this->hashToken($token),
        ];
    }

    /**
     * Hash a token using SHA-256.
     * Used for storing magic link tokens securely.
     */
    public function hashToken(string $token): string
    {
        return hash('sha256', $token);
    }

    /**
     * Verify a plain token against its stored hash.
     * Uses timing-safe comparison to prevent timing attacks.
     */
    public function verifyToken(string $plainToken, string $storedHash): bool
    {
        return hash_equals($storedHash, $this->hashToken($plainToken));
    }
}
