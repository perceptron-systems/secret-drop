<?php

namespace Tests\Feature;

use Tests\TestCase;

class SeoControllerTest extends TestCase
{
    public function testRobotsReturnsPlainText(): void
    {
        $response = $this->get('/robots.txt');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/plain; charset=UTF-8');
    }

    public function testRobotsContainsDisallowRules(): void
    {
        $response = $this->get('/robots.txt');

        $content = $response->getContent();
        $this->assertStringContainsString('Disallow: /s/', $content);
        $this->assertStringContainsString('Disallow: /api/', $content);
        $this->assertStringContainsString('Disallow: /fr/admin/', $content);
        $this->assertStringContainsString('Disallow: /en/admin/', $content);
    }

    public function testRobotsContainsSitemap(): void
    {
        $response = $this->get('/robots.txt');

        $content = $response->getContent();
        $this->assertStringContainsString('Sitemap:', $content);
        $this->assertStringContainsString('sitemap.xml', $content);
    }

    public function testRobotsDoesNotExposeSuperadmin(): void
    {
        $response = $this->get('/robots.txt');

        $content = $response->getContent();
        $this->assertStringNotContainsString('superadmin', $content);
    }

    public function testSitemapReturnsXml(): void
    {
        $response = $this->get('/sitemap.xml');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/xml');
    }

    public function testSitemapContainsLocales(): void
    {
        $response = $this->get('/sitemap.xml');

        $content = $response->getContent();
        $this->assertStringContainsString('hreflang="fr"', $content);
        $this->assertStringContainsString('hreflang="en"', $content);
        $this->assertStringContainsString('<loc>', $content);
    }

    public function testSitemapStylesheetReturnsXsl(): void
    {
        $response = $this->get('/sitemap.xsl');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/xsl; charset=UTF-8');
    }

    public function testSecurityTxtReturnsPlainText(): void
    {
        $response = $this->get('/.well-known/security.txt');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/plain; charset=UTF-8');
    }

    public function testSecurityTxtContainsRequiredFields(): void
    {
        $response = $this->get('/.well-known/security.txt');

        $content = $response->getContent();
        $this->assertStringContainsString('Contact: mailto:', $content);
        $this->assertStringContainsString('Expires:', $content);
        $this->assertStringContainsString('Canonical:', $content);
    }
}
