<?php

namespace App\Notifications\Auth;

use App\Mail\Auth\ResetPasswordMail;
use Illuminate\Auth\Notifications\ResetPassword as BaseResetPassword;

class ResetPasswordNotification extends BaseResetPassword
{
    public function toMail($notifiable)
    {
        return (new ResetPasswordMail(
            user: $notifiable,
            resetUrl: $this->resetUrl($notifiable),
        ))->to($notifiable->email);
    }
}
