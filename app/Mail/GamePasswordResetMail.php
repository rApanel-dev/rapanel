<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class GamePasswordResetMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $userid,
        public readonly string $resetUrl,
        public readonly string $serverName,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "[{$this->serverName}] " . __('Game Account Password Reset'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.game-password-reset',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
