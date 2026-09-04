<?php

namespace App\Jobs;

use App\Mail\AdminBroadcastMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendBroadcastEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Nombre maximal de tentatives.
     */
    public int $tries = 3;

    /**
     * Timeout d'un seul envoi.
     */
    public int $timeout = 60;

    public function __construct(
        public string $email,
        public string $firstName,
        public string $lastName,
        public string $subject,
        public string $body
    ) {}

    /**
     * Délais avant les nouvelles tentatives :
     * 30 sec, 2 min, puis 5 min.
     */
    public function backoff(): array
    {
        return [30, 120, 300];
    }

    public function handle(): void
    {
        $recipient = (object) [
            'first_name' => $this->firstName,
            'last_name' => $this->lastName,
            'email' => $this->email,
        ];

        Mail::to($this->email)->send(
            new AdminBroadcastMail(
                recipient: $recipient,
                subjectText: $this->subject,
                bodyText: $this->body
            )
        );
    }

    /**
     * Appelé uniquement si les tentatives ont toutes échoué.
     */
    public function failed(Throwable $exception): void
    {
        Log::error('Échec définitif d’un e-mail groupé', [
            'email' => $this->email,
            'subject' => $this->subject,
            'error' => $exception->getMessage(),
        ]);
    }
}
