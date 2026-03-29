<?php

namespace App\Console\Commands;

use App\Models\Secret;
use App\Services\SecretStorageService;
use Illuminate\Console\Command;

class AuditDataConsistencyCommand extends Command
{
    protected $signature = 'secrets:audit
                            {--fix : Attempt to fix inconsistencies}';

    protected $description = 'Audit data consistency between database and file storage';

    public function handle(SecretStorageService $storage): int
    {
        $fix = $this->option('fix');
        $issues = 0;

        $issues += $this->auditOrphanFiles($storage, $fix);
        $issues += $this->auditMissingFiles($storage, $fix);
        $issues += $this->auditDestroyedSecretsWithContent();
        $issues += $this->auditContentIntegrity();

        if ($issues === 0) {
            $this->info('No inconsistencies found.');
        } else {
            $this->warn("Found {$issues} inconsistencies.");
        }

        return $issues > 0 ? Command::FAILURE : Command::SUCCESS;
    }

    private function auditOrphanFiles(SecretStorageService $storage, bool $fix): int
    {
        $this->components->info('Checking for orphan files (on disk but not in DB)...');

        $files = $storage->disk()->allFiles();
        $validPaths = Secret::query()
            ->where('type', 'file')
            ->whereNotNull('file_path')
            ->pluck('file_path')
            ->toArray();

        $orphans = array_diff($files, $validPaths);

        foreach ($orphans as $file) {
            $size = $storage->disk()->size($file);
            $this->warn("  Orphan file: {$file} ({$this->formatBytes($size)})");

            if ($fix) {
                $storage->delete($file);
                $this->line('    -> Deleted');
            }
        }

        $count = count($orphans);

        if ($count > 0) {
            $label = $fix ? "Fixed {$count} orphan files" : "Found {$count} orphan files (use --fix to delete)";
            $this->components->warn($label);
        }

        return $count;
    }

    private function auditMissingFiles(SecretStorageService $storage, bool $fix): int
    {
        $this->components->info('Checking for missing files (in DB but not on disk)...');

        $count = 0;

        Secret::query()
            ->where('type', 'file')
            ->whereNotNull('file_path')
            ->each(function (Secret $secret) use ($storage, $fix, &$count) {
                if (! $storage->exists($secret->file_path)) {
                    $this->warn("  Missing file: {$secret->file_path} (secret {$secret->token})");
                    $count++;

                    if ($fix) {
                        $secret->file_path = null;
                        $secret->revoked_at = now();
                        $secret->save();
                        $this->line('    -> Revoked and cleared file_path');
                    }
                }
            });

        if ($count > 0) {
            $label = $fix ? "Fixed {$count} missing files (revoked)" : "Found {$count} missing files (use --fix to revoke)";
            $this->components->warn($label);
        }

        return $count;
    }

    private function auditDestroyedSecretsWithContent(): int
    {
        $this->components->info('Checking for destroyed secrets still holding content...');

        $count = Secret::query()
            ->where(function ($q) {
                $q->whereNotNull('revoked_at')
                    ->orWhere(function ($q2) {
                        $q2->whereNotNull('max_views')
                            ->whereColumn('read_count', '>=', 'max_views');
                    });
            })
            ->where(function ($q) {
                $q->whereNotNull('ciphertext')
                    ->orWhereNotNull('file_path');
            })
            ->count();

        if ($count > 0) {
            $this->components->warn("Found {$count} destroyed secrets still holding content (pending cleanup).");
        }

        return $count;
    }

    private function auditContentIntegrity(): int
    {
        $this->components->info('Checking for text secrets without ciphertext...');

        $count = Secret::query()
            ->where('type', 'text')
            ->whereNull('ciphertext')
            ->whereNull('revoked_at')
            ->where(function ($q) {
                $q->whereNull('max_views')
                    ->orWhereColumn('read_count', '<', 'max_views');
            })
            ->where(function ($q) {
                $q->whereNull('expire_at')
                    ->orWhere('expire_at', '>', now());
            })
            ->count();

        if ($count > 0) {
            $this->components->warn("Found {$count} active text secrets with missing ciphertext.");
        }

        return $count;
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 1).' MB';
        }

        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 1).' KB';
        }

        return $bytes.' B';
    }
}
