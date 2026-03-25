<?php

namespace Tests\Feature;

use App\Models\Secret;
use App\Services\SecretStorageService;
use App\Services\TokenService;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class DownloadSecurityTest extends TestCase
{
    private TokenService $tokenService;

    private SecretStorageService $storage;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tokenService = app(TokenService::class);
        $this->storage = app(SecretStorageService::class);
    }

    /** Vérifie le Content-Type application/octet-stream du téléchargement. */
    public function testDownloadHasCorrectContentType(): void
    {
        $secret = $this->createFileSecret();

        $response = $this->get("/s/{$secret->token}/download");

        $response->assertHeader('Content-Type', 'application/octet-stream');

        $this->cleanup($secret);
    }

    /** Vérifie la présence du header X-Content-Type-Options: nosniff. */
    public function testDownloadHasNoSniffHeader(): void
    {
        $secret = $this->createFileSecret();

        $response = $this->get("/s/{$secret->token}/download");

        $response->assertHeader('X-Content-Type-Options', 'nosniff');

        $this->cleanup($secret);
    }

    /** Vérifie le Content-Disposition: attachment. */
    public function testDownloadHasAttachmentDisposition(): void
    {
        $secret = $this->createFileSecret();

        $response = $this->get("/s/{$secret->token}/download");

        $contentDisposition = $response->headers->get('Content-Disposition');
        $this->assertStringContainsString('attachment', $contentDisposition);

        $this->cleanup($secret);
    }

    /** Vérifie les headers no-cache/no-store. */
    public function testDownloadHasNoCacheHeaders(): void
    {
        $secret = $this->createFileSecret();

        $response = $this->get("/s/{$secret->token}/download");

        $cacheControl = $response->headers->get('Cache-Control');
        $this->assertStringContainsString('no-store', $cacheControl);
        $this->assertStringContainsString('no-cache', $cacheControl);

        $this->cleanup($secret);
    }

    /** Vérifie le header Pragma: no-cache. */
    public function testDownloadHasPragmaNoCacheHeader(): void
    {
        $secret = $this->createFileSecret();

        $response = $this->get("/s/{$secret->token}/download");

        $response->assertHeader('Pragma', 'no-cache');

        $this->cleanup($secret);
    }

    /** Vérifie le header X-Download-Options: noopen. */
    public function testDownloadHasNoOpenHeader(): void
    {
        $secret = $this->createFileSecret();

        $response = $this->get("/s/{$secret->token}/download");

        $response->assertHeader('X-Download-Options', 'noopen');

        $this->cleanup($secret);
    }

    /** Vérifie que le nom de fichier original n'est pas exposé. */
    public function testDownloadDoesNotExposeOriginalFilename(): void
    {
        $secret = $this->createFileSecret('sensitive_document.pdf');

        $response = $this->get("/s/{$secret->token}/download");

        $contentDisposition = $response->headers->get('Content-Disposition');
        // Should use generic "encrypted" filename, not the original
        $this->assertStringContainsString('filename="encrypted"', $contentDisposition);
        $this->assertStringNotContainsString('sensitive_document', $contentDisposition);

        $this->cleanup($secret);
    }

    /** Vérifie que le download retourne 404 pour un secret texte. */
    public function testDownloadReturns404ForTextSecret(): void
    {
        $secret = Secret::create([
            'token' => $this->tokenService->generatePublicToken(),
            'admin_token_hash' => $this->tokenService->generateAdminToken()['hash'],
            'type' => 'text',
            'cipher_meta' => ['alg' => 'AES-256-GCM', 'iv' => 'testiv', 'version' => 1],
            'ciphertext' => 'not_a_file',
            'expire_at' => now()->addDay(),
        ]);

        $response = $this->get("/s/{$secret->token}/download");

        $response->assertStatus(404);

        $secret->delete();
    }

    /** Vérifie que le download retourne 410 pour un secret expiré. */
    public function testDownloadReturns410ForExpiredSecret(): void
    {
        $token = $this->tokenService->generatePublicToken();
        $file = UploadedFile::fake()->create('test.bin', 256);
        $this->storage->store($token, $file);

        $secret = Secret::create([
            'token' => $token,
            'admin_token_hash' => $this->tokenService->generateAdminToken()['hash'],
            'type' => 'file',
            'cipher_meta' => ['alg' => 'AES-256-GCM', 'iv' => 'testiv', 'version' => 1],
            'file_path' => $token,
            'expire_at' => now()->subHour(),
        ]);

        $response = $this->get("/s/{$secret->token}/download");

        $response->assertStatus(410);

        $this->cleanup($secret);
    }

    /** Vérifie que le download retourne 410 pour un secret révoqué. */
    public function testDownloadReturns410ForRevokedSecret(): void
    {
        $token = $this->tokenService->generatePublicToken();
        $file = UploadedFile::fake()->create('test.bin', 256);
        $this->storage->store($token, $file);

        $secret = Secret::create([
            'token' => $token,
            'admin_token_hash' => $this->tokenService->generateAdminToken()['hash'],
            'type' => 'file',
            'cipher_meta' => ['alg' => 'AES-256-GCM', 'iv' => 'testiv', 'version' => 1],
            'file_path' => $token,
            'expire_at' => now()->addDay(),
            'revoked_at' => now(),
        ]);

        $response = $this->get("/s/{$secret->token}/download");

        $response->assertStatus(410);

        $this->cleanup($secret);
    }

    /** Vérifie que le download retourne 404 pour un fichier manquant. */
    public function testDownloadReturns404ForMissingFile(): void
    {
        $secret = Secret::create([
            'token' => $this->tokenService->generatePublicToken(),
            'admin_token_hash' => $this->tokenService->generateAdminToken()['hash'],
            'type' => 'file',
            'cipher_meta' => ['alg' => 'AES-256-GCM', 'iv' => 'testiv', 'version' => 1],
            'file_path' => 'nonexistent_path',
            'expire_at' => now()->addDay(),
        ]);

        $response = $this->get("/s/{$secret->token}/download");

        $response->assertStatus(404);

        $secret->delete();
    }

    private function createFileSecret(): Secret
    {
        $token = $this->tokenService->generatePublicToken();
        $file = UploadedFile::fake()->create('test.bin', 256);
        $filePath = $this->storage->store($token, $file);

        return Secret::create([
            'token' => $token,
            'admin_token_hash' => $this->tokenService->generateAdminToken()['hash'],
            'type' => 'file',
            'cipher_meta' => ['alg' => 'AES-256-GCM', 'iv' => 'testiv', 'version' => 1],
            'file_path' => $filePath,
            'expire_at' => now()->addDay(),
        ]);
    }

    private function cleanup(Secret $secret): void
    {
        if ($secret->file_path && $this->storage->exists($secret->file_path)) {
            $this->storage->delete($secret->file_path);
        }
        $secret->delete();
    }
}
