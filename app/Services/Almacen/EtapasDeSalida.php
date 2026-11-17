<?php

namespace App\Services\Almacen;

use App\Models\OrdenSalida;
use App\Models\OrdenSalidaEtapa;
use App\Models\OrdenSalidaItem;
use App\Models\User;
use App\Models\VentaBitacora;
use App\Notifications\EtapaSalidaActualizada;
use App\Notifications\EtapaSalidaAsignada;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Las tres etapas de una orden de salida: Ingeniería → Empaque → Entrega.
 *
 * Cada etapa la hace quien se asignó (o un admin / quien asigna). Una etapa
 * empieza cuando termina la anterior. Al terminar Empaque la orden queda
 * lista para salir; al terminar Entrega, la orden se da por entregada.
 *
 * Una etapa terminada se puede corregir: las primeras 72 horas basta con
 * pedirlo; después hay que escribir el NIP (lo asigna un administrador).
 * Cada cambio avisa al asesor de la venta y a los administradores.
 */
class EtapasDeSalida
{
    /** Minutos que dura abierta una etapa terminada para corregirla. */
    public const MINUTOS_DESBLOQUEO = 30;

    public function __construct(private readonly AsignacionDeSalida $asignacion) {}

    /** Crea las tres etapas si faltan (solo en órdenes que siguen abiertas). */
    public function asegurar(OrdenSalida $orden): Collection
    {
        if (! $orden->cerrada() && $orden->etapas()->count() < count(OrdenSalidaEtapa::ETAPAS)) {
            foreach (array_keys(OrdenSalidaEtapa::ETAPAS) as $etapa) {
                OrdenSalidaEtapa::firstOrCreate(['orden_salida_id' => $orden->id, 'etapa' => $etapa], ['estado' => OrdenSalidaEtapa::PENDIENTE]);
            }
        }

        return $orden->etapas()->with(['asignadaA', 'terminadaPor', 'iniciadaPor', 'editadaPor'])->get();
    }

    /** ¿Ya se está trabajando por etapas? (alguien asignado o algo empezado) */
    public function activas(OrdenSalida $orden): bool
    {
        return $orden->etapas()->where(fn ($q) => $q->whereNotNull('asignada_a')->orWhere('estado', '!=', OrdenSalidaEtapa::PENDIENTE))->exists();
    }

    public function candidatos(): Collection
    {
        return $this->asignacion->candidatos();
    }

    /** La etapa anterior, si hay. */
    public function anterior(OrdenSalidaEtapa $etapa): ?OrdenSalidaEtapa
    {
        $orden = array_keys(OrdenSalidaEtapa::ETAPAS);
        $i = array_search($etapa->etapa, $orden, true);

        return $i > 0 ? OrdenSalidaEtapa::where('orden_salida_id', $etapa->orden_salida_id)->where('etapa', $orden[$i - 1])->first() : null;
    }

    /** ¿Ya le toca? Empieza cuando termina la anterior. */
    public function disponible(OrdenSalidaEtapa $etapa): bool
    {
        $anterior = $this->anterior($etapa);

        return ! $anterior || $anterior->terminada()
            // Órdenes que ya se habían dejado listas sin etapas: la entrega sigue disponible.
            || ($etapa->etapa === OrdenSalidaEtapa::ENTREGA && $etapa->orden->listaParaFirmar());
    }

    /** Quien la tiene asignada, un admin o quien asigna; si nadie la tiene, cualquiera que prepare salidas. */
    public function esResponsable(?User $user, OrdenSalidaEtapa $etapa): bool
    {
        if (! $user) {
            return false;
        }

        return (int) $etapa->asignada_a === (int) $user->id
            || $user->isAdmin()
            || $user->can('salidas.asignar')
            || (! $etapa->asignada_a && $user->can('salidas.preparar'));
    }

