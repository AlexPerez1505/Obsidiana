<?php

namespace App\Mail;

use App\Models\TiendaSolicitud;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class TiendaSolicitudMail extends Mailable
{
    public function __construct(public TiendaSolicitud $solicitud) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: ($this->solicitud->esContacto() ? 'Nuevo mensaje web ' : 'Nueva solicitud web ')
                .$this->solicitud->folio.' · '.$this->solicitud->nombre,
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.tienda-solicitud');
    }
}
