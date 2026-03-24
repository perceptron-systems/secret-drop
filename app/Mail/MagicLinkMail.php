<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MagicLinkMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public string $verifyUrl,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('messages.email_magic_link_subject'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.magic-link',
            text: 'emails.magic-link-text',
            with: [
                'verifyUrl' => $this->verifyUrl,
            ],
        );
    }
}
