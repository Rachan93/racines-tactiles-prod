<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class AdminBroadcastMail extends Mailable
{
    use Queueable;

   public function __construct(
    public mixed $recipient,
    public string $subjectText,
    public string $bodyText,
    public bool $isTest = false,
    public bool $isPreview = false
) {}

    /**
     * Sujet du message.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: ($this->isTest ? '[TEST] ' : '') . $this->subjectText,
        );
    }

    /**
     * Rendu HTML du message.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.admin-broadcast',
            with: [
                'recipient' => $this->recipient,
                'subject' => $this->subjectText,
                'content' => $this->processedBody(),
                'isTest' => $this->isTest,
                'isPreview' => $this->isPreview,
            ],
        );
    }

    /**
     * Remplace les variables personnalisées du message.
     */
    private function processedBody(): string
    {
        $firstName = is_object($this->recipient)
            ? ($this->recipient->first_name ?? 'Membre')
            : 'Membre';

        $lastName = is_object($this->recipient)
            ? ($this->recipient->last_name ?? '')
            : '';

        $email = is_object($this->recipient)
            ? ($this->recipient->email ?? '')
            : (string) $this->recipient;

        return str_replace(
            ['{prenom}', '{nom}', '{email}'],
            [$firstName, $lastName, $email],
            $this->bodyText
        );
    }
}
