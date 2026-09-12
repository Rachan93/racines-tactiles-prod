<?php

namespace App\Mail\Auth;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class ResetPasswordMail extends Mailable
{
    use Queueable;

    public function __construct(
        public mixed $user,
        public string $resetUrl,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Réinitialisation de votre mot de passe',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.auth.reset-password',
            with: [
                'user' => $this->user,
                'resetUrl' => $this->resetUrl,
            ],
        );
    }
}
