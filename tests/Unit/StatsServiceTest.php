<?php

namespace Tests\Unit;

use App\Services\StatsService;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class StatsServiceTest extends TestCase
{
    private StatsService $statsService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->statsService = new StatsService();
    }

    /** Vérifie que increment crée un nouvel enregistrement. */
    public function testIncrementCreatesNewRecord(): void
    {
        DB::table('stats_daily')->where('metric', 'test_metric')->delete();

        $this->statsService->increment('test_metric');

        $this->assertDatabaseHas('stats_daily', [
            'date' => now()->toDateString(),
            'metric' => 'test_metric',
            'count' => 1,
        ]);

        DB::table('stats_daily')->where('metric', 'test_metric')->delete();
    }

    /** Vérifie que increment met à jour un enregistrement existant. */
    public function testIncrementUpdatesExistingRecord(): void
    {
        DB::table('stats_daily')->where('metric', 'test_increment')->delete();

        $this->statsService->increment('test_increment');
        $this->statsService->increment('test_increment');
        $this->statsService->increment('test_increment');

        $this->assertDatabaseHas('stats_daily', [
            'date' => now()->toDateString(),
            'metric' => 'test_increment',
            'count' => 3,
        ]);

        DB::table('stats_daily')->where('metric', 'test_increment')->delete();
    }

    /** Vérifie l'incrémentation par un montant spécifique. */
    public function testIncrementByAmount(): void
    {
        DB::table('stats_daily')->where('metric', 'test_amount')->delete();

        $this->statsService->increment('test_amount', 100);

        $this->assertDatabaseHas('stats_daily', [
            'date' => now()->toDateString(),
            'metric' => 'test_amount',
            'count' => 100,
        ]);

        DB::table('stats_daily')->where('metric', 'test_amount')->delete();
    }

    /** Vérifie que getStats retourne la bonne structure. */
    public function testGetStatsReturnsCorrectStructure(): void
    {
        $stats = $this->statsService->getStats('7d');

        $this->assertArrayHasKey('period', $stats);
        $this->assertArrayHasKey('days', $stats);
        $this->assertArrayHasKey('start_date', $stats);
        $this->assertArrayHasKey('end_date', $stats);
        $this->assertArrayHasKey('metrics', $stats);
        $this->assertArrayHasKey('totals', $stats);

        $this->assertEquals('7d', $stats['period']);
        $this->assertEquals(7, $stats['days']);
    }

    /** Vérifie getStats avec différentes périodes. */
    public function testGetStatsWithDifferentPeriods(): void
    {
        $stats7d = $this->statsService->getStats('7d');
        $stats30d = $this->statsService->getStats('30d');
        $stats90d = $this->statsService->getStats('90d');
        $stats1y = $this->statsService->getStats('1y');

        $this->assertEquals(7, $stats7d['days']);
        $this->assertEquals(30, $stats30d['days']);
        $this->assertEquals(90, $stats90d['days']);
        $this->assertEquals(365, $stats1y['days']);
    }

    /** Vérifie que getTotals retourne les sommes des métriques. */
    public function testGetTotalsReturnsMetricSums(): void
    {
        DB::table('stats_daily')->where('metric', 'test_totals')->delete();

        DB::table('stats_daily')->insert([
            ['date' => now()->subDays(1)->toDateString(), 'metric' => 'test_totals', 'count' => 5, 'created_at' => now(), 'updated_at' => now()],
            ['date' => now()->toDateString(), 'metric' => 'test_totals', 'count' => 10, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $totals = $this->statsService->getTotals(now()->subDays(7)->toDateString());

        $this->assertEquals(15, $totals['test_totals']);

        DB::table('stats_daily')->where('metric', 'test_totals')->delete();
    }

    /** Vérifie les totaux all-time. */
    public function testGetAllTimeTotals(): void
    {
        DB::table('stats_daily')->where('metric', 'test_alltime')->delete();

        DB::table('stats_daily')->insert([
            ['date' => now()->subDays(100)->toDateString(), 'metric' => 'test_alltime', 'count' => 50, 'created_at' => now(), 'updated_at' => now()],
            ['date' => now()->toDateString(), 'metric' => 'test_alltime', 'count' => 25, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $allTime = $this->statsService->getAllTimeTotals();

        $this->assertEquals(75, $allTime['test_alltime']);

        DB::table('stats_daily')->where('metric', 'test_alltime')->delete();
    }

    /** Vérifie que les constantes de métriques sont définies. */
    public function testConstantsAreDefined(): void
    {
        $this->assertEquals('secrets_created_text', StatsService::SECRETS_CREATED_TEXT);
        $this->assertEquals('secrets_created_file', StatsService::SECRETS_CREATED_FILE);
        $this->assertEquals('secrets_read', StatsService::SECRETS_READ);
        $this->assertEquals('magic_links_requested', StatsService::MAGIC_LINKS_REQUESTED);
        $this->assertEquals('magic_links_used', StatsService::MAGIC_LINKS_USED);
    }
}
