<?php

namespace Tests\Unit;

use App\Models\Secret;
use App\Services\TokenService;
use Tests\TestCase;

class SecretModelTest extends TestCase
{
    private TokenService $tokenService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tokenService = app(TokenService::class);
    }

    private function createSecret(array $attributes = []): Secret
    {
        return Secret::create(array_merge([
            'token' => $this->tokenService->generatePublicToken(),
            'admin_token_hash' => $this->tokenService->generateAdminToken()['hash'],
            'type' => 'text',
            'cipher_meta' => ['alg' => 'AES-256-GCM', 'iv' => 'testiv', 'version' => 1],
            'ciphertext' => 'encrypted',
            'expire_at' => now()->addDay(),
        ], $attributes));
    }

    public function testIsExpiredReturnsTrueWhenExpired(): void
    {
        $secret = $this->createSecret(['expire_at' => now()->subHour()]);

        $this->assertTrue($secret->isExpired());

        $secret->delete();
    }

    public function testIsExpiredReturnsFalseWhenNotExpired(): void
    {
        $secret = $this->createSecret(['expire_at' => now()->addDay()]);

        $this->assertFalse($secret->isExpired());

        $secret->delete();
    }

    public function testIsRevokedReturnsTrueWhenRevoked(): void
    {
        $secret = $this->createSecret(['revoked_at' => now()]);

        $this->assertTrue($secret->isRevoked());

        $secret->delete();
    }

    public function testIsRevokedReturnsFalseWhenNotRevoked(): void
    {
        $secret = $this->createSecret();

        $this->assertFalse($secret->isRevoked());

        $secret->delete();
    }

    public function testHasReachedMaxViewsReturnsTrueWhenReached(): void
    {
        $secret = $this->createSecret([
            'max_views' => 3,
            'read_count' => 3,
        ]);

        $this->assertTrue($secret->hasReachedMaxViews());

        $secret->delete();
    }

    public function testHasReachedMaxViewsReturnsTrueWhenExceeded(): void
    {
        $secret = $this->createSecret([
            'max_views' => 3,
            'read_count' => 5,
        ]);

        $this->assertTrue($secret->hasReachedMaxViews());

        $secret->delete();
    }

    public function testHasReachedMaxViewsReturnsFalseWhenUnderLimit(): void
    {
        $secret = $this->createSecret([
            'max_views' => 5,
            'read_count' => 2,
        ]);

        $this->assertFalse($secret->hasReachedMaxViews());

        $secret->delete();
    }

    public function testHasReachedMaxViewsReturnsFalseWhenNoLimit(): void
    {
        $secret = $this->createSecret([
            'max_views' => null,
            'read_count' => 100,
        ]);

        $this->assertFalse($secret->hasReachedMaxViews());

        $secret->delete();
    }

    public function testIsAccessibleReturnsTrueForValidSecret(): void
    {
        $secret = $this->createSecret();

        $this->assertTrue($secret->isAccessible());

        $secret->delete();
    }

    public function testIsAccessibleReturnsFalseWhenExpired(): void
    {
        $secret = $this->createSecret(['expire_at' => now()->subHour()]);

        $this->assertFalse($secret->isAccessible());

        $secret->delete();
    }

    public function testIsAccessibleReturnsFalseWhenRevoked(): void
    {
        $secret = $this->createSecret(['revoked_at' => now()]);

        $this->assertFalse($secret->isAccessible());

        $secret->delete();
    }

    public function testIsAccessibleReturnsFalseWhenMaxViewsReached(): void
    {
        $secret = $this->createSecret([
            'max_views' => 1,
            'read_count' => 1,
        ]);

        $this->assertFalse($secret->isAccessible());

        $secret->delete();
    }

    public function testIsAccessibleReturnsTrueWhenUnderMaxViews(): void
    {
        $secret = $this->createSecret([
            'max_views' => 3,
            'read_count' => 2,
        ]);

        $this->assertTrue($secret->isAccessible());

        $secret->delete();
    }

    public function testIncrementReadCountIncrementsCounter(): void
    {
        $secret = $this->createSecret(['read_count' => 0]);

        $secret->incrementReadCount();
        $secret->refresh();

        $this->assertEquals(1, $secret->read_count);

        $secret->incrementReadCount();
        $secret->refresh();

        $this->assertEquals(2, $secret->read_count);

        $secret->delete();
    }

    public function testIncrementReadCountSetsFirstReadAt(): void
    {
        $secret = $this->createSecret(['read_count' => 0]);

        $this->assertNull($secret->first_read_at);

        $secret->incrementReadCount();
        $secret->refresh();

        $this->assertNotNull($secret->first_read_at);

        $secret->delete();
    }

    public function testIncrementReadCountDoesNotOverwriteFirstReadAt(): void
    {
        $secret = $this->createSecret(['read_count' => 0]);

        $secret->incrementReadCount();
        $secret->refresh();

        $firstReadAt = $secret->first_read_at;

        // Wait a tiny bit to ensure time difference
        usleep(10000);

        $secret->incrementReadCount();
        $secret->refresh();

        $this->assertEquals($firstReadAt->timestamp, $secret->first_read_at->timestamp);

        $secret->delete();
    }

    public function testCipherMetaIsCastToArray(): void
    {
        $secret = $this->createSecret([
            'cipher_meta' => ['alg' => 'AES-256-GCM', 'iv' => 'abc123', 'version' => 1],
        ]);

        $this->assertIsArray($secret->cipher_meta);
        $this->assertEquals('AES-256-GCM', $secret->cipher_meta['alg']);

        $secret->delete();
    }

    public function testExpireAtIsCastToDatetime(): void
    {
        $secret = $this->createSecret(['expire_at' => now()->addDays(7)]);

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $secret->expire_at);

        $secret->delete();
    }

    public function testRevokedAtIsCastToDatetime(): void
    {
        $secret = $this->createSecret(['revoked_at' => now()]);

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $secret->revoked_at);

        $secret->delete();
    }

    public function testFirstReadAtIsCastToDatetime(): void
    {
        $secret = $this->createSecret();
        $secret->incrementReadCount();
        $secret->refresh();

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $secret->first_read_at);

        $secret->delete();
    }

    public function testShouldBeDestroyedReturnsTrueWhenMaxViewsReached(): void
    {
        $secret = $this->createSecret([
            'max_views' => 3,
            'read_count' => 3,
        ]);

        $this->assertTrue($secret->shouldBeDestroyed());

        $secret->delete();
    }

    public function testShouldBeDestroyedReturnsFalseWhenUnderLimit(): void
    {
        $secret = $this->createSecret([
            'max_views' => 5,
            'read_count' => 2,
        ]);

        $this->assertFalse($secret->shouldBeDestroyed());

        $secret->delete();
    }

    public function testShouldBeDestroyedReturnsFalseForUnlimitedViews(): void
    {
        $secret = $this->createSecret([
            'max_views' => null,
            'read_count' => 100,
        ]);

        $this->assertFalse($secret->shouldBeDestroyed());

        $secret->delete();
    }

    public function testDestroyContentClearsCiphertextForTextType(): void
    {
        $secret = $this->createSecret([
            'type' => 'text',
            'ciphertext' => 'encrypted_content',
        ]);

        $this->assertNotNull($secret->ciphertext);

        $secret->destroyContent();
        $secret->refresh();

        $this->assertNull($secret->ciphertext);

        $secret->delete();
    }

    public function testDestroyContentClearsFileMetadata(): void
    {
        $secret = $this->createSecret([
            'type' => 'file',
            'ciphertext' => null,
            'file_path' => 'some/path',
        ]);

        $secret->destroyContent();
        $secret->refresh();

        $this->assertNull($secret->file_path);

        $secret->delete();
    }

    public function testHasCreatorEmailReturnsTrueWhenSet(): void
    {
        $secret = $this->createSecret([
            'creator_email_hash' => hash('sha256', 'test@example.com'),
        ]);

        $this->assertTrue($secret->hasCreatorEmail());

        $secret->delete();
    }

    public function testHasCreatorEmailReturnsFalseWhenNotSet(): void
    {
        $secret = $this->createSecret(['creator_email_hash' => null]);

        $this->assertFalse($secret->hasCreatorEmail());

        $secret->delete();
    }

    public function testVerifyCreatorEmailReturnsTrueForCorrectEmail(): void
    {
        $email = 'test@example.com';
        $secret = $this->createSecret([
            'creator_email_hash' => hash('sha256', strtolower(trim($email))),
        ]);

        $this->assertTrue($secret->verifyCreatorEmail($email));
        $this->assertTrue($secret->verifyCreatorEmail('TEST@EXAMPLE.COM'));
        $this->assertTrue($secret->verifyCreatorEmail('  test@example.com  '));

        $secret->delete();
    }

    public function testVerifyCreatorEmailReturnsFalseForWrongEmail(): void
    {
        $secret = $this->createSecret([
            'creator_email_hash' => hash('sha256', 'correct@example.com'),
        ]);

        $this->assertFalse($secret->verifyCreatorEmail('wrong@example.com'));

        $secret->delete();
    }

    public function testVerifyCreatorEmailReturnsFalseWhenNoEmailSet(): void
    {
        $secret = $this->createSecret(['creator_email_hash' => null]);

        $this->assertFalse($secret->verifyCreatorEmail('any@example.com'));

        $secret->delete();
    }

    public function testLastReadAtUpdatesOnEachRead(): void
    {
        $secret = $this->createSecret(['read_count' => 0]);

        $secret->incrementReadCount();
        $secret->refresh();
        $firstLastRead = $secret->last_read_at;

        usleep(10000);

        $secret->incrementReadCount();
        $secret->refresh();
        $secondLastRead = $secret->last_read_at;

        $this->assertTrue($secondLastRead->gte($firstLastRead));

        $secret->delete();
    }
}