    public function razonParaNoTrabajar(?User $user, OrdenSalidaEtapa $etapa): ?string
    {
        $orden = $etapa->orden;

        return match (true) {
            $orden->estado === OrdenSalida::CANCELADA => 'La orden está cancelada.',
            $etapa->terminada() => 'Esta etapa ya se terminó.',
            $orden->cerrada() => 'La orden ya se entregó.',
            ! $this->disponible($etapa) => 'Todavía no le toca: primero se termina '.$this->anterior($etapa)?->nombre().'.',
            ! $this->esResponsable($user, $etapa) => 'Esta etapa la tiene asignada '.($etapa->asignadaA?->name ?? 'otra persona').'.',
            default => null,
        };
    }

    /** ¿Puede trabajar la etapa o corregirla (si ya está terminada y la desbloqueó)? */
    public function puedeEditar(?User $user, OrdenSalidaEtapa $etapa): bool
    {
        if (! $etapa->terminada()) {
            return $this->razonParaNoTrabajar($user, $etapa) === null;
        }

        return $this->puedeCorregir($user, $etapa) && $this->desbloqueada($etapa);
    }

    /** Corregir una etapa terminada: quien la terminó, quien la tiene asignada, un admin o quien asigna. */
    public function puedeCorregir(?User $user, OrdenSalidaEtapa $etapa): bool
    {
        return $user !== null && $etapa->terminada()
            && $etapa->orden->estado !== OrdenSalida::CANCELADA
            && ((int) $etapa->terminada_por === (int) $user->id || $this->esResponsable($user, $etapa));
    }

    public function desbloqueada(OrdenSalidaEtapa $etapa): bool
    {
        $hasta = session('etapas_desbloqueadas.'.$etapa->id);

        return $hasta !== null && now()->timestamp < (int) $hasta;
    }

    /**
     * Abre una etapa terminada para corregirla. Dentro de las 72 horas no
     * pide nada; después pide el NIP de quien corrige.
     */
    public function desbloquear(OrdenSalidaEtapa $etapa, User $user, ?string $nip): void
    {
        if (! $this->puedeCorregir($user, $etapa)) {
            throw ValidationException::withMessages(['nip' => 'No puedes corregir esta etapa.']);
        }

        if (! $etapa->enPlazoDeEdicion()) {
            if (! $user->approval_pin_hash) {
                throw ValidationException::withMessages(['nip' => 'Pasaron más de '.OrdenSalidaEtapa::HORAS_EDICION.' horas: hace falta tu NIP y no tienes uno. Pídele a un administrador que te lo asigne en Usuarios.']);
            }
            if (trim((string) $nip) === '') {
                throw ValidationException::withMessages(['nip' => 'Pasaron más de '.OrdenSalidaEtapa::HORAS_EDICION.' horas desde que se terminó: escribe tu NIP para corregirla.']);
            }
            if (! $user->checkApprovalPin(trim((string) $nip))) {
                throw ValidationException::withMessages(['nip' => 'NIP incorrecto.']);
            }
        }

        session(['etapas_desbloqueadas.'.$etapa->id => now()->addMinutes(self::MINUTOS_DESBLOQUEO)->timestamp]);
    }

    public function asignar(OrdenSalidaEtapa $etapa, ?int $userId, User $quien): string
    {
        $orden = $etapa->orden;
        if ($orden->cerrada()) {
            throw ValidationException::withMessages(['user_id' => 'La orden ya se entregó o se canceló.']);
        }

        if ($userId === null) {
            $etapa->update(['asignada_a' => null, 'asignada_por' => $quien->id, 'asignada_en' => now()]);
            $this->bitacora($etapa, "{$quien->name} quitó al responsable de {$etapa->nombre()}");

            return "{$etapa->nombre()} quedó sin responsable.";
        }

        $persona = $this->candidatos()->firstWhere('id', $userId);
        if (! $persona) {
            throw ValidationException::withMessages(['user_id' => 'Esa persona no está activa o no tiene permiso para preparar salidas.']);
        }

        if ((int) $etapa->asignada_a === $persona->id) {
            return "{$persona->name} ya tenía {$etapa->nombre()}.";
        }

        $etapa->update(['asignada_a' => $persona->id, 'asignada_por' => $quien->id, 'asignada_en' => now()]);
        $this->bitacora($etapa, "{$quien->name} asignó {$etapa->nombre()} a {$persona->name}");
        $this->avisar(collect([$persona]), new EtapaSalidaAsignada($etapa->fresh(['orden.venta.customer', 'asignadaPor'])));

        return "Listo: {$persona->name} tiene {$etapa->nombre()}. Le llegó el aviso.";
    }

