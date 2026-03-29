<?php

namespace Tests\Feature;

use App\Models\Secret;
use Tests\TestCase;

class CreateSecretTest extends TestCase
{
    // Valid base64url test values (correct decoded byte lengths)
    private const VALID_IV = 'YWFhYWFhYWFhYWFh'; // 12 bytes

    private const VALID_SALT = 'YmJiYmJiYmJiYmJiYmJiYg'; // 16 bytes

    private const VALID_IV2 = 'Y2NjY2NjY2NjY2Nj'; // 12 bytes

    private const VALID_CIPHERTEXT = 'ZGRkZGRkZGRkZGRkZGRkZGRkZGRkZGRkZGRkZGRkZGQ'; // 32 bytes

    /** Vérifie que la racine redirige vers la page d'accueil localisée. */
    public function testRootRedirectsToLocalizedHome(): void
    {
        $response = $this->get('/');

        $response->assertRedirect();
    }

    /** Vérifie que la page de création retourne un 200. */
    public function testCreatePageReturnsSuccessfulResponse(): void
    {
        $response = $this->get('/fr');

        $response->assertStatus(200);
        $response->assertSee('Secret Drop');
    }

    /** Vérifie la création d'un secret texte avec les bons paramètres. */
    public function testCanCreateTextSecret(): void
    {
        $response = $this->postJson('/api/secrets', [
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

        $response->assertStatus(201)
            ->assertJsonStructure(['token', 'expire_at']);

        $token = $response->json('token');
        $this->assertNotEmpty($token);
        $this->assertEquals(32, strlen($token));

        $secret = Secret::where('token', $token)->first();
        $this->assertNotNull($secret);
        $this->assertEquals('text', $secret->type);
        $this->assertEquals(self::VALID_CIPHERTEXT, $secret->ciphertext);
        $this->assertEquals(1, $secret->max_views);
        $this->assertNotNull($secret->expire_at);

        $secret->delete();
    }

    /** Vérifie la création d'un secret avec toutes les options (salt, kdf, email). */
    public function testCanCreateSecretWithAllOptions(): void
    {
        $response = $this->postJson('/api/secrets', [
            'type' => 'text',
            'ciphertext' => self::VALID_CIPHERTEXT,
            'cipher_meta' => [
                'alg' => 'AES-256-GCM',
                'iv' => self::VALID_IV,
                'version' => 1,
                'salt' => self::VALID_SALT,
                'iv2' => self::VALID_IV2,
                'kdf' => 'PBKDF2-SHA256-600k',
                'has_passphrase' => true,
            ],
            'expiration' => '1h',
            'max_views' => 5,
            'creator_email' => 'test@example.com',
        ]);

        $response->assertStatus(201);

        $secret = Secret::where('token', $response->json('token'))->first();
        $this->assertEquals(5, $secret->max_views);
        $this->assertTrue($secret->verifyCreatorEmail('test@example.com'));
        $this->assertEquals(self::VALID_SALT, $secret->cipher_meta['salt']);

        $secret->delete();
    }

    /** Vérifie que le ciphertext est requis pour un secret texte. */
    public function testRequiresCiphertextForTextSecrets(): void
    {
        $response = $this->postJson('/api/secrets', [
            'type' => 'text',
            'cipher_meta' => [
                'alg' => 'AES-256-GCM',
                'iv' => self::VALID_IV,
                'version' => 1,
            ],
            'expiration' => '7d',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['ciphertext']);
    }

    /** Vérifie que cipher_meta est requis. */
    public function testRequiresCipherMeta(): void
    {
        $response = $this->postJson('/api/secrets', [
            'type' => 'text',
            'ciphertext' => self::VALID_CIPHERTEXT,
            'expiration' => '7d',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['cipher_meta']);
    }

    /** Vérifie le rejet d'une expiration invalide. */
    public function testRejectsInvalidExpiration(): void
    {
        $response = $this->postJson('/api/secrets', [
            'type' => 'text',
            'ciphertext' => self::VALID_CIPHERTEXT,
            'cipher_meta' => [
                'alg' => 'AES-256-GCM',
                'iv' => self::VALID_IV,
                'version' => 1,
            ],
            'expiration' => '1y',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['expiration']);
    }

    /** Vérifie le rejet d'un email invalide. */
    public function testRejectsInvalidEmail(): void
    {
        $response = $this->postJson('/api/secrets', [
            'type' => 'text',
            'ciphertext' => self::VALID_CIPHERTEXT,
            'cipher_meta' => [
                'alg' => 'AES-256-GCM',
                'iv' => self::VALID_IV,
                'version' => 1,
            ],
            'expiration' => '7d',
            'creator_email' => 'not-an-email',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['creator_email']);
    }

    /** Vérifie que max_views doit être dans la plage autorisée. */
    public function testMaxViewsMustBeWithinRange(): void
    {
        $response = $this->postJson('/api/secrets', [
            'type' => 'text',
            'ciphertext' => self::VALID_CIPHERTEXT,
            'cipher_meta' => [
                'alg' => 'AES-256-GCM',
                'iv' => self::VALID_IV,
                'version' => 1,
            ],
            'expiration' => '7d',
            'max_views' => 101,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['max_views']);
    }
}
