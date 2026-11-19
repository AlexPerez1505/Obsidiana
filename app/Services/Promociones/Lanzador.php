<?php

namespace App\Services\Promociones;

use App\Jobs\EnviarMensajeCampanaJob;
use App\Models\Campana;
use App\Models\CampanaDestinatario;
use App\Models\CampanaItem;
use App\Models\Cupon;
use App\Models\Customer;
use App\Models\MktEvento;
use App\Services\WhatsApp\Telefono;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Programar, enviar, pausar, reanudar, cancelar y duplicar campañas, y
 * mandar pruebas. Nada se manda directo: todo pasa por la cola
 * (EnviarMensajeCampanaJob), que respeta el ritmo y reintenta.
 */
class Lanzador
{
    public function __construct(private readonly Audiencia $audiencia)
    {
    }

    public function programar(Campana $campana, \DateTimeInterface $cuando): void
    {
        $this->exigir($campana->puedeEnviarse(), 'Esta campaña ya se envió o se canceló.');
        $this->exigir($cuando > now(), 'La fecha programada debe ser futura.');
        $this->exigirContenido($campana);

        $campana->update(['estado' => Campana::PROGRAMADA, 'programada_para' => $cuando, 'actualizado_por' => auth()->id()]);
        MktEvento::registrar('programada', 'Programó el envío para el '.$campana->programada_para->format('d/m/Y H:i'), $campana);
    }

    /**
     * Arma la lista definitiva (con las exclusiones del momento), crea un
     * renglón por destinatario con su enlace y su cupón, y encola los envíos.
     *
     * @return int cuántos mensajes se encolaron
     */
    public function iniciarEnvio(Campana $campana): int
    {
        $creados = DB::transaction(function () use ($campana) {
            /** @var Campana $c */
            $c = Campana::whereKey($campana->id)->lockForUpdate()->firstOrFail();
            $this->exigir($c->puedeEnviarse(), 'Esta campaña ya se envió o se canceló.');
            $this->exigirContenido($c);

            $aud = $this->audiencia->calcular($c->filtros ?? []);
            $this->exigir($aud['elegibles']->isNotEmpty(), 'Ningún cliente califica para esta campaña (con el segmento elegido, consentimiento vigente y teléfono válido).');

            $plantilla = $c->cupon;
            // Un cupón general queda ligado a la campaña para atribuirle las ventas.
            if ($plantilla && ! $c->cupon_exclusivo && ! $plantilla->campana_id) {
                $plantilla->update(['campana_id' => $c->id]);
            }

            $n = 0;
            foreach ($aud['elegibles'] as $cliente) {
                $d = CampanaDestinatario::firstOrCreate(
                    ['campana_id' => $c->id, 'clave' => 'c'.$cliente->id],
                    [
                        'cliente_id' => $cliente->id,
                        'es_prueba' => false,
                        'token' => CampanaDestinatario::nuevoToken(),
                        'telefono' => Telefono::e164($cliente->telefono),
                        'estado' => CampanaDestinatario::PENDIENTE,
                    ]
                );

                if ($d->wasRecentlyCreated) {
                    if ($plantilla && $c->cupon_exclusivo) {
                        $d->update(['cupon_id' => $this->cuponExclusivo($plantilla, $cliente, $c)->id]);
                    }
                    $n++;
                }
            }

            $c->update([
                'estado' => Campana::ENVIANDO,
                'iniciada_en' => now(),
                'enviado_por' => auth()->id() ?? $c->enviado_por,
            ]);

            MktEvento::registrar('envio_iniciado', "Inició el envío a {$n} destinatario(s)", $c, null, [
                'destinatarios' => $n, 'excluidos' => $aud['excluidos'], 'automatico' => auth()->guest(),
            ]);

            return $n;
        });

        $this->encolarPendientes($campana);

        return $creados;
    }

    public function pausar(Campana $campana): void
    {
        $this->exigir($campana->puedePausarse(), 'Solo se pausa una campaña programada o enviándose.');
        $campana->update(['estado' => Campana::PAUSADA, 'pausada_en' => now(), 'actualizado_por' => auth()->id()]);
        MktEvento::registrar('pausada', 'Pausó la campaña', $campana);
    }

