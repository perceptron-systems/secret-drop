<?php

namespace Tests\Unit;

use App\Services\SecretStorageService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SecretStorageServiceTest extends TestCase
{
    private SecretStorageService $storage;

    protected function setUp(): void
    {
        parent::setUp();
        $this->storage = app(SecretStorageService::class);
    }

    protected function tearDown(): void
    {
        Storage::disk('secrets')->deleteDirectory('.');
        parent::tearDown();
    }

    /** Vérifie que store crée un fichier sur le disque. */
    public function testStoreCreatesFileOnDisk(): void
    {
        $file = UploadedFile::fake()->create('test.bin', 100);
        $token = 'test_token_'.uniqid();

        $path = $this->storage->store($token, $file);

        $this->assertEquals(substr($token, 0, 2).'/'.$token, $path);
        $this->assertTrue($this->storage->exists($path));
    }

    /** Vérifie que exists retourne false pour un fichier inexistant. */
    public function testExistsReturnsFalseForNonExistentFile(): void
    {
        $this->assertFalse($this->storage->exists('nonexistent_file'));
    }

    /** Vérifie que exists retourne true pour un fichier existant. */
    public function testExistsReturnsTrueForExistingFile(): void
    {
        $file = UploadedFile::fake()->create('test.bin', 50);
        $token = 'exists_test_'.uniqid();

        $path = $this->storage->store($token, $file);

        $this->assertTrue($this->storage->exists($path));
    }

    /** Vérifie que size retourne la taille du fichier. */
    public function testSizeReturnsFileSize(): void
    {
        $content = str_repeat('x', 1024);
        $file = UploadedFile::fake()->createWithContent('test.bin', $content);
        $token = 'size_test_'.uniqid();

        $path = $this->storage->store($token, $file);

        $size = $this->storage->size($path);
        $this->assertEquals(1024, $size);
    }

    /** Vérifie que delete supprime le fichier et le répertoire vide. */
    public function testDeleteRemovesFileAndEmptyDirectory(): void
    {
        $file = UploadedFile::fake()->create('test.bin', 100);
        $token = 'delete_test_'.uniqid();

        $path = $this->storage->store($token, $file);
        $this->assertTrue($this->storage->exists($path));

        $result = $this->storage->delete($path);

        $this->assertTrue($result);
        $this->assertFalse($this->storage->exists($path));
        $this->assertFalse($this->storage->disk()->exists(dirname($path)));
    }

    /** Vérifie que delete retourne false pour un fichier inexistant. */
    public function testDeleteReturnsFalseForNonExistentFile(): void
    {
        $result = $this->storage->delete('nonexistent_file');

        $this->assertFalse($result);
    }

    /** Vérifie que download retourne une réponse streamée. */
    public function testDownloadReturnsStreamedResponse(): void
    {
        $file = UploadedFile::fake()->create('test.bin', 100);
        $token = 'download_test_'.uniqid();

        $path = $this->storage->store($token, $file);

        $response = $this->storage->download($path);

        $this->assertInstanceOf(\Symfony\Component\HttpFoundation\StreamedResponse::class, $response);
    }

    /** Vérifie que readStream retourne une ressource. */
    public function testReadStreamReturnsResource(): void
    {
        $file = UploadedFile::fake()->create('test.bin', 100);
        $token = 'stream_test_'.uniqid();

        $path = $this->storage->store($token, $file);

        $stream = $this->storage->readStream($path);

        $this->assertIsResource($stream);
        fclose($stream);
    }

    /** Vérifie que disk retourne une instance Filesystem. */
    public function testDiskReturnsFilesystemInstance(): void
    {
        $disk = $this->storage->disk();

        $this->assertInstanceOf(\Illuminate\Contracts\Filesystem\Filesystem::class, $disk);
    }
}