    /** Asigna las tres de golpe (lo que se elige en la orden). Regresa los mensajes. */
    public function asignarVarias(OrdenSalida $orden, array $porEtapa, User $quien): array
    {
        $mensajes = [];
        foreach ($this->asegurar($orden) as $etapa) {
            if (! array_key_exists($etapa->etapa, $porEtapa)) {
                continue;
            }
            $nuevo = $porEtapa[$etapa->etapa] ? (int) $porEtapa[$etapa->etapa] : null;
            if ($nuevo === ($etapa->asignada_a ? (int) $etapa->asignada_a : null)) {
                continue;
            }
            $mensajes[] = $this->asignar($etapa, $nuevo, $quien);
        }

        return $mensajes ?: ['Sin cambios en los responsables.'];
    }

    public static function reglas(string $etapa): array
    {
        $reglas = [
            'respuestas' => ['nullable', 'array'],
            'respuestas.*' => ['nullable', Rule::in(['si', 'no', 'na'])],
            'notas_punto' => ['nullable', 'array'],
            'notas_punto.*' => ['nullable', 'string', 'max:500'],
            'puntos_nuevos' => ['nullable', 'array', 'max:20'],
            'puntos_nuevos.*' => ['nullable', 'string', 'max:200'],
            'quitar_puntos' => ['nullable', 'array'],
            'quitar_puntos.*' => ['string', 'max:80'],
            'extras' => ['nullable', 'array', 'max:60'],
            'extras.*.texto' => ['nullable', 'string', 'max:200'],
            'extras.*.cantidad' => ['nullable', 'integer', 'min:1', 'max:9999'],
            'extras.*.sale' => ['nullable', 'boolean'],
            'notas' => ['nullable', 'string', 'max:3000'],
            'firma' => ['nullable', 'string'],
            'usar_firma_registrada' => ['nullable', 'boolean'],
            'accion' => ['required', Rule::in(['guardar', 'terminar'])],
        ];

        if ($etapa === OrdenSalidaEtapa::ENTREGA) {
            $reglas += [
                'modalidad' => ['nullable', Rule::in(array_keys(OrdenSalidaEtapa::MODALIDADES))],
                'paqueteria' => ['nullable', 'string', 'max:120'],
                'guia' => ['nullable', 'string', 'max:120'],
                'recibe_nombre' => ['nullable', 'string', 'max:255'],
                'firma_recibe' => ['nullable', 'string'],
            ];
        }

        return $reglas;
    }

