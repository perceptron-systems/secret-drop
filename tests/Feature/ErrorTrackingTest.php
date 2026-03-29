<?php

namespace Tests\Feature;

use App\Services\StatsService;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ErrorTrackingTest extends TestCase
{
    private function getMetricTotal(string $metric): int
    {
        return (int) (DB::table('stats_daily')
            ->where('metric', $metric)
            ->sum('count') ?? 0);
    }

    /** Vérifie que les erreurs 404 sont trackées. */
    public function testTracks404Errors(): void
    {
        $initial4xx = $this->getMetricTotal(StatsService::HTTP_ERRORS_4XX);
        $initial404 = $this->getMetricTotal(StatsService::HTTP_ERRORS_404);

        $this->getJson('/api/secrets/nonexistent-token-12345678');

        $new4xx = $this->getMetricTotal(StatsService::HTTP_ERRORS_4XX);
        $new404 = $this->getMetricTotal(StatsService::HTTP_ERRORS_404);

        $this->assertEquals($initial4xx + 1, $new4xx);
        $this->assertEquals($initial404 + 1, $new404);
    }

    /** Vérifie que les erreurs de validation 422 sont trackées. */
    public function testTracks422ValidationErrors(): void
    {
        $initial4xx = $this->getMetricTotal(StatsService::HTTP_ERRORS_4XX);
        $initial422 = $this->getMetricTotal(StatsService::HTTP_ERRORS_422);

        $this->postJson('/api/secrets', [
            'type' => 'text',
        ]);

        $new4xx = $this->getMetricTotal(StatsService::HTTP_ERRORS_4XX);
        $new422 = $this->getMetricTotal(StatsService::HTTP_ERRORS_422);

        $this->assertEquals($initial4xx + 1, $new4xx);
        $this->assertEquals($initial422 + 1, $new422);
    }

    /** Vérifie que les réponses 200 ne sont pas trackées. */
    public function testDoesNotTrackSuccessfulResponses(): void
    {
        $initial4xx = $this->getMetricTotal(StatsService::HTTP_ERRORS_4XX);
        $initial5xx = $this->getMetricTotal(StatsService::HTTP_ERRORS_5XX);

        $this->get('/fr');

        $new4xx = $this->getMetricTotal(StatsService::HTTP_ERRORS_4XX);
        $new5xx = $this->getMetricTotal(StatsService::HTTP_ERRORS_5XX);

        $this->assertEquals($initial4xx, $new4xx);
        $this->assertEquals($initial5xx, $new5xx);
    }

    /** Vérifie que les erreurs 5xx ne sont pas comptées comme 4xx. */
    public function testSeparates4xxAnd5xxCounts(): void
    {
        $initial5xx = $this->getMetricTotal(StatsService::HTTP_ERRORS_5XX);

        // A 404 should NOT increment 5xx
        $this->getJson('/api/secrets/nonexistent-token-12345678');

        $new5xx = $this->getMetricTotal(StatsService::HTTP_ERRORS_5XX);
        $this->assertEquals($initial5xx, $new5xx);
    }
}
