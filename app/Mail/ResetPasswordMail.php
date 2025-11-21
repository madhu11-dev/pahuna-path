<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ResetPasswordMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public string $resetUrl)
    {
    }

    public function build()
    {
        return $this
            ->subject('Reset Your Pahuna Path Password')
            ->text('emails.reset-password-plain', [
                'url' => $this->resetUrl,
            ]);
    }
}
