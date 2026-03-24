<?php

namespace Tests\Feature;

use App\Mail\MagicLinkMail;
use App\Mail\SuperAdminMagicLinkMail;
use Tests\TestCase;

class MagicLinkMailTest extends TestCase
{
    public function testMagicLinkMailHasCorrectSubject(): void
    {
        $mail = new MagicLinkMail('https://example.com/verify/token123');

        $this->assertEquals(__('messages.email_magic_link_subject'), $mail->envelope()->subject);
    }

    public function testMagicLinkMailRendersWithVerifyUrl(): void
    {
        $url = 'https://example.com/verify/token123';
        $mail = new MagicLinkMail($url);

        $rendered = $mail->render();

        $this->assertStringContainsString($url, $rendered);
        $this->assertStringContainsString(__('messages.email_magic_link_button'), $rendered);
        $this->assertStringContainsString(e(__('messages.email_magic_link_warning')), $rendered);
    }

    public function testMagicLinkMailContainsClickableUrl(): void
    {
        $url = 'https://example.com/verify/token123';
        $mail = new MagicLinkMail($url);

        $rendered = $mail->render();

        $this->assertStringContainsString('href="'.$url.'"', $rendered);
    }

    public function testSuperAdminMagicLinkMailHasCorrectSubject(): void
    {
        $mail = new SuperAdminMagicLinkMail('https://example.com/verify/token123');

        $this->assertEquals(__('messages.email_superadmin_subject'), $mail->envelope()->subject);
    }

    public function testSuperAdminMagicLinkMailRendersWithVerifyUrl(): void
    {
        $url = 'https://example.com/verify/token123';
        $mail = new SuperAdminMagicLinkMail($url);

        $rendered = $mail->render();

        $this->assertStringContainsString($url, $rendered);
        $this->assertStringContainsString(__('messages.email_superadmin_button'), $rendered);
    }

    public function testSuperAdminMagicLinkMailContainsBadge(): void
    {
        $mail = new SuperAdminMagicLinkMail('https://example.com/verify/token123');

        $rendered = $mail->render();

        $this->assertStringContainsString(__('messages.superadmin_title'), $rendered);
    }
}
