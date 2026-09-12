<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class ContactMail extends Mailable
{
    use Queueable;

    public function __construct(
        public array $contact,
        public bool $isExistingMember = false,
    ) {}
    public function envelope(): Envelope
    {
        return new Envelope(
            replyTo: [
                new Address(
                    $this->contact['email'],
                    trim($this->contact['first_name'] . ' ' . $this->contact['last_name']),
                ),
            ],
            subject: '[Contact] ' . $this->contact['subject'],
        );
    }

    public function content(): Content
{
    return new Content(
        view: 'emails.contact',
        with: [
            'contact' => $this->contact,
            'isExistingMember' => $this->isExistingMember,
        ],
    );
}
}
