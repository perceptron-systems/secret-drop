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

    /** Vérifie la détection des bots qui ne contiennent pas "bot"/"crawl"/"spider". */
    public function testDetectsStealthBotUserAgents(): void
    {
        $this->assertTrue($this->service->isBot(
            'Mozilla/5.0 (compatible; GoogleDocs; apps-spreadsheets; +http://docs.google.com)'
        ));
        $this->assertTrue($this->service->isBot('Chrome Privacy Preserving Prefetch Proxy'));
        $this->assertTrue($this->service->isBot(
            'Mozilla/5.0 (l9scan/2.0; +https://leakix.net)'
        ));
        $this->assertTrue($this->service->isBot(
            'Hello from Palo Alto Networks, find out more about our scans in https://docs-cortex.paloaltonetworks.com/'
        ));
        $this->assertTrue($this->service->isBot(
            'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko; Google Web Preview) Chrome/141.0'
        ));
    }

    /** Vérifie l'identification des bots spécifiques. */
    public function testIdentifiesSpecificBots(): void
    {
        $this->assertSame('Google Docs', $this->service->identifyBot(
            'Mozilla/5.0 (compatible; GoogleDocs; apps-spreadsheets; +http://docs.google.com)'
        ));
        $this->assertSame('Google Images', $this->service->identifyBot('Googlebot-Image/1.0'));
        $this->assertSame('Google Other', $this->service->identifyBot(
            'Mozilla/5.0 AppleWebKit/537.36 (compatible; GoogleOther) Chrome/141'
        ));
        $this->assertSame('UptimeRobot', $this->service->identifyBot(
            'Mozilla/5.0+(compatible; UptimeRobot/2.0; http://www.uptimerobot.com/)'
        ));
        $this->assertSame('Linkup', $this->service->identifyBot(
            'LinkupBot/1.0 (LinkupBot for web indexing; https://linkup.so/bot; bot@linkup.so)'
        ));
        $this->assertSame('LeakIX', $this->service->identifyBot(
            'Mozilla/5.0 (l9scan/2.0; +https://leakix.net)'
        ));
        $this->assertSame('Chrome Prefetch', $this->service->identifyBot(
            'Chrome Privacy Preserving Prefetch Proxy'
        ));
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

    /** Vérifie la détection des apps IA. */
    public function testDetectsAiApps(): void
    {
        $this->assertSame('(chatgpt-app)', $this->service->detectAiApp(
            'ChatGPT/1.2025.287 (iOS 18.6.2; iPhone17,1; build 18608390057)'
        ));
        $this->assertSame('(chatgpt-app)', $this->service->detectAiApp(
            'ChatGPT/1.2025.258 (Windows_NT 10.0.26200; x86_64) Electron/37.4.0'
        ));
        $this->assertSame('(perplexity-app)', $this->service->detectAiApp('Perplexity/1.0 (iOS 18)'));
        $this->assertSame('(claude-app)', $this->service->detectAiApp('Claude/1.0 (iOS)'));
        $this->assertNull($this->service->detectAiApp('Mozilla/5.0 Chrome/120'));
    }

    /** Vérifie que les apps IA sont taguées comme pseudo-referrer. */
    public function testTrackTagsAiAppAsReferrer(): void
    {
        $this->service->track(
            'home',
            'ChatGPT/1.2025.287 (iOS 18.6.2; iPhone17,1; build 18608390057)',
            'en-US',
            0,
            '',
            ''
        );

        $this->assertDatabaseHas('stats_referrers', [
            'referrer_domain' => '(chatgpt-app)',
            'is_bot' => false,
        ]);
    }

    /** Vérifie que les apps IA restent comptées comme humains côté device. */
    public function testTrackAiAppIsNotBot(): void
    {
        $ua = 'ChatGPT/1.2025.287 (iOS 18.6.2; iPhone17,1; build 18608390057)';
        $this->assertFalse($this->service->isBot($ua));

        $this->service->track('home', $ua, 'en-US', 0);

        $this->assertDatabaseHas('stats_pageviews', [
            'page' => 'home',
            'is_bot' => false,
        ]);
        $this->assertDatabaseHas('stats_devices', [
            'device_type' => 'mobile',
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
