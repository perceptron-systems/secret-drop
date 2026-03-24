<?php

namespace Tests\Unit;

use App\Models\MagicLink;
use App\Services\TokenService;
use Tests\TestCase;

class MagicLinkModelTest extends TestCase
{
    private TokenService $tokenService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tokenService = app(TokenService::class);
    }

    private function createMagicLink(array $attributes = []): MagicLink
    {
        $tokenData = $this->tokenService->generateMagicLinkToken();

        return MagicLink::create(array_merge([
            'email_hash' => MagicLink::hashEmail('test@example.com'),
            'token_hash' => $tokenData['hash'],
            'expire_at' => now()->addMinutes(5),
        ], $attributes));
    }

    public function testIsExpiredReturnsTrueWhenExpired(): void
    {
        $magicLink = $this->createMagicLink(['expire_at' => now()->subMinute()]);

        $this->assertTrue($magicLink->isExpired());

        $magicLink->delete();
    }

    public function testIsExpiredReturnsFalseWhenNotExpired(): void
    {
        $magicLink = $this->createMagicLink(['expire_at' => now()->addMinutes(5)]);

        $this->assertFalse($magicLink->isExpired());

        $magicLink->delete();
    }

    public function testIsUsedReturnsTrueWhenUsed(): void
    {
        $magicLink = $this->createMagicLink(['used_at' => now()]);

        $this->assertTrue($magicLink->isUsed());

        $magicLink->delete();
    }

    public function testIsUsedReturnsFalseWhenNotUsed(): void
    {
        $magicLink = $this->createMagicLink();

        $this->assertFalse($magicLink->isUsed());

        $magicLink->delete();
    }

    public function testIsValidReturnsTrueForFreshLink(): void
    {
        $magicLink = $this->createMagicLink();

        $this->assertTrue($magicLink->isValid());

        $magicLink->delete();
    }

    public function testIsValidReturnsFalseWhenExpired(): void
    {
        $magicLink = $this->createMagicLink(['expire_at' => now()->subMinute()]);

        $this->assertFalse($magicLink->isValid());

        $magicLink->delete();
    }

    public function testIsValidReturnsFalseWhenUsed(): void
    {
        $magicLink = $this->createMagicLink(['used_at' => now()]);

        $this->assertFalse($magicLink->isValid());

        $magicLink->delete();
    }

    public function testIsValidReturnsFalseWhenExpiredAndUsed(): void
    {
        $magicLink = $this->createMagicLink([
            'expire_at' => now()->subMinute(),
            'used_at' => now()->subMinutes(2),
        ]);

        $this->assertFalse($magicLink->isValid());

        $magicLink->delete();
    }

    public function testMarkAsUsedSetsUsedAt(): void
    {
        $magicLink = $this->createMagicLink();

        $this->assertNull($magicLink->used_at);

        $magicLink->markAsUsed();
        $magicLink->refresh();

        $this->assertNotNull($magicLink->used_at);
        $this->assertTrue($magicLink->isUsed());

        $magicLink->delete();
    }

    public function testFindByTokenReturnsCorrectLink(): void
    {
        $tokenData = $this->tokenService->generateMagicLinkToken();

        $magicLink = MagicLink::create([
            'email_hash' => MagicLink::hashEmail('test@example.com'),
            'token_hash' => $tokenData['hash'],
            'expire_at' => now()->addMinutes(5),
        ]);

        $found = MagicLink::findByToken($tokenData['token']);

        $this->assertNotNull($found);
        $this->assertEquals($magicLink->id, $found->id);

        $magicLink->delete();
    }

    public function testFindByTokenReturnsNullForInvalidToken(): void
    {
        $this->createMagicLink();

        $found = MagicLink::findByToken('invalid_token_that_does_not_exist');

        $this->assertNull($found);

        MagicLink::query()->delete();
    }

    public function testHashEmailIsDeterministic(): void
    {
        $hash1 = MagicLink::hashEmail('Test@Example.com');
        $hash2 = MagicLink::hashEmail('test@example.com');
        $hash3 = MagicLink::hashEmail('  TEST@EXAMPLE.COM  ');

        $this->assertEquals($hash1, $hash2);
        $this->assertEquals($hash2, $hash3);
    }

    public function testHashEmailProducesSha256(): void
    {
        $hash = MagicLink::hashEmail('test@example.com');

        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $hash);
    }

    public function testHashEmailIsDifferentForDifferentEmails(): void
    {
        $hash1 = MagicLink::hashEmail('user1@example.com');
        $hash2 = MagicLink::hashEmail('user2@example.com');

        $this->assertNotEquals($hash1, $hash2);
    }

    public function testExpireAtIsCastToDatetime(): void
    {
        $magicLink = $this->createMagicLink();

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $magicLink->expire_at);

        $magicLink->delete();
    }

    public function testUsedAtIsCastToDatetime(): void
    {
        $magicLink = $this->createMagicLink(['used_at' => now()]);

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $magicLink->used_at);

        $magicLink->delete();
    }
}
