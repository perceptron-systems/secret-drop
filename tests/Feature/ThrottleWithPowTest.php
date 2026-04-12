<?php

namespace Tests\Feature;

use App\Services\ProofOfWorkService;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class ThrottleWithPowTest extends TestCase
{
    /** Route threshold is 3/min — exhaust it before testing PoW. */
    private const API_THRESHOLD = 3;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        config(['pow.difficulty' => 8]); // Low difficulty for fast tests
    }

    private function exhaustRateLimit(array $payload): void
    {
        for ($i = 0; $i < self::API_THRESHOLD; $i++) {
            $this->postJson('/api/secrets', $payload);
        }
    }

    /** Vérifie que les requêtes sous la limite passent normalement. */
    public function test_requests_under_limit_pass_through(): void
    {
        $payload = $this->getValidSecretPayload();

        for ($i = 0; $i < self::API_THRESHOLD; $i++) {
            $response = $this->postJson('/api/secrets', $payload);
            $response->assertStatus(201);
        }
    }

    /** Vérifie que les requêtes au-delà de la limite retournent un challenge PoW. */
    public function test_requests_over_limit_return_pow_challenge(): void
    {
        $payload = $this->getValidSecretPayload();
        $this->exhaustRateLimit($payload);

        $response = $this->postJson('/api/secrets', $payload);

        $response->assertStatus(429);
        $response->assertJson([
            'error' => 'rate_limit_exceeded',
            'pow_required' => true,
        ]);
        $response->assertJsonStructure([
            'pow_token',
            'pow_challenge',
            'pow_difficulty',
            'retry_after',
        ]);
    }

    /** Vérifie qu'un PoW valide laisse passer la requête. */
    public function test_valid_pow_allows_request(): void
    {
        $payload = $this->getValidSecretPayload();
        $this->exhaustRateLimit($payload);

        $response = $this->postJson('/api/secrets', $payload);
        $response->assertStatus(429);

        $pow = app(ProofOfWorkService::class);
        $nonce = $pow->solve($response->json('pow_challenge'), $response->json('pow_difficulty'));

        $payload['pow_token'] = $response->json('pow_token');
        $payload['pow_nonce'] = $nonce;

        $response = $this->postJson('/api/secrets', $payload);
        $response->assertStatus(201);
    }

    /** Vérifie que le compteur ne se reset PAS après un PoW réussi. */
    public function test_counter_does_not_reset_after_valid_pow(): void
    {
        $payload = $this->getValidSecretPayload();
        $this->exhaustRateLimit($payload);

        // Solve first PoW
        $response = $this->postJson('/api/secrets', $payload);
        $response->assertStatus(429);

        $pow = app(ProofOfWorkService::class);
        $nonce = $pow->solve($response->json('pow_challenge'), $response->json('pow_difficulty'));

        $payload['pow_token'] = $response->json('pow_token');
        $payload['pow_nonce'] = $nonce;

        $this->postJson('/api/secrets', $payload)->assertStatus(201);

        // Next request without PoW should still require it (counter not reset)
        unset($payload['pow_token'], $payload['pow_nonce']);
        $response = $this->postJson('/api/secrets', $payload);
        $response->assertStatus(429);
        $response->assertJson(['pow_required' => true]);
    }

    /** Vérifie qu'un PoW invalide retourne un nouveau challenge. */
    public function test_invalid_pow_returns_new_challenge(): void
    {
        $payload = $this->getValidSecretPayload();
        $this->exhaustRateLimit($payload);

        $response = $this->postJson('/api/secrets', $payload);
        $response->assertStatus(429);
        $firstToken = $response->json('pow_token');

        $payload['pow_token'] = $firstToken;
        $payload['pow_nonce'] = 'ffffffff';

        $response = $this->postJson('/api/secrets', $payload);
        $response->assertStatus(429);
        $response->assertJson(['pow_required' => true]);

        $newToken = $response->json('pow_token');
        $this->assertNotEquals($firstToken, $newToken);
    }

    /** Vérifie le rate limiting sur les magic links admin. */
    public function test_admin_magic_link_has_rate_limiting(): void
    {
        for ($i = 0; $i < 3; $i++) {
            $this->post('/fr/admin/request-access', ['email' => 'test@example.com']);
        }

        $response = $this->post('/fr/admin/request-access', ['email' => 'test@example.com']);

        $response->assertRedirect();
        $response->assertSessionHas('pow_required', true);
        $response->assertSessionHas('pow_token');
        $response->assertSessionHas('pow_challenge');
        $response->assertSessionHas('pow_difficulty');
    }

    /** Vérifie que la validation du PoW admin fonctionne. */
    public function test_admin_pow_validation_works(): void
    {
        for ($i = 0; $i < 3; $i++) {
            $this->post('/fr/admin/request-access', ['email' => 'test@example.com']);
        }

        $response = $this->post('/fr/admin/request-access', ['email' => 'test@example.com']);
        $powToken = session('pow_token');
        $powChallenge = session('pow_challenge');
        $powDifficulty = session('pow_difficulty');

        $pow = app(ProofOfWorkService::class);
        $nonce = $pow->solve($powChallenge, $powDifficulty);

        $response = $this->post('/fr/admin/request-access', [
            'email' => 'test@example.com',
            'pow_token' => $powToken,
            'pow_nonce' => $nonce,
        ]);

        $response->assertRedirect(route('admin.accessSent'));
    }

    /** Vérifie le rate limiting sur les magic links superadmin. */
    public function test_superadmin_magic_link_has_rate_limiting(): void
    {
        for ($i = 0; $i < 3; $i++) {
            $this->post('/fr/superadmin/request-access', ['email' => 'test@example.com']);
        }

        $response = $this->post('/fr/superadmin/request-access', ['email' => 'test@example.com']);

        $response->assertRedirect();
        $response->assertSessionHas('pow_required', true);
    }

    /** Vérifie le header X-Pow-Required. */
    public function test_pow_header_is_set_on_rate_limit(): void
    {
        $payload = $this->getValidSecretPayload();
        $this->exhaustRateLimit($payload);

        $response = $this->postJson('/api/secrets', $payload);

        $response->assertHeader('X-Pow-Required', 'true');
        $response->assertHeader('Retry-After');
    }

    private function getValidSecretPayload(): array
    {
        return [
            'type' => 'text',
            'ciphertext' => 'ZGRkZGRkZGRkZGRkZGRkZGRkZGRkZGRkZGRkZGRkZGQ', // 32 bytes
            'cipher_meta' => [
                'alg' => 'AES-256-GCM',
                'iv' => 'YWFhYWFhYWFhYWFh', // 12 bytes
                'version' => 1,
            ],
            'expiration' => '7d',
        ];
    }
}
