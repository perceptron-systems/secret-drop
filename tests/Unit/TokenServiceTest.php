<?php

namespace Tests\Unit;

use App\Services\TokenService;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class TokenServiceTest extends TestCase
{
    private TokenService $tokenService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tokenService = new TokenService();
    }

    /** Vérifie que le token public fait 32 caractères. */
    #[Test]
    public function publicTokenHasCorrectLength(): void
    {
        $token = $this->tokenService->generatePublicToken();

        $this->assertSame(32, strlen($token));
    }

    /** Vérifie que le token public est en hexadécimal. */
    #[Test]
    public function publicTokenIsHexadecimal(): void
    {
        $token = $this->tokenService->generatePublicToken();

        $this->assertMatchesRegularExpression('/^[a-f0-9]{32}$/', $token);
    }

    /** Vérifie que 100 tokens publics générés sont tous uniques. */
    #[Test]
    public function publicTokensAreUnique(): void
    {
        $tokens = [];
        for ($i = 0; $i < 100; $i++) {
            $tokens[] = $this->tokenService->generatePublicToken();
        }

        $this->assertCount(100, array_unique($tokens));
    }

    /** Vérifie que le token admin retourne un token et son hash. */
    #[Test]
    public function adminTokenReturnsTokenAndHash(): void
    {
        $result = $this->tokenService->generateAdminToken();

        $this->assertArrayHasKey('token', $result);
        $this->assertArrayHasKey('hash', $result);
        $this->assertSame(32, strlen($result['token']));
        $this->assertSame(64, strlen($result['hash']));
    }

    /** Vérifie que le token admin et son hash sont en hexadécimal. */
    #[Test]
    public function adminTokenIsHexadecimal(): void
    {
        $result = $this->tokenService->generateAdminToken();

        $this->assertMatchesRegularExpression('/^[a-f0-9]{32}$/', $result['token']);
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $result['hash']);
    }

    /** Vérifie que le token magic link retourne un token et son hash. */
    #[Test]
    public function magicLinkTokenReturnsTokenAndHash(): void
    {
        $result = $this->tokenService->generateMagicLinkToken();

        $this->assertArrayHasKey('token', $result);
        $this->assertArrayHasKey('hash', $result);
    }

    /** Vérifie que le token magic link a les bonnes longueurs. */
    #[Test]
    public function magicLinkTokenHasCorrectLength(): void
    {
        $result = $this->tokenService->generateMagicLinkToken();

        $this->assertSame(32, strlen($result['token']));
        $this->assertSame(64, strlen($result['hash']));
    }

    /** Vérifie que le hash du magic link est un SHA-256 valide. */
    #[Test]
    public function magicLinkHashIsSha256(): void
    {
        $result = $this->tokenService->generateMagicLinkToken();

        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $result['hash']);
    }

    /** Vérifie que hashToken produit un SHA-256 correct. */
    #[Test]
    public function hashTokenProducesSha256(): void
    {
        $token = 'test_token';
        $hash = $this->tokenService->hashToken($token);

        $this->assertSame(hash('sha256', $token), $hash);
    }

    /** Vérifie que verifyToken retourne true pour un token valide. */
    #[Test]
    public function verifyTokenReturnsTrueForValidToken(): void
    {
        $result = $this->tokenService->generateMagicLinkToken();

        $this->assertTrue(
            $this->tokenService->verifyToken($result['token'], $result['hash'])
        );
    }

    /** Vérifie que verifyToken retourne false pour un mauvais token. */
    #[Test]
    public function verifyTokenReturnsFalseForInvalidToken(): void
    {
        $result = $this->tokenService->generateMagicLinkToken();

        $this->assertFalse(
            $this->tokenService->verifyToken('wrong_token', $result['hash'])
        );
    }

    /** Vérifie que verifyToken retourne false pour un hash altéré. */
    #[Test]
    public function verifyTokenReturnsFalseForTamperedHash(): void
    {
        $result = $this->tokenService->generateMagicLinkToken();
        $tamperedHash = str_repeat('0', 64);

        $this->assertFalse(
            $this->tokenService->verifyToken($result['token'], $tamperedHash)
        );
    }
}
