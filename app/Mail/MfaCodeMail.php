<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class MfaCodeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public string $code
    ) {}

    public function envelope()
    {
        return [
            'subject' => 'Your DevHub Two-Factor Authentication Code',
        ];
    }

    public function content()
    {
        return view('emails.mfa-code', [
            'user' => $this->user,
            'code' => $this->code,
        ]);
    }
}


