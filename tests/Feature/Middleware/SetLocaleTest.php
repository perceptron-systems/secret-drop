<?php

namespace Tests\Feature\Middleware;

use App\Http\Middleware\SetLocale;
use App\Support\LocaleConfig;
use Illuminate\Http\Request;
use Tests\TestCase;

class SetLocaleTest extends TestCase
{
    private SetLocale $middleware;

    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new SetLocale();
    }

    /** Vérifie le fallback sur le français sans header Accept-Language. */
    public function testDefaultsToFrenchWithoutHeader(): void
    {
        $request = Request::create('/test', 'GET');
        $request->headers->remove('Accept-Language');

        $response = $this->middleware->handle($request, fn ($req) => response('OK'));

        $this->assertEquals('fr', app()->getLocale());
        $this->assertEquals('fr', $response->headers->get('Content-Language'));
    }

    /** Vérifie le fallback sur le français avec un header vide. */
    public function testDefaultsToFrenchWithEmptyHeader(): void
    {
        $request = Request::create('/test', 'GET');
        $request->headers->set('Accept-Language', '');

        $response = $this->middleware->handle($request, fn ($req) => response('OK'));

        $this->assertEquals('fr', app()->getLocale());
        $this->assertEquals('fr', $response->headers->get('Content-Language'));
    }

    /** Vérifie la détection de l'anglais depuis le header. */
    public function testDetectsEnglishFromHeader(): void
    {
        $request = Request::create('/test', 'GET');
        $request->headers->set('Accept-Language', 'en');

        $response = $this->middleware->handle($request, fn ($req) => response('OK'));

        $this->assertEquals('en', app()->getLocale());
        $this->assertEquals('en', $response->headers->get('Content-Language'));
    }

    /** Vérifie la détection du français depuis le header. */
    public function testDetectsFrenchFromHeader(): void
    {
        $request = Request::create('/test', 'GET');
        $request->headers->set('Accept-Language', 'fr');

        $response = $this->middleware->handle($request, fn ($req) => response('OK'));

        $this->assertEquals('fr', app()->getLocale());
        $this->assertEquals('fr', $response->headers->get('Content-Language'));
    }

    /** Vérifie la gestion des variantes régionales (en-US). */
    public function testHandlesRegionalVariants(): void
    {
        $request = Request::create('/test', 'GET');
        $request->headers->set('Accept-Language', 'en-US,en;q=0.9');

        $response = $this->middleware->handle($request, fn ($req) => response('OK'));

        $this->assertEquals('en', app()->getLocale());
        $this->assertEquals('en', $response->headers->get('Content-Language'));
    }

    /** Vérifie la gestion des variantes régionales françaises (fr-CA). */
    public function testHandlesFrenchRegionalVariants(): void
    {
        $request = Request::create('/test', 'GET');
        $request->headers->set('Accept-Language', 'fr-CA,fr;q=0.9');

        $response = $this->middleware->handle($request, fn ($req) => response('OK'));

        $this->assertEquals('fr', app()->getLocale());
        $this->assertEquals('fr', $response->headers->get('Content-Language'));
    }

    /** Vérifie le respect des valeurs de qualité (q=). */
    public function testRespectsQualityValues(): void
    {
        $request = Request::create('/test', 'GET');
        $request->headers->set('Accept-Language', 'zh;q=0.9,en;q=0.8,fr;q=0.7');

        $response = $this->middleware->handle($request, fn ($req) => response('OK'));

        $this->assertEquals('en', app()->getLocale());
        $this->assertEquals('en', $response->headers->get('Content-Language'));
    }

    /** Vérifie le fallback sur le français pour une langue non supportée. */
    public function testFallsBackToFrenchForUnsupportedLanguage(): void
    {
        $request = Request::create('/test', 'GET');
        $request->headers->set('Accept-Language', 'zh,ru,th');

        $response = $this->middleware->handle($request, fn ($req) => response('OK'));

        $this->assertEquals('fr', app()->getLocale());
        $this->assertEquals('fr', $response->headers->get('Content-Language'));
    }

    /** Vérifie la préférence du français quand sa qualité est plus élevée. */
    public function testPrefersFrenchOverEnglishWhenHigherQuality(): void
    {
        $request = Request::create('/test', 'GET');
        $request->headers->set('Accept-Language', 'en;q=0.7,fr;q=0.9');

        $response = $this->middleware->handle($request, fn ($req) => response('OK'));

        $this->assertEquals('fr', app()->getLocale());
        $this->assertEquals('fr', $response->headers->get('Content-Language'));
    }

    /** Vérifie la préférence de l'anglais quand il est premier sans qualité. */
    public function testPrefersEnglishWhenFirstWithoutQuality(): void
    {
        $request = Request::create('/test', 'GET');
        $request->headers->set('Accept-Language', 'en,fr');

        $response = $this->middleware->handle($request, fn ($req) => response('OK'));

        $this->assertEquals('en', app()->getLocale());
        $this->assertEquals('en', $response->headers->get('Content-Language'));
    }

    /** Vérifie la gestion de headers Accept-Language complexes. */
    public function testHandlesComplexAcceptLanguageHeader(): void
    {
        $request = Request::create('/test', 'GET');
        $request->headers->set('Accept-Language', 'de-DE,de;q=0.9,en-GB;q=0.8,en;q=0.7,fr;q=0.6');

        $response = $this->middleware->handle($request, fn ($req) => response('OK'));

        $this->assertEquals('de', app()->getLocale());
        $this->assertEquals('de', $response->headers->get('Content-Language'));
    }

    /** Vérifie la présence du header Content-Language. */
    public function testSetsContentLanguageHeader(): void
    {
        $request = Request::create('/test', 'GET');
        $request->headers->set('Accept-Language', 'en');

        $response = $this->middleware->handle($request, fn ($req) => response('OK'));

        $this->assertTrue($response->headers->has('Content-Language'));
        $this->assertEquals('en', $response->headers->get('Content-Language'));
    }

    /** Vérifie la détection de toutes les locales supportées. */
    #[\PHPUnit\Framework\Attributes\DataProvider('supportedLocalesProvider')]
    public function testDetectsAllSupportedLocales(string $locale): void
    {
        $request = Request::create('/test', 'GET');
        $request->headers->set('Accept-Language', $locale);

        $response = $this->middleware->handle($request, fn ($req) => response('OK'));

        $this->assertEquals($locale, app()->getLocale());
        $this->assertEquals($locale, $response->headers->get('Content-Language'));
    }

    /**
     * @return array<string, array{string}>
     */
    public static function supportedLocalesProvider(): array
    {
        return [
            'german' => ['de'],
            'spanish' => ['es'],
            'italian' => ['it'],
            'portuguese' => ['pt'],
            'dutch' => ['nl'],
            'polish' => ['pl'],
            'japanese' => ['ja'],
            'korean' => ['ko'],
            'arabic' => ['ar'],
        ];
    }

    /** Vérifie la détection de la locale depuis le segment d'URL. */
    public function testDetectsLocaleFromUrlSegment(): void
    {
        $request = Request::create('/en/test', 'GET');
        $request->headers->set('Accept-Language', 'fr');

        $response = $this->middleware->handle($request, fn ($req) => response('OK'));

        $this->assertEquals('en', app()->getLocale());
    }

    /** Vérifie que le segment d'URL a priorité sur le header. */
    public function testUrlSegmentTakesPriorityOverHeader(): void
    {
        $request = Request::create('/de/something', 'GET');
        $request->headers->set('Accept-Language', 'en');

        $response = $this->middleware->handle($request, fn ($req) => response('OK'));

        $this->assertEquals('de', app()->getLocale());
        $this->assertEquals('de', $response->headers->get('Content-Language'));
    }

    /** Vérifie le fallback sur le header quand le segment URL n'est pas une locale. */
    public function testFallsBackToHeaderWhenUrlSegmentIsNotLocale(): void
    {
        $request = Request::create('/s/some-token', 'GET');
        $request->headers->set('Accept-Language', 'es');

        $response = $this->middleware->handle($request, fn ($req) => response('OK'));

        $this->assertEquals('es', app()->getLocale());
    }

    /** Vérifie les constantes de configuration des locales. */
    public function testLocaleConfigConstants(): void
    {
        $this->assertContains('fr', LocaleConfig::SUPPORTED_LOCALES);
        $this->assertContains('en', LocaleConfig::SUPPORTED_LOCALES);
        $this->assertCount(11, LocaleConfig::SUPPORTED_LOCALES);
        $this->assertEquals('fr', LocaleConfig::DEFAULT_LOCALE);
    }
}
