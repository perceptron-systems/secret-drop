<?php

namespace Tests\Feature;

use App\Listeners\SignEmailWithDkim;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Mime\Email;
use Tests\TestCase;

class SignEmailWithDkimTest extends TestCase
{
    /** Vérifie que le listener ne fait rien sans configuration DKIM. */
    public function testDoesNothingWhenDkimNotConfigured(): void
    {
        Config::set('mail.dkim.domain', null);
        Config::set('mail.dkim.selector', null);
        Config::set('mail.dkim.private_key_path', null);

        $message = (new Email())
            ->from('test@example.com')
            ->to('recipient@example.com')
            ->subject('Test')
            ->text('Test body');

        $event = new MessageSending($message);
        $originalMessage = $event->message;

        $listener = new SignEmailWithDkim();
        $listener->handle($event);

        $this->assertSame($originalMessage, $event->message);
    }

    /** Vérifie le warning quand le fichier de clé est introuvable. */
    public function testLogsWarningWhenKeyFileNotFound(): void
    {
        Config::set('mail.dkim.domain', 'example.com');
        Config::set('mail.dkim.selector', 'secretdrop');
        Config::set('mail.dkim.private_key_path', 'nonexistent/path/private.key');

        Log::shouldReceive('warning')
            ->once()
            ->with('DKIM private key not found', ['path' => 'nonexistent/path/private.key']);

        $message = (new Email())
            ->from('test@example.com')
            ->to('recipient@example.com')
            ->subject('Test')
            ->text('Test body');

        $event = new MessageSending($message);
        $originalMessage = $event->message;

        $listener = new SignEmailWithDkim();
        $listener->handle($event);

        $this->assertSame($originalMessage, $event->message);
    }

    /** Vérifie la signature DKIM quand tout est correctement configuré. */
    public function testSignsMessageWhenProperlyConfigured(): void
    {
        $keyDir = storage_path('app/test-dkim');
        $keyPath = $keyDir.'/test-private.key';

        if (! is_dir($keyDir)) {
            mkdir($keyDir, 0755, true);
        }

        $config = [
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ];
        $keyResource = openssl_pkey_new($config);
        openssl_pkey_export($keyResource, $privateKey);

        file_put_contents($keyPath, $privateKey);

        Config::set('mail.dkim.domain', 'example.com');
        Config::set('mail.dkim.selector', 'secretdrop');
        Config::set('mail.dkim.private_key_path', 'storage/app/test-dkim/test-private.key');
        Config::set('mail.dkim.passphrase', '');

        $message = (new Email())
            ->from('test@example.com')
            ->to('recipient@example.com')
            ->subject('Test')
            ->text('Test body');

        $event = new MessageSending($message);
        $originalToString = $event->message->toString();

        $listener = new SignEmailWithDkim();
        $listener->handle($event);

        $signedToString = $event->message->toString();

        $this->assertStringContainsString('DKIM-Signature:', $signedToString);
        $this->assertStringContainsString('d=example.com', $signedToString);
        $this->assertStringContainsString('s=secretdrop', $signedToString);

        unlink($keyPath);
        rmdir($keyDir);
    }
}
