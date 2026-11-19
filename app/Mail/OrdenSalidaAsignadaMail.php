<?php

namespace App\Mail;

use App\Models\OrdenSalida;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class OrdenSalidaAsignadaMail extends Mailable
{
    public function __construct(
        public OrdenSalida $orden,
        public string $name,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Te toca preparar el equipo · '.$this->orden->folio.' ('.($this->orden->venta?->folio ?? '').')',
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.orden-salida-asignada');
    }
}
