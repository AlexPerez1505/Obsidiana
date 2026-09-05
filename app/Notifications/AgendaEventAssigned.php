<?php

namespace App\Notifications;

use App\Models\AgendaEvent;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class AgendaEventAssigned extends Notification
{
    use Queueable;

    public function __construct(private AgendaEvent $event)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'agenda_event_id' => $this->event->id,
            'title' => $this->event->title,
            'message' => 'Fuiste agregado como participante de la cita "'.$this->event->title.'".',
            'start_date' => $this->event->start_date?->toDateString(),
            'end_date' => $this->event->end_date?->toDateString(),
            'start_time' => $this->event->start_time,
            'type' => $this->event->event_type,
            'created_by' => $this->event->creator?->name,
            'url' => route('admin.agenda.index'),
        ];
    }
}
