<?php

namespace App\Services\Agenda;

use App\Models\AgendaActividad;
use App\Models\AgendaRecordatorio;
use App\Models\ClienteSeguimiento;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Recordatorios de la agenda.
 *
 * programar() calcula qué avisos le tocan a un item y deja la tabla igual a
 * eso: crea los que faltan, mueve los que cambiaron de hora y cancela los
 * que ya no aplican. Se puede llamar las veces que sea (al crear, editar,
 * reprogramar, reasignar, completar o reintentar) sin duplicar nada.
 *
 * despachar() manda los que ya tocan. Cada fila se "aparta" con un UPDATE
 * condicionado antes de enviarse, así dos procesos a la vez no la mandan
 * dos veces.
 */
class Recordatorios
{
    public function __construct(private readonly Avisos $avisos) {}

    private function tz(): string
    {
        return config('agenda.timezone');
    }

    /** Avisa cuando algo sin hora toca ese día (ej. 08:00). */
    private function horaSinHora(string $fecha): CarbonImmutable
    {
        return CarbonImmutable::parse($fecha.' '.config('agenda.hora_aviso_sin_hora'), $this->tz());
    }

    /** @return array<int, int> minutos antes elegidos para el item */
    private function minutosDe(Model $item, ?User $responsable): array
    {
        $lista = $item->recordatorios;

        if ($lista === null) {
            $lista = $responsable ? Avisos::preferencias($responsable)['recordatorios'] : config('agenda.recordatorio_default');
        }

        return array_values(array_unique(array_map('intval', (array) $lista)));
    }

    /**
     * Lo que debería existir para el item: [user_id, tipo, minutos, enviar_en(UTC)].
     *
     * @return array<int, array{0:int,1:string,2:int,3:CarbonImmutable}>
     */
    public function deseados(Model $item): array
    {
        $filas = [];

        if ($item instanceof ClienteSeguimiento) {
            if (! $item->abierto() || ! $item->fecha || ! $item->user_id) {
                return [];
            }

            $fecha = $item->fecha->toDateString();

            if ($item->horaCorta()) {
                $momento = $item->momento();
                foreach ($this->minutosDe($item, $item->responsable) as $m) {
                    $filas[] = [(int) $item->user_id, 'recordatorio', $m, $momento->subMinutes($m)];
                }
                $filas[] = [(int) $item->user_id, 'vencido', 0, $momento->addHour()];
            } else {
                // Pendiente del día: un aviso ese día (o el anterior si eligió "1 día antes").
                $minutos = $this->minutosDe($item, $item->responsable);
                $filas[] = [(int) $item->user_id, 'recordatorio', 0, $this->horaSinHora($fecha)];
                if (in_array(1440, $minutos, true)) {
                    $filas[] = [(int) $item->user_id, 'recordatorio', 1440, $this->horaSinHora($fecha)->subDay()];
                }
                $filas[] = [(int) $item->user_id, 'vencido', 0, $this->horaSinHora($fecha)->addDay()];
            }

            return $filas;
        }

        if ($item instanceof AgendaActividad) {
            if ($item->cerrada() || $item->trashed()) {
                return [];
            }

            $destinatarios = collect([(int) $item->responsable_id])
                ->merge($item->participantes()->wherePivot('respuesta', '!=', 'rechazada')->pluck('users.id')->map(fn ($id) => (int) $id))
                ->filter()->unique()->values();

            $minutos = $this->minutosDe($item, $item->responsable);
            $conHora = $item->hora_inicio && ! $item->todo_el_dia;

            foreach ($destinatarios as $uid) {
                foreach ($minutos as $m) {
                    $cuando = $conHora
                        ? $item->inicio()->subMinutes($m)
                        : ($m >= 1440 ? $this->horaSinHora($item->fecha_inicio->toDateString())->subDay() : $this->horaSinHora($item->fecha_inicio->toDateString()));
                    $filas[] = [$uid, 'recordatorio', $m, $cuando];
                }
            }

            if ($item->esTarea() && $item->responsable_id) {
                $vence = $conHora ? $item->fin()->addHour() : $this->horaSinHora($item->fecha_fin->toDateString())->addDay();
                $filas[] = [(int) $item->responsable_id, 'vencido', 0, $vence];
            }
        }

        return $filas;
    }

