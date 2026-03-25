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

    /** Vérifie que la page de consultation retourne 200 avec un token valide. */
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

    /** Vérifie que la page retourne 200 même pour un token inexistant. */
    public function testShowPageReturns200EvenForNonExistentToken(): void
    {
        $response = $this->get('/s/nonexistenttoken12345678901');

        $response->assertStatus(200);
    }

    /** Vérifie que l'API retourne les données chiffrées du secret. */
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

    /** Vérifie que la confirmation de lecture incrémente le compteur. */
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

    /** Vérifie que l'API retourne 404 pour un secret inexistant. */
    public function testApiFetchReturns404ForNonExistentSecret(): void
    {
        $response = $this->getJson('/api/secrets/nonexistenttoken12345678901');

        $response->assertStatus(404);
        $response->assertJson(['error' => 'not_found']);
    }

    /** Vérifie que l'API retourne 404 pour un secret expiré. */
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

    /** Vérifie que l'API retourne 404 pour un secret révoqué. */
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

    /** Vérifie que l'API retourne 404 quand le max de vues est atteint. */
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

    /** Vérifie que le fetch API n'incrémente pas le compteur de lectures. */
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

    /** Vérifie que la confirmation de lecture incrémente le compteur plusieurs fois. */
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

    /** Vérifie qu'un secret usage unique devient inaccessible après lecture. */
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

    /** Vérifie que confirm-read retourne 404 pour un secret inexistant. */
    public function testApiConfirmReadReturns404ForNonExistentSecret(): void
    {
        $response = $this->postJson('/api/secrets/nonexistenttoken12345678901/read');

        $response->assertStatus(404);
        $response->assertJson(['error' => 'not_found']);
    }

    /** Vérifie que confirm-read retourne 404 pour un secret expiré. */
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

    /** Vérifie que le ciphertext est détruit après lecture d'un secret usage unique. */
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

    /** Vérifie que le ciphertext est détruit après la dernière lecture autorisée. */
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

    /** Vérifie que l'API retourne les métadonnées d'un secret fichier. */
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

    /** Vérifie que le fichier est supprimé après lecture d'un secret fichier usage unique. */
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

    /** Vérifie que la révocation supprime le ciphertext et marque le secret comme révoqué. */
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

    /** Vérifie que la révocation supprime le fichier associé. */
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

    /** Vérifie que la révocation retourne 404 pour un admin token invalide. */
    public function testRevokeReturns404ForInvalidAdminToken(): void
    {
        $response = $this->postJson('/api/secrets/invalidtoken123/revoke');

        $response->assertStatus(404);
        $response->assertJson(['error' => 'not_found']);
    }

    /** Vérifie que la révocation retourne 409 pour un secret déjà révoqué. */
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

    /** Vérifie qu'un secret révoqué est inaccessible. */
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
