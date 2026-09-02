<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class UserMail extends Notification /*implements ShouldQueue*/
{
    use Queueable;
    public $validatedData;
    public $user;
    /**
     * Create a new notification instance.
     */
    public function __construct($validatedData, $user)
    {
        $this->validatedData = $validatedData;
        $this->user = $user;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */

     public function via(object $notifiable): array
    {
        $channels = [];

        if (in_array('mail', $this->validatedData['channels'])) {
            $channels[] = 'mail';
        }

        if (in_array('notification', $this->validatedData['channels'])) {
            $channels[] = 'database';
            $channels[] = 'broadcast';
        }

        return $channels;
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)

            ->subject($this->validatedData['subject'])
            ->view('emails.users.mail', ['validatedData' => $this->validatedData, 'user' => $this->user]);
        //         ->line('The introduction to the notification.')
        //         ->action('Notification Action', url('/'))
        //         ->line('Thank you for using our application!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'user_id' => $this->user->id,
            'user_name' => $this->user->last_name . ' ' . $this->user->first_name,
            'validatedData' => $this->validatedData,
        ];
    }

    /**
     * Get the broadcast representation of the notification.
     */
    public function toBroadcast($notifiable)
    {
        return new BroadcastMessage([
            'user_id' => $this->user->id,
            'user_name' => $this->user->last_name . ' ' . $this->user->first_name,
            'validatedData' => $this->validatedData,
        ]);
    }
}
