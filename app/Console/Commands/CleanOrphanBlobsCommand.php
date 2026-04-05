<?php

namespace App\Console\Commands;

use App\Models\Secret;
use App\Services\SecretStorageService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

/** Removes encrypted files on disk that no longer have a matching secret record. */
class CleanOrphanBlobsCommand extends Command
{
    protected $signature = 'secrets:clean-blobs
                            {--dry-run : Show what would be deleted without actually deleting}';

    protected $description = 'Delete orphan blobs (files without corresponding secrets)';

    public function handle(SecretStorageService $storage): int
    {
        $dryRun = $this->option('dry-run');

        $files = $storage->disk()->allFiles();

        if (empty($files)) {
            $this->info('No files in storage.');

            return Command::SUCCESS;
        }

        $this->info('Found '.count($files).' files in storage.');

        $validPaths = Secret::query()
            ->where('type', 'file')
            ->whereNotNull('file_path')
            ->pluck('file_path')
            ->toArray();

        $orphans = array_diff($files, $validPaths);

        if (empty($orphans)) {
            $this->info('No orphan blobs found.');

            return Command::SUCCESS;
        }

        $this->info(($dryRun ? '[DRY RUN] ' : '').'Found '.count($orphans).' orphan blobs to delete.');

        $deleted = 0;
        foreach ($orphans as $file) {
            $this->line("Processing orphan: {$file}...");

            if (! $dryRun) {
                $storage->delete($file);
            }
            $deleted++;
        }

        $prefix = $dryRun ? '[DRY RUN] Would delete' : 'Deleted';
        $this->info("{$prefix} {$deleted} orphan blobs.");

        if (! $dryRun) {
            Cache::forget('disk_usage_secrets');
        }

        return Command::SUCCESS;
    }
}
