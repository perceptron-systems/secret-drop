<?php

namespace Tests\Feature;

use App\Models\Secret;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ExpirationTest extends TestCase
{
    /** Vérifie l'expiration à 1 heure. */
    public function testExpirationOneHour(): void
    {
        $response = $this->postJson('/api/secrets', $this->getValidPayload('1h'));

        $response->assertStatus(201);
        $token = $response->json('token');
        $secret = Secret::where('token', $token)->first();

        // Should expire in approximately 1 hour (55-65 minutes from now)
        $minutesUntilExpire = now()->diffInMinutes($secret->expire_at, false);
        $this->assertGreaterThanOrEqual(55, $minutesUntilExpire);
        $this->assertLessThanOrEqual(65, $minutesUntilExpire);

        $secret->delete();
    }

    /** Vérifie l'expiration à 1 jour. */
    public function testExpirationOneDay(): void
    {
        $response = $this->postJson('/api/secrets', $this->getValidPayload('1d'));

        $response->assertStatus(201);
        $token = $response->json('token');
        $secret = Secret::where('token', $token)->first();

        // Should expire in approximately 24 hours (23-25 hours from now)
        $hoursUntilExpire = now()->diffInHours($secret->expire_at, false);
        $this->assertGreaterThanOrEqual(23, $hoursUntilExpire);
        $this->assertLessThanOrEqual(25, $hoursUntilExpire);

        $secret->delete();
    }

    /** Vérifie l'expiration à 7 jours. */
    public function testExpirationSevenDays(): void
    {
        $response = $this->postJson('/api/secrets', $this->getValidPayload('7d'));

        $response->assertStatus(201);
        $token = $response->json('token');
        $secret = Secret::where('token', $token)->first();

        // Should expire in approximately 7 days (6.9-7.1 days from now)
        $daysUntilExpire = now()->diffInDays($secret->expire_at, false);
        $this->assertGreaterThanOrEqual(6, $daysUntilExpire);
        $this->assertLessThanOrEqual(8, $daysUntilExpire);

        $secret->delete();
    }

    /** Vérifie l'expiration à 30 jours. */
    public function testExpirationThirtyDays(): void
    {
        $response = $this->postJson('/api/secrets', $this->getValidPayload('30d'));

        $response->assertStatus(201);
        $token = $response->json('token');
        $secret = Secret::where('token', $token)->first();

        // Should expire in approximately 30 days (29-31 days from now)
        $daysUntilExpire = now()->diffInDays($secret->expire_at, false);
        $this->assertGreaterThanOrEqual(29, $daysUntilExpire);
        $this->assertLessThanOrEqual(31, $daysUntilExpire);

        $secret->delete();
    }

    /** Vérifie le rejet d'un format d'expiration invalide. */
    public function testInvalidExpirationFormatIsRejected(): void
    {
        $response = $this->postJson('/api/secrets', $this->getValidPayload('24h'));

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['expiration']);
    }

    /** Vérifie qu'un secret devient inaccessible après expiration. */
    public function testSecretBecomesInaccessibleAfterExpiration(): void
    {
        // Create secret that expires in 1 hour
        $response = $this->postJson('/api/secrets', $this->getValidPayload('1h'));
        $token = $response->json('token');

        // Verify it's accessible now
        $fetchResponse = $this->getJson("/api/secrets/{$token}");
        $fetchResponse->assertStatus(200);

        // Manually expire the secret
        Secret::where('token', $token)->update([
            'expire_at' => now()->subMinute(),
        ]);

        // Verify it's no longer accessible (uniform 404 to prevent enumeration)
        $expiredResponse = $this->getJson("/api/secrets/{$token}");
        $expiredResponse->assertStatus(404);
        $expiredResponse->assertJson(['error' => 'not_found']);

        // Cleanup
        Secret::where('token', $token)->delete();
    }

    /** Vérifie que confirm-read échoue après expiration. */
    public function testConfirmReadFailsAfterExpiration(): void
    {
        // Create secret
        $response = $this->postJson('/api/secrets', $this->getValidPayload('1h'));
        $token = $response->json('token');

        // Manually expire the secret
        Secret::where('token', $token)->update([
            'expire_at' => now()->subMinute(),
        ]);

        // Try to confirm read on expired secret (uniform 404)
        $readResponse = $this->postJson("/api/secrets/{$token}/read");
        $readResponse->assertStatus(404);
        $readResponse->assertJson(['error' => 'not_found']);

        // Cleanup
        Secret::where('token', $token)->delete();
    }

    /** Vérifie que la page show charge même pour un secret expiré. */
    public function testShowPageStillLoadsForExpiredSecret(): void
    {
        // Create and expire a secret
        $response = $this->postJson('/api/secrets', $this->getValidPayload('1h'));
        $token = $response->json('token');

        Secret::where('token', $token)->update([
            'expire_at' => now()->subMinute(),
        ]);

        // The show page should still return 200 (error is handled client-side)
        $showResponse = $this->get("/s/{$token}");
        $showResponse->assertStatus(200);

        // Cleanup
        Secret::where('token', $token)->delete();
    }

    /** Vérifie que expire_at est stocké correctement en base. */
    public function testExpireAtIsStoredCorrectly(): void
    {
        $response = $this->postJson('/api/secrets', $this->getValidPayload('7d'));
        $token = $response->json('token');
        $returnedExpireAt = Carbon::parse($response->json('expire_at'));

        $secret = Secret::where('token', $token)->first();

        // Database value should match returned value (within a second)
        $this->assertTrue(abs($returnedExpireAt->timestamp - $secret->expire_at->timestamp) < 2);

        // Cleanup
        $secret->delete();
    }

    /** Vérifie que expire_at est casté en Carbon. */
    public function testExpireAtIsCastToCarbon(): void
    {
        $response = $this->postJson('/api/secrets', $this->getValidPayload('7d'));
        $token = $response->json('token');

        $secret = Secret::where('token', $token)->first();

        $this->assertInstanceOf(Carbon::class, $secret->expire_at);

        // Cleanup
        $secret->delete();
    }

    /** Vérifie qu'un secret est accessible juste avant expiration. */
    public function testSecretAccessibleJustBeforeExpiration(): void
    {
        // Create secret
        $response = $this->postJson('/api/secrets', $this->getValidPayload('1h'));
        $token = $response->json('token');

        // Set expire_at to 1 second in the future
        Secret::where('token', $token)->update([
            'expire_at' => now()->addSecond(),
        ]);

        // Should still be accessible
        $fetchResponse = $this->getJson("/api/secrets/{$token}");
        $fetchResponse->assertStatus(200);

        // Cleanup
        Secret::where('token', $token)->delete();
    }

    /**
     * @return array<string, mixed>
     */
    private function getValidPayload(string $expiration): array
    {
        return [
            'type' => 'text',
            'ciphertext' => 'test_content_'.uniqid(),
            'cipher_meta' => [
                'alg' => 'AES-256-GCM',
                'iv' => 'randomiv12345',
                'version' => 1,
            ],
            'expiration' => $expiration,
        ];
    }
}
