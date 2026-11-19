<?php

namespace App\Services\Garantias;

use App\Models\Garantia;
use App\Models\GarantiaArchivo;
use App\Models\GarantiaCosto;
use App\Models\GarantiaReclamacion;
use App\Models\GarantiaSustitucion;
use App\Models\User;
use App\Notifications\AvisoDeGarantia;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

/**
 * Todo lo que le pasa a una reclamación, con sus reglas:
 *
 *  1. Toda reclamación lleva folio, aunque no se encuentre la garantía.
 *  2. Negar la garantía exige motivo, fundamento y al menos una evidencia.
 *  3. Dar por buena una garantía vencida, anulada o inexistente es una
 *     excepción: solo la autoriza quien tiene permiso de aprobar.
 *  4. Un gasto arriba del límite queda "por aprobar" hasta que alguien
 *     con permiso lo autorice.
 *  5. Un cambio definitivo de equipo deja registrada la serie anterior y la nueva.
 *  6. Un posible incidente de seguridad (tecnovigilancia) se escala desde
 *     el primer minuto, esté o no vigente la garantía.
 *  7. No se cierra con dictamen pendiente, gastos sin aprobar o préstamos sin regresar.
 */
class AtencionDeReclamaciones
{
    public function abrir(array $data, ?User $usuario, array $archivos = []): GarantiaReclamacion
    {
        return DB::transaction(function () use ($data, $usuario, $archivos) {
            $garantia = ! empty($data['garantia_id'])
                ? Garantia::find($data['garantia_id'])
                : $this->buscarPorSerie($data['no_serie'] ?? null);

            $seguridad = (bool) ($data['es_seguridad'] ?? false);
            $prioridad = $seguridad ? 'critica' : ($data['prioridad'] ?? 'media');
            $recibida = now();

            $rec = GarantiaReclamacion::create([
                'garantia_id' => $garantia?->id,
                'customer_id' => $garantia?->customer_id ?? ($data['customer_id'] ?? null),
                'no_serie' => $garantia?->no_serie ?? ($data['no_serie'] ?? null),
                'equipo' => $garantia ? null : ($data['equipo'] ?? null),
                'canal' => $data['canal'] ?? 'telefono',
                'contacto_nombre' => $data['contacto_nombre'] ?? null,
                'contacto_telefono' => $data['contacto_telefono'] ?? null,
                'contacto_correo' => $data['contacto_correo'] ?? null,
                'sintomas' => $data['sintomas'],
                'prioridad' => $prioridad,
                'es_seguridad' => $seguridad,
                'etapa' => 'recibida',
                'responsable_id' => $data['responsable_id'] ?? $garantia?->responsable_id ?? $usuario?->id,
                'recibida_en' => $recibida,
                // Si la captura alguien del equipo (llamada, visita) el cliente ya
                // habló con una persona; si llega por el portal, todavía no.
                'primera_respuesta_en' => $usuario && ($data['canal'] ?? '') !== 'portal' ? $recibida : null,
                'limite_en' => $recibida->copy()->addHours($this->horasPara($prioridad)),
                'created_by' => $usuario?->id,
            ]);

            $cobertura = match (true) {
                ! $garantia => 'sin garantía localizada',
                $garantia->anulada() => 'garantía anulada',
                ! $garantia->vigente() => 'garantía vencida el '.$garantia->vence->format('d/m/Y'),
                default => 'garantía vigente hasta el '.$garantia->vence->format('d/m/Y'),
            };

            $rec->registrar($rec->canal === 'portal' ? 'portal' : 'creada',
                'Reclamación recibida por '.mb_strtolower($rec->canalLabel()).' · '.$cobertura.'.');

            foreach ($archivos as $archivo) {
                $this->guardarArchivo($archivo, 'evidencia', $usuario, $rec);
            }

            if ($seguridad) {
                $rec->registrar('seguridad', 'Marcada como posible incidente de seguridad: se evalúa por tecnovigilancia, '
                    .'independientemente de la vigencia de la garantía.');
            }

            $this->avisarNueva($rec);

            return $rec;
        });
    }

    public function buscarPorSerie(?string $serie): ?Garantia
    {
        $serie = trim((string) $serie);
        if ($serie === '') {
            return null;
        }

        return Garantia::where(fn ($q) => $q->where('no_serie', $serie)->orWhere('codigo_interno', $serie))
            ->orderByRaw("estado = 'activa' desc")
            ->latest('vence')
            ->first();
    }