    /**
     * Guarda el avance de la etapa y, si se pide, la termina. También sirve
     * para corregir una etapa ya terminada (desbloqueada).
     */
    public function guardar(OrdenSalidaEtapa $etapa, array $data, User $user): string
    {
        $orden = $etapa->orden;
        $corrigiendo = $etapa->terminada();
        $terminar = ($data['accion'] ?? 'guardar') === 'terminar' && ! $corrigiendo;

        $etapa->checklist = $this->checklistActualizado($etapa->checklist ?: [], $data);
        if (array_key_exists('extras', $data)) {
            $etapa->extras = $this->extrasLimpios($data['extras']) ?? [];
        }
        $etapa->notas = trim((string) ($data['notas'] ?? '')) ?: null;

        if ($etapa->etapa === OrdenSalidaEtapa::ENTREGA) {
            $etapa->datos = array_filter([
                'modalidad' => $data['modalidad'] ?? null,
                'paqueteria' => trim((string) ($data['paqueteria'] ?? '')) ?: null,
                'guia' => trim((string) ($data['guia'] ?? '')) ?: null,
                'recibe_nombre' => trim((string) ($data['recibe_nombre'] ?? '')) ?: null,
                'firma_recibe_path' => $this->guardarFirmaSiViene($data['firma_recibe'] ?? null) ?? ($etapa->datos['firma_recibe_path'] ?? null),
            ]);
        }

        if ($firma = $this->guardarFirmaSiViene($data['firma'] ?? null)) {
            $etapa->firma_path = $firma;
        } elseif (! empty($data['usar_firma_registrada']) && $user->tieneFirma()) {
            $etapa->firma_path = $this->copiarFirmaRegistrada($user);
        }

        $empezo = false;
        if ($etapa->estado === OrdenSalidaEtapa::PENDIENTE) {
            $etapa->estado = OrdenSalidaEtapa::EN_CURSO;
            $etapa->iniciada_por = $user->id;
            $etapa->iniciada_en = now();
            $empezo = true;
        }

        if ($terminar || $corrigiendo) {
            $this->exigirCompleta($etapa);
        }

        DB::transaction(function () use ($etapa, $orden, $user, $terminar, $corrigiendo) {
            if ($corrigiendo) {
                $etapa->editada_por = $user->id;
                $etapa->editada_en = now();
            }

            if ($terminar) {
                $etapa->estado = OrdenSalidaEtapa::TERMINADA;
                $etapa->terminada_por = $user->id;
                $etapa->terminada_en = now();
            }

            $etapa->save();

            if ($terminar && $etapa->etapa === OrdenSalidaEtapa::EMPAQUE) {
                $this->cerrarPreparacion($orden, $user);
            }

            if ($terminar && $etapa->etapa === OrdenSalidaEtapa::ENTREGA) {
                $this->darSalida($orden, $etapa, $user);
            }
        });

        $accion = match (true) {
            $terminar => 'terminada',
            $corrigiendo => 'corregida',
            $empezo => 'iniciada',
            default => null,
        };

        if ($accion) {
            $texto = match ($accion) {
                'terminada' => "{$user->name} terminó {$etapa->nombre()}",
                'corregida' => "{$user->name} corrigió {$etapa->nombre()}",
                default => "{$user->name} comenzó {$etapa->nombre()}",
            };
            $this->bitacora($etapa, $texto);
            $this->avisarAvance($etapa->fresh(['orden.venta.seller', 'orden.venta.customer']), $accion, $user);
        }

        return match ($accion) {
            'terminada' => $etapa->etapa === OrdenSalidaEtapa::ENTREGA
                ? "Entrega terminada: {$orden->folio} quedó entregada."
                : "{$etapa->nombre()} terminada. Ya sigue ".($this->siguiente($etapa)?->nombre() ?? 'la entrega').'.',
            'corregida' => "{$etapa->nombre()}: cambios guardados.",
            default => 'Avance guardado.',
        };
    }

    public function siguiente(OrdenSalidaEtapa $etapa): ?OrdenSalidaEtapa
    {
        $orden = array_keys(OrdenSalidaEtapa::ETAPAS);
        $i = array_search($etapa->etapa, $orden, true);

        return isset($orden[$i + 1]) ? OrdenSalidaEtapa::where('orden_salida_id', $etapa->orden_salida_id)->where('etapa', $orden[$i + 1])->first() : null;
    }

