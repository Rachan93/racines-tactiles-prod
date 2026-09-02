<?php

namespace App\Jobs;

use App\Mail\AdminBroadcastMail;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendBulkEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Timeout du job (10 minutes max).
     */
    public int $timeout = 600;

    /**
     * Crée une nouvelle tâche d'envoi groupé.
     */
    public function __construct(
        public array $recipientIds,
        public array $customEmails,
        public string $subject,
        public string $body
    ) {}

    /**
     * Exécute la tâche en arrière-plan.
     */
    public function handle(): void
    {
        // 1. Envoi aux membres inscrits en base
        if (! empty($this->recipientIds)) {
            $users = User::whereIn('id', $this->recipientIds)->get(['id', 'first_name', 'last_name', 'email']);

            foreach ($users as $user) {
                if (empty($user->email) || ! filter_var($user->email, FILTER_VALIDATE_EMAIL)) {
                    continue;
                }

                try {
                    Mail::to($user->email)->send(
                        new AdminBroadcastMail(
                            recipient: $user,
                            subjectText: $this->subject,
                            bodyText: $this->body
                        )
                    );
                    Log::info("E-mail groupé envoyé avec succès au membre ID {$user->id} ({$user->email})");
                } catch (\Throwable $e) {
                    Log::error("Échec d'envoi d'e-mail groupé au membre ID {$user->id} ({$user->email}): " . $e->getMessage());
                }

                // Pause de 1s entre chaque envoi pour respecter les quotas Mailtrap / SMTP
                usleep(2000000);
            }
        }

        // 2. Envoi aux adresses e-mails externes / libres
        if (! empty($this->customEmails)) {
            foreach ($this->customEmails as $email) {
                $cleanEmail = trim((string) $email);

                if (empty($cleanEmail) || ! filter_var($cleanEmail, FILTER_VALIDATE_EMAIL)) {
                    continue;
                }

                $pseudoRecipient = (object) [
                    'first_name' => explode('@', $cleanEmail)[0],
                    'last_name' => '',
                    'email' => $cleanEmail,
                ];

                try {
                    Mail::to($cleanEmail)->send(
                        new AdminBroadcastMail(
                            recipient: $pseudoRecipient,
                            subjectText: $this->subject,
                            bodyText: $this->body
                        )
                    );
                    Log::info("E-mail groupé envoyé avec succès à l'adresse externe ({$cleanEmail})");
                } catch (\Throwable $e) {
                    Log::error("Échec d'envoi d'e-mail groupé à l'adresse externe ({$cleanEmail}): " . $e->getMessage());
                }

                // Pause de 1s entre chaque envoi
                usleep(2000000);
            }
        }
    }
}
