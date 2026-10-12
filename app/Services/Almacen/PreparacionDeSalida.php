<?php

namespace App\Services\Almacen;

use App\Models\InventoryMovement;
use App\Models\OrdenSalida;
use App\Models\OrdenSalidaItem;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Lo que almacén le hace a una orden de salida ya creada: marcar el
 * checklist, cerrar la preparación, firmar la salida y sacar la hoja.
 *
 * Vive aquí (y no en el controlador) porque lo usan la web y la API, y
 * las dos tienen que decir que no por las mismas razones. Crear o rehacer
 * la orden a partir de la venta es cosa de OrdenesDeSalida.
 *
 * Los métodos razonParaNo...() devuelven el porqué no se puede, o null si
 * sí se puede: cada pantalla decide cómo enseñarlo.
 */
class PreparacionDeSalida
{
    /** Reglas de la firma de salida, iguales en la web y en la API. */
    public const REGLAS_ENTREGA = [
        'recibe_nombre' => ['required', 'string', 'max:255'],
        'firma_entrega' => ['required', 'string'],
        'firma_recibe' => ['required', 'string'],
    ];

    public const MENSAJES_ENTREGA = [
        'recibe_nombre.required' => 'Escribe el nombre de quien se lleva el equipo.',
        'firma_entrega.required' => 'Falta la firma de quien entrega.',
        'firma_recibe.required' => 'Falta la firma de quien recibe.',
    ];

    public const REGLAS_PARTIDA = [
        'preparado' => ['nullable', 'boolean'],
        'emplayado' => ['nullable', 'boolean'],
        'requiere_emplayado' => ['nullable', 'boolean'],
        'observaciones' => ['nullable', 'string', 'max:500'],
    ];

    public const REGLAS_NOTAS = ['notas' => ['nullable', 'string', 'max:2000']];

    public const FIRMA_INVALIDA = 'Alguna firma no es válida. Vuelve a firmar e intenta de nuevo.';

    /** Filtro de la lista: todo lo que no ha salido ni se canceló. */
    public const ABIERTAS = 'abiertas';

    public function filtrarPorEstado(Builder $query, string $estado): Builder
    {
        return $query
            ->when($estado === self::ABIERTAS, fn ($q) => $q->whereNotIn('estado', [OrdenSalida::ENTREGADA, OrdenSalida::CANCELADA]))
            ->when($estado !== self::ABIERTAS, fn ($q) => $q->where('estado', $estado));
    }

    /**
     * Los números de las pestañas de la lista.
     *
     * @return array{abiertas: int, lista: int, entregada: int}
     */
    public function conteos(): array
    {
        $conteos = OrdenSalida::selectRaw('estado, COUNT(*) as n')->groupBy('estado')->pluck('n', 'estado');

        return [
            self::ABIERTAS => (int) ($conteos[OrdenSalida::PENDIENTE] ?? 0) + (int) ($conteos[OrdenSalida::EN_PREPARACION] ?? 0) + (int) ($conteos[OrdenSalida::LISTA] ?? 0),
            OrdenSalida::LISTA => (int) ($conteos[OrdenSalida::LISTA] ?? 0),
            OrdenSalida::ENTREGADA => (int) ($conteos[OrdenSalida::ENTREGADA] ?? 0),
        ];
    }

    /**
     * Lo que pidió cambiar el formulario de una partida: solo las llaves
     * que llegaron, ya como booleanos.
     *
     * @param  array  $validado  lo que regresó la validación con REGLAS_PARTIDA
     */
    public function cambiosDePartida(Request $request, array $validado): array
    {
        $cambios = [];

        foreach (['preparado', 'emplayado', 'requiere_emplayado'] as $campo) {
            if ($request->has($campo)) {
                $cambios[$campo] = $request->boolean($campo);
            }
        }

        if ($request->has('observaciones')) {
            $cambios['observaciones'] = $validado['observaciones'] ?? null;
        }

        return $cambios;
    }

    public function razonParaNoMarcar(OrdenSalida $orden): ?string
    {
        return $orden->cerrada() ? 'Esta orden ya salió: no se puede modificar.' : null;
    }

    /**
     * Marca o desmarca un paso de una partida (preparado / emplayado),
     * cambia si se emplaya o no, o guarda una observación.
     *
     * Solo se tocan las llaves que vienen en $cambios: quien marca
     * "preparado" no tiene por qué mandar también lo de emplayado.
     *
     * @param  array{preparado?: bool, emplayado?: bool, requiere_emplayado?: bool, observaciones?: ?string}  $cambios
     */
    public function marcarPartida(OrdenSalida $orden, OrdenSalidaItem $item, array $cambios): void
    {
        if (array_key_exists('preparado', $cambios)) {
            $item->preparado = (bool) $cambios['preparado'];
            $item->preparado_en = $item->preparado ? now() : null;
        }

        if (array_key_exists('requiere_emplayado', $cambios)) {
            $item->requiere_emplayado = (bool) $cambios['requiere_emplayado'];
            // Si ya no se emplaya, lo marcado de emplayado deja de contar.
            if (! $item->requiere_emplayado) {
                $item->emplayado = false;
                $item->emplayado_en = null;
            }
        }

        if (array_key_exists('emplayado', $cambios) && $item->requiere_emplayado) {
            $item->emplayado = (bool) $cambios['emplayado'];
            $item->emplayado_en = $item->emplayado ? now() : null;
        }

        if (array_key_exists('observaciones', $cambios)) {
            $item->observaciones = trim((string) $cambios['observaciones']) ?: null;
        }

        $item->save();
        $orden->actualizarEstado();
    }

