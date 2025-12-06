<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PasswordUpdatedSuccessfullyMail extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public function __construct($user)
    {
        $this->user = $user;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Password Updated Successfully',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.password-updated-successfully',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
