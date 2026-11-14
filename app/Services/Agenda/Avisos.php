<?php

namespace App\Services\Agenda;

use App\Models\User;
use App\Notifications\AgendaAviso;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Manda los avisos internos de la agenda respetando las preferencias de
 * cada usuario y sin duplicar: el mismo aviso (usuario + tipo + item +
 * contenido) no sale dos veces en 10 minutos aunque la acción se reintente.
 */
class Avisos
{
    public const TIPOS = [
        'asignada' => 'Nueva actividad asignada',
        'recordatorio' => 'Recordatorio',
        'vencido' => 'Seguimiento o tarea vencida',
        'reasignada' => 'Cambio de responsable',
        'reprogramada' => 'Reprogramación',
        'cancelada' => 'Cancelación',
        'invitacion' => 'Invitación a un evento',
        'evento_empresa' => 'Evento general',
        'resumen' => 'Resumen diario',
    ];

    public static function preferencias(User $u): array
    {
        return array_merge([
            'correo' => false,
            'resumen' => true,
            'recordatorios' => config('agenda.recordatorio_default'),
        ], (array) ($u->agenda_preferencias ?? []));
    }

    /** ¿Hay correo de salida configurado de verdad? */
    public static function hayCorreo(): bool
    {
        return ! in_array(config('mail.default'), ['log', 'array', null], true) && filled(config('mail.from.address'));
    }

    /**
     * @param  iterable<User>|User|null  $usuarios
     */
    public function enviar(iterable|User|null $usuarios, string $tipo, string $titulo, string $texto, string $url, ?string $item = null, ?int $excepto = null): int
    {
        if ($usuarios === null) {
            return 0;
        }

        $enviados = 0;
        $usuarios = $usuarios instanceof User ? [$usuarios] : $usuarios;

        foreach ($usuarios as $u) {
            if (! $u || ($excepto && (int) $u->id === (int) $excepto)) {
                continue;
            }

            $huella = 'agenda-aviso:'.sha1($u->id.'|'.$tipo.'|'.$item.'|'.$titulo.'|'.$texto);
            if (! Cache::add($huella, 1, now()->addMinutes(10))) {
                continue;
            }

            $correo = self::preferencias($u)['correo'] && self::hayCorreo();

            try {
                $u->notify(new AgendaAviso($tipo, $titulo, $texto, $url, $item, $correo));
                $enviados++;
            } catch (\Throwable $e) {
                // Si falla el correo, el aviso de la campana ya se guardó.
                Log::warning('Aviso de agenda: falló el envío', ['user' => $u->id, 'tipo' => $tipo, 'error' => $e->getMessage()]);
            }
        }

        return $enviados;
    }

    /** Da por leídos los avisos pendientes de un item (al completar, cancelar o mover). */
    public static function marcarLeidos(string $item, ?int $seguimientoId = null): void
    {
        DB::table('notifications')->whereNull('read_at')
            ->where(function ($q) use ($item, $seguimientoId) {
                $q->where('data->agenda_item', $item);
                if ($seguimientoId) {
                    $q->orWhere('data->seguimiento_id', $seguimientoId);
                }
            })
            ->whereIn('data->agenda_tipo', ['recordatorio', 'vencido'])
            ->update(['read_at' => now()]);

        if ($seguimientoId) {
            // Los avisos del sistema anterior (sin agenda_tipo).
            DB::table('notifications')->whereNull('read_at')->where('data->seguimiento_id', $seguimientoId)->update(['read_at' => now()]);
        }
    }
}