    /** Deja los recordatorios del item exactamente como deben estar. */
    public function programar(Model $item): void
    {
        $item->refresh();
        $existentes = AgendaRecordatorio::where('item_type', $item->getMorphClass())->where('item_id', $item->getKey())
            ->where('tipo', '!=', 'pospuesto')->get()
            ->keyBy(fn ($r) => $r->user_id.'|'.$r->tipo.'|'.$r->minutos_antes);

        $quedan = [];

        foreach ($this->deseados($item) as [$uid, $tipo, $min, $cuando]) {
            $llave = $uid.'|'.$tipo.'|'.$min;
            $quedan[$llave] = true;
            $enviarEn = $cuando->utc();
            $fila = $existentes->get($llave);

            if (! $fila) {
                AgendaRecordatorio::query()->insertOrIgnore([[
                    'item_type' => $item->getMorphClass(), 'item_id' => $item->getKey(), 'user_id' => $uid,
                    'tipo' => $tipo, 'minutos_antes' => $min, 'enviar_en' => $enviarEn->toDateTimeString(),
                    'estado' => 'pendiente', 'created_at' => now(), 'updated_at' => now(),
                ]]);

                continue;
            }

            // Cambió la hora: se vuelve a avisar en la nueva. Si ya se mandó
            // para esta misma hora, no se repite.
            if ($fila->enviar_en->toDateTimeString() !== $enviarEn->toDateTimeString() || $fila->estado === 'cancelado') {
                $fila->update(['enviar_en' => $enviarEn, 'estado' => 'pendiente', 'enviado_en' => null]);
            }
        }

        foreach ($existentes as $llave => $fila) {
            if (! isset($quedan[$llave]) && $fila->estado === 'pendiente') {
                $fila->update(['estado' => 'cancelado']);
            }
        }

        // Un item cerrado tampoco conserva avisos pospuestos.
        if (! $quedan) {
            $this->cancelar($item);
        }
    }

    public function cancelar(Model $item): void
    {
        AgendaRecordatorio::where('item_type', $item->getMorphClass())->where('item_id', $item->getKey())
            ->where('estado', 'pendiente')->update(['estado' => 'cancelado', 'updated_at' => now()]);
    }

    /** Vuelve a avisar dentro de X minutos sin mover la actividad. */
    public function posponer(Model $item, User $u, int $minutos): AgendaRecordatorio
    {
        $fila = AgendaRecordatorio::firstOrNew([
            'item_type' => $item->getMorphClass(), 'item_id' => $item->getKey(),
            'user_id' => $u->id, 'tipo' => 'pospuesto', 'minutos_antes' => 0,
        ]);
        $fila->fill(['enviar_en' => now()->addMinutes($minutos), 'estado' => 'pendiente', 'enviado_en' => null])->save();

        return $fila;
    }

    /** Manda los que ya tocan. @return int cuántos salieron */
    public function despachar(?CarbonImmutable $ahora = null): int
    {
        $ahora ??= CarbonImmutable::now();
        $enviados = 0;

        $this->cubrirHuerfanos();

        $ids = AgendaRecordatorio::where('estado', 'pendiente')->where('enviar_en', '<=', $ahora->utc())
            ->orderBy('enviar_en')->limit(500)->pluck('id');

        foreach ($ids as $id) {
            // Se aparta la fila: si otro proceso ya la tomó, no se toca.
            if (DB::table('agenda_recordatorios')->where('id', $id)->where('estado', 'pendiente')->update(['estado' => 'enviando', 'updated_at' => now()]) !== 1) {
                continue;
            }

            $r = AgendaRecordatorio::with(['item', 'usuario'])->find($id);

            try {
                $salio = $r && $this->enviarUno($r, $ahora);
            } catch (\Throwable $e) {
                Log::warning('Recordatorio de agenda falló', ['id' => $id, 'error' => $e->getMessage()]);
                $salio = false;
            }

            DB::table('agenda_recordatorios')->where('id', $id)->update([
                'estado' => $salio ? 'enviado' : 'cancelado',
                'enviado_en' => $salio ? now() : null,
                'updated_at' => now(),
            ]);
            $enviados += $salio ? 1 : 0;
        }

        return $enviados;
    }

