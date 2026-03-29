<?php

namespace Tests\Feature;

use App\Models\Secret;
use App\Services\SecretStorageService;
use App\Services\StatsService;
use App\Services\TokenService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class StatsTrackingTest extends TestCase
{
    private TokenService $tokenService;

    private StatsService $statsService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tokenService = app(TokenService::class);
        $this->statsService = app(StatsService::class);
    }

    private function getMetricTotal(string $metric): int
    {
        return (int) (DB::table('stats_daily')
            ->where('metric', $metric)
            ->sum('count') ?? 0);
    }

    private function getTodayMetric(string $metric): int
    {
        return (int) (DB::table('stats_daily')
            ->where('metric', $metric)
            ->where('date', now()->toDateString())
            ->value('count') ?? 0);
    }

    /** Vérifie que la création d'un secret texte incrémente les stats. */
    public function testTextSecretCreationIncrementsStats(): void
    {
        $initialCount = $this->getMetricTotal(StatsService::SECRETS_CREATED_TEXT);

        $this->postJson('/api/secrets', [
            'type' => 'text',
            'ciphertext' => 'ZGRkZGRkZGRkZGRkZGRkZGRkZGRkZGRkZGRkZGRkZGQ',
            'cipher_meta' => [
                'alg' => 'AES-256-GCM',
                'iv' => 'YWFhYWFhYWFhYWFh',
                'version' => 1,
            ],
            'expiration' => '7d',
        ]);

        $newCount = $this->getMetricTotal(StatsService::SECRETS_CREATED_TEXT);
        $this->assertEquals($initialCount + 1, $newCount);

        // Cleanup
        Secret::orderBy('id', 'desc')->first()->delete();
    }

    /** Vérifie que la création d'un secret fichier incrémente les stats. */
    public function testFileSecretCreationIncrementsStats(): void
    {
        $initialFileCount = $this->getMetricTotal(StatsService::SECRETS_CREATED_FILE);
        $initialSizeCount = $this->getMetricTotal(StatsService::TOTAL_FILE_SIZE_BYTES);

        // Create a 256 KB file
        $file = UploadedFile::fake()->create('test.bin', 256);

        $this->postJson('/api/secrets', [
            'type' => 'file',
            'encrypted_file' => $file,
            'cipher_meta' => json_encode([
                'alg' => 'AES-256-GCM',
                'iv' => 'YWFhYWFhYWFhYWFh',
                'version' => 1,
            ]),
            'expiration' => '7d',
        ]);

        $newFileCount = $this->getMetricTotal(StatsService::SECRETS_CREATED_FILE);
        $newSizeCount = $this->getMetricTotal(StatsService::TOTAL_FILE_SIZE_BYTES);

        $this->assertEquals($initialFileCount + 1, $newFileCount);
        // Size is now calculated from the actual stored file
        $this->assertGreaterThan($initialSizeCount, $newSizeCount);

        // Cleanup
        $secret = Secret::orderBy('id', 'desc')->first();
        if ($secret->file_path) {
            app(SecretStorageService::class)->delete($secret->file_path);
        }
        $secret->delete();
    }

    /** Vérifie que la création avec max_views incrémente les stats. */
    public function testMaxViewsSecretIncrementsStats(): void
    {
        $initialCount = $this->getMetricTotal(StatsService::SECRETS_WITH_MAX_VIEWS);

        $this->postJson('/api/secrets', [
            'type' => 'text',
            'ciphertext' => 'ZGRkZGRkZGRkZGRkZGRkZGRkZGRkZGRkZGRkZGRkZGQ',
            'cipher_meta' => [
                'alg' => 'AES-256-GCM',
                'iv' => 'YWFhYWFhYWFhYWFh',
                'version' => 1,
            ],
            'expiration' => '7d',
            'max_views' => 5,
        ]);

        $newCount = $this->getMetricTotal(StatsService::SECRETS_WITH_MAX_VIEWS);
        $this->assertEquals($initialCount + 1, $newCount);

        // Cleanup
        Secret::orderBy('id', 'desc')->first()->delete();
    }

    /** Vérifie que confirm-read incrémente la stat de lectures. */
    public function testConfirmReadIncrementsSecretsReadStats(): void
    {
        $secret = $this->createTextSecret();
        $initialCount = $this->getMetricTotal(StatsService::SECRETS_READ);

        $this->postJson("/api/secrets/{$secret->token}/read");

        $newCount = $this->getMetricTotal(StatsService::SECRETS_READ);
        $this->assertEquals($initialCount + 1, $newCount);

        // Cleanup
        $secret->delete();
    }

    /** Vérifie que l'atteinte du max_views incrémente les stats. */
    public function testMaxViewsReachedIncrementsStats(): void
    {
        $initialCount = $this->getMetricTotal(StatsService::SECRETS_MAX_VIEWS_REACHED);

        // Create secret with max_views = 1
        $response = $this->postJson('/api/secrets', [
            'type' => 'text',
            'ciphertext' => 'ZGRkZGRkZGRkZGRkZGRkZGRkZGRkZGRkZGRkZGRkZGQ',
            'cipher_meta' => [
                'alg' => 'AES-256-GCM',
                'iv' => 'YWFhYWFhYWFhYWFh',
                'version' => 1,
            ],
            'expiration' => '7d',
            'max_views' => 1,
        ]);

        $token = $response->json('token');

        // Confirm read (should trigger max_views_reached)
        $this->postJson("/api/secrets/{$token}/read");

        $newCount = $this->getMetricTotal(StatsService::SECRETS_MAX_VIEWS_REACHED);
        $this->assertEquals($initialCount + 1, $newCount);

        // Cleanup
        Secret::where('token', $token)->delete();
    }

    /** Vérifie que la heatmap de création est incrémentée. */
    public function testHeatmapCreatedIsIncremented(): void
    {
        $dayOfWeek = (int) now()->format('w');
        $hour = (int) now()->format('G');

        $initialHeatmap = $this->statsService->getHeatmap(StatsService::HEATMAP_SECRETS_CREATED);
        $initialValue = $initialHeatmap[$dayOfWeek][$hour] ?? 0;

        $this->postJson('/api/secrets', [
            'type' => 'text',
            'ciphertext' => 'ZGRkZGRkZGRkZGRkZGRkZGRkZGRkZGRkZGRkZGRkZGQ',
            'cipher_meta' => [
                'alg' => 'AES-256-GCM',
                'iv' => 'YWFhYWFhYWFhYWFh',
                'version' => 1,
            ],
            'expiration' => '7d',
        ]);

        $newHeatmap = $this->statsService->getHeatmap(StatsService::HEATMAP_SECRETS_CREATED);
        $newValue = $newHeatmap[$dayOfWeek][$hour] ?? 0;

        $this->assertEquals($initialValue + 1, $newValue);

        // Cleanup
        Secret::orderBy('id', 'desc')->first()->delete();
    }

    /** Vérifie que la heatmap de lecture est incrémentée. */
    public function testHeatmapReadIsIncremented(): void
    {
        $secret = $this->createTextSecret();
        $dayOfWeek = (int) now()->format('w');
        $hour = (int) now()->format('G');

        $initialHeatmap = $this->statsService->getHeatmap(StatsService::HEATMAP_SECRETS_READ);
        $initialValue = $initialHeatmap[$dayOfWeek][$hour] ?? 0;

        $this->postJson("/api/secrets/{$secret->token}/read");

        $newHeatmap = $this->statsService->getHeatmap(StatsService::HEATMAP_SECRETS_READ);
        $newValue = $newHeatmap[$dayOfWeek][$hour] ?? 0;

        $this->assertEquals($initialValue + 1, $newValue);

        // Cleanup
        $secret->delete();
    }

    /** Vérifie que le délai de première lecture est suivi. */
    public function testFirstReadDelayIsTracked(): void
    {
        // Get initial stats
        $initialDelayCount = $this->getTodayMetric(StatsService::FIRST_READ_DELAY_COUNT);

        // Create and immediately read a secret
        $response = $this->postJson('/api/secrets', [
            'type' => 'text',
            'ciphertext' => 'ZGRkZGRkZGRkZGRkZGRkZGRkZGRkZGRkZGRkZGRkZGQ',
            'cipher_meta' => [
                'alg' => 'AES-256-GCM',
                'iv' => 'YWFhYWFhYWFhYWFh',
                'version' => 1,
            ],
            'expiration' => '7d',
        ]);

        $token = $response->json('token');

        // Confirm read
        $this->postJson("/api/secrets/{$token}/read");

        // Verify delay was tracked
        $newDelayCount = $this->getTodayMetric(StatsService::FIRST_READ_DELAY_COUNT);
        $this->assertEquals($initialDelayCount + 1, $newDelayCount);

        // Cleanup
        Secret::where('token', $token)->delete();
    }

    /** Vérifie que le délai n'est pas suivi sur les lectures suivantes. */
    public function testFirstReadDelayNotTrackedOnSubsequentReads(): void
    {
        $secret = $this->createTextSecret();

        // First read
        $this->postJson("/api/secrets/{$secret->token}/read");

        $countAfterFirst = $this->getTodayMetric(StatsService::FIRST_READ_DELAY_COUNT);

        // Second read
        $this->postJson("/api/secrets/{$secret->token}/read");

        $countAfterSecond = $this->getTodayMetric(StatsService::FIRST_READ_DELAY_COUNT);

        // Count should not increase on second read
        $this->assertEquals($countAfterFirst, $countAfterSecond);

        // Cleanup
        $secret->delete();
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function createTextSecret(array $attributes = []): Secret
    {
        return Secret::create(array_merge([
            'token' => $this->tokenService->generatePublicToken(),
            'admin_token_hash' => $this->tokenService->generateAdminToken()['hash'],
            'type' => 'text',
            'cipher_meta' => ['alg' => 'AES-256-GCM', 'iv' => 'YWFhYWFhYWFhYWFh', 'version' => 1],
            'ciphertext' => 'ZGRkZGRkZGRkZGRkZGRkZGRkZGRkZGRkZGRkZGRkZGQ',
            'expire_at' => now()->addDay(),
        ], $attributes));
    }
}
