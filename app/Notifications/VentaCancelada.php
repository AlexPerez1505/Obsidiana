<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * "La venta VEN-2026-0040 se canceló: ya no se prepara; regresa el equipo
 * a su lugar." Llega a la campana y al correo de quien vendió y de quienes
 * preparaban el equipo. Los datos vienen ya armados (la venta puede no
 * existir: si se eliminó, ya se borró al llegar aquí).
 */
class VentaCancelada extends Notification
{
    public function __construct(public array $datos) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toDatabase(object $notifiable): array
    {
        $d = $this->datos;

        return [
            'titulo' => 'Venta '.$this->palabra().' · '.$d['folio'],
            'texto' => $this->resumen(),
            'url' => $this->url(),
            'icono' => 'salidas',
            'venta_id' => $d['venta_id'],
            'orden_salida_id' => $d['orden_salida_id'],
            'equipos' => $d['equipos'],
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $d = $this->datos;
        $mail = (new MailMessage)
            ->subject('Venta '.$this->palabra().': '.$d['folio'].($d['cliente'] ? ' · '.$d['cliente'] : ''))
            ->greeting('Hola, '.$notifiable->name)
            ->line(($d['quien'] ?? 'Alguien').' '.($d['accion'] === 'eliminada' ? 'eliminó' : 'canceló').' la venta '.$d['folio']
                .($d['cliente'] ? ' de '.$d['cliente'] : '').'. Ya no se hizo la venta.');

        if (! empty($d['motivo'])) {
            $mail->line('Motivo: '.$d['motivo']);
        }

        if ($d['orden_folio']) {
            $mail->line('La orden de salida '.$d['orden_folio'].' '.($d['accion'] === 'eliminada' ? 'se eliminó' : 'se canceló').': ya no hay nada que preparar.');
        }

        if (! empty($d['equipos'])) {
            $mail->line($d['hay_que_regresar']
                ? '**Regresa estos equipos a su lugar en el almacén** (en el sistema ya volvieron a stock):'
                : 'Estos equipos ya volvieron a stock en el sistema:');
            foreach ($d['equipos'] as $e) {
                $mail->line('• '.$e['nombre'].' × '.$e['cantidad']
                    .(! empty($e['series']) ? ' — '.implode(', ', $e['series']) : '')
                    .(! empty($e['preparado']) ? ' (ya estaba preparado)' : ''));
            }
        }

        return $mail->action($d['orden_salida_id'] && $d['accion'] !== 'eliminada' ? 'Ver la orden de salida' : 'Ver ventas', $this->url());
    }

    private function palabra(): string
    {
        return $this->datos['accion'] === 'eliminada' ? 'eliminada' : 'cancelada';
    }

    private function resumen(): string
    {
        $d = $this->datos;
        $piezas = collect($d['equipos'])->map(fn ($e) => $e['nombre'].' × '.$e['cantidad']
            .(! empty($e['series']) ? ' ('.implode(', ', $e['series']).')' : ''))->implode('; ');

        return ($d['quien'] ?? 'Alguien').' '.($d['accion'] === 'eliminada' ? 'eliminó' : 'canceló').' la venta '.$d['folio']
            .($d['cliente'] ? ' ('.$d['cliente'].')' : '').': ya no se hizo.'
            .($d['hay_que_regresar'] ? ' Regresa el equipo a su lugar en el almacén' : ' El equipo volvió a stock')
            .($piezas !== '' ? ': '.$piezas.'.' : '.');
    }

    private function url(): string
    {
        $d = $this->datos;
        if ($d['accion'] !== 'eliminada' && $d['orden_salida_id']) {
            return route('inventory.salidas.show', $d['orden_salida_id']);
        }

        return $d['accion'] !== 'eliminada'
            ? route('commercial.ventas.show', $d['venta_id'])
            : route('commercial.ventas.index');
    }
}
