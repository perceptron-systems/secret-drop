<?php

namespace Tests\Feature;

use Tests\TestCase;

class ContactControllerTest extends TestCase
{
    /** Vérifie la redirection vers mailto. */
    public function testContactRedirectsToMailto(): void
    {
        $response = $this->get('/contact');

        $response->assertRedirect();
        $this->assertStringStartsWith('mailto:', $response->headers->get('Location'));
    }

    /** Vérifie l'utilisation de l'email configuré. */
    public function testContactUsesConfiguredEmail(): void
    {
        config(['legal.contact_email' => 'test@example.com']);

        $response = $this->get('/contact');

        $response->assertRedirect('mailto:test@example.com');
    }

    /** Vérifie le fallback sur l'adresse mail.from. */
    public function testContactFallsBackToMailFromAddress(): void
    {
        config(['legal.contact_email' => null, 'mail.from.address' => 'fallback@example.com']);

        $response = $this->get('/contact');

        $this->assertStringContainsString('mailto:', $response->headers->get('Location'));
    }
}
