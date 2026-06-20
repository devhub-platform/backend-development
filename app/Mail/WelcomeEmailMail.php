<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class WelcomeEmailMail extends Mailable
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
            from: 'welcome@' . parse_url(config('app.url'), PHP_URL_HOST),
            replyTo: ['devhub-community@outlook.com'],
            subject: 'Welcome to ' . config('app.name') . ' - Get Started Today! 🚀',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.welcome-email',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
