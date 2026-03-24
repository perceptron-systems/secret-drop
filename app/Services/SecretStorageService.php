<?php

namespace App\Services;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SecretStorageService
{
    private const DISK_NAME = 'secrets';

    public function disk(): Filesystem
    {
        return Storage::disk(self::DISK_NAME);
    }

    /**
     * Store an encrypted file using streaming to avoid loading into memory.
     * The file content is already encrypted client-side, we only store the blob.
     */
    public function store(string $token, UploadedFile $file): string
    {
        $path = $this->buildPath($token);

        $stream = fopen($file->getRealPath(), 'rb');

        try {
            $this->disk()->writeStream($path, $stream);
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }
        }

        return $path;
    }

    /**
     * Check if an encrypted file exists.
     */
    public function exists(string $path): bool
    {
        return $this->disk()->exists($path);
    }

    /**
     * Get the size of an encrypted file in bytes.
     */
    public function size(string $path): int
    {
        return $this->disk()->size($path);
    }

    /**
     * Stream download the encrypted file.
     * Returns the raw encrypted bytes for client-side decryption.
     *
     * Security headers:
     * - X-Content-Type-Options: nosniff - Prevents MIME type sniffing
     * - Content-Disposition: attachment - Forces download, never inline
     * - Cache-Control: no-store - Prevents caching of sensitive data
     * - X-Download-Options: noopen - IE: prevents direct open
     */
    public function download(string $path): StreamedResponse
    {
        return $this->disk()->download(
            $path,
            'encrypted',
            [
                'Content-Type' => 'application/octet-stream',
                'X-Content-Type-Options' => 'nosniff',
                'Content-Disposition' => 'attachment; filename="encrypted"',
                'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
                'Pragma' => 'no-cache',
                'X-Download-Options' => 'noopen',
            ]
        );
    }

    /**
     * Get the encrypted file contents as a stream.
     *
     * @return resource|null
     */
    public function readStream(string $path)
    {
        return $this->disk()->readStream($path);
    }

    /**
     * Delete an encrypted file and clean up empty parent directory.
     */
    public function delete(string $path): bool
    {
        if (! $this->exists($path)) {
            return false;
        }

        $this->disk()->delete($path);

        $dir = dirname($path);

        if ($dir !== '.' && $this->disk()->exists($dir) && empty($this->disk()->files($dir))) {
            $this->disk()->deleteDirectory($dir);
        }

        return true;
    }

    /**
     * Build the storage path for a secret file.
     * Partitions into subdirectories using first 2 chars of token.
     */
    private function buildPath(string $token): string
    {
        return substr($token, 0, 2).'/'.$token;
    }
}
