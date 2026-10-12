<?php

namespace App\Mail;

use App\Models\ClienteSeguimiento;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class SeguimientoRecordatorioMail extends Mailable
{
    public function __construct(
        public ClienteSeguimiento $seguimiento,
        public string $name,
    ) {}

    public function envelope(): Envelope
    {
        $cliente = trim(($this->seguimiento->customer->nombre ?? '').' '.($this->seguimiento->customer->apellido ?? ''));

        return new Envelope(
            subject: 'Recordatorio: '.$this->seguimiento->tipoLabel().' · '.$cliente,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.seguimiento-recordatorio',
        );
    }
}
