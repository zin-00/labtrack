<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ReportResolvedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $studentName;
    public $reportDescription;
    public $resolvedAt;

    /**
     * Create a new message instance.
     */
    public function __construct($studentName, $reportDescription, $resolvedAt = null)
    {
        $this->studentName = $studentName;
        $this->reportDescription = $reportDescription;
        $this->resolvedAt = $resolvedAt ?? now()->format('F j, Y \a\t g:i A');
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your Report Has Been Resolved - LabTrack',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.report-resolved',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