    public function reanudar(Campana $campana): void
    {
        $this->exigir($campana->puedeReanudarse(), 'La campaña no está pausada.');

        // Si se pausó antes de empezar, vuelve a quedar programada (o se manda ya).
        if (! $campana->iniciada_en) {
            $campana->update(['estado' => Campana::PROGRAMADA, 'pausada_en' => null]);
            MktEvento::registrar('reanudada', 'Reanudó la campaña (sigue programada)', $campana);
            if (! $campana->programada_para || $campana->programada_para->isPast()) {
                $this->iniciarEnvio($campana);
            }

            return;
        }

        $campana->update(['estado' => Campana::ENVIANDO, 'pausada_en' => null, 'actualizado_por' => auth()->id()]);
        MktEvento::registrar('reanudada', 'Reanudó el envío', $campana);
        $this->encolarPendientes($campana);
        $this->cerrarSiTermino($campana);
    }

    public function cancelar(Campana $campana): int
    {
        $this->exigir($campana->puedeCancelarse(), 'Esta campaña ya terminó o ya estaba cancelada.');

        $n = DB::transaction(function () use ($campana) {
            $n = $campana->destinatarios()->where('estado', CampanaDestinatario::PENDIENTE)
                ->update(['estado' => CampanaDestinatario::CANCELADO]);
            $campana->update([
                'estado' => Campana::CANCELADA, 'cancelada_en' => now(), 'cancelado_por' => auth()->id(),
            ]);

            return $n;
        });

        MktEvento::registrar('cancelada', 'Canceló la campaña'.($n ? " ({$n} envío(s) pendientes ya no salieron)" : ''), $campana);

        return $n;
    }

    public function duplicar(Campana $campana): Campana
    {
        return DB::transaction(function () use ($campana) {
            $copia = $campana->replicate([
                'estado', 'programada_para', 'iniciada_en', 'pausada_en', 'terminada_en', 'cancelada_en',
                'prueba_enviada_en', 'enviado_por', 'cancelado_por', 'actualizado_por',
            ]);
            $copia->nombre = mb_substr('Copia de '.$campana->nombre, 0, 255);
            $copia->estado = Campana::BORRADOR;
            $copia->creado_por = auth()->id() ?? $campana->creado_por;
            $copia->save();

            foreach ($campana->items as $i) {
                CampanaItem::create(['campana_id' => $copia->id, 'tipo_item' => $i->tipo_item, 'ref_id' => $i->ref_id, 'orden' => $i->orden]);
            }

            MktEvento::registrar('duplicada', 'Creó la campaña como copia de "'.$campana->nombre.'"', $copia);
            MktEvento::registrar('duplicada', 'Se duplicó en la campaña #'.$copia->id, $campana);

            return $copia;
        });
    }

    /**
     * Prueba a un número autorizado. Con un cliente elegido, el mensaje,
     * el enlace y el cupón quedan a su nombre (para probar la compra
     * completa); la prueba nunca cuenta en los resultados de la campaña.
     */
    public function enviarPrueba(Campana $campana, string $telefono, ?Customer $cliente = null): CampanaDestinatario
    {
        $tel = Telefono::e164($telefono);
        $autorizados = array_map(fn ($n) => Telefono::e164($n), config('promociones.numeros_prueba'));
        $this->exigir($tel && in_array($tel, $autorizados, true), 'Ese número no está autorizado para pruebas (PROMO_NUMEROS_PRUEBA).');
        $this->exigir($campana->estado !== Campana::CANCELADA, 'La campaña está cancelada.');
        $this->exigirContenido($campana);

        $d = DB::transaction(function () use ($campana, $tel, $cliente) {
            $cupon = null;
            if ($campana->cupon && $campana->cupon_exclusivo && $cliente) {
                $cupon = $this->cuponExclusivo($campana->cupon, $cliente, null, true);
            }

            return CampanaDestinatario::create([
                'campana_id' => $campana->id,
                'cliente_id' => $cliente?->id,
                'clave' => 'p'.str_replace('-', '', (string) \Illuminate\Support\Str::uuid()),
                'es_prueba' => true,
                'token' => CampanaDestinatario::nuevoToken(),
                'telefono' => $tel,
                'cupon_id' => $cupon?->id,
                'estado' => CampanaDestinatario::PENDIENTE,
            ]);
        });

        EnviarMensajeCampanaJob::dispatchSync($d->id, true);

        $campana->update(['prueba_enviada_en' => now()]);
        $d->refresh();
        MktEvento::registrar('prueba', 'Mandó una prueba a '.Telefono::ocultar($tel).' → '.$d->estadoLabel(), $campana, null, [
            'destinatario_id' => $d->id, 'cliente_id' => $cliente?->id,
        ]);

        return $d;
    }

