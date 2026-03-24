<?php

namespace App\Console\Commands;

use App\Models\MagicLink;
use App\Models\Secret;
use App\Services\SecretStorageService;
use App\Services\StatsService;
use Illuminate\Console\Command;

class CleanExpiredSecretsCommand extends Command
{
    protected $signature = 'secrets:clean
                            {--dry-run : Show what would be deleted without actually deleting}';

    protected $description = 'Delete expired secrets, files, and magic links';

    public function handle(SecretStorageService $storage, StatsService $stats): int
    {
        $dryRun = $this->option('dry-run');

        $secrets = Secret::query()
            ->where(function ($query) {
                $query->where('expire_at', '<', now())
                    ->orWhereNotNull('revoked_at')
                    ->orWhere(function ($q) {
                        $q->whereNotNull('max_views')
                            ->whereColumn('read_count', '>=', 'max_views');
                    });
            })
            ->get();

        if ($secrets->isEmpty()) {
            $this->info('No expired secrets to clean.');
        } else {
            $this->info(($dryRun ? '[DRY RUN] ' : '')."Found {$secrets->count()} secrets to delete.");

            $deletedFiles = 0;
            $deletedSecrets = 0;
            $expiredUnread = 0;

            foreach ($secrets as $secret) {
                $this->line("Processing secret {$secret->token}...");

                if ($secret->isExpired() && $secret->read_count === 0 && ! $secret->isRevoked()) {
                    $expiredUnread++;
                }

                if ($secret->type === 'file' && $secret->file_path) {
                    if ($storage->exists($secret->file_path)) {
                        if (! $dryRun) {
                            $storage->delete($secret->file_path);
                        }
                        $deletedFiles++;
                        $this->line("  - Deleted file: {$secret->file_path}");
                    }
                }

                if (! $dryRun) {
                    $secret->delete();
                }
                $deletedSecrets++;
            }

            if (! $dryRun && $expiredUnread > 0) {
                $stats->increment(StatsService::SECRETS_EXPIRED_UNREAD, $expiredUnread);
            }

            $prefix = $dryRun ? '[DRY RUN] Would delete' : 'Deleted';
            $this->info("{$prefix} {$deletedSecrets} secrets and {$deletedFiles} files.");
        }

        $this->cleanMagicLinks($dryRun);

        return Command::SUCCESS;
    }

    private function cleanMagicLinks(bool $dryRun): void
    {
        $magicLinks = MagicLink::query()
            ->where(function ($query) {
                $query->where('expire_at', '<', now())
                    ->orWhereNotNull('used_at');
            })
            ->get();

        if ($magicLinks->isEmpty()) {
            $this->info('No expired magic links to clean.');

            return;
        }

        $this->info(($dryRun ? '[DRY RUN] ' : '')."Found {$magicLinks->count()} magic links to delete.");

        if (! $dryRun) {
            MagicLink::query()
                ->where(function ($query) {
                    $query->where('expire_at', '<', now())
                        ->orWhereNotNull('used_at');
                })
                ->delete();
        }

        $prefix = $dryRun ? '[DRY RUN] Would delete' : 'Deleted';
        $this->info("{$prefix} {$magicLinks->count()} magic links.");
    }
}
