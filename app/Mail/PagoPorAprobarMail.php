<?php

namespace App\Mail;

use App\Models\CobroSolicitud;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class PagoPorAprobarMail extends Mailable
{
    public function __construct(
        public CobroSolicitud $solicitud,
        public string $name,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Pago por aprobar: $'.number_format((float) $this->solicitud->monto, 2).' · '.$this->solicitud->venta?->folio,
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.pago-por-aprobar');
    }
}
