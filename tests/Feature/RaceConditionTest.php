<?php

namespace Tests\Feature;

use App\Models\Secret;
use App\Services\TokenService;
use Tests\TestCase;

class RaceConditionTest extends TestCase
{
    private const VALID_IV = 'YWFhYWFhYWFhYWFh';

    private const VALID_CIPHERTEXT = 'ZGRkZGRkZGRkZGRkZGRkZGRkZGRkZGRkZGRkZGRkZGQ';

    private TokenService $tokenService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tokenService = app(TokenService::class);
    }

    /** Vérifie que des lectures concurrentes ne dépassent pas max_views. */
    public function testConcurrentReadsDoNotExceedMaxViews(): void
    {
        $createResponse = $this->postJson('/api/secrets', [
            'type' => 'text',
            'ciphertext' => self::VALID_CIPHERTEXT,
            'cipher_meta' => [
                'alg' => 'AES-256-GCM',
                'iv' => self::VALID_IV,
                'version' => 1,
            ],
            'expiration' => '7d',
            'max_views' => 3,
        ]);

        $token = $createResponse->json('token');

        // Simuler 6 lectures rapides (le double du max_views)
        $successCount = 0;
        for ($i = 0; $i < 6; $i++) {
            $response = $this->postJson("/api/secrets/{$token}/read");

            if ($response->status() === 200) {
                $successCount++;
            }
        }

        // Exactement 3 lectures doivent réussir
        $this->assertEquals(3, $successCount);

        // Le secret doit être inaccessible
        $this->getJson("/api/secrets/{$token}")
            ->assertStatus(404);

        // Le read_count en base ne dépasse pas max_views
        $secret = Secret::where('token', $token)->first();
        $this->assertEquals(3, $secret->read_count);
        $this->assertNull($secret->ciphertext);

        // Cleanup
        $secret->delete();
    }

    /** Vérifie que le contenu est détruit exactement au max_views atteint. */
    public function testContentDestroyedExactlyAtMaxViews(): void
    {
        $createResponse = $this->postJson('/api/secrets', [
            'type' => 'text',
            'ciphertext' => self::VALID_CIPHERTEXT,
            'cipher_meta' => [
                'alg' => 'AES-256-GCM',
                'iv' => self::VALID_IV,
                'version' => 1,
            ],
            'expiration' => '7d',
            'max_views' => 1,
        ]);

        $token = $createResponse->json('token');

        // Première lecture : succès
        $this->postJson("/api/secrets/{$token}/read")
            ->assertStatus(200)
            ->assertJson(['success' => true]);

        // Vérifier que le contenu est détruit
        $secret = Secret::where('token', $token)->first();
        $this->assertNull($secret->ciphertext);
        $this->assertEquals(1, $secret->read_count);

        // Deuxième lecture : 404
        $this->postJson("/api/secrets/{$token}/read")
            ->assertStatus(404);

        // Le read_count ne doit pas avoir augmenté
        $secret->refresh();
        $this->assertEquals(1, $secret->read_count);

        // Cleanup
        $secret->delete();
    }

    /** Vérifie que la révocation pendant une lecture est gérée proprement. */
    public function testRevocationDuringReadIsHandled(): void
    {
        $adminToken = bin2hex(random_bytes(16));
        $token = $this->tokenService->generatePublicToken();

        $secret = Secret::create([
            'token' => $token,
            'admin_token_hash' => hash('sha256', $adminToken),
            'type' => 'text',
            'cipher_meta' => [
                'alg' => 'AES-256-GCM',
                'iv' => self::VALID_IV,
                'version' => 1,
            ],
            'ciphertext' => self::VALID_CIPHERTEXT,
            'expire_at' => now()->addDays(7),
        ]);

        // Révoquer le secret
        $this->postJson("/api/secrets/{$adminToken}/revoke")
            ->assertStatus(200);

        // Tenter de lire après révocation
        $this->getJson("/api/secrets/{$token}")
            ->assertStatus(404);

        $this->postJson("/api/secrets/{$token}/read")
            ->assertStatus(404);

        // Cleanup
        $secret->delete();
    }
}
