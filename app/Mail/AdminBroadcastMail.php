<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class AdminBroadcastMail extends Mailable
{
    use Queueable;

    /**
     * Crée une nouvelle instance du message.
     */
    public function __construct(
        public mixed $recipient,
        public string $subjectText,
        public string $bodyText,
        public bool $isTest = false
    ) {}

    /**
     * En-tête de l'e-mail.
     */
    public function envelope(): Envelope
    {
        $prefix = $this->isTest ? '[TEST] ' : '';

        return new Envelope(
            subject: $prefix . $this->subjectText,
        );
    }

    /**
     * Contenu et rendu HTML de l'e-mail.
     */
    public function content(): Content
    {
        $firstName = is_object($this->recipient) ? ($this->recipient->first_name ?? 'Membre') : 'Membre';
        $lastName = is_object($this->recipient) ? ($this->recipient->last_name ?? '') : '';
        $email = is_object($this->recipient) ? ($this->recipient->email ?? '') : (string) $this->recipient;

        // Remplacement automatique des variables magiques
        $processedBody = str_replace(
            ['{prenom}', '{nom}', '{email}'],
            [$firstName, $lastName, $email],
            $this->bodyText
        );

        return new Content(
            view: 'emails.admin-broadcast',
            with: [
                'recipient' => $this->recipient,
                'content' => $processedBody,
                'isTest' => $this->isTest,
            ]
        );
    }
}