    /** Lo que falta para terminarla, en palabras. */
    public function faltantes(OrdenSalidaEtapa $etapa): array
    {
        $faltan = [];
        $r = $etapa->resumenChecklist();

        if ($r['total'] === 0) {
            $faltan[] = 'Arma el checklist.';
        } elseif ($r['faltan'] > 0) {
            $faltan[] = "Contesta los {$r['faltan']} punto(s) del checklist que faltan.";
        }

        $sinNota = collect($etapa->checklist ?: [])->filter(fn ($p) => ($p['respuesta'] ?? null) === 'no' && blank($p['nota'] ?? null))->count();
        if ($sinNota) {
            $faltan[] = "Explica en una nota los {$sinNota} punto(s) que marcaste en \"No\".";
        }

        if (! $etapa->orden->evidencias()->where('etapa', $etapa->etapa)->exists()) {
            $faltan[] = 'Sube al menos una foto o video de evidencia de esta etapa.';
        }

        if (! $etapa->firma_path) {
            $faltan[] = 'Falta tu firma.';
        }

        if ($etapa->etapa === OrdenSalidaEtapa::EMPAQUE && collect($etapa->extras ?: [])->where('sale', true)->isEmpty()) {
            $faltan[] = 'Marca con qué piezas sale.';
        }

        if ($etapa->etapa === OrdenSalidaEtapa::ENTREGA) {
            $d = $etapa->datos ?: [];
            if (empty($d['modalidad'])) {
                $faltan[] = 'Elige cómo se entrega.';
            } elseif ($d['modalidad'] === 'paqueteria') {
                if (empty($d['paqueteria']) || empty($d['guia'])) {
                    $faltan[] = 'Escribe la paquetería y el número de guía.';
                }
            } elseif (empty($d['recibe_nombre']) || empty($d['firma_recibe_path'])) {
                $faltan[] = 'Falta el nombre y la firma de quien recibe.';
            }
        }

        return $faltan;
    }

    private function exigirCompleta(OrdenSalidaEtapa $etapa): void
    {
        if ($faltan = $this->faltantes($etapa)) {
            // Lo contestado no se pierde aunque falte algo para terminar.
            $etapa->save();

            throw ValidationException::withMessages(['etapa' => $faltan]);
        }
    }

    /** Piezas con las que sale (Empaque), armadas con las partidas y lo que agregó Ingeniería. */
    public function piezasSugeridas(OrdenSalida $orden): array
    {
        $piezas = $orden->items->map(fn (OrdenSalidaItem $i) => [
            'texto' => trim($i->nombre.($i->no_series ? ' · S/N '.$i->no_series : '')),
            'cantidad' => (int) $i->cantidad,
            'sale' => false,
        ]);

        $ingenieria = $orden->etapas->firstWhere('etapa', OrdenSalidaEtapa::INGENIERIA);
        foreach ($ingenieria?->extras ?: [] as $extra) {
            $piezas->push(['texto' => $extra['texto'].' (agregado por ingeniería)', 'cantidad' => (int) ($extra['cantidad'] ?? 1), 'sale' => false]);
        }

        return $piezas->values()->all();
    }

    private function checklistActualizado(array $puntos, array $data): array
    {
        $quitar = collect($data['quitar_puntos'] ?? []);
        $respuestas = $data['respuestas'] ?? [];
        $notas = $data['notas_punto'] ?? [];

        $puntos = collect($puntos)
            ->reject(fn ($p) => ($p['manual'] ?? false) && $quitar->contains($p['clave']))
            ->map(function ($p) use ($respuestas, $notas) {
                if (array_key_exists($p['clave'], $respuestas)) {
                    $p['respuesta'] = $respuestas[$p['clave']] ?: null;
                }
                if (array_key_exists($p['clave'], $notas)) {
                    $p['nota'] = trim((string) $notas[$p['clave']]) ?: null;
                }

                return $p;
            });

        foreach ($data['puntos_nuevos'] ?? [] as $texto) {
            if (trim((string) $texto) !== '' && $puntos->count() < ChecklistDeSalida::MAX_PUNTOS + 20) {
                $puntos->push(ChecklistDeSalida::puntoManual($texto));
            }
        }

        return $puntos->values()->all();
    }

    private function extrasLimpios(?array $extras): ?array
    {
        if ($extras === null) {
            return null;
        }

        return collect($extras)
            ->map(fn ($e) => [
                'texto' => Str::limit(trim((string) ($e['texto'] ?? '')), 200, ''),
                'cantidad' => max(1, (int) ($e['cantidad'] ?? 1)),
                'sale' => (bool) ($e['sale'] ?? false),
            ])
            ->filter(fn ($e) => $e['texto'] !== '')
            ->values()->all();
    }

