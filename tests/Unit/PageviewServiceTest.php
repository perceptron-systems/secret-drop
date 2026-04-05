<?php

namespace Tests\Unit;

use App\Services\PageviewService;
use Tests\TestCase;

class PageviewServiceTest extends TestCase
{
    private PageviewService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new PageviewService();
    }

    /** Vérifie la détection des user-agents de bots. */
    public function testDetectsBotUserAgents(): void
    {
        $this->assertTrue($this->service->isBot('Googlebot/2.1'));
        $this->assertTrue($this->service->isBot('Mozilla/5.0 (compatible; bingbot/2.0)'));
        $this->assertTrue($this->service->isBot('python-requests/2.28'));
        $this->assertTrue($this->service->isBot('curl/7.88'));
        $this->assertTrue($this->service->isBot(''));
    }

    /** Vérifie la détection des user-agents humains. */
    public function testDetectsHumanUserAgents(): void
    {
        $this->assertFalse($this->service->isBot('Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/120'));
        $this->assertFalse($this->service->isBot('Mozilla/5.0 (iPhone; CPU iPhone OS 17_0) Safari/605'));
        $this->assertFalse($this->service->isBot('Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) Firefox/120'));
    }

    /** Vérifie que track insère une vue de page. */
    public function testTrackInsertsPageview(): void
    {
        $this->service->track('home', 'Mozilla/5.0 Chrome/120', 'fr-FR', -60);

        $this->assertDatabaseHas('stats_pageviews', [
            'page' => 'home',
            'is_bot' => false,
            'country' => 'FR',
        ]);
    }

    /** Vérifie que track incrémente une vue existante. */
    public function testTrackIncrementsExistingPageview(): void
    {
        $this->service->track('home', 'Mozilla/5.0 Chrome/120', 'fr-FR', -60);
        $this->service->track('home', 'Mozilla/5.0 Chrome/120', 'fr-FR', -60);

        $this->assertDatabaseHas('stats_pageviews', [
            'page' => 'home',
            'is_bot' => false,
            'country' => 'FR',
            'count' => 2,
        ]);
    }

    /** Vérifie que les bots ne créent pas d'entrée local_hour. */
    public function testTrackBotDoesNotCreateLocalHour(): void
    {
        $this->service->track('home', 'Googlebot/2.1', 'en-US', 0);

        $this->assertDatabaseMissing('stats_local_hours', [
            'date' => now()->toDateString(),
        ]);
    }

    /** Vérifie que les humains créent une entrée local_hour. */
    public function testTrackHumanCreatesLocalHour(): void
    {
        $this->service->track('home', 'Mozilla/5.0 Chrome/120', 'en-US', 300);

        $this->assertDatabaseHas('stats_local_hours', [
            'date' => now()->toDateString(),
        ]);
    }

    /** Vérifie la détection du pays depuis Accept-Language. */
    public function testDetectsCountryFromAcceptLanguage(): void
    {
        $this->service->track('home', 'Mozilla/5.0 Chrome/120', 'de-DE,de;q=0.9', 0);

        $this->assertDatabaseHas('stats_pageviews', [
            'page' => 'home',
            'country' => 'DE',
        ]);
    }

    /** Vérifie le mapping langue → pays sans région. */
    public function testMapsLanguageToCountryWhenNoRegion(): void
    {
        $this->service->track('home', 'Mozilla/5.0 Chrome/120', 'ja', 0);

        $this->assertDatabaseHas('stats_pageviews', [
            'page' => 'home',
            'country' => 'JP',
        ]);
    }

    /** Vérifie le fallback sur XX sans Accept-Language. */
    public function testFallsBackToXxWhenNoAcceptLanguage(): void
    {
        $this->service->track('home', 'Mozilla/5.0 Chrome/120', '', 0);

        $this->assertDatabaseHas('stats_pageviews', [
            'page' => 'home',
            'country' => 'XX',
        ]);
    }
}
