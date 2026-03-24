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

    #[Test]
    public function publicTokenHasCorrectLength(): void
    {
        $token = $this->tokenService->generatePublicToken();

        $this->assertSame(32, strlen($token));
    }

    #[Test]
    public function publicTokenIsHexadecimal(): void
    {
        $token = $this->tokenService->generatePublicToken();

        $this->assertMatchesRegularExpression('/^[a-f0-9]{32}$/', $token);
    }

    #[Test]
    public function publicTokensAreUnique(): void
    {
        $tokens = [];
        for ($i = 0; $i < 100; $i++) {
            $tokens[] = $this->tokenService->generatePublicToken();
        }

        $this->assertCount(100, array_unique($tokens));
    }

    #[Test]
    public function adminTokenReturnsTokenAndHash(): void
    {
        $result = $this->tokenService->generateAdminToken();

        $this->assertArrayHasKey('token', $result);
        $this->assertArrayHasKey('hash', $result);
        $this->assertSame(32, strlen($result['token']));
        $this->assertSame(64, strlen($result['hash']));
    }

    #[Test]
    public function adminTokenIsHexadecimal(): void
    {
        $result = $this->tokenService->generateAdminToken();

        $this->assertMatchesRegularExpression('/^[a-f0-9]{32}$/', $result['token']);
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $result['hash']);
    }

    #[Test]
    public function magicLinkTokenReturnsTokenAndHash(): void
    {
        $result = $this->tokenService->generateMagicLinkToken();

        $this->assertArrayHasKey('token', $result);
        $this->assertArrayHasKey('hash', $result);
    }

    #[Test]
    public function magicLinkTokenHasCorrectLength(): void
    {
        $result = $this->tokenService->generateMagicLinkToken();

        $this->assertSame(32, strlen($result['token']));
        $this->assertSame(64, strlen($result['hash']));
    }

    #[Test]
    public function magicLinkHashIsSha256(): void
    {
        $result = $this->tokenService->generateMagicLinkToken();

        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $result['hash']);
    }

    #[Test]
    public function hashTokenProducesSha256(): void
    {
        $token = 'test_token';
        $hash = $this->tokenService->hashToken($token);

        $this->assertSame(hash('sha256', $token), $hash);
    }

    #[Test]
    public function verifyTokenReturnsTrueForValidToken(): void
    {
        $result = $this->tokenService->generateMagicLinkToken();

        $this->assertTrue(
            $this->tokenService->verifyToken($result['token'], $result['hash'])
        );
    }

    #[Test]
    public function verifyTokenReturnsFalseForInvalidToken(): void
    {
        $result = $this->tokenService->generateMagicLinkToken();

        $this->assertFalse(
            $this->tokenService->verifyToken('wrong_token', $result['hash'])
        );
    }

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
