<?php

namespace Tests\Feature;

use App\Models\Secret;
use App\Services\SecretStorageService;
use App\Services\TokenService;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class FileSecretTest extends TestCase
{
    private TokenService $tokenService;

    private SecretStorageService $storage;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tokenService = app(TokenService::class);
        $this->storage = app(SecretStorageService::class);
    }

    /** Vérifie la création d'un secret fichier. */
    public function testCanCreateFileSecret(): void
    {
        $file = UploadedFile::fake()->create('encrypted', 1024, 'application/octet-stream');

        $response = $this->postJson('/api/secrets', [
            'type' => 'file',
            'encrypted_file' => $file,
            'cipher_meta' => json_encode([
                'alg' => 'AES-256-GCM',
                'iv' => 'testiv123456',
                'version' => 1,
            ]),
            'expiration' => '7d',
        ]);

        $response->assertStatus(201);
        $response->assertJsonStructure(['token', 'expire_at']);

        $token = $response->json('token');
        $secret = Secret::where('token', $token)->first();

        $this->assertNotNull($secret);
        $this->assertEquals('file', $secret->type);
        // filename/mime/size are encrypted in the file payload, not stored in DB
        $this->assertNotNull($secret->file_path);
        $this->assertTrue($this->storage->exists($secret->file_path));

        // Cleanup
        $this->storage->delete($secret->file_path);
        $secret->delete();
    }

    /** Vérifie que le fichier chiffré est requis. */
    public function testFileSecretRequiresEncryptedFile(): void
    {
        $response = $this->postJson('/api/secrets', [
            'type' => 'file',
            'cipher_meta' => json_encode([
                'alg' => 'AES-256-GCM',
                'iv' => 'testiv123456',
                'version' => 1,
            ]),
            'expiration' => '7d',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['encrypted_file']);
    }

    /** Vérifie le rejet d'un fichier trop volumineux (> 100 Mo). */
    public function testFileSecretRejectsFileTooLarge(): void
    {
        // 101MB file (limit is 100MB)
        $file = UploadedFile::fake()->create('encrypted', 101 * 1024, 'application/octet-stream');

        $response = $this->postJson('/api/secrets', [
            'type' => 'file',
            'encrypted_file' => $file,
            'cipher_meta' => json_encode([
                'alg' => 'AES-256-GCM',
                'iv' => 'testiv123456',
                'version' => 1,
            ]),
            'expiration' => '7d',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['encrypted_file']);
    }

    /** Vérifie le téléchargement du fichier chiffré. */
    public function testCanDownloadEncryptedFile(): void
    {
        $file = UploadedFile::fake()->create('encrypted', 512, 'application/octet-stream');

        // Create secret via API
        $createResponse = $this->postJson('/api/secrets', [
            'type' => 'file',
            'encrypted_file' => $file,
            'cipher_meta' => json_encode([
                'alg' => 'AES-256-GCM',
                'iv' => 'testiv123456',
                'version' => 1,
            ]),
            'expiration' => '7d',
        ]);

        $token = $createResponse->json('token');

        // Download the file
        $response = $this->get("/s/{$token}/download");

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/octet-stream');

        // Cleanup
        $secret = Secret::where('token', $token)->first();
        $this->storage->delete($secret->file_path);
        $secret->delete();
    }

    /** Vérifie que max_views est null par défaut pour les fichiers. */
    public function testFileSecretDefaultsToUnlimitedViews(): void
    {
        $file = UploadedFile::fake()->create('encrypted', 256);

        $response = $this->postJson('/api/secrets', [
            'type' => 'file',
            'encrypted_file' => $file,
            'cipher_meta' => json_encode([
                'alg' => 'AES-256-GCM',
                'iv' => 'testiv123456',
                'version' => 1,
            ]),
            'expiration' => '7d',
        ]);

        $token = $response->json('token');
        $secret = Secret::where('token', $token)->first();

        $this->assertNull($secret->max_views);

        // Cleanup
        $this->storage->delete($secret->file_path);
        $secret->delete();
    }
}
