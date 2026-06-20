<?php

namespace App\Mail;

use App\Models\Report;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SupportReportMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User   $user,
        public Report $report,
    )
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Report Support',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.support-report',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
