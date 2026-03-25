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

    /** Vérifie que isExpired retourne true quand le lien est expiré. */
    public function testIsExpiredReturnsTrueWhenExpired(): void
    {
        $magicLink = $this->createMagicLink(['expire_at' => now()->subMinute()]);

        $this->assertTrue($magicLink->isExpired());

        $magicLink->delete();
    }

    /** Vérifie que isExpired retourne false quand le lien est encore valide. */
    public function testIsExpiredReturnsFalseWhenNotExpired(): void
    {
        $magicLink = $this->createMagicLink(['expire_at' => now()->addMinutes(5)]);

        $this->assertFalse($magicLink->isExpired());

        $magicLink->delete();
    }

    /** Vérifie que isUsed retourne true quand le lien a été utilisé. */
    public function testIsUsedReturnsTrueWhenUsed(): void
    {
        $magicLink = $this->createMagicLink(['used_at' => now()]);

        $this->assertTrue($magicLink->isUsed());

        $magicLink->delete();
    }

    /** Vérifie que isUsed retourne false quand le lien n'a pas été utilisé. */
    public function testIsUsedReturnsFalseWhenNotUsed(): void
    {
        $magicLink = $this->createMagicLink();

        $this->assertFalse($magicLink->isUsed());

        $magicLink->delete();
    }

    /** Vérifie que isValid retourne true pour un lien frais. */
    public function testIsValidReturnsTrueForFreshLink(): void
    {
        $magicLink = $this->createMagicLink();

        $this->assertTrue($magicLink->isValid());

        $magicLink->delete();
    }

    /** Vérifie que isValid retourne false quand le lien est expiré. */
    public function testIsValidReturnsFalseWhenExpired(): void
    {
        $magicLink = $this->createMagicLink(['expire_at' => now()->subMinute()]);

        $this->assertFalse($magicLink->isValid());

        $magicLink->delete();
    }

    /** Vérifie que isValid retourne false quand le lien a été utilisé. */
    public function testIsValidReturnsFalseWhenUsed(): void
    {
        $magicLink = $this->createMagicLink(['used_at' => now()]);

        $this->assertFalse($magicLink->isValid());

        $magicLink->delete();
    }

    /** Vérifie que isValid retourne false quand le lien est expiré ET utilisé. */
    public function testIsValidReturnsFalseWhenExpiredAndUsed(): void
    {
        $magicLink = $this->createMagicLink([
            'expire_at' => now()->subMinute(),
            'used_at' => now()->subMinutes(2),
        ]);

        $this->assertFalse($magicLink->isValid());

        $magicLink->delete();
    }

    /** Vérifie que markAsUsed définit correctement used_at. */
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

    /** Vérifie que findByToken retrouve le bon lien. */
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

    /** Vérifie que findByToken retourne null pour un token invalide. */
    public function testFindByTokenReturnsNullForInvalidToken(): void
    {
        $this->createMagicLink();

        $found = MagicLink::findByToken('invalid_token_that_does_not_exist');

        $this->assertNull($found);

        MagicLink::query()->delete();
    }

    /** Vérifie que hashEmail est déterministe (insensible à la casse et aux espaces). */
    public function testHashEmailIsDeterministic(): void
    {
        $hash1 = MagicLink::hashEmail('Test@Example.com');
        $hash2 = MagicLink::hashEmail('test@example.com');
        $hash3 = MagicLink::hashEmail('  TEST@EXAMPLE.COM  ');

        $this->assertEquals($hash1, $hash2);
        $this->assertEquals($hash2, $hash3);
    }

    /** Vérifie que hashEmail produit un SHA-256 valide. */
    public function testHashEmailProducesSha256(): void
    {
        $hash = MagicLink::hashEmail('test@example.com');

        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $hash);
    }

    /** Vérifie que deux emails différents produisent des hash différents. */
    public function testHashEmailIsDifferentForDifferentEmails(): void
    {
        $hash1 = MagicLink::hashEmail('user1@example.com');
        $hash2 = MagicLink::hashEmail('user2@example.com');

        $this->assertNotEquals($hash1, $hash2);
    }

    /** Vérifie que expire_at est casté en datetime. */
    public function testExpireAtIsCastToDatetime(): void
    {
        $magicLink = $this->createMagicLink();

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $magicLink->expire_at);

        $magicLink->delete();
    }

    /** Vérifie que used_at est casté en datetime. */
    public function testUsedAtIsCastToDatetime(): void
    {
        $magicLink = $this->createMagicLink(['used_at' => now()]);

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $magicLink->used_at);

        $magicLink->delete();
    }
}
