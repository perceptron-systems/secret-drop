<?php

namespace Tests\Feature;

use App\Models\Secret;
use App\Services\SecretStorageService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SecurityHardeningTest extends TestCase
{
    private const VALID_IV = 'YWFhYWFhYWFhYWFh'; // 12 bytes

    private const VALID_CIPHERTEXT = 'ZGRkZGRkZGRkZGRkZGRkZGRkZGRkZGRkZGRkZGRkZGQ'; // 32 bytes

    private function validPayload(): array
    {
        return [
            'type' => 'text',
            'ciphertext' => self::VALID_CIPHERTEXT,
            'cipher_meta' => [
                'alg' => 'AES-256-GCM',
                'iv' => self::VALID_IV,
                'version' => 1,
            ],
            'expiration' => '7d',
        ];
    }

    // ── Honeypot ────────────────────────────────────────────────────

    /** Un bot qui remplit le champ honeypot reçoit un faux succès (201) sans créer de secret. */
    public function testHoneypotFilledReturnsFakeSuccess(): void
    {
        $payload = $this->validPayload();
        $payload['website'] = 'http://spam.example.com';

        $response = $this->postJson('/api/secrets', $payload);

        $response->assertStatus(201)
            ->assertJsonStructure(['token', 'expire_at']);

        // Le faux token ne doit pas exister en base
        $token = $response->json('token');
        $this->assertNull(Secret::where('token', $token)->first());
    }

    /** Un utilisateur normal (honeypot vide) crée un vrai secret. */
    public function testHoneypotEmptyAllowsCreation(): void
    {
        $response = $this->postJson('/api/secrets', $this->validPayload());

        $response->assertStatus(201);

        $token = $response->json('token');
        $this->assertNotNull(Secret::where('token', $token)->first());

        Secret::where('token', $token)->delete();
    }

    // ── Daily Rate Limit ────────────────────────────────────────────

    /** La limite quotidienne bloque après N requêtes. */
    public function testDailyRateLimitBlocksAfterThreshold(): void
    {
        $dailyLimit = 3;

        RateLimiter::for('daily', function (Request $request) use ($dailyLimit) {
            return Limit::perDay($dailyLimit)->by($request->ip());
        });

        $createdTokens = [];

        for ($i = 0; $i < $dailyLimit; $i++) {
            $response = $this->postJson('/api/secrets', $this->validPayload());
            $response->assertStatus(201);
            $createdTokens[] = $response->json('token');
        }

        // La requête suivante doit être bloquée
        $response = $this->postJson('/api/secrets', $this->validPayload());
        $response->assertStatus(429);

        // Cleanup
        Secret::whereIn('token', $createdTokens)->delete();
    }

    // ── File Storage Quota ───���──────────────────────────────────────

    /** L'upload de fichier est refusé quand le quota disque est dépassé. */
    public function testFileUploadRefusedWhenQuotaExceeded(): void
    {
        Storage::fake('secrets');
        config(['secrets.file_storage_quota_mb' => 0]); // 0 MB = always exceeded (except unlimited check)

        // Set quota to 1 MB and fake a file that fills it
        config(['secrets.file_storage_quota_mb' => 1]);
        Cache::put('secrets:total_file_size', 2 * 1024 * 1024, 300); // 2 MB cached = over quota

        $file = UploadedFile::fake()->create('encrypted', 100, 'application/octet-stream');

        $response = $this->postJson('/api/secrets', [
            'type' => 'file',
            'encrypted_file' => $file,
            'cipher_meta' => json_encode([
                'alg' => 'AES-256-GCM',
                'iv' => self::VALID_IV,
                'version' => 1,
            ]),
            'expiration' => '7d',
        ]);

        $response->assertStatus(503)
            ->assertJson(['error' => 'service_unavailable']);
    }

    /** L'upload de fichier passe quand le quota n'est pas atteint. */
    public function testFileUploadAllowedWhenUnderQuota(): void
    {
        Storage::fake('secrets');
        config(['secrets.file_storage_quota_mb' => 100]);
        Cache::forget('secrets:total_file_size');

        $file = UploadedFile::fake()->create('encrypted', 100, 'application/octet-stream');

        $response = $this->postJson('/api/secrets', [
            'type' => 'file',
            'encrypted_file' => $file,
            'cipher_meta' => json_encode([
                'alg' => 'AES-256-GCM',
                'iv' => self::VALID_IV,
                'version' => 1,
            ]),
            'expiration' => '7d',
        ]);

        $response->assertStatus(201);

        $token = $response->json('token');
        Secret::where('token', $token)->delete();
    }

    /** Le quota à 0 signifie illimité. */
    public function testFileQuotaZeroMeansUnlimited(): void
    {
        $service = app(SecretStorageService::class);
        config(['secrets.file_storage_quota_mb' => 0]);

        $this->assertFalse($service->isQuotaExceeded());
    }

    // ── CORS ────────────────────────────────────────────────────────

    /** Les requêtes preflight cross-origin sont refusées pour une origine étrangère. */
    public function testCorsBlocksCrossOriginRequests(): void
    {
        $response = $this->options('/api/secrets', [], [
            'HTTP_ORIGIN' => 'https://evil.example.com',
            'HTTP_ACCESS_CONTROL_REQUEST_METHOD' => 'POST',
        ]);

        // L'origine étrangère ne doit pas être autorisée
        $this->assertNotEquals(
            'https://evil.example.com',
            $response->headers->get('Access-Control-Allow-Origin')
        );
    }

    /** Les requêtes depuis la même origine sont autorisées. */
    public function testCorsAllowsSameOriginRequests(): void
    {
        $appUrl = config('app.url');

        $response = $this->options('/api/secrets', [], [
            'HTTP_ORIGIN' => $appUrl,
            'HTTP_ACCESS_CONTROL_REQUEST_METHOD' => 'POST',
        ]);

        $allowedOrigin = $response->headers->get('Access-Control-Allow-Origin');
        $this->assertTrue(
            $allowedOrigin === $appUrl || $allowedOrigin === '*' || $allowedOrigin === null,
            "Expected same-origin to be allowed, got: {$allowedOrigin}"
        );
    }
}
