<?php

namespace Tests\Feature;

use App\Services\CaptchaService;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class ThrottleWithCaptchaTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    public function test_requests_under_limit_pass_through(): void
    {
        $payload = $this->getValidSecretPayload();

        for ($i = 0; $i < 5; $i++) {
            $response = $this->postJson('/api/secrets', $payload);
            $this->assertNotEquals(429, $response->status());
        }
    }

    public function test_requests_over_limit_return_captcha_challenge(): void
    {
        $payload = $this->getValidSecretPayload();

        // Exhaust the rate limit (10 requests per minute for secrets)
        for ($i = 0; $i < 10; $i++) {
            $this->postJson('/api/secrets', $payload);
        }

        // Next request should trigger captcha
        $response = $this->postJson('/api/secrets', $payload);

        $response->assertStatus(429);
        $response->assertJson([
            'error' => 'rate_limit_exceeded',
            'captcha_required' => true,
        ]);
        $response->assertJsonStructure([
            'captcha_token',
            'captcha_challenge',
            'retry_after',
        ]);
    }

    public function test_valid_captcha_bypasses_rate_limit(): void
    {
        $payload = $this->getValidSecretPayload();

        // Exhaust the rate limit
        for ($i = 0; $i < 10; $i++) {
            $this->postJson('/api/secrets', $payload);
        }

        // Get captcha challenge
        $response = $this->postJson('/api/secrets', $payload);
        $response->assertStatus(429);

        $captchaToken = $response->json('captcha_token');
        $captchaService = app(CaptchaService::class);
        $expectedAnswer = $captchaService->getExpectedAnswer($captchaToken);

        // Submit with valid captcha
        $payload['captcha_token'] = $captchaToken;
        $payload['captcha_answer'] = $expectedAnswer;

        $response = $this->postJson('/api/secrets', $payload);
        $response->assertStatus(201);
    }

    public function test_invalid_captcha_returns_new_challenge(): void
    {
        $payload = $this->getValidSecretPayload();

        // Exhaust the rate limit
        for ($i = 0; $i < 10; $i++) {
            $this->postJson('/api/secrets', $payload);
        }

        // Get captcha challenge
        $response = $this->postJson('/api/secrets', $payload);
        $response->assertStatus(429);
        $firstToken = $response->json('captcha_token');

        // Submit with wrong answer
        $payload['captcha_token'] = $firstToken;
        $payload['captcha_answer'] = 99999;

        $response = $this->postJson('/api/secrets', $payload);
        $response->assertStatus(429);
        $response->assertJson(['captcha_required' => true]);

        // Should get a new token
        $newToken = $response->json('captcha_token');
        $this->assertNotEquals($firstToken, $newToken);
    }

    public function test_admin_magic_link_has_rate_limiting(): void
    {
        // Exhaust the rate limit (3 per 10 minutes)
        for ($i = 0; $i < 3; $i++) {
            $this->post('/fr/admin/request-access', ['email' => 'test@example.com']);
        }

        // Next request should redirect back with captcha
        $response = $this->post('/fr/admin/request-access', ['email' => 'test@example.com']);

        $response->assertRedirect();
        $response->assertSessionHas('captcha_required', true);
        $response->assertSessionHas('captcha_token');
        $response->assertSessionHas('captcha_challenge');
    }

    public function test_admin_captcha_validation_works(): void
    {
        // Exhaust the rate limit
        for ($i = 0; $i < 3; $i++) {
            $this->post('/fr/admin/request-access', ['email' => 'test@example.com']);
        }

        // Get captcha
        $response = $this->post('/fr/admin/request-access', ['email' => 'test@example.com']);
        $captchaToken = session('captcha_token');

        $captchaService = app(CaptchaService::class);
        $expectedAnswer = $captchaService->getExpectedAnswer($captchaToken);

        // Submit with valid captcha
        $response = $this->post('/fr/admin/request-access', [
            'email' => 'test@example.com',
            'captcha_token' => $captchaToken,
            'captcha_answer' => $expectedAnswer,
        ]);

        // Should proceed (will show access-sent view)
        $response->assertViewIs('admin.access-sent');
    }

    public function test_superadmin_magic_link_has_rate_limiting(): void
    {
        // Exhaust the rate limit (3 per 10 minutes)
        for ($i = 0; $i < 3; $i++) {
            $this->post('/fr/superadmin/request-access', ['email' => 'test@example.com']);
        }

        // Next request should redirect back with captcha
        $response = $this->post('/fr/superadmin/request-access', ['email' => 'test@example.com']);

        $response->assertRedirect();
        $response->assertSessionHas('captcha_required', true);
    }

    public function test_captcha_header_is_set_on_rate_limit(): void
    {
        $payload = $this->getValidSecretPayload();

        // Exhaust the rate limit
        for ($i = 0; $i < 10; $i++) {
            $this->postJson('/api/secrets', $payload);
        }

        $response = $this->postJson('/api/secrets', $payload);

        $response->assertHeader('X-Captcha-Required', 'true');
        $response->assertHeader('Retry-After');
    }

    private function getValidSecretPayload(): array
    {
        return [
            'type' => 'text',
            'ciphertext' => base64_encode('encrypted-content'),
            'cipher_meta' => [
                'alg' => 'AES-256-GCM',
                'iv' => base64_encode(random_bytes(12)),
                'version' => 1,
            ],
            'expiration' => '7d',
        ];
    }
}
