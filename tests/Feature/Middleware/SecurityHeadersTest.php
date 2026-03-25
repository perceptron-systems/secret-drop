<?php

namespace Tests\Feature\Middleware;

use App\Http\Middleware\SecurityHeaders;
use Illuminate\Http\Request;
use Tests\TestCase;

class SecurityHeadersTest extends TestCase
{
    private SecurityHeaders $middleware;

    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new SecurityHeaders();
    }

    /** Vérifie la présence du header X-Content-Type-Options. */
    public function testSetsXContentTypeOptions(): void
    {
        $request = Request::create('/test', 'GET');

        $response = $this->middleware->handle($request, fn ($req) => response('OK'));

        $this->assertEquals('nosniff', $response->headers->get('X-Content-Type-Options'));
    }

    /** Vérifie la présence du header X-Frame-Options: DENY. */
    public function testSetsXFrameOptions(): void
    {
        $request = Request::create('/test', 'GET');

        $response = $this->middleware->handle($request, fn ($req) => response('OK'));

        $this->assertEquals('DENY', $response->headers->get('X-Frame-Options'));
    }

    /** Vérifie l'absence du header obsolète X-XSS-Protection. */
    public function testDoesNotSetObsoleteXXssProtection(): void
    {
        $request = Request::create('/test', 'GET');

        $response = $this->middleware->handle($request, fn ($req) => response('OK'));

        $this->assertNull($response->headers->get('X-XSS-Protection'));
    }

    /** Vérifie la présence du header Referrer-Policy. */
    public function testSetsReferrerPolicy(): void
    {
        $request = Request::create('/test', 'GET');

        $response = $this->middleware->handle($request, fn ($req) => response('OK'));

        $this->assertEquals('strict-origin-when-cross-origin', $response->headers->get('Referrer-Policy'));
    }

    /** Vérifie la présence du header Permissions-Policy. */
    public function testSetsPermissionsPolicy(): void
    {
        $request = Request::create('/test', 'GET');

        $response = $this->middleware->handle($request, fn ($req) => response('OK'));

        $this->assertEquals('camera=(), microphone=(), geolocation=(), payment=(), usb=()', $response->headers->get('Permissions-Policy'));
    }

    /** Vérifie la présence du CSP avec les directives principales. */
    public function testSetsContentSecurityPolicy(): void
    {
        $request = Request::create('/test', 'GET');

        $response = $this->middleware->handle($request, fn ($req) => response('OK'));

        $csp = $response->headers->get('Content-Security-Policy');

        $this->assertNotNull($csp);
        $this->assertStringContainsString("default-src 'self'", $csp);
        $this->assertStringContainsString("script-src 'self'", $csp);
        $this->assertStringContainsString("style-src 'self'", $csp);
        $this->assertStringContainsString("object-src 'none'", $csp);
    }

    /** Vérifie que le CSP contient un nonce. */
    public function testCspContainsNonce(): void
    {
        $request = Request::create('/test', 'GET');

        $response = $this->middleware->handle($request, fn ($req) => response('OK'));

        $csp = $response->headers->get('Content-Security-Policy');

        $this->assertMatchesRegularExpression("/nonce-[A-Za-z0-9+\/=]+/", $csp);
    }

    /** Vérifie la présence du HSTS en production. */
    public function testSetsHstsInProduction(): void
    {
        $this->app->detectEnvironment(fn () => 'production');

        $request = Request::create('/test', 'GET');

        $response = $this->middleware->handle($request, fn ($req) => response('OK'));

        $hsts = $response->headers->get('Strict-Transport-Security');
        $this->assertNotNull($hsts);
        $this->assertStringContainsString('max-age=31536000', $hsts);
        $this->assertStringContainsString('includeSubDomains', $hsts);
    }

    /** Vérifie l'absence du HSTS en local. */
    public function testDoesNotSetHstsInLocal(): void
    {
        $this->app->detectEnvironment(fn () => 'local');

        $request = Request::create('/test', 'GET');

        $response = $this->middleware->handle($request, fn ($req) => response('OK'));

        $this->assertNull($response->headers->get('Strict-Transport-Security'));
    }

    /** Vérifie que le CSP autorise les websockets en local. */
    public function testCspAllowsWebsocketInLocal(): void
    {
        $this->app->detectEnvironment(fn () => 'local');

        $request = Request::create('/test', 'GET');

        $response = $this->middleware->handle($request, fn ($req) => response('OK'));

        $csp = $response->headers->get('Content-Security-Policy');

        $this->assertStringContainsString('ws://localhost:', $csp);
    }

    /** Vérifie que le CSP n'autorise pas les websockets en production. */
    public function testCspDoesNotAllowWebsocketInProduction(): void
    {
        $this->app->detectEnvironment(fn () => 'production');

        $request = Request::create('/test', 'GET');

        $response = $this->middleware->handle($request, fn ($req) => response('OK'));

        $csp = $response->headers->get('Content-Security-Policy');

        $this->assertStringNotContainsString('ws://localhost:', $csp);
        $this->assertStringContainsString("connect-src 'self'", $csp);
    }

    /** Vérifie que le CSP bloque les frame-ancestors. */
    public function testCspBlocksFrameAncestors(): void
    {
        $request = Request::create('/test', 'GET');

        $response = $this->middleware->handle($request, fn ($req) => response('OK'));

        $csp = $response->headers->get('Content-Security-Policy');

        $this->assertStringContainsString("frame-ancestors 'none'", $csp);
    }

    /** Vérifie que le CSP restreint form-action à self. */
    public function testCspBlocksFormActionToExternal(): void
    {
        $request = Request::create('/test', 'GET');

        $response = $this->middleware->handle($request, fn ($req) => response('OK'));

        $csp = $response->headers->get('Content-Security-Policy');

        $this->assertStringContainsString("form-action 'self'", $csp);
    }

    /** Vérifie que le CSP est strict en production (pas unsafe-eval/inline). */
    public function testCspIsStrictInProduction(): void
    {
        $this->app->detectEnvironment(fn () => 'production');

        $request = Request::create('/test', 'GET');

        $response = $this->middleware->handle($request, fn ($req) => response('OK'));

        $csp = $response->headers->get('Content-Security-Policy');

        $this->assertStringNotContainsString('unsafe-eval', $csp);
        // No unsafe-inline in script-src or style-src (nonce-only), but allowed in style-src-attr
        $this->assertDoesNotMatchRegularExpression("/script-src[^;]*'unsafe-inline'/", $csp);
        $this->assertDoesNotMatchRegularExpression("/style-src [^;]*'unsafe-inline'/", $csp);
        // style-src-attr allows Alpine.js inline style="" attributes (no nonce mechanism for these)
        $this->assertStringContainsString("style-src-attr 'unsafe-inline'", $csp);
    }

    /** Vérifie que le CSP autorise unsafe-eval en local (dev HMR). */
    public function testCspAllowsUnsafeEvalInLocal(): void
    {
        $this->app->detectEnvironment(fn () => 'local');

        $request = Request::create('/test', 'GET');

        $response = $this->middleware->handle($request, fn ($req) => response('OK'));

        $csp = $response->headers->get('Content-Security-Policy');

        $this->assertStringContainsString('unsafe-eval', $csp);
        $this->assertStringContainsString('unsafe-inline', $csp);
    }

    /** Vérifie la présence du header Cross-Origin-Opener-Policy. */
    public function testSetsCoopHeader(): void
    {
        $request = Request::create('/test', 'GET');

        $response = $this->middleware->handle($request, fn ($req) => response('OK'));

        $this->assertEquals('same-origin', $response->headers->get('Cross-Origin-Opener-Policy'));
    }
}