    /** Lo que hace el comando cada minuto. */
    public function tareasPeriodicas(): array
    {
        $lanzadas = 0;
        foreach (Campana::where('estado', Campana::PROGRAMADA)->where('programada_para', '<=', now())->get() as $c) {
            try {
                $this->iniciarEnvio($c);
                $lanzadas++;
            } catch (ValidationException $e) {
                $c->update(['estado' => Campana::BORRADOR]);
                MktEvento::registrar('programacion_fallida', 'No se pudo enviar a la hora programada: '.collect($e->errors())->flatten()->first(), $c);
            }
        }

        // Un envío que quedó "enviando" más de 15 min es un proceso que se
        // cayó a la mitad: no se reintenta (podría duplicarse), se marca.
        $atascados = CampanaDestinatario::where('estado', CampanaDestinatario::ENVIANDO)
            ->where('updated_at', '<', now()->subMinutes(15))
            ->update([
                'estado' => CampanaDestinatario::FALLO, 'fallo_en' => now(), 'error_codigo' => 'interrumpido',
                'error' => 'El envío se interrumpió y no se reintentó para no duplicar el mensaje. Revisa si llegó.',
            ]);

        $cerradas = 0;
        foreach (Campana::where('estado', Campana::ENVIANDO)->get() as $c) {
            $cerradas += $this->cerrarSiTermino($c) ? 1 : 0;
        }

        $liberadas = app(MotorDeCupones::class)->liberarReservasVencidas();

        return compact('lanzadas', 'atascados', 'cerradas', 'liberadas');
    }

    public function cerrarSiTermino(Campana $campana): bool
    {
        $campana->refresh();
        if ($campana->estado !== Campana::ENVIANDO) {
            return false;
        }

        $faltan = $campana->destinatarios()->where('es_prueba', false)
            ->whereIn('estado', [CampanaDestinatario::PENDIENTE, CampanaDestinatario::ENVIANDO])->exists();

        if ($faltan) {
            return false;
        }

        $actualizadas = Campana::whereKey($campana->id)->where('estado', Campana::ENVIANDO)
            ->update(['estado' => Campana::COMPLETADA, 'terminada_en' => now()]);

        if ($actualizadas) {
            MktEvento::create(['campana_id' => $campana->id, 'accion' => 'completada', 'detalle' => 'Terminó el envío de todos los mensajes']);
        }

        return (bool) $actualizadas;
    }

    // ------------------------------------------------------------------

    private function encolarPendientes(Campana $campana): void
    {
        $campana->destinatarios()->where('estado', CampanaDestinatario::PENDIENTE)->where('es_prueba', false)
            ->pluck('id')
            ->each(fn ($id) => EnviarMensajeCampanaJob::dispatch($id));
    }

    /** Copia personal del cupón molde: mismo descuento y reglas, código propio, ligado al cliente. */
    private function cuponExclusivo(Cupon $molde, Customer $cliente, ?Campana $campana, bool $prueba = false): Cupon
    {
        $copia = $molde->replicate(['codigo', 'cliente_id', 'origen_cupon_id', 'campana_id', 'usos_max', 'creado_por', 'actualizado_por']);
        $copia->codigo = Cupon::codigoAleatorio(substr($molde->codigo, 0, 12));
        $copia->tipo = Cupon::TIPO_EXCLUSIVO;
        $copia->cliente_id = $cliente->id;
        $copia->origen_cupon_id = $molde->id;
        $copia->campana_id = $campana?->id;
        $copia->usos_max = max(1, (int) $molde->usos_por_cliente);
        $copia->nombre = mb_substr($molde->nombre.($prueba ? ' (prueba)' : ''), 0, 255);
        $copia->creado_por = auth()->id();
        $copia->save();

        foreach ($molde->alcances as $a) {
            $copia->alcances()->create(['tipo' => $a->tipo, 'ref_id' => $a->ref_id]);
        }

        return $copia;
    }

    private function exigirContenido(Campana $c): void
    {
        $this->exigir(filled($c->mensaje), 'La campaña no tiene texto de oferta.');
        $this->exigir($c->items()->exists(), 'Agrega al menos un producto a la oferta.');
        $this->exigir(! ($c->vigencia_fin && $c->vigencia_fin->isPast()), 'La vigencia de la oferta ya terminó.');
        if ($c->cupon) {
            $estado = $c->cupon->estadoReal();
            $this->exigir(! in_array($estado, ['pausado', 'vencido', 'agotado'], true), 'El cupón de la campaña está '.Cupon::etiquetaEstado($estado).'.');
            $this->exigir(! $c->cupon_exclusivo || $c->cupon->esPlantilla(), 'Para códigos personales elige un cupón "exclusivo" sin cliente (molde).');
        }
    }

    private function exigir(bool $cond, string $mensaje): void
    {
        if (! $cond) {
            throw ValidationException::withMessages(['campana' => $mensaje]);
        }
    }
}
