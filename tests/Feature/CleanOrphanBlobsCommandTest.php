<?php

namespace Tests\Feature;

use App\Models\Secret;
use App\Services\SecretStorageService;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class CleanOrphanBlobsCommandTest extends TestCase
{
    private SecretStorageService $storage;

    protected function setUp(): void
    {
        parent::setUp();
        $this->storage = app(SecretStorageService::class);
    }

    protected function tearDown(): void
    {
        $files = $this->storage->disk()->allFiles();
        foreach ($files as $file) {
            $this->storage->delete($file);
        }

        parent::tearDown();
    }

    /** Vérifie la suppression des blobs orphelins. */
    public function testCommandDeletesOrphanBlobs(): void
    {
        $orphanPath = 'orphan_file_'.bin2hex(random_bytes(8));
        $this->storage->disk()->put($orphanPath, 'orphan content');

        $this->assertTrue($this->storage->exists($orphanPath));

        $this->artisan('secrets:clean-blobs')
            ->expectsOutputToContain('1 orphan blobs')
            ->assertExitCode(0);

        $this->assertFalse($this->storage->exists($orphanPath));
    }

    /** Vérifie que les fichiers avec secret associé sont conservés. */
    public function testCommandKeepsFilesWithCorrespondingSecrets(): void
    {
        $token = bin2hex(random_bytes(16));
        $file = UploadedFile::fake()->create('test.enc', 100);
        $filePath = $this->storage->store($token, $file);

        Secret::create([
            'token' => $token,
            'admin_token_hash' => hash('sha256', bin2hex(random_bytes(16))),
            'type' => 'file',
            'cipher_meta' => ['alg' => 'AES-256-GCM', 'iv' => 'test', 'version' => 1],
            'file_path' => $filePath,
            'expire_at' => now()->addDays(7),
        ]);

        $this->assertTrue($this->storage->exists($filePath));

        $this->artisan('secrets:clean-blobs')
            ->expectsOutputToContain('No orphan blobs found')
            ->assertExitCode(0);

        $this->assertTrue($this->storage->exists($filePath));

        Secret::where('token', $token)->delete();
    }

    /** Vérifie la suppression des orphelins tout en gardant les valides. */
    public function testCommandDeletesOrphanButKeepsValid(): void
    {
        $validToken = bin2hex(random_bytes(16));
        $validFile = UploadedFile::fake()->create('valid.enc', 100);
        $validPath = $this->storage->store($validToken, $validFile);

        Secret::create([
            'token' => $validToken,
            'admin_token_hash' => hash('sha256', bin2hex(random_bytes(16))),
            'type' => 'file',
            'cipher_meta' => ['alg' => 'AES-256-GCM', 'iv' => 'test', 'version' => 1],
            'file_path' => $validPath,
            'expire_at' => now()->addDays(7),
        ]);

        $orphanPath = 'orphan_'.bin2hex(random_bytes(8));
        $this->storage->disk()->put($orphanPath, 'orphan content');

        $this->assertTrue($this->storage->exists($validPath));
        $this->assertTrue($this->storage->exists($orphanPath));

        $this->artisan('secrets:clean-blobs')
            ->expectsOutputToContain('1 orphan blobs')
            ->assertExitCode(0);

        $this->assertTrue($this->storage->exists($validPath));
        $this->assertFalse($this->storage->exists($orphanPath));

        Secret::where('token', $validToken)->delete();
    }

    /** Vérifie que --dry-run ne supprime pas les fichiers. */
    public function testDryRunDoesNotDeleteFiles(): void
    {
        $orphanPath = 'orphan_dry_run_'.bin2hex(random_bytes(8));
        $this->storage->disk()->put($orphanPath, 'orphan content');

        $this->assertTrue($this->storage->exists($orphanPath));

        $this->artisan('secrets:clean-blobs', ['--dry-run' => true])
            ->expectsOutputToContain('[DRY RUN]')
            ->assertExitCode(0);

        $this->assertTrue($this->storage->exists($orphanPath));

        $this->storage->delete($orphanPath);
    }

    /** Vérifie la gestion du storage vide. */
    public function testCommandHandlesEmptyStorage(): void
    {
        $files = $this->storage->disk()->allFiles();
        foreach ($files as $file) {
            $this->storage->delete($file);
        }

        $this->artisan('secrets:clean-blobs')
            ->expectsOutputToContain('No files in storage')
            ->assertExitCode(0);
    }
}
