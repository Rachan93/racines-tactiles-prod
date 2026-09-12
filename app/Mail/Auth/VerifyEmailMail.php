<?php

namespace App\Mail\Auth;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class VerifyEmailMail extends Mailable
{
    use Queueable;

    public function __construct(
        public mixed $user,
        public string $verificationUrl,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Vérifiez votre adresse e-mail',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.auth.verify-email',
            with: [
                'user' => $this->user,
                'verificationUrl' => $this->verificationUrl,
            ],
        );
    }
}
