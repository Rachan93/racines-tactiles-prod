<?php

namespace App\Notifications\Auth;

use App\Mail\Auth\VerifyEmailMail;
use Illuminate\Auth\Notifications\VerifyEmail as BaseVerifyEmail;

class VerifyEmailNotification extends BaseVerifyEmail
{
    public function toMail($notifiable)
    {
        return (new VerifyEmailMail(
            user: $notifiable,
            verificationUrl: $this->verificationUrl($notifiable),
        ))->to($notifiable->email);
    }
}
