<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AccountStatusMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param string $type 'approved' | 'banned' | 'reactivated'
     */
    public function __construct(
        public string $type,
        public string $name,
        public ?string $reason = null,
    ) {}

    public function envelope(): Envelope
    {
        $subject = match ($this->type) {
            'approved'    => 'Tu cuenta fue aprobada',
            'reactivated' => 'Tu cuenta fue reactivada',
            'banned'      => 'Tu cuenta fue desactivada',
            default       => 'Actualización de tu cuenta',
        };

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.account-status');
    }
}