    public function vincularGarantia(GarantiaReclamacion $rec, Garantia $garantia): void
    {
        $rec->update([
            'garantia_id' => $garantia->id,
            'customer_id' => $rec->customer_id ?: $garantia->customer_id,
            'no_serie' => $garantia->no_serie ?: $rec->no_serie,
        ]);
        $this->responder($rec);
        $rec->registrar('vinculada', 'Se ligó a la garantía '.$garantia->folio.' ('.$garantia->equipoNombre().').');
    }

    public function avanzar(GarantiaReclamacion $rec, string $etapa, ?string $nota = null): void
    {
        $orden = array_keys(GarantiaReclamacion::ETAPAS);

        if (! in_array($etapa, $orden, true) || $etapa === GarantiaReclamacion::CERRADA) {
            throw ValidationException::withMessages(['etapa' => 'Para cerrar la reclamación usa "Cerrar".']);
        }
        if (! $rec->abierta()) {
            throw ValidationException::withMessages(['etapa' => 'La reclamación ya está cerrada.']);
        }

        $destino = array_search($etapa, $orden, true);
        $requiereDictamen = array_search('atencion', $orden, true);

        if ($destino >= $requiereDictamen && $rec->dictamen === 'pendiente') {
            throw ValidationException::withMessages(['etapa' => 'Primero dictamina si la garantía procede.']);
        }
        if ($etapa === 'atencion' && $rec->dictamen === 'no_procede') {
            throw ValidationException::withMessages(['etapa' => 'La garantía no procede: pasa directo a entrega o cierre.']);
        }

        $antes = $rec->etapaLabel();
        $rec->update(['etapa' => $etapa]);
        $this->responder($rec);
        $rec->registrar('etapa', 'Etapa: '.$antes.' → '.$rec->etapaLabel().'.'.($nota ? ' '.$nota : ''));
    }

    public function diagnosticar(GarantiaReclamacion $rec, array $data): void
    {
        $rec->update([
            'diagnostico' => $data['diagnostico'] ?? null,
            'pruebas' => $data['pruebas'] ?? null,
            'causa' => $data['causa'] ?? null,
        ]);
        $this->responder($rec);
        $rec->registrar('diagnostico', 'Diagnóstico técnico actualizado.', array_filter([
            'diagnostico' => $data['diagnostico'] ?? null,
            'causa' => $data['causa'] ?? null,
        ]));
    }

    public function dictaminar(GarantiaReclamacion $rec, array $data, User $usuario): void
    {
        $dictamen = $data['dictamen'];
        $garantia = $rec->garantia;

        if ($dictamen === 'no_procede') {
            if (blank($data['motivo'] ?? null) || blank($data['fundamento'] ?? null)) {
                throw ValidationException::withMessages(['motivo' => 'Para negar la garantía escribe el motivo y su fundamento (cláusula de la póliza o disposición aplicable).']);
            }
            if (! $rec->archivos()->exists()) {
                throw ValidationException::withMessages(['motivo' => 'Para negar la garantía adjunta al menos una evidencia (fotos, reporte técnico...).']);
            }
        }

        $excepcion = in_array($dictamen, ['procede', 'parcial'], true) && (! $garantia || ! $garantia->vigente());
        if ($excepcion && ! $usuario->can('garantias.aprobar')) {
            throw ValidationException::withMessages(['dictamen' => 'La garantía no está vigente: aceptarla es una excepción que solo autoriza quien tiene permiso de aprobar.']);
        }

        $rec->update([
            'dictamen' => $dictamen,
            'dictamen_motivo' => $data['motivo'] ?? null,
            'dictamen_fundamento' => $data['fundamento'] ?? null,
            'dictamen_por' => $usuario->id,
            'dictamen_en' => now(),
        ]);
        $this->responder($rec);

        $rec->registrar($excepcion ? 'aprobacion' : 'dictamen',
            'Dictamen: '.$rec->dictamenLabel().($excepcion ? ' (excepción autorizada fuera de cobertura)' : '').'.'
            .(filled($data['motivo'] ?? null) ? ' Motivo: '.$data['motivo'] : ''),
            array_filter(['fundamento' => $data['fundamento'] ?? null]));
    }

