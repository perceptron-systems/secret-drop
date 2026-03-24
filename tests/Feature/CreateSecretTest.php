<?php

namespace Tests\Feature;

use App\Models\Secret;
use Tests\TestCase;

class CreateSecretTest extends TestCase
{
    public function testRootRedirectsToLocalizedHome(): void
    {
        $response = $this->get('/');

        $response->assertRedirect();
    }

    public function testCreatePageReturnsSuccessfulResponse(): void
    {
        $response = $this->get('/fr');

        $response->assertStatus(200);
        $response->assertSee('Secret Drop');
    }

    public function testCanCreateTextSecret(): void
    {
        $response = $this->postJson('/api/secrets', [
            'type' => 'text',
            'ciphertext' => 'base64encodedciphertext',
            'cipher_meta' => [
                'alg' => 'AES-256-GCM',
                'iv' => 'base64encodediv',
                'version' => 1,
            ],
            'expiration' => '7d',
            'max_views' => 1,
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure(['token', 'expire_at']);

        $token = $response->json('token');
        $this->assertNotEmpty($token);
        $this->assertEquals(32, strlen($token));

        $secret = Secret::where('token', $token)->first();
        $this->assertNotNull($secret);
        $this->assertEquals('text', $secret->type);
        $this->assertEquals('base64encodedciphertext', $secret->ciphertext);
        $this->assertEquals(1, $secret->max_views);
        $this->assertNotNull($secret->expire_at);

        $secret->delete();
    }

    public function testCanCreateSecretWithAllOptions(): void
    {
        $response = $this->postJson('/api/secrets', [
            'type' => 'text',
            'ciphertext' => 'encrypteddata',
            'cipher_meta' => [
                'alg' => 'AES-256-GCM',
                'iv' => 'randomiv',
                'version' => 1,
                'salt' => 'randomsalt',
                'kdf' => 'PBKDF2-SHA256-200k',
            ],
            'expiration' => '1h',
            'max_views' => 5,
            'creator_email' => 'test@example.com',
        ]);

        $response->assertStatus(201);

        $secret = Secret::where('token', $response->json('token'))->first();
        $this->assertEquals(5, $secret->max_views);
        $this->assertTrue($secret->verifyCreatorEmail('test@example.com'));
        $this->assertEquals('randomsalt', $secret->cipher_meta['salt']);

        $secret->delete();
    }

    public function testRequiresCiphertextForTextSecrets(): void
    {
        $response = $this->postJson('/api/secrets', [
            'type' => 'text',
            'cipher_meta' => [
                'alg' => 'AES-256-GCM',
                'iv' => 'randomiv',
                'version' => 1,
            ],
            'expiration' => '7d',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['ciphertext']);
    }

    public function testRequiresCipherMeta(): void
    {
        $response = $this->postJson('/api/secrets', [
            'type' => 'text',
            'ciphertext' => 'encrypted',
            'expiration' => '7d',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['cipher_meta']);
    }

    public function testRejectsInvalidExpiration(): void
    {
        $response = $this->postJson('/api/secrets', [
            'type' => 'text',
            'ciphertext' => 'encrypted',
            'cipher_meta' => [
                'alg' => 'AES-256-GCM',
                'iv' => 'randomiv',
                'version' => 1,
            ],
            'expiration' => '1y',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['expiration']);
    }

    public function testRejectsInvalidEmail(): void
    {
        $response = $this->postJson('/api/secrets', [
            'type' => 'text',
            'ciphertext' => 'encrypted',
            'cipher_meta' => [
                'alg' => 'AES-256-GCM',
                'iv' => 'randomiv',
                'version' => 1,
            ],
            'expiration' => '7d',
            'creator_email' => 'not-an-email',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['creator_email']);
    }

    public function testMaxViewsMustBeWithinRange(): void
    {
        $response = $this->postJson('/api/secrets', [
            'type' => 'text',
            'ciphertext' => 'encrypted',
            'cipher_meta' => [
                'alg' => 'AES-256-GCM',
                'iv' => 'randomiv',
                'version' => 1,
            ],
            'expiration' => '7d',
            'max_views' => 101,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['max_views']);
    }
}
