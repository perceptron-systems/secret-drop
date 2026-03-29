<?php

namespace Tests\Feature;

use Tests\TestCase;

class LocalizedPagesControllerTest extends TestCase
{
    /** Vérifie que les pages localisées EN retournent 200. */
    public function testEnglishPagesReturnSuccess(): void
    {
        $this->get('/en/how-it-works')->assertStatus(200);
        $this->get('/en/use-cases')->assertStatus(200);
        $this->get('/en/legal-notice')->assertStatus(200);
        $this->get('/en/faq')->assertStatus(200);
    }

    /** Vérifie que les pages localisées FR retournent 200. */
    public function testFrenchPagesReturnSuccess(): void
    {
        $this->get('/fr/comment-ca-marche')->assertStatus(200);
        $this->get('/fr/cas-d-usage')->assertStatus(200);
        $this->get('/fr/mentions-legales')->assertStatus(200);
        $this->get('/fr/faq')->assertStatus(200);
    }

    /** Vérifie qu'un slug EN sous /fr redirige 301 vers le bon slug FR. */
    public function testEnglishSlugUnderFrenchLocaleRedirects(): void
    {
        $response = $this->get('/fr/how-it-works');

        $response->assertStatus(301);
        $this->assertStringContainsString('comment-ca-marche', $response->headers->get('Location'));
    }

    /** Vérifie qu'un slug FR sous /en redirige 301 vers le bon slug EN. */
    public function testFrenchSlugUnderEnglishLocaleRedirects(): void
    {
        $response = $this->get('/en/comment-ca-marche');

        $response->assertStatus(301);
        $this->assertStringContainsString('how-it-works', $response->headers->get('Location'));
    }

    /** Vérifie qu'un slug inexistant retourne 404. */
    public function testUnknownSlugReturns404(): void
    {
        $response = $this->get('/en/this-page-does-not-exist');

        $response->assertStatus(404);
    }

    /** Vérifie que la page d'accueil localisée retourne 200. */
    public function testLocalizedHomeReturnsSuccess(): void
    {
        $this->get('/en')->assertStatus(200);
        $this->get('/fr')->assertStatus(200);
    }
}
