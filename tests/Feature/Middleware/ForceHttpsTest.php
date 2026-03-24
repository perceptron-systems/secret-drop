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

    public function testDoesNotRedirectInLocalEnvironment(): void
    {
        $this->app->detectEnvironment(fn () => 'local');

        $request = Request::create('http://localhost/test', 'GET');

        $response = $this->middleware->handle($request, fn ($req) => response('OK'));

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('OK', $response->getContent());
    }

    public function testRedirectsHttpToHttpsInProduction(): void
    {
        $this->app->detectEnvironment(fn () => 'production');

        $request = Request::create('http://example.com/test', 'GET');
        $request->server->set('HTTPS', 'off');

        $response = $this->middleware->handle($request, fn ($req) => response('OK'));

        $this->assertEquals(301, $response->getStatusCode());
        $this->assertStringStartsWith('https://', $response->headers->get('Location'));
    }

    public function testDoesNotRedirectHttpsInProduction(): void
    {
        $this->app->detectEnvironment(fn () => 'production');

        $request = Request::create('https://example.com/test', 'GET');
        $request->server->set('HTTPS', 'on');

        $response = $this->middleware->handle($request, fn ($req) => response('OK'));

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('OK', $response->getContent());
    }

    public function testForcesHttpsSchemeInProduction(): void
    {
        $this->app->detectEnvironment(fn () => 'production');

        $request = Request::create('https://example.com/test', 'GET');
        $request->server->set('HTTPS', 'on');

        $this->middleware->handle($request, fn ($req) => response('OK'));

        // URL::forceScheme should have been called
        $this->assertEquals('https', parse_url(URL::to('/'), PHP_URL_SCHEME));
    }

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
