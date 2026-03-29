<?php

namespace Tests\Feature;

use Tests\TestCase;

class RedirectControllerTest extends TestCase
{
    /** Vérifie que la racine redirige vers la locale détectée. */
    public function testRootRedirectsToLocalizedHome(): void
    {
        $response = $this->get('/');

        $response->assertRedirect();
        $response->assertStatus(302);
    }

    /** Vérifie la redirection 301 de /how-it-works vers la version localisée. */
    public function testHowItWorksRedirects301(): void
    {
        $response = $this->get('/how-it-works');

        $response->assertStatus(301);
    }

    /** Vérifie la redirection 301 de /use-cases vers la version localisée. */
    public function testUseCasesRedirects301(): void
    {
        $response = $this->get('/use-cases');

        $response->assertStatus(301);
    }

    /** Vérifie la redirection 301 de /legal vers la version localisée. */
    public function testLegalRedirects301(): void
    {
        $response = $this->get('/legal');

        $response->assertStatus(301);
    }

    /** Vérifie la redirection 301 de /faq vers la version localisée. */
    public function testFaqRedirects301(): void
    {
        $response = $this->get('/faq');

        $response->assertStatus(301);
    }

    /** Vérifie la redirection de /admin vers la version localisée. */
    public function testAdminRedirectsToLocalizedAdmin(): void
    {
        $response = $this->get('/admin');

        $response->assertRedirect();
        $this->assertStringContainsString('admin', $response->headers->get('Location'));
    }

    /** Vérifie la redirection de /superadmin vers la version localisée. */
    public function testSuperadminRedirectsToLocalizedSuperadmin(): void
    {
        $response = $this->get('/superadmin');

        $response->assertRedirect();
        $this->assertStringContainsString('superadmin', $response->headers->get('Location'));
    }
}
