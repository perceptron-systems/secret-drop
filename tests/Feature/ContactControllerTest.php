<?php

namespace Tests\Feature;

use Tests\TestCase;

class ContactControllerTest extends TestCase
{
    public function testContactRedirectsToMailto(): void
    {
        $response = $this->get('/contact');

        $response->assertRedirect();
        $this->assertStringStartsWith('mailto:', $response->headers->get('Location'));
    }

    public function testContactUsesConfiguredEmail(): void
    {
        config(['legal.contact_email' => 'test@example.com']);

        $response = $this->get('/contact');

        $response->assertRedirect('mailto:test@example.com');
    }

    public function testContactFallsBackToMailFromAddress(): void
    {
        config(['legal.contact_email' => null, 'mail.from.address' => 'fallback@example.com']);

        $response = $this->get('/contact');

        $this->assertStringContainsString('mailto:', $response->headers->get('Location'));
    }
}
