<?php

namespace App\Mail;

use App\Models\JobNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class GovernmentJobDigestMail extends Mailable
{
    use Queueable, SerializesModels;

    /** @param Collection<int, JobNotification> $notifications */
    public function __construct(public Collection $notifications) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'QuestionYear government job updates',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.government-job-digest',
        );
    }
}