    public function razonParaNoConfirmar(OrdenSalida $orden): ?string
    {
        return match (true) {
            $orden->cerrada() => 'Esta orden ya está cerrada.',
            ! $orden->todoListo() => 'Todavía hay partidas sin preparar o sin emplayar: termina el checklist antes de dejarla lista.',
            $orden->preparada_en !== null => 'Esta orden ya estaba lista para salir.',
            default => null,
        };
    }

    /** Notas generales de la orden (instrucciones para almacén, transporte...). */
    public function guardarNotas(OrdenSalida $orden, ?string $notas): void
    {
        $orden->notas = trim((string) $notas) ?: null;
        $orden->save();
    }

    /**
     * La preparación se cierra antes de firmar: así queda claro quién
     * preparó y quién entregó, aunque sea la misma persona.
     */
    public function razonParaNoEntregar(OrdenSalida $orden): ?string
    {
        return match (true) {
            $orden->cerrada() => 'Esta orden ya está cerrada.',
            ! $orden->todoListo() => 'Todavía hay partidas sin preparar o sin emplayar. Termina el checklist antes de firmar la salida.',
            ! $orden->listaParaFirmar() => 'Primero deja la orden preparada para firma: así queda registrado quién la preparó.',
            default => null,
        };
    }

    /**
     * Firma de salida: firma quien entrega por almacén y quien se lleva el
     * equipo (chofer, cliente, paquetería). Las firmas llegan como data URL
     * (lo que da un canvas) y se guardan como PNG.
     *
     * Devuelve false si alguna firma no sirve; en ese caso no se guarda
     * nada, ni siquiera la firma que sí venía bien.
     */
    public function entregar(OrdenSalida $orden, int $usuarioId, string $recibeNombre, string $firmaEntrega, string $firmaRecibe): bool
    {
        $disco = config('filesystems.fotos_disk', 'public');
        $pathEntrega = $this->guardarFirma($firmaEntrega, $disco);
        $pathRecibe = $this->guardarFirma($firmaRecibe, $disco);

        if (! $pathEntrega || ! $pathRecibe) {
            foreach (array_filter([$pathEntrega, $pathRecibe]) as $p) {
                Storage::disk($disco)->delete($p);
            }

            return false;
        }

        $entregadaEn = now();

        DB::transaction(function () use ($orden, $usuarioId, $recibeNombre, $pathEntrega, $pathRecibe, $entregadaEn) {
            $orden->update([
                'estado' => OrdenSalida::ENTREGADA,
                'entregada_por' => $usuarioId,
                'entregada_en' => $entregadaEn,
                'recibe_nombre' => $recibeNombre,
                'firma_entrega_path' => $pathEntrega,
                'firma_recibe_path' => $pathRecibe,
            ]);

            $this->marcarSalidasEntregadas($orden, $entregadaEn);
        });

        return true;
    }

    /**
     * Hoja impresa de la orden: qué salió, cuándo, quién preparó, quién
     * autorizó la salida y quién recibió, con las firmas. Se puede sacar
     * en cualquier momento; antes de firmar se marca como avance.
     *
     * @return \Barryvdh\DomPDF\PDF
     */
    public function pdf(OrdenSalida $orden)
    {
        $orden->load(['venta.customer', 'venta.seller', 'items', 'preparadaPor', 'entregadaPor', 'creator']);

        return Pdf::loadView('structure.gestion_Inventario.ordenes_salida.pdf', [
            'orden' => $orden,
            'firmaEntrega' => $this->firmaDataUri($orden->firma_entrega_path),
            'firmaRecibe' => $this->firmaDataUri($orden->firma_recibe_path),
        ])->setPaper('letter');
    }

    /** La firma guardada en disco, embebida para que dompdf la pinte. */
    private function firmaDataUri(?string $path): ?string
    {
        $disco = config('filesystems.fotos_disk', 'public');

        if (! $path || ! Storage::disk($disco)->exists($path)) {
            return null;
        }

        return 'data:image/png;base64,'.base64_encode(Storage::disk($disco)->get($path));
    }

    /**
     * Cierra el movimiento de inventario: hasta aquí era una venta pendiente
     * de entrega, ahora es una salida consumada.
     *
     * El stock ya se había descontado al vender; lo que se registra aquí es
     * la fecha en que el equipo de verdad dejó el almacén.
     */
    private function marcarSalidasEntregadas(OrdenSalida $orden, Carbon $entregadaEn): void
    {
        $itemIds = $orden->venta?->items()->pluck('id') ?? collect();

        if ($itemIds->isEmpty()) {
            return;
        }

        // Misma liga que usa la venta para encontrar sus salidas.
        InventoryMovement::where('movement_type', InventoryMovement::TYPE_EXIT)
            ->whereIn('metadata->venta_item_id', $itemIds->all())
            ->whereNull('entregado_en')
            ->update(['entregado_en' => $entregadaEn]);
    }

    /** Igual que la firma de las entradas: data URL base64 a PNG en disco. */
    private function guardarFirma(string $firmaDataUrl, string $disco): ?string
    {
        if (! preg_match('/^data:image\/(png|jpe?g);base64,(.+)$/', $firmaDataUrl, $match)) {
            return null;
        }

        $contenido = base64_decode($match[2], true);

        if ($contenido === false || $contenido === '') {
            return null;
        }

        $path = 'inventario/salidas/'.uniqid('firma_').'.png';
        Storage::disk($disco)->put($path, $contenido);

        return $path;
    }
}
