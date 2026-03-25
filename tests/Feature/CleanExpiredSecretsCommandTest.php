<?php

namespace Tests\Feature;

use App\Models\MagicLink;
use App\Models\Secret;
use App\Services\SecretStorageService;
use App\Services\TokenService;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class CleanExpiredSecretsCommandTest extends TestCase
{
    private TokenService $tokenService;

    private SecretStorageService $storage;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tokenService = app(TokenService::class);
        $this->storage = app(SecretStorageService::class);
    }

    /** Vérifie la suppression des secrets expirés. */
    public function testDeletesExpiredSecrets(): void
    {
        $expiredSecret = Secret::create([
            'token' => $this->tokenService->generatePublicToken(),
            'admin_token_hash' => $this->tokenService->generateAdminToken()['hash'],
            'type' => 'text',
            'cipher_meta' => ['alg' => 'AES-256-GCM', 'iv' => 'testiv', 'version' => 1],
            'ciphertext' => 'expired',
            'expire_at' => now()->subHour(),
        ]);

        $validSecret = Secret::create([
            'token' => $this->tokenService->generatePublicToken(),
            'admin_token_hash' => $this->tokenService->generateAdminToken()['hash'],
            'type' => 'text',
            'cipher_meta' => ['alg' => 'AES-256-GCM', 'iv' => 'testiv', 'version' => 1],
            'ciphertext' => 'valid',
            'expire_at' => now()->addDay(),
        ]);

        $this->artisan('secrets:clean')
            ->assertSuccessful();

        $this->assertNull(Secret::find($expiredSecret->id));
        $this->assertNotNull(Secret::find($validSecret->id));

        $validSecret->delete();
    }

    /** Vérifie la suppression des secrets révoqués. */
    public function testDeletesRevokedSecrets(): void
    {
        $revokedSecret = Secret::create([
            'token' => $this->tokenService->generatePublicToken(),
            'admin_token_hash' => $this->tokenService->generateAdminToken()['hash'],
            'type' => 'text',
            'cipher_meta' => ['alg' => 'AES-256-GCM', 'iv' => 'testiv', 'version' => 1],
            'ciphertext' => 'revoked',
            'expire_at' => now()->addDay(),
            'revoked_at' => now(),
        ]);

        $this->artisan('secrets:clean')
            ->assertSuccessful();

        $this->assertNull(Secret::find($revokedSecret->id));
    }

    /** Vérifie la suppression des secrets ayant atteint le max de vues. */
    public function testDeletesMaxViewsReachedSecrets(): void
    {
        $maxViewsSecret = Secret::create([
            'token' => $this->tokenService->generatePublicToken(),
            'admin_token_hash' => $this->tokenService->generateAdminToken()['hash'],
            'type' => 'text',
            'cipher_meta' => ['alg' => 'AES-256-GCM', 'iv' => 'testiv', 'version' => 1],
            'ciphertext' => 'maxviews',
            'max_views' => 3,
            'read_count' => 3,
            'expire_at' => now()->addDay(),
        ]);

        $this->artisan('secrets:clean')
            ->assertSuccessful();

        $this->assertNull(Secret::find($maxViewsSecret->id));
    }

    /** Vérifie la suppression des secrets usage unique déjà lus. */
    public function testDeletesSingleUseSecretsAlreadyRead(): void
    {
        $singleUseSecret = Secret::create([
            'token' => $this->tokenService->generatePublicToken(),
            'admin_token_hash' => $this->tokenService->generateAdminToken()['hash'],
            'type' => 'text',
            'cipher_meta' => ['alg' => 'AES-256-GCM', 'iv' => 'testiv', 'version' => 1],
            'ciphertext' => 'singleuse',
            'max_views' => 1,
            'read_count' => 1,
            'expire_at' => now()->addDay(),
        ]);

        $this->artisan('secrets:clean')
            ->assertSuccessful();

        $this->assertNull(Secret::find($singleUseSecret->id));
    }

    /** Vérifie la suppression du fichier du storage. */
    public function testDeletesFileFromStorage(): void
    {
        $token = $this->tokenService->generatePublicToken();
        $file = UploadedFile::fake()->create('encrypted', 256);
        $filePath = $this->storage->store($token, $file);

        $fileSecret = Secret::create([
            'token' => $token,
            'admin_token_hash' => $this->tokenService->generateAdminToken()['hash'],
            'type' => 'file',
            'cipher_meta' => ['alg' => 'AES-256-GCM', 'iv' => 'testiv', 'version' => 1],
            'file_path' => $filePath,
            'expire_at' => now()->subHour(),
        ]);

        $this->assertTrue($this->storage->exists($filePath));

        $this->artisan('secrets:clean')
            ->assertSuccessful();

        $this->assertNull(Secret::find($fileSecret->id));
        $this->assertFalse($this->storage->exists($filePath));
    }

    /** Vérifie que --dry-run ne supprime rien. */
    public function testDryRunDoesNotDelete(): void
    {
        $expiredSecret = Secret::create([
            'token' => $this->tokenService->generatePublicToken(),
            'admin_token_hash' => $this->tokenService->generateAdminToken()['hash'],
            'type' => 'text',
            'cipher_meta' => ['alg' => 'AES-256-GCM', 'iv' => 'testiv', 'version' => 1],
            'ciphertext' => 'dryrun',
            'expire_at' => now()->subHour(),
        ]);

        $this->artisan('secrets:clean --dry-run')
            ->assertSuccessful();

        $this->assertNotNull(Secret::find($expiredSecret->id));

        $expiredSecret->delete();
    }

    /** Vérifie le message quand il n'y a rien à nettoyer. */
    public function testOutputsNothingToCleanMessage(): void
    {
        // Delete any existing expired secrets first
        Secret::where('expire_at', '<', now())->delete();
        Secret::whereNotNull('revoked_at')->delete();

        $this->artisan('secrets:clean')
            ->expectsOutput('No expired secrets to clean.')
            ->assertSuccessful();
    }

    /** Vérifie la suppression des magic links expirés. */
    public function testDeletesExpiredMagicLinks(): void
    {
        $expiredLink = MagicLink::create([
            'email_hash' => MagicLink::hashEmail('test@example.com'),
            'token_hash' => hash('sha256', 'expired-token'),
            'expire_at' => now()->subMinutes(10),
        ]);

        $validLink = MagicLink::create([
            'email_hash' => MagicLink::hashEmail('test2@example.com'),
            'token_hash' => hash('sha256', 'valid-token'),
            'expire_at' => now()->addMinutes(5),
        ]);

        $this->artisan('secrets:clean')
            ->assertSuccessful();

        $this->assertNull(MagicLink::find($expiredLink->id));
        $this->assertNotNull(MagicLink::find($validLink->id));

        $validLink->delete();
    }

    /** Vérifie la suppression des magic links utilisés. */
    public function testDeletesUsedMagicLinks(): void
    {
        $usedLink = MagicLink::create([
            'email_hash' => MagicLink::hashEmail('test@example.com'),
            'token_hash' => hash('sha256', 'used-token'),
            'expire_at' => now()->addMinutes(5),
            'used_at' => now(),
        ]);

        $this->artisan('secrets:clean')
            ->assertSuccessful();

        $this->assertNull(MagicLink::find($usedLink->id));
    }

    /** Vérifie que --dry-run ne supprime pas les magic links. */
    public function testDryRunDoesNotDeleteMagicLinks(): void
    {
        $expiredLink = MagicLink::create([
            'email_hash' => MagicLink::hashEmail('test@example.com'),
            'token_hash' => hash('sha256', 'dryrun-token'),
            'expire_at' => now()->subMinutes(10),
        ]);

        $this->artisan('secrets:clean --dry-run')
            ->assertSuccessful();

        $this->assertNotNull(MagicLink::find($expiredLink->id));

        $expiredLink->delete();
    }
}
