<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PasswordResetMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public string $token) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Password Reset Request');
    }

    public function content(): Content
    {
        return new Content(
            htmlString: "
                <h2>Password Reset Request</h2>
                <p>Use the token below to reset your password:</p>
                <h3>{$this->token}</h3>
                <p>This token expires in 60 minutes.</p>
                <p>If you did not request this, ignore this email.</p>
            "
        );
    }
}