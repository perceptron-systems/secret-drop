<?php

namespace Tests\Feature;

use Tests\TestCase;

class CipherValidationTest extends TestCase
{
    // Valid base64url test values (correct decoded byte lengths)
    private const VALID_IV = 'YWFhYWFhYWFhYWFh'; // 12 bytes

    private const VALID_SALT = 'YmJiYmJiYmJiYmJiYmJiYg'; // 16 bytes

    private const VALID_IV2 = 'Y2NjY2NjY2NjY2Nj'; // 12 bytes

    private const VALID_CIPHERTEXT = 'ZGRkZGRkZGRkZGRkZGRkZGRkZGRkZGRkZGRkZGRkZGQ'; // 32 bytes

    private const BAD_IV = 'ZmZmZmZmZmZmZg'; // 10 bytes (should be 12)

    private const BAD_SALT = 'Z2dnZ2dnZ2dnZ2dn'; // 12 bytes (should be 16)

    private const SHORT_CIPHERTEXT = 'ZWVlZWVlZWVlZWVl'; // 15 bytes (< 16 GCM tag)

    /**
     * @return array<string, mixed>
     */
    private function validPayload(array $overrides = []): array
    {
        $data = [
            'type' => 'text',
            'ciphertext' => self::VALID_CIPHERTEXT,
            'cipher_meta' => [
                'alg' => 'AES-256-GCM',
                'iv' => self::VALID_IV,
                'version' => 1,
            ],
            'expiration' => '7d',
        ];

        return array_replace_recursive($data, $overrides);
    }

    /** Vérifie qu'un payload valide passe la validation. */
    public function testValidPayloadPassesValidation(): void
    {
        $response = $this->postJson('/api/secrets', $this->validPayload());

        $response->assertStatus(201);
    }

    /** Vérifie le rejet d'un IV avec une taille incorrecte (10 bytes au lieu de 12). */
    public function testRejectsIvWithWrongByteLength(): void
    {
        $response = $this->postJson('/api/secrets', $this->validPayload([
            'cipher_meta' => ['iv' => self::BAD_IV],
        ]));

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['cipher_meta.iv']);
    }

    /** Vérifie le rejet d'un IV qui n'est pas du base64url valide. */
    public function testRejectsIvWithInvalidBase64UrlCharacters(): void
    {
        $response = $this->postJson('/api/secrets', $this->validPayload([
            'cipher_meta' => ['iv' => 'invalid+chars/here=='],
        ]));

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['cipher_meta.iv']);
    }

    /** Vérifie le rejet d'un salt avec une taille incorrecte (12 bytes au lieu de 16). */
    public function testRejectsSaltWithWrongByteLength(): void
    {
        $response = $this->postJson('/api/secrets', $this->validPayload([
            'cipher_meta' => [
                'salt' => self::BAD_SALT,
                'iv2' => self::VALID_IV2,
            ],
        ]));

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['cipher_meta.salt']);
    }

    /** Vérifie le rejet d'un iv2 avec une taille incorrecte. */
    public function testRejectsIv2WithWrongByteLength(): void
    {
        $response = $this->postJson('/api/secrets', $this->validPayload([
            'cipher_meta' => [
                'salt' => self::VALID_SALT,
                'iv2' => self::BAD_IV,
            ],
        ]));

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['cipher_meta.iv2']);
    }

    /** Vérifie le rejet d'un ciphertext trop court (moins de 16 bytes = tag GCM). */
    public function testRejectsCiphertextShorterThanGcmTag(): void
    {
        $response = $this->postJson('/api/secrets', $this->validPayload([
            'ciphertext' => self::SHORT_CIPHERTEXT,
        ]));

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['ciphertext']);
    }

    /** Vérifie le rejet d'un ciphertext qui n'est pas du base64url. */
    public function testRejectsCiphertextWithInvalidBase64Url(): void
    {
        $response = $this->postJson('/api/secrets', $this->validPayload([
            'ciphertext' => 'not valid base64url!!!',
        ]));

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['ciphertext']);
    }

    /** Vérifie que salt et iv2 doivent être présents ensemble. */
    public function testRejectsSaltWithoutIv2(): void
    {
        $response = $this->postJson('/api/secrets', $this->validPayload([
            'cipher_meta' => ['salt' => self::VALID_SALT],
        ]));

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['cipher_meta.salt']);
    }

    /** Vérifie que iv2 sans salt est rejeté. */
    public function testRejectsIv2WithoutSalt(): void
    {
        $response = $this->postJson('/api/secrets', $this->validPayload([
            'cipher_meta' => ['iv2' => self::VALID_IV2],
        ]));

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['cipher_meta.salt']);
    }

    /** Vérifie que has_passphrase=true sans salt/iv2 est rejeté. */
    public function testRejectsPassphraseFlagWithoutSaltAndIv2(): void
    {
        $response = $this->postJson('/api/secrets', $this->validPayload([
            'cipher_meta' => ['has_passphrase' => true],
        ]));

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['cipher_meta.has_passphrase']);
    }

    /** Vérifie qu'un payload avec passphrase complet est accepté. */
    public function testAcceptsValidPassphrasePayload(): void
    {
        $response = $this->postJson('/api/secrets', $this->validPayload([
            'cipher_meta' => [
                'salt' => self::VALID_SALT,
                'iv2' => self::VALID_IV2,
                'kdf' => 'PBKDF2-SHA256-600k',
                'has_passphrase' => true,
            ],
        ]));

        $response->assertStatus(201);
    }

    /** Vérifie que salt+iv2 sans has_passphrase est accepté (flag optionnel). */
    public function testAcceptsSaltAndIv2WithoutPassphraseFlag(): void
    {
        $response = $this->postJson('/api/secrets', $this->validPayload([
            'cipher_meta' => [
                'salt' => self::VALID_SALT,
                'iv2' => self::VALID_IV2,
                'kdf' => 'PBKDF2-SHA256-600k',
            ],
        ]));

        $response->assertStatus(201);
    }

    /** Vérifie que le ciphertext exact de 16 bytes (GCM tag seul, texte vide) est accepté. */
    public function testAcceptsCiphertextOfExactlyGcmTagLength(): void
    {
        // 16 bytes = GCM tag only (empty plaintext encryption)
        $sixteenBytes = rtrim(strtr(base64_encode(str_repeat("\x00", 16)), '+/', '-_'), '=');

        $response = $this->postJson('/api/secrets', $this->validPayload([
            'ciphertext' => $sixteenBytes,
        ]));

        $response->assertStatus(201);
    }

    /** Vérifie que le ciphertext vide est rejeté. */
    public function testRejectsEmptyCiphertext(): void
    {
        $response = $this->postJson('/api/secrets', $this->validPayload([
            'ciphertext' => '',
        ]));

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['ciphertext']);
    }
}
