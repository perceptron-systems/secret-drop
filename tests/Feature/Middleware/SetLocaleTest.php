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

    public function testDefaultsToFrenchWithoutHeader(): void
    {
        $request = Request::create('/test', 'GET');
        $request->headers->remove('Accept-Language');

        $response = $this->middleware->handle($request, fn ($req) => response('OK'));

        $this->assertEquals('fr', app()->getLocale());
        $this->assertEquals('fr', $response->headers->get('Content-Language'));
    }

    public function testDefaultsToFrenchWithEmptyHeader(): void
    {
        $request = Request::create('/test', 'GET');
        $request->headers->set('Accept-Language', '');

        $response = $this->middleware->handle($request, fn ($req) => response('OK'));

        $this->assertEquals('fr', app()->getLocale());
        $this->assertEquals('fr', $response->headers->get('Content-Language'));
    }

    public function testDetectsEnglishFromHeader(): void
    {
        $request = Request::create('/test', 'GET');
        $request->headers->set('Accept-Language', 'en');

        $response = $this->middleware->handle($request, fn ($req) => response('OK'));

        $this->assertEquals('en', app()->getLocale());
        $this->assertEquals('en', $response->headers->get('Content-Language'));
    }

    public function testDetectsFrenchFromHeader(): void
    {
        $request = Request::create('/test', 'GET');
        $request->headers->set('Accept-Language', 'fr');

        $response = $this->middleware->handle($request, fn ($req) => response('OK'));

        $this->assertEquals('fr', app()->getLocale());
        $this->assertEquals('fr', $response->headers->get('Content-Language'));
    }

    public function testHandlesRegionalVariants(): void
    {
        $request = Request::create('/test', 'GET');
        $request->headers->set('Accept-Language', 'en-US,en;q=0.9');

        $response = $this->middleware->handle($request, fn ($req) => response('OK'));

        $this->assertEquals('en', app()->getLocale());
        $this->assertEquals('en', $response->headers->get('Content-Language'));
    }

    public function testHandlesFrenchRegionalVariants(): void
    {
        $request = Request::create('/test', 'GET');
        $request->headers->set('Accept-Language', 'fr-CA,fr;q=0.9');

        $response = $this->middleware->handle($request, fn ($req) => response('OK'));

        $this->assertEquals('fr', app()->getLocale());
        $this->assertEquals('fr', $response->headers->get('Content-Language'));
    }

    public function testRespectsQualityValues(): void
    {
        $request = Request::create('/test', 'GET');
        $request->headers->set('Accept-Language', 'zh;q=0.9,en;q=0.8,fr;q=0.7');

        $response = $this->middleware->handle($request, fn ($req) => response('OK'));

        $this->assertEquals('en', app()->getLocale());
        $this->assertEquals('en', $response->headers->get('Content-Language'));
    }

    public function testFallsBackToFrenchForUnsupportedLanguage(): void
    {
        $request = Request::create('/test', 'GET');
        $request->headers->set('Accept-Language', 'zh,ru,th');

        $response = $this->middleware->handle($request, fn ($req) => response('OK'));

        $this->assertEquals('fr', app()->getLocale());
        $this->assertEquals('fr', $response->headers->get('Content-Language'));
    }

    public function testPrefersFrenchOverEnglishWhenHigherQuality(): void
    {
        $request = Request::create('/test', 'GET');
        $request->headers->set('Accept-Language', 'en;q=0.7,fr;q=0.9');

        $response = $this->middleware->handle($request, fn ($req) => response('OK'));

        $this->assertEquals('fr', app()->getLocale());
        $this->assertEquals('fr', $response->headers->get('Content-Language'));
    }

    public function testPrefersEnglishWhenFirstWithoutQuality(): void
    {
        $request = Request::create('/test', 'GET');
        $request->headers->set('Accept-Language', 'en,fr');

        $response = $this->middleware->handle($request, fn ($req) => response('OK'));

        $this->assertEquals('en', app()->getLocale());
        $this->assertEquals('en', $response->headers->get('Content-Language'));
    }

    public function testHandlesComplexAcceptLanguageHeader(): void
    {
        $request = Request::create('/test', 'GET');
        $request->headers->set('Accept-Language', 'de-DE,de;q=0.9,en-GB;q=0.8,en;q=0.7,fr;q=0.6');

        $response = $this->middleware->handle($request, fn ($req) => response('OK'));

        $this->assertEquals('de', app()->getLocale());
        $this->assertEquals('de', $response->headers->get('Content-Language'));
    }

    public function testSetsContentLanguageHeader(): void
    {
        $request = Request::create('/test', 'GET');
        $request->headers->set('Accept-Language', 'en');

        $response = $this->middleware->handle($request, fn ($req) => response('OK'));

        $this->assertTrue($response->headers->has('Content-Language'));
        $this->assertEquals('en', $response->headers->get('Content-Language'));
    }

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

    public function testDetectsLocaleFromUrlSegment(): void
    {
        $request = Request::create('/en/test', 'GET');
        $request->headers->set('Accept-Language', 'fr');

        $response = $this->middleware->handle($request, fn ($req) => response('OK'));

        $this->assertEquals('en', app()->getLocale());
    }

    public function testUrlSegmentTakesPriorityOverHeader(): void
    {
        $request = Request::create('/de/something', 'GET');
        $request->headers->set('Accept-Language', 'en');

        $response = $this->middleware->handle($request, fn ($req) => response('OK'));

        $this->assertEquals('de', app()->getLocale());
        $this->assertEquals('de', $response->headers->get('Content-Language'));
    }

    public function testFallsBackToHeaderWhenUrlSegmentIsNotLocale(): void
    {
        $request = Request::create('/s/some-token', 'GET');
        $request->headers->set('Accept-Language', 'es');

        $response = $this->middleware->handle($request, fn ($req) => response('OK'));

        $this->assertEquals('es', app()->getLocale());
    }

    public function testLocaleConfigConstants(): void
    {
        $this->assertContains('fr', LocaleConfig::SUPPORTED_LOCALES);
        $this->assertContains('en', LocaleConfig::SUPPORTED_LOCALES);
        $this->assertCount(11, LocaleConfig::SUPPORTED_LOCALES);
        $this->assertEquals('fr', LocaleConfig::DEFAULT_LOCALE);
    }
}
