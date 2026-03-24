<?php

namespace Tests\Feature;

use App\Models\Secret;
use App\Services\TokenService;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ShowSecretTest extends TestCase
{
    private TokenService $tokenService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tokenService = app(TokenService::class);
    }

    public function testShowPageReturns200WithToken(): void
    {
        $secret = Secret::create([
            'token' => $this->tokenService->generatePublicToken(),
            'admin_token_hash' => $this->tokenService->generateAdminToken()['hash'],
            'type' => 'text',
            'cipher_meta' => ['alg' => 'AES-256-GCM', 'iv' => 'testiv', 'version' => 1],
            'ciphertext' => 'encryptedcontent',
            'expire_at' => now()->addDay(),
        ]);

        $response = $this->get("/s/{$secret->token}");

        $response->assertStatus(200);
        $response->assertSee($secret->token);

        $secret->delete();
    }

    public function testShowPageReturns200EvenForNonExistentToken(): void
    {
        $response = $this->get('/s/nonexistenttoken12345678901');

        $response->assertStatus(200);
    }

    public function testApiFetchReturnsSecretData(): void
    {
        $secret = Secret::create([
            'token' => $this->tokenService->generatePublicToken(),
            'admin_token_hash' => $this->tokenService->generateAdminToken()['hash'],
            'type' => 'text',
            'cipher_meta' => ['alg' => 'AES-256-GCM', 'iv' => 'testiv', 'version' => 1],
            'ciphertext' => 'encryptedcontent',
            'expire_at' => now()->addDay(),
        ]);

        $response = $this->getJson("/api/secrets/{$secret->token}");

        $response->assertStatus(200);
        $response->assertJson([
            'type' => 'text',
            'ciphertext' => 'encryptedcontent',
            'cipher_meta' => ['alg' => 'AES-256-GCM', 'iv' => 'testiv', 'version' => 1],
            'will_be_destroyed' => false,
        ]);

        $secret->refresh();
        $this->assertEquals(0, $secret->read_count);
        $this->assertNull($secret->first_read_at);

        $secret->delete();
    }

    public function testApiConfirmReadIncrementsReadCount(): void
    {
        $secret = Secret::create([
            'token' => $this->tokenService->generatePublicToken(),
            'admin_token_hash' => $this->tokenService->generateAdminToken()['hash'],
            'type' => 'text',
            'cipher_meta' => ['alg' => 'AES-256-GCM', 'iv' => 'testiv', 'version' => 1],
            'ciphertext' => 'encryptedcontent',
            'expire_at' => now()->addDay(),
        ]);

        $response = $this->postJson("/api/secrets/{$secret->token}/read");

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $secret->refresh();
        $this->assertEquals(1, $secret->read_count);
        $this->assertNotNull($secret->first_read_at);

        $secret->delete();
    }

    public function testApiFetchReturns404ForNonExistentSecret(): void
    {
        $response = $this->getJson('/api/secrets/nonexistenttoken12345678901');

        $response->assertStatus(404);
        $response->assertJson(['error' => 'not_found']);
    }

    public function testApiFetchReturns404ForExpiredSecret(): void
    {
        $secret = Secret::create([
            'token' => $this->tokenService->generatePublicToken(),
            'admin_token_hash' => $this->tokenService->generateAdminToken()['hash'],
            'type' => 'text',
            'cipher_meta' => ['alg' => 'AES-256-GCM', 'iv' => 'testiv', 'version' => 1],
            'ciphertext' => 'expired',
            'expire_at' => now()->subHour(),
        ]);

        $response = $this->getJson("/api/secrets/{$secret->token}");

        $response->assertStatus(404);
        $response->assertJson(['error' => 'not_found']);

        $secret->delete();
    }

    public function testApiFetchReturns404ForRevokedSecret(): void
    {
        $secret = Secret::create([
            'token' => $this->tokenService->generatePublicToken(),
            'admin_token_hash' => $this->tokenService->generateAdminToken()['hash'],
            'type' => 'text',
            'cipher_meta' => ['alg' => 'AES-256-GCM', 'iv' => 'testiv', 'version' => 1],
            'ciphertext' => 'revoked',
            'expire_at' => now()->addDay(),
            'revoked_at' => now(),
        ]);

        $response = $this->getJson("/api/secrets/{$secret->token}");

        $response->assertStatus(404);
        $response->assertJson(['error' => 'not_found']);

        $secret->delete();
    }

    public function testApiFetchReturns404WhenMaxViewsReached(): void
    {
        $secret = Secret::create([
            'token' => $this->tokenService->generatePublicToken(),
            'admin_token_hash' => $this->tokenService->generateAdminToken()['hash'],
            'type' => 'text',
            'cipher_meta' => ['alg' => 'AES-256-GCM', 'iv' => 'testiv', 'version' => 1],
            'ciphertext' => 'maxviews',
            'max_views' => 1,
            'read_count' => 1,
            'expire_at' => now()->addDay(),
        ]);

        $response = $this->getJson("/api/secrets/{$secret->token}");

        $response->assertStatus(404);
        $response->assertJson(['error' => 'not_found']);

        $secret->delete();
    }

    public function testApiFetchDoesNotIncrementReadCount(): void
    {
        $secret = Secret::create([
            'token' => $this->tokenService->generatePublicToken(),
            'admin_token_hash' => $this->tokenService->generateAdminToken()['hash'],
            'type' => 'text',
            'cipher_meta' => ['alg' => 'AES-256-GCM', 'iv' => 'testiv', 'version' => 1],
            'ciphertext' => 'counting',
            'expire_at' => now()->addDay(),
        ]);

        $this->getJson("/api/secrets/{$secret->token}");
        $secret->refresh();
        $this->assertEquals(0, $secret->read_count);

        $this->getJson("/api/secrets/{$secret->token}");
        $secret->refresh();
        $this->assertEquals(0, $secret->read_count);

        $secret->delete();
    }

    public function testApiConfirmReadIncrementsReadCountMultipleTimes(): void
    {
        $secret = Secret::create([
            'token' => $this->tokenService->generatePublicToken(),
            'admin_token_hash' => $this->tokenService->generateAdminToken()['hash'],
            'type' => 'text',
            'cipher_meta' => ['alg' => 'AES-256-GCM', 'iv' => 'testiv', 'version' => 1],
            'ciphertext' => 'counting',
            'expire_at' => now()->addDay(),
        ]);

        $this->postJson("/api/secrets/{$secret->token}/read");
        $secret->refresh();
        $this->assertEquals(1, $secret->read_count);

        $this->postJson("/api/secrets/{$secret->token}/read");
        $secret->refresh();
        $this->assertEquals(2, $secret->read_count);

        $secret->delete();
    }

    public function testApiSingleUseSecretBecomesInaccessibleAfterConfirmRead(): void
    {
        $secret = Secret::create([
            'token' => $this->tokenService->generatePublicToken(),
            'admin_token_hash' => $this->tokenService->generateAdminToken()['hash'],
            'type' => 'text',
            'cipher_meta' => ['alg' => 'AES-256-GCM', 'iv' => 'testiv', 'version' => 1],
            'ciphertext' => 'singleuse',
            'max_views' => 1,
            'expire_at' => now()->addDay(),
        ]);

        $response = $this->getJson("/api/secrets/{$secret->token}");
        $response->assertStatus(200);
        $response->assertJson(['will_be_destroyed' => true]);

        $this->postJson("/api/secrets/{$secret->token}/read");

        $response = $this->getJson("/api/secrets/{$secret->token}");
        $response->assertStatus(404);

        $secret->delete();
    }

    public function testApiConfirmReadReturns404ForNonExistentSecret(): void
    {
        $response = $this->postJson('/api/secrets/nonexistenttoken12345678901/read');

        $response->assertStatus(404);
        $response->assertJson(['error' => 'not_found']);
    }

    public function testApiConfirmReadReturns404ForExpiredSecret(): void
    {
        $secret = Secret::create([
            'token' => $this->tokenService->generatePublicToken(),
            'admin_token_hash' => $this->tokenService->generateAdminToken()['hash'],
            'type' => 'text',
            'cipher_meta' => ['alg' => 'AES-256-GCM', 'iv' => 'testiv', 'version' => 1],
            'ciphertext' => 'expired',
            'expire_at' => now()->subHour(),
        ]);

        $response = $this->postJson("/api/secrets/{$secret->token}/read");

        $response->assertStatus(404);
        $response->assertJson(['error' => 'not_found']);

        $secret->delete();
    }

    public function testSingleUseSecretCiphertextIsDestroyedAfterRead(): void
    {
        $secret = Secret::create([
            'token' => $this->tokenService->generatePublicToken(),
            'admin_token_hash' => $this->tokenService->generateAdminToken()['hash'],
            'type' => 'text',
            'cipher_meta' => ['alg' => 'AES-256-GCM', 'iv' => 'testiv', 'version' => 1],
            'ciphertext' => 'topsecretdata',
            'max_views' => 1,
            'expire_at' => now()->addDay(),
        ]);

        $response = $this->getJson("/api/secrets/{$secret->token}");
        $response->assertStatus(200);
        $response->assertJson(['ciphertext' => 'topsecretdata']);

        $this->postJson("/api/secrets/{$secret->token}/read");

        $secret->refresh();
        $this->assertNull($secret->ciphertext);

        $secret->delete();
    }

    public function testMaxViewsSecretCiphertextIsDestroyedAfterLastRead(): void
    {
        $secret = Secret::create([
            'token' => $this->tokenService->generatePublicToken(),
            'admin_token_hash' => $this->tokenService->generateAdminToken()['hash'],
            'type' => 'text',
            'cipher_meta' => ['alg' => 'AES-256-GCM', 'iv' => 'testiv', 'version' => 1],
            'ciphertext' => 'limitedviewsdata',
            'max_views' => 2,
            'expire_at' => now()->addDay(),
        ]);

        $this->postJson("/api/secrets/{$secret->token}/read");
        $secret->refresh();
        $this->assertEquals('limitedviewsdata', $secret->ciphertext);

        $this->postJson("/api/secrets/{$secret->token}/read");
        $secret->refresh();
        $this->assertNull($secret->ciphertext);

        $secret->delete();
    }

    public function testApiFetchReturnsFileMetadata(): void
    {
        $secret = Secret::create([
            'token' => $this->tokenService->generatePublicToken(),
            'admin_token_hash' => $this->tokenService->generateAdminToken()['hash'],
            'type' => 'file',
            'cipher_meta' => ['alg' => 'AES-256-GCM', 'iv' => 'testiv', 'version' => 1],
            'file_path' => 'secrets/test',
            'expire_at' => now()->addDay(),
        ]);

        $response = $this->getJson("/api/secrets/{$secret->token}");

        $response->assertStatus(200);
        $response->assertJson([
            'type' => 'file',
            'cipher_meta' => ['alg' => 'AES-256-GCM', 'iv' => 'testiv', 'version' => 1],
        ]);
        // filename/mime/size are encrypted in file payload, not returned by API
        $response->assertJsonMissing(['ciphertext', 'filename', 'mime', 'size', 'encrypted_size']);

        $secret->delete();
    }

    public function testSingleUseFileSecretIsDeletedAfterRead(): void
    {
        Storage::fake('secrets');

        $token = $this->tokenService->generatePublicToken();
        $filePath = $token;

        Storage::disk('secrets')->put($filePath, 'encrypted-file-content');

        $secret = Secret::create([
            'token' => $token,
            'admin_token_hash' => $this->tokenService->generateAdminToken()['hash'],
            'type' => 'file',
            'cipher_meta' => ['alg' => 'AES-256-GCM', 'iv' => 'testiv', 'version' => 1],
            'file_path' => $filePath,
            'max_views' => 1,
            'expire_at' => now()->addDay(),
        ]);

        Storage::disk('secrets')->assertExists($filePath);

        $this->postJson("/api/secrets/{$secret->token}/read");

        Storage::disk('secrets')->assertMissing($filePath);

        $secret->delete();
    }

    public function testRevokeSecretDeletesCiphertextAndMarksRevoked(): void
    {
        $adminToken = bin2hex(random_bytes(16));
        $secret = Secret::create([
            'token' => $this->tokenService->generatePublicToken(),
            'admin_token_hash' => hash('sha256', $adminToken),
            'type' => 'text',
            'cipher_meta' => ['alg' => 'AES-256-GCM', 'iv' => 'testiv', 'version' => 1],
            'ciphertext' => 'secretdata',
            'expire_at' => now()->addDay(),
        ]);

        $response = $this->postJson("/api/secrets/{$adminToken}/revoke");

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $secret->refresh();
        $this->assertNotNull($secret->revoked_at);
        $this->assertNull($secret->ciphertext);

        $secret->delete();
    }

    public function testRevokeSecretDeletesFile(): void
    {
        Storage::fake('secrets');

        $token = $this->tokenService->generatePublicToken();
        $filePath = $token;
        $adminToken = bin2hex(random_bytes(16));

        Storage::disk('secrets')->put($filePath, 'encrypted-file-content');

        $secret = Secret::create([
            'token' => $token,
            'admin_token_hash' => hash('sha256', $adminToken),
            'type' => 'file',
            'cipher_meta' => ['alg' => 'AES-256-GCM', 'iv' => 'testiv', 'version' => 1],
            'file_path' => $filePath,
            'expire_at' => now()->addDay(),
        ]);

        Storage::disk('secrets')->assertExists($filePath);

        $this->postJson("/api/secrets/{$adminToken}/revoke");

        Storage::disk('secrets')->assertMissing($filePath);

        $secret->refresh();
        $this->assertNotNull($secret->revoked_at);

        $secret->delete();
    }

    public function testRevokeReturns404ForInvalidAdminToken(): void
    {
        $response = $this->postJson('/api/secrets/invalidtoken123/revoke');

        $response->assertStatus(404);
        $response->assertJson(['error' => 'not_found']);
    }

    public function testRevokeReturns409ForAlreadyRevokedSecret(): void
    {
        $adminToken = bin2hex(random_bytes(16));
        $secret = Secret::create([
            'token' => $this->tokenService->generatePublicToken(),
            'admin_token_hash' => hash('sha256', $adminToken),
            'type' => 'text',
            'cipher_meta' => ['alg' => 'AES-256-GCM', 'iv' => 'testiv', 'version' => 1],
            'ciphertext' => 'secretdata',
            'expire_at' => now()->addDay(),
            'revoked_at' => now(),
        ]);

        $response = $this->postJson("/api/secrets/{$adminToken}/revoke");

        $response->assertStatus(409);
        $response->assertJson(['error' => 'already_revoked']);

        $secret->delete();
    }

    public function testRevokedSecretIsInaccessible(): void
    {
        $adminToken = bin2hex(random_bytes(16));
        $secret = Secret::create([
            'token' => $this->tokenService->generatePublicToken(),
            'admin_token_hash' => hash('sha256', $adminToken),
            'type' => 'text',
            'cipher_meta' => ['alg' => 'AES-256-GCM', 'iv' => 'testiv', 'version' => 1],
            'ciphertext' => 'secretdata',
            'expire_at' => now()->addDay(),
        ]);

        $this->postJson("/api/secrets/{$adminToken}/revoke");

        $response = $this->getJson("/api/secrets/{$secret->token}");

        $response->assertStatus(404);
        $response->assertJson(['error' => 'not_found']);

        $secret->delete();
    }
}