    public function registrarCosto(GarantiaReclamacion $rec, array $data, User $usuario): GarantiaCosto
    {
        $monto = (float) $data['monto'];
        $necesita = $monto > (float) config('garantias.limite_aprobacion', 5000);
        $autoriza = $necesita && $usuario->can('garantias.aprobar');

        $costo = $rec->costos()->create([
            'garantia_id' => $rec->garantia_id,
            'concepto' => $data['concepto'],
            'descripcion' => $data['descripcion'] ?? null,
            'monto' => $monto,
            'absorbe' => $data['absorbe'] ?? 'medibuy',
            'estado' => $necesita && ! $autoriza ? 'pendiente' : 'aprobado',
            'aprobado_por' => $autoriza ? $usuario->id : null,
            'aprobado_en' => $autoriza ? now() : null,
            'created_by' => $usuario->id,
        ]);

        $this->responder($rec);
        $rec->registrar('costo', 'Gasto: '.$costo->conceptoLabel().' $'.number_format($monto, 2)
            .' · lo absorbe '.$costo->absorbeLabel().($costo->estado === 'pendiente' ? ' · espera aprobación' : '').'.');

        if ($costo->estado === 'pendiente') {
            Notification::send($this->quienesPueden('garantias.aprobar'), new AvisoDeGarantia(
                'Gasto por aprobar en '.$rec->folio,
                $costo->conceptoLabel().' por $'.number_format($monto, 2).' supera el límite de $'
                    .number_format((float) config('garantias.limite_aprobacion'), 2).'.',
                route('garantias.reclamaciones.show', $rec),
            ));
        }

        return $costo;
    }

    public function resolverCosto(GarantiaCosto $costo, bool $aprobar, User $usuario): void
    {
        if ($costo->estado !== 'pendiente') {
            throw ValidationException::withMessages(['costo' => 'Ese gasto ya se resolvió.']);
        }

        $costo->update([
            'estado' => $aprobar ? 'aprobado' : 'rechazado',
            'aprobado_por' => $usuario->id,
            'aprobado_en' => now(),
        ]);

        $costo->reclamacion->registrar('aprobacion', ($aprobar ? 'Aprobado' : 'Rechazado').' el gasto de '
            .$costo->conceptoLabel().' por $'.number_format((float) $costo->monto, 2).'.');
    }

    public function marcarRecuperado(GarantiaCosto $costo, float $monto): void
    {
        $monto = min(max(0, $monto), (float) $costo->monto);
        $costo->update(['recuperado' => $monto]);
        $costo->reclamacion->registrar('costo', 'Recuperado del fabricante: $'.number_format($monto, 2)
            .' de $'.number_format((float) $costo->monto, 2).' ('.$costo->conceptoLabel().').');
    }

    public function sustituir(GarantiaReclamacion $rec, array $data, User $usuario): GarantiaSustitucion
    {
        return DB::transaction(function () use ($rec, $data, $usuario) {
            $garantia = $rec->garantia;
            $definitiva = $data['tipo'] === 'definitiva';

            if ($definitiva && $rec->dictamen === 'pendiente') {
                throw ValidationException::withMessages(['tipo' => 'Una sustitución definitiva necesita dictamen.']);
            }

            $sust = $rec->sustituciones()->create([
                'garantia_id' => $garantia?->id,
                'tipo' => $data['tipo'],
                'equipo' => $data['equipo'],
                'no_serie_entregado' => $data['no_serie_entregado'] ?? null,
                'no_serie_anterior' => $definitiva ? ($garantia?->no_serie ?? $rec->no_serie) : null,
                'entregado_en' => $data['entregado_en'] ?? today(),
                'devolver_antes' => $definitiva ? null : ($data['devolver_antes'] ?? null),
                'condiciones' => $data['condiciones'] ?? null,
                'created_by' => $usuario->id,
            ]);

            if ($definitiva && $garantia && filled($sust->no_serie_entregado)) {
                $garantia->update([
                    'no_serie' => $sust->no_serie_entregado,
                    'producto_serial_id' => null,
                    'codigo_interno' => null,
                ]);
                $garantia->registrar('sustitucion', 'Equipo sustituido: serie '.($sust->no_serie_anterior ?: 'sin serie')
                    .' → '.$sust->no_serie_entregado.' (reclamación '.$rec->folio.').', [
                        'serie_anterior' => $sust->no_serie_anterior,
                        'serie_nueva' => $sust->no_serie_entregado,
                    ], $rec);
            } else {
                $rec->registrar('sustitucion', $sust->tipoLabel().': '.$sust->equipo
                    .($sust->no_serie_entregado ? ' (serie '.$sust->no_serie_entregado.')' : '')
                    .($sust->devolver_antes ? ' · regresa a más tardar el '.$sust->devolver_antes->format('d/m/Y') : '').'.');
            }

            $this->responder($rec);

            return $sust;
        });
    }

    public function devolverPrestamo(GarantiaSustitucion $sust): void
    {
        $sust->update(['devuelto_en' => today()]);
        $sust->reclamacion->registrar('sustitucion', 'El cliente regresó el equipo de préstamo '.$sust->equipo.'.');
    }

