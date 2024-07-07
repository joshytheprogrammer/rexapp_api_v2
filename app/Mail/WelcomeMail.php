<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class WelcomeMail extends Mailable
{
    use Queueable, SerializesModels;
    public $details;
    public $isAdminNotification;

    /**
     * Create a new message instance.
     */

    public function __construct($details, $isAdminNotification = false)
    {
        $this->details = $details;
        $this->isAdminNotification = $isAdminNotification;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        if ($this->isAdminNotification) {
            return new Envelope(
                subject: 'RexApp Information - New User',
            );
        }else {
            return new Envelope(
                subject: 'Welcome '. $this->details['username'],
            );
        }

    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        if ($this->isAdminNotification) {
            return new Content(
                view: 'emails.admin_notification',
                with: $this->details,
            );
        } else {
            return new Content(
                view: 'emails.new_user',
                with: $this->details,
            );
        }
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
