<?php

namespace Tests\Feature\Middleware;

use App\Http\Middleware\ForceHttps;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class ForceHttpsTest extends TestCase
{
    private ForceHttps $middleware;

    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new ForceHttps();
    }

    /** Vérifie que le middleware ne redirige pas en environnement local. */
    public function testDoesNotRedirectInLocalEnvironment(): void
    {
        $this->app->detectEnvironment(fn () => 'local');

        $request = Request::create('http://localhost/test', 'GET');

        $response = $this->middleware->handle($request, fn ($req) => response('OK'));

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('OK', $response->getContent());
    }

    /** Vérifie la redirection HTTP vers HTTPS en production. */
    public function testRedirectsHttpToHttpsInProduction(): void
    {
        $this->app->detectEnvironment(fn () => 'production');

        $request = Request::create('http://example.com/test', 'GET');
        $request->server->set('HTTPS', 'off');

        $response = $this->middleware->handle($request, fn ($req) => response('OK'));

        $this->assertEquals(301, $response->getStatusCode());
        $this->assertStringStartsWith('https://', $response->headers->get('Location'));
    }

    /** Vérifie que HTTPS ne déclenche pas de redirection en production. */
    public function testDoesNotRedirectHttpsInProduction(): void
    {
        $this->app->detectEnvironment(fn () => 'production');

        $request = Request::create('https://example.com/test', 'GET');
        $request->server->set('HTTPS', 'on');

        $response = $this->middleware->handle($request, fn ($req) => response('OK'));

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('OK', $response->getContent());
    }

    /** Vérifie que le schéma HTTPS est forcé pour la génération d'URL. */
    public function testForcesHttpsSchemeInProduction(): void
    {
        $this->app->detectEnvironment(fn () => 'production');

        $request = Request::create('https://example.com/test', 'GET');
        $request->server->set('HTTPS', 'on');

        $this->middleware->handle($request, fn ($req) => response('OK'));

        // URL::forceScheme should have been called
        $this->assertEquals('https', parse_url(URL::to('/'), PHP_URL_SCHEME));
    }

    /** Vérifie que l'URI et les query params sont préservés lors de la redirection. */
    public function testPreservesRequestUriOnRedirect(): void
    {
        $this->app->detectEnvironment(fn () => 'production');

        $request = Request::create('http://example.com/s/abc123?foo=bar', 'GET');
        $request->server->set('HTTPS', 'off');

        $response = $this->middleware->handle($request, fn ($req) => response('OK'));

        $location = $response->headers->get('Location');
        $this->assertStringContainsString('/s/abc123', $location);
        $this->assertStringContainsString('foo=bar', $location);
    }
}