    /** Empaque terminado: las partidas quedan preparadas y emplayadas y la orden, lista para salir. */
    private function cerrarPreparacion(OrdenSalida $orden, User $user): void
    {
        foreach ($orden->items as $item) {
            $item->preparado = true;
            $item->preparado_en ??= now();
            if ($item->requiere_emplayado) {
                $item->emplayado = true;
                $item->emplayado_en ??= now();
            }
            $item->save();
        }

        $orden->refresh();
        if (! $orden->preparada_en) {
            $orden->confirmarPreparacion($user->id);
        } else {
            $orden->actualizarEstado();
        }
    }

    /** Entrega terminada: la orden queda entregada con las firmas de la etapa. */
    private function darSalida(OrdenSalida $orden, OrdenSalidaEtapa $etapa, User $user): void
    {
        $d = $etapa->datos ?: [];
        $recibe = ($d['modalidad'] ?? null) === 'paqueteria'
            ? trim(($d['paqueteria'] ?? 'Paquetería').' · guía '.($d['guia'] ?? ''))
            : ($d['recibe_nombre'] ?? 'Cliente');

        $orden->update([
            'estado' => OrdenSalida::ENTREGADA,
            'entregada_por' => $user->id,
            'entregada_en' => now(),
            'recibe_nombre' => Str::limit($recibe, 250, ''),
            'firma_entrega_path' => $etapa->firma_path,
            'firma_recibe_path' => $d['firma_recibe_path'] ?? null,
        ]);

        app(PreparacionDeSalida::class)->marcarSalidasEntregadas($orden, now());
    }

    private function guardarFirmaSiViene(?string $dataUrl): ?string
    {
        if (! $dataUrl || ! preg_match('/^data:image\/(png|jpe?g);base64,(.+)$/', $dataUrl, $m)) {
            return null;
        }

        $contenido = base64_decode($m[2], true);
        if ($contenido === false || $contenido === '') {
            return null;
        }

        $path = 'inventario/salidas/etapas/'.uniqid('firma_').'.png';
        Storage::disk(config('filesystems.fotos_disk', 'public'))->put($path, $contenido);

        return $path;
    }

    private function copiarFirmaRegistrada(User $user): ?string
    {
        $disco = Storage::disk(config('filesystems.fotos_disk', 'public'));
        $path = 'inventario/salidas/etapas/'.uniqid('firma_').'.png';

        return $disco->copy($user->firma_path, $path) ? $path : null;
    }

    private function bitacora(OrdenSalidaEtapa $etapa, string $texto): void
    {
        $venta = $etapa->orden?->venta;
        if ($venta) {
            VentaBitacora::registrar($venta, 'salida_etapa', $texto.' · '.$etapa->orden->folio, [
                'orden_salida_id' => $etapa->orden_salida_id,
                'etapa' => $etapa->etapa,
            ]);
        }
    }

    /**
     * Avisa del avance al asesor de la venta, a los administradores y, si
     * terminó una etapa, a quien tiene la siguiente ("ya te toca").
     */
    private function avisarAvance(OrdenSalidaEtapa $etapa, string $accion, User $quien): void
    {
        $orden = $etapa->orden;
        $destinatarios = User::query()->where('status', User::STATUS_APPROVED)->get()
            ->filter(fn (User $u) => $u->isAdmin())
            ->push($orden->venta?->seller)
            ->filter()
            ->unique('id')
            ->reject(fn (User $u) => $u->id === $quien->id);

        $this->avisar($destinatarios, new EtapaSalidaActualizada($etapa, $accion, $quien));

        if ($accion === 'terminada' && ($siguiente = $this->siguiente($etapa)) && $siguiente->asignadaA && $siguiente->asignada_a !== $quien->id) {
            $this->avisar(collect([$siguiente->asignadaA]), new EtapaSalidaActualizada($siguiente->fresh(['orden.venta.customer']), 'te_toca', $quien));
        }
    }

    private function avisar(Collection $personas, $aviso): void
    {
        \Illuminate\Support\defer(function () use ($personas, $aviso) {
            foreach ($personas as $persona) {
                try {
                    $persona->notify($aviso);
                } catch (\Throwable $e) {
                    Log::warning('[Salidas] No salió un aviso de etapa', ['user_id' => $persona->id, 'error' => $e->getMessage()]);
                }
            }
        });
    }
}
