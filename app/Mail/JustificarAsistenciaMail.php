<?php

namespace App\Mail;

use App\Models\AsistenciaJustificacion;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class JustificarAsistenciaMail extends Mailable
{
    public function __construct(
        public AsistenciaJustificacion $justificacion,
        public string $name,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Justifica tu asistencia del '.$this->justificacion->work_date->format('d/m/Y'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.justificar-asistencia',
        );
    }
}