    public function vincularServicio(GarantiaReclamacion $rec, ?int $serviceId): void
    {
        $rec->update(['service_id' => $serviceId]);
        $this->responder($rec);
        $rec->registrar('servicio', $serviceId
            ? 'Ligada a la orden de servicio '.($rec->service?->service_number ?? '#'.$serviceId).'.'
            : 'Se quitó la orden de servicio ligada.');
    }

    public function anotar(GarantiaReclamacion $rec, string $nota): void
    {
        $this->responder($rec);
        $rec->registrar('nota', $nota);
    }

    public function cerrar(GarantiaReclamacion $rec, array $data): void
    {
        if ($rec->dictamen === 'pendiente') {
            throw ValidationException::withMessages(['resolucion' => 'No se puede cerrar sin dictamen.']);
        }
        if ($rec->costos()->where('estado', 'pendiente')->exists()) {
            throw ValidationException::withMessages(['resolucion' => 'Hay gastos esperando aprobación.']);
        }
        if ($rec->sustituciones()->where('tipo', 'prestamo')->whereNull('devuelto_en')->exists()) {
            throw ValidationException::withMessages(['resolucion' => 'El cliente todavía tiene un equipo de préstamo.']);
        }

        $rec->update([
            'etapa' => GarantiaReclamacion::CERRADA,
            'resolucion' => $data['resolucion'],
            'resolucion_detalle' => $data['resolucion_detalle'] ?? null,
            'entregado_a' => $data['entregado_a'] ?? null,
            'satisfaccion' => $data['satisfaccion'] ?? null,
            'comentario_cliente' => $data['comentario_cliente'] ?? null,
            'cerrada_en' => now(),
        ]);
        $this->responder($rec);

        $rec->registrar('cerrada', 'Reclamación cerrada: '.(GarantiaReclamacion::RESOLUCIONES[$rec->resolucion] ?? $rec->resolucion)
            .($rec->entregado_a ? ' · recibió '.$rec->entregado_a : '').'.');
    }

    public function guardarArchivo(UploadedFile $archivo, string $tipo, ?User $usuario, ?GarantiaReclamacion $rec = null, ?Garantia $garantia = null): GarantiaArchivo
    {
        $garantia ??= $rec?->garantia;
        $path = $archivo->store('garantias/'.($rec ? 'reclamaciones/'.$rec->id : 'expedientes/'.$garantia?->id), GarantiaArchivo::disco());

        $registro = GarantiaArchivo::create([
            'garantia_id' => $garantia?->id,
            'reclamacion_id' => $rec?->id,
            'tipo' => $tipo,
            'nombre' => mb_substr($archivo->getClientOriginalName(), 0, 250),
            'path' => $path,
            'mime' => $archivo->getMimeType(),
            'size' => $archivo->getSize(),
            'user_id' => $usuario?->id,
        ]);

        $texto = 'Archivo adjunto: '.$registro->nombre.' ('.mb_strtolower($registro->tipoLabel()).').';
        $rec ? $rec->registrar('archivo', $texto) : $garantia?->registrar('archivo', $texto);

        return $registro;
    }

    /** La primera acción del personal cuenta como primera respuesta. */
    private function responder(GarantiaReclamacion $rec): void
    {
        if (! $rec->primera_respuesta_en && auth()->check()) {
            $rec->forceFill(['primera_respuesta_en' => now()])->save();
        }
    }

    private function horasPara(string $prioridad): int
    {
        return (int) (config('garantias.horas_resolucion')[$prioridad] ?? 120);
    }

    private function avisarNueva(GarantiaReclamacion $rec): void
    {
        $destino = $rec->responsable && $rec->responsable_id !== auth()->id()
            ? collect([$rec->responsable])
            : ($rec->es_seguridad || $rec->canal === 'portal' ? $this->quienesPueden('garantias.gestionar') : collect());

        if ($rec->es_seguridad) {
            $destino = $destino->merge($this->quienesPueden('garantias.aprobar'))->unique('id');
        }

        if ($destino->isEmpty()) {
            return;
        }

        Notification::send($destino, new AvisoDeGarantia(
            ($rec->es_seguridad ? 'Posible incidente de seguridad · ' : 'Nueva reclamación · ').$rec->folio,
            $rec->equipoNombre().' — '.mb_strimwidth($rec->sintomas, 0, 110, '…'),
            route('garantias.reclamaciones.show', $rec),
        ));
    }

    /** Usuarios activos con un permiso (los administradores siempre entran). */
    public function quienesPueden(string $permiso)
    {
        return User::where('status', User::STATUS_APPROVED)->get()->filter(fn (User $u) => $u->can($permiso))->values();
    }
}