    /**
     * Seguimientos que ya tocan y nunca pasaron por el servicio (creados por
     * otro camino, importaciones, datos viejos): se les programan sus avisos
     * para que nadie se quede sin el recordatorio del día.
     */
    private function cubrirHuerfanos(): void
    {
        ClienteSeguimiento::porAvisar()->whereDoesntHave('recordatoriosAgenda')->limit(200)->get()
            ->each(fn (ClienteSeguimiento $s) => $this->programar($s));
    }

    private function enviarUno(AgendaRecordatorio $r, CarbonImmutable $ahora): bool
    {
        $item = $r->item;
        $u = $r->usuario;

        if (! $item || ! $u) {
            return false;
        }

        $tz = $this->tz();
        $local = $ahora->setTimezone($tz);

        if ($item instanceof ClienteSeguimiento) {
            $item->loadMissing('customer');
            if (! $item->abierto() || (int) $item->user_id !== (int) $u->id) {
                return false;
            }

            $cliente = $item->nombreCliente();
            $url = route('agenda.index', ['item' => $item->llaveAgenda()]);

            if ($r->tipo === 'vencido') {
                if (! $item->vencido()) {
                    return false;
                }
                $this->avisos->enviar($u, 'vencido', 'Seguimiento vencido: '.$cliente,
                    $item->tipoLabel().' · era para el '.$item->fecha->format('d/m/Y').($item->horaCorta() ? ' a las '.$item->horaCorta() : '').($item->nota ? ' · '.$item->nota : ''),
                    $url, $item->llaveAgenda());
            } else {
                // Si el momento ya pasó hace rato, ya no es "próximo" (lo pospuesto sí sale).
                $pospuesto = $r->tipo === 'pospuesto';
                if (! $pospuesto && $item->horaCorta() && $item->momento()->lt($local->subHour())) {
                    return false;
                }
                if (! $pospuesto && ! $item->horaCorta() && $item->fecha->toDateString() < $local->toDateString()) {
                    return false;
                }
                $this->avisos->enviar($u, 'recordatorio', $item->tipoLabel().': '.$cliente,
                    ($item->esHoy() ? 'Seguimiento para hoy' : 'Próximo seguimiento el '.$item->fecha->format('d/m/Y'))
                        .($item->horaCorta() ? ' a las '.$item->horaCorta() : ' (sin hora)')
                        .($item->nota ? ' · '.$item->nota : ''),
                    $url, $item->llaveAgenda());
            }

            $item->forceFill(['notificado_en' => now()])->saveQuietly();

            return true;
        }

        if ($item instanceof AgendaActividad) {
            if ($item->cerrada() || $item->trashed()) {
                return false;
            }

            $url = route('agenda.index', ['item' => $item->llaveAgenda()]);
            $cuando = $item->fecha_inicio->format('d/m/Y').($item->hora_inicio && ! $item->todo_el_dia ? ' '.substr($item->hora_inicio, 0, 5) : ' (todo el día)');

            if ($r->tipo === 'vencido') {
                if (! $item->vencida()) {
                    return false;
                }

                return $this->avisos->enviar($u, 'vencido', 'Tarea vencida: '.$item->titulo, $item->tipoLabel().' · era para el '.$cuando, $url, $item->llaveAgenda()) > 0;
            }

            if ($r->tipo !== 'pospuesto' && $item->inicio()->lt($local->subHour()) && ! $item->todo_el_dia) {
                return false;
            }

            return $this->avisos->enviar($u, 'recordatorio', 'Recordatorio: '.$item->titulo,
                $item->tipoLabel().' · '.$cuando.($item->ubicacion ? ' · '.$item->ubicacion : ''), $url, $item->llaveAgenda()) > 0;
        }

        return false;
    }

    /** Respaldo por si el programador de tareas no corre: como mucho una vez por minuto. */
    public function despacharSiToca(): void
    {
        if (! Cache::add('agenda:ultimo_despacho', 1, now()->addMinute())) {
            return;
        }

        try {
            $this->despachar();
        } catch (\Throwable $e) {
            Log::warning('Falló el disparo de respaldo de recordatorios de agenda', ['error' => $e->getMessage()]);
        }
    }
}
