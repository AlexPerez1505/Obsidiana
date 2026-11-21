<?php

namespace App\Services\Inventario;

use App\Models\Garantia;
use App\Models\GarantiaReclamacion;
use App\Models\InventoryMovement;
use App\Models\OrdenSalida;
use App\Models\OrdenSalidaEtapa;
use App\Models\PiezaComponente;
use App\Models\Producto;
use App\Models\ProductoSerial;
use App\Models\User;
use App\Models\Venta;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Consulta por escaneo: qué es lo que se escaneó, dónde está, cuántas
 * piezas se pueden surtir y qué le pasó hasta llegar a como está.
 *
 * Es SOLO LECTURA. Nada de aquí guarda, reserva ni mueve existencias: solo
 * junta lo que ya registran Entrada y salida, Procesos, Ventas, Órdenes de
 * salida, Congresos y Garantías.
 *
 * Lo que se puede escanear (identificadores reales del sistema):
 *   - la etiqueta interna de la pieza (MB-000147), sola o dentro de la liga
 *     pública del QR, que es lo que teclea la pistola;
 *   - el número de serie de la pieza o de uno de sus componentes;
 *   - la referencia del producto.
 * El código se compara como texto (no como número), así que los ceros a la
 * izquierda se respetan: "000123" no es lo mismo que "123".
 *
 * Dos alcances:
 *   - "pieza": una unidad con etiqueta (inventario serializado).
 *   - "producto": todas las piezas del modelo, sumadas por almacén.
 */
class ConsultaPorEscaneo
{
    /** Cuántos movimientos se mandan de golpe (los más recientes). */
    public const POR_PAGINA = 25;

    /** Tope de coincidencias a ofrecer cuando hay varias. */
    private const MAX_OPCIONES = 12;

    public function __construct(private ?User $user = null)
    {
        $this->user ??= auth()->user();
    }

    /* =====================================================================
     |  Búsqueda
     ===================================================================== */

    /**
     * Qué coincide con lo leído.
     *
     * @return array{estado: string, codigo: string, opciones?: array, tipo?: string, id?: int}
     */
    public function buscar(string $leido): array
    {
        $leido = trim($leido);

        // La pistola teclea la liga completa del QR: de ahí sale la etiqueta.
        // Ojo: el patrón también "encuentra" etiquetas dentro de series como
        // END-GAS-OLY-GIT-0003, así que solo cuenta si de verdad existe; si
        // no, se busca lo leído tal cual.
        $etiqueta = EscaneoDePiezas::codigo($leido);
        $codigo = $leido;

        $opciones = collect();

        if ($etiqueta !== null) {
            $pieza = ProductoSerial::where('codigo', $etiqueta)->with('producto', 'entrada:id,warehouse')->first();
            if ($pieza) {
                $codigo = $etiqueta;
                $opciones->push($this->opcionPieza($pieza, 'Etiqueta'));
            }
        }

        // Si fue una etiqueta y existe, no hay ambigüedad posible.
        if ($opciones->isEmpty()) {
            // Número de serie de la pieza (exacto; sin importar mayúsculas,
            // la BD ya compara así, pero los ceros a la izquierda cuentan).
            ProductoSerial::where('no_serie', $codigo)
                ->with('producto', 'entrada:id,warehouse')
                ->limit(self::MAX_OPCIONES)->get()
                ->each(fn (ProductoSerial $p) => $opciones->push($this->opcionPieza($p, 'Número de serie')));

            // Serie de un componente: se consulta la pieza que lo trae.
            PiezaComponente::where('no_serie', $codigo)
                ->with('pieza.producto', 'pieza.entrada:id,warehouse')
                ->limit(self::MAX_OPCIONES)->get()
                ->each(function (PiezaComponente $c) use ($opciones) {
                    if ($c->pieza) {
                        $opciones->push($this->opcionPieza($c->pieza, 'Serie del componente '.$c->nombre));
                    }
                });

            // Referencia del producto: alcance de producto completo.
            Producto::where('referencia', $codigo)
                ->limit(self::MAX_OPCIONES)->get()
                ->each(fn (Producto $p) => $opciones->push($this->opcionProducto($p)));
        }

        // Una misma pieza puede salir por dos caminos (su serie y la de un
        // componente): se ofrece una sola vez.
        $opciones = $opciones->unique(fn ($o) => $o['tipo'].':'.$o['id'])->values();

        return match ($opciones->count()) {
            0 => ['estado' => 'no_encontrado', 'codigo' => $codigo],
            1 => ['estado' => 'unico', 'codigo' => $codigo] + $opciones->first(),
            default => ['estado' => 'varios', 'codigo' => $codigo, 'opciones' => $opciones->all()],
        };
    }

    private function opcionPieza(ProductoSerial $p, string $porQue): array
    {
        return [
            'tipo' => 'pieza',
            'id' => $p->id,
            'titulo' => $this->nombreProducto($p->producto),
            'detalle' => collect([
                $p->codigo,
                $p->no_serie ? 'Serie '.$p->no_serie : null,
                $p->entrada?->warehouse,
                $p->vendido ? 'Vendida' : $p->estadoLabel(),
            ])->filter()->implode(' · '),
            'por' => $porQue,
        ];
    }

    private function opcionProducto(Producto $p): array
    {
        return [
            'tipo' => 'producto',
            'id' => $p->id,
            'titulo' => $this->nombreProducto($p),
            'detalle' => collect(['Ref. '.$p->referencia, 'Todo el producto'])->implode(' · '),
            'por' => 'Referencia',
        ];
    }

    /* =====================================================================
     |  Ficha
     ===================================================================== */

    /** Resumen + la página más reciente del historial. */
    public function ficha(string $tipo, int $id, string $codigo): ?array
    {
        if ($tipo === 'pieza') {
            $pieza = ProductoSerial::with([
                'producto', 'congress:id,nombre', 'entrada.creator:id,name',
                'procesos', 'componentes',
                'ventaItem.venta.customer', 'ventaItem.venta.ordenSalida',
            ])->find($id);

            if (! $pieza) {
                return null;
            }

            $resumen = $this->resumenPieza($pieza, $codigo);
            $eventos = $this->eventosPieza($pieza);
        } elseif ($tipo === 'producto') {
            $producto = Producto::find($id);

            if (! $producto) {
                return null;
            }

            $resumen = $this->resumenProducto($producto, $codigo);
            $eventos = $this->eventosProducto($producto);
        } else {
            return null;
        }

        return [
            'tipo' => $tipo,
            'id' => $id,
            'resumen' => $resumen,
            'historial' => $this->pagina($eventos, 0),
        ];
    }

    /** Movimientos anteriores a los que ya se tienen en pantalla. */
    public function historial(string $tipo, int $id, int $yaCargados): ?array
    {
        $eventos = match ($tipo) {
            'pieza' => ($p = ProductoSerial::with([
                'producto', 'congress:id,nombre', 'entrada.creator:id,name', 'procesos', 'componentes',
                'ventaItem.venta.customer', 'ventaItem.venta.ordenSalida',
            ])->find($id)) ? $this->eventosPieza($p) : null,
            'producto' => ($p = Producto::find($id)) ? $this->eventosProducto($p) : null,
            default => null,
        };

        return $eventos === null ? null : $this->pagina($eventos, max(0, $yaCargados));
    }

    /**
     * Los movimientos van del más viejo al más nuevo. La primera página son
     * los últimos POR_PAGINA; las siguientes van hacia atrás.
     */
    private function pagina(Collection $eventos, int $yaCargados): array
    {
        $total = $eventos->count();
        $hasta = max(0, $total - $yaCargados);
        $desde = max(0, $hasta - self::POR_PAGINA);

        return [
            'eventos' => $eventos->slice($desde, $hasta - $desde)->values()->all(),
            'total' => $total,
            'hay_anteriores' => $desde > 0,
        ];
    }

    /* ---------------------------------------------------------------------
     |  Resumen de una pieza
     --------------------------------------------------------------------- */

    private function resumenPieza(ProductoSerial $pieza, string $codigo): array
    {
        $producto = $pieza->producto;
        $venta = $pieza->ventaItem?->venta;
        $orden = $venta?->ordenSalida;
        $entrada = $pieza->entrada;
        $almacen = $entrada?->warehouse;
        $verVenta = $venta && $this->puedeVerVenta($venta);

        $estadoFisico = $this->estadoFisicoPieza($pieza, $orden);
        $estadoComercial = $this->estadoComercialPieza($pieza, $venta, $orden);

        // Cantidades de una unidad: 1 o 0, siempre con el cálculo oficial
        // (vendible() es el mismo criterio que Producto::serialesDisponibles).
        $entregada = $orden?->estado === OrdenSalida::ENTREGADA;
        $baja = $pieza->estado === 'baja';
        $enAlmacen = ! $baja && ! $entregada && ! $pieza->enCongreso();
        $apartada = $pieza->vendido && ! $entregada && ! $baja;

        return [
            'alcance' => 'pieza',
            'alcance_label' => 'Unidad serializada',
            'codigo_escaneado' => $codigo,
            'nombre' => $this->nombreProducto($producto),
            'marca' => $producto?->marca,
            'modelo' => $producto?->modelo,
            'referencia' => $producto?->referencia,
            'etiqueta' => $pieza->codigo,
            'no_serie' => $pieza->no_serie,
            'condicion' => $pieza->condicion ? ucfirst($pieza->condicion) : null,
            'foto' => $pieza->fotoUrl() ?: ($producto?->imagen_path ? asset('storage/'.$producto->imagen_path) : null),
            'unidad' => 'Pieza',
            'equivalencia' => null,
            'fabricacion' => $entrada?->fecha_fabricacion?->format('d/m/Y'),
            'caducidad' => $entrada?->fecha_caducidad?->format('d/m/Y'),
            'origen' => 'Inventario físico',
            'ubicaciones' => [[
                'almacen' => $almacen,
                'lugar' => match (true) {
                    $baja => 'Dada de baja',
                    $entregada => 'Entregada al cliente',
                    $pieza->enCongreso() => 'En el congreso '.$pieza->congress?->nombre,
                    default => $almacen ? 'En almacén' : null,
                },
                'cantidad' => $enAlmacen || $pieza->enCongreso() ? 1 : 0,
            ]],
            'cantidades' => [
                'fisica' => $enAlmacen ? 1 : 0,
                'fuera' => ! $baja && ! $entregada && $pieza->enCongreso() ? 1 : 0,
                'apartada' => $apartada ? 1 : 0,
                'en_proceso' => ! $pieza->vendido && ! $baja && ! $pieza->vendible() ? 1 : 0,
                'disponible' => $pieza->vendible() ? 1 : 0,
                'entregada' => $entregada ? 1 : 0,
                'baja' => $baja ? 1 : 0,
            ],
            'estado_fisico' => $estadoFisico,
            'estado_comercial' => $estadoComercial,
            'estado_embarque' => $this->estadoEmbarquePieza($pieza, $orden),
            'falta' => $pieza->faltaTexto(),
            'componentes_por_llegar' => $pieza->componentes->where('estado', 'por_llegar')->pluck('nombre')->values(),
            'venta' => $venta ? [
                'folio' => $verVenta ? $venta->folio : null,
                'cliente' => $verVenta ? $this->cliente($venta) : null,
                'url' => $verVenta ? route('commercial.ventas.show', $venta) : null,
                'oculta' => ! $verVenta,
            ] : null,
            'salida' => $orden ? [
                'folio' => $orden->folio,
                'estado' => $orden->estadoLabel(),
                'url' => $this->can('salidas.ver') ? route('inventory.salidas.show', $orden) : null,
            ] : null,
            'entrada' => $entrada ? [
                'folio' => $entrada->folio,
                'url' => $this->can('inventario.ver') ? route('inventory.movimientos.show', $entrada) : null,
            ] : null,
            'ficha_publica' => $pieza->urlPublica(),
        ];
    }

    /** Dónde y cómo está físicamente. */
    private function estadoFisicoPieza(ProductoSerial $pieza, ?OrdenSalida $orden): array
    {
        return match (true) {
            $pieza->estado === 'baja' => ['texto' => 'Dada de baja', 'tono' => 'danger'],
            $orden?->estado === OrdenSalida::ENTREGADA => ['texto' => 'Entregada', 'tono' => 'ok'],
            $pieza->enCongreso() => ['texto' => 'En congreso', 'tono' => 'info'],
            $pieza->faltaTexto() !== null => ['texto' => 'En proceso: '.$pieza->faltaTexto(), 'tono' => 'warn'],
            in_array($pieza->estado, ProductoSerial::NO_VENDIBLES, true) => ['texto' => $pieza->estadoLabel(), 'tono' => 'warn'],
            default => ['texto' => 'En almacén', 'tono' => 'ok'],
        };
    }

    /** Qué pasa con ella comercialmente (puede convivir con el físico). */
    private function estadoComercialPieza(ProductoSerial $pieza, ?Venta $venta, ?OrdenSalida $orden): array
    {
        if ($venta?->estado === 'cancelada') {
            return ['texto' => 'Venta cancelada', 'tono' => 'danger'];
        }

        if (! $pieza->vendido) {
            return $pieza->vendible()
                ? ['texto' => 'Disponible para venta', 'tono' => 'ok']
                : ['texto' => 'Todavía no se puede vender', 'tono' => 'warn'];
        }

        return [
            'texto' => 'Venta '.mb_strtolower($venta?->estadoLabel() ?? 'registrada'),
            'tono' => 'info',
        ];
    }

    /**
     * Cómo va su salida, aparte del estado comercial: "Venta confirmada" y
     * "Pendiente de embarque" se muestran juntos.
     */
    private function estadoEmbarquePieza(ProductoSerial $pieza, ?OrdenSalida $orden): ?array
    {
        if (! $pieza->vendido) {
            return null;
        }

        return match ($orden?->estado) {
            OrdenSalida::ENTREGADA => ['texto' => 'Entregada', 'tono' => 'ok'],
            OrdenSalida::LISTA => ['texto' => 'Lista para salir', 'tono' => 'info'],
            OrdenSalida::EN_PREPARACION => ['texto' => 'En preparación', 'tono' => 'info'],
            OrdenSalida::CANCELADA => ['texto' => 'Orden de salida cancelada', 'tono' => 'danger'],
            OrdenSalida::PENDIENTE => ['texto' => 'Pendiente de embarque', 'tono' => 'warn'],
            default => ['texto' => 'Sin orden de salida', 'tono' => 'muted'],
        };
    }

    /* ---------------------------------------------------------------------
     |  Resumen de un producto (todas sus piezas)
     --------------------------------------------------------------------- */

    private function resumenProducto(Producto $producto, string $codigo): array
    {
        // Una sola consulta para las piezas y una para sus órdenes de salida;
        // nada de ir pieza por pieza.
        $piezas = $producto->seriales()
            ->with(['entrada:id,warehouse', 'congress:id,nombre', 'ventaItem:id,venta_id'])
            ->get(['id', 'producto_id', 'codigo', 'estado', 'vendido', 'venta_item_id', 'inventory_movement_id', 'congress_id']);

        $ventaIds = $piezas->pluck('ventaItem.venta_id')->filter()->unique();
        $ordenes = $ventaIds->isEmpty() ? collect()
            : OrdenSalida::whereIn('venta_id', $ventaIds)->get(['id', 'venta_id', 'estado'])->keyBy('venta_id');

        $cuenta = ['fisica' => 0, 'fuera' => 0, 'apartada' => 0, 'en_proceso' => 0, 'disponible' => 0, 'entregada' => 0, 'baja' => 0];
        $porAlmacen = [];

        foreach ($piezas as $p) {
            $orden = $p->ventaItem ? $ordenes->get($p->ventaItem->venta_id) : null;
            $entregada = $orden?->estado === OrdenSalida::ENTREGADA;
            $baja = $p->estado === 'baja';

            if ($baja) { $cuenta['baja']++; continue; }
            if ($entregada) { $cuenta['entregada']++; continue; }

            $almacen = $p->entrada?->warehouse ?: null;

            if ($p->enCongreso()) {
                $cuenta['fuera']++;
                $clave = 'Congreso: '.($p->congress?->nombre ?? '—');
                $porAlmacen[$clave] ??= ['almacen' => $almacen, 'lugar' => 'En el congreso '.($p->congress?->nombre ?? ''), 'cantidad' => 0];
                $porAlmacen[$clave]['cantidad']++;
            } else {
                $cuenta['fisica']++;
                $clave = 'A: '.($almacen ?? '');
                $porAlmacen[$clave] ??= ['almacen' => $almacen, 'lugar' => $almacen ? 'En almacén' : null, 'cantidad' => 0];
                $porAlmacen[$clave]['cantidad']++;
            }

            if ($p->vendido) {
                $cuenta['apartada']++;
            } elseif ($p->vendible()) {
                $cuenta['disponible']++;
            } else {
                $cuenta['en_proceso']++;
            }
        }

        $hayPiezas = $piezas->isNotEmpty();

        return [
            'alcance' => 'producto',
            'alcance_label' => 'Producto completo ('.$piezas->count().' '.($piezas->count() === 1 ? 'pieza registrada' : 'piezas registradas').')',
            'codigo_escaneado' => $codigo,
            'nombre' => $this->nombreProducto($producto),
            'marca' => $producto->marca,
            'modelo' => $producto->modelo,
            'referencia' => $producto->referencia,
            'etiqueta' => null,
            'no_serie' => null,
            'condicion' => null,
            'foto' => $producto->imagen_path ? asset('storage/'.$producto->imagen_path) : null,
            'unidad' => 'Pieza',
            'equivalencia' => null,
            'fabricacion' => null,
            'caducidad' => null,
            'origen' => 'Inventario físico',
            'ubicaciones' => array_values($porAlmacen),
            // Sin piezas registradas no se puede afirmar que haya cero.
            'cantidades' => $hayPiezas ? $cuenta : null,
            // Lo oficial del sistema, para confirmar que cuadra.
            'stock_oficial' => (int) $producto->stock,
            'estado_fisico' => match (true) {
                ! $hayPiezas => ['texto' => 'Sin piezas registradas', 'tono' => 'muted'],
                $cuenta['fisica'] > 0 => ['texto' => $cuenta['fisica'].' en almacén', 'tono' => 'ok'],
                $cuenta['fuera'] > 0 => ['texto' => 'Solo en congreso', 'tono' => 'info'],
                default => ['texto' => 'Nada en almacén', 'tono' => 'warn'],
            },
            'estado_embarque' => $cuenta['apartada'] > 0 && $hayPiezas
                ? ['texto' => $cuenta['apartada'].' vendida'.($cuenta['apartada'] === 1 ? '' : 's').' sin entregar', 'tono' => 'warn'] : null,
            'estado_comercial' => match (true) {
                ! $hayPiezas => ['texto' => 'Sin movimientos comerciales', 'tono' => 'muted'],
                $cuenta['disponible'] > 0 => ['texto' => $cuenta['disponible'].' para surtir', 'tono' => 'ok'],
                default => ['texto' => 'Nada disponible para surtir', 'tono' => 'warn'],
            },
            'falta' => null,
            'componentes_por_llegar' => [],
            'venta' => null,
            'salida' => null,
            'entrada' => null,
            'producto_url' => $this->can('inventario.ver') ? route('inventory.productos.show', $producto) : null,
            'ficha_publica' => null,
        ];
    }

    /* =====================================================================
     |  Historial
     ===================================================================== */

    /** Todo lo registrado para una pieza, del más viejo al más nuevo. */
    private function eventosPieza(ProductoSerial $pieza): Collection
    {
        $ev = collect();
        $entrada = $pieza->entrada;

        if ($entrada) {
            $ev->push($this->evento(
                $this->momento($entrada->movement_date, $entrada->created_at), 10, 'entrada-'.$entrada->id,
                'recibido', 'Recibido', 1,
                detalle: collect([$entrada->supplier ? 'Proveedor: '.$entrada->supplier : null, $entrada->reference])->filter()->implode(' · ') ?: null,
                destino: $entrada->warehouse,
                folio: $entrada->folio,
                url: $this->can('inventario.ver') ? route('inventory.movimientos.show', $entrada) : null,
                responsable: $entrada->creator?->name,
            ));
        }

        foreach ($pieza->componentes as $c) {
            if ($c->recibido_en) {
                $ev->push($this->evento($c->recibido_en, 15, 'comp-'.$c->id, 'recibido',
                    'Llega componente: '.$c->nombre, null,
                    detalle: $c->no_serie ? 'Serie '.$c->no_serie : null,
                    responsable: $this->nombreUsuario($c->recibido_por)));
            }
        }

        $avances = $pieza->procesos->isEmpty() ? collect()
            : \App\Models\PiezaProcesoAvance::whereIn('pieza_proceso_id', $pieza->procesos->pluck('id'))
                ->with('autor:id,name')->orderBy('id')->get()->groupBy('pieza_proceso_id');

        foreach ($pieza->procesos as $proc) {
            $nombre = $proc->nombre();
            if ($proc->iniciado_en) {
                $ev->push($this->evento($proc->iniciado_en, 20, 'proc-ini-'.$proc->id, 'proceso',
                    'Inicia '.mb_strtolower($nombre), null,
                    responsable: $this->nombreUsuario($proc->responsable_id),
                    url: $this->can('procesos.ver') ? route('inventory.procesos.show', $proc) : null));
            }
            foreach ($avances->get($proc->id, collect()) as $a) {
                $ev->push($this->evento($a->created_at, 21, 'proc-av-'.$a->id, 'proceso',
                    'Avance de '.mb_strtolower($nombre), null,
                    detalle: $a->nota ? \Illuminate\Support\Str::limit($a->nota, 140) : null,
                    responsable: $a->autor?->name));
            }
            if ($proc->terminado_en) {
                $omitido = $proc->estado === 'omitido';
                $ev->push($this->evento($proc->terminado_en, 22, 'proc-fin-'.$proc->id, $omitido ? 'ajuste' : 'proceso',
                    ($omitido ? 'Se omite ' : 'Termina ').mb_strtolower($nombre), null,
                    detalle: $proc->motivo,
                    responsable: $this->nombreUsuario($proc->cerrado_por)));
            }
        }

        if ($pieza->congress_id && $pieza->enviado_a_congreso_en) {
            $ev->push($this->evento($pieza->enviado_a_congreso_en, 30, 'congreso-'.$pieza->congress_id, 'traslado',
                'Enviada a congreso', 1,
                origen: $entrada?->warehouse,
                destino: $pieza->congress?->nombre,
                url: $this->can('congresos.ver') ? route('inventory.congresos.index') : null));
        }

        $venta = $pieza->ventaItem?->venta;
        if ($venta) {
            $ev = $ev->merge($this->eventosVenta($venta, $pieza->vendido_en, 1, 'pieza-'.$pieza->id));
        }

        $ev = $ev->merge($this->eventosGarantia($pieza->id));

        return $this->ordenar($ev);
    }

    /** Venta + orden de salida de una pieza, sin repetir la operación. */
    private function eventosVenta(Venta $venta, ?Carbon $vendidoEn, ?int $cantidad, string $sufijo): Collection
    {
        $ev = collect();
        $ver = $this->puedeVerVenta($venta);
        $orden = $venta->ordenSalida;

        $ev->push($this->evento($vendidoEn ?? $venta->created_at, 40, 'venta-'.$venta->id.'-'.$sufijo, 'venta',
            'Venta '.mb_strtolower($venta->estadoLabel()), $cantidad,
            detalle: $ver ? ($this->cliente($venta) ?: null) : 'Venta de otro asesor',
            folio: $ver ? $venta->folio : null,
            url: $ver ? route('commercial.ventas.show', $venta) : null,
            responsable: $ver ? $this->nombreUsuario($venta->seller_id) : null));

        if ($venta->estado === 'cancelada') {
            $ev->push($this->evento($venta->updated_at, 41, 'venta-cancel-'.$venta->id, 'cancelado',
                'Venta cancelada', $cantidad, folio: $ver ? $venta->folio : null));
        }

        if (! $orden) {
            return $ev;
        }

        $url = $this->can('salidas.ver') ? route('inventory.salidas.show', $orden) : null;

        $ev->push($this->evento($orden->created_at, 50, 'os-'.$orden->id, 'reserva',
            'Orden de salida generada · pendiente de embarque', $cantidad, folio: $orden->folio, url: $url,
            responsable: $this->nombreUsuario($orden->created_by)));

        if ($orden->asignada_en) {
            $ev->push($this->evento($orden->asignada_en, 51, 'os-asig-'.$orden->id, 'picking',
                'Asignada para preparar', null, folio: $orden->folio, url: $url,
                responsable: $this->nombreUsuario($orden->asignada_a)));
        }

        foreach ($orden->etapas()->get() as $etapa) {
            if ($etapa->iniciada_en) {
                $ev->push($this->evento($etapa->iniciada_en, 52, 'os-et-ini-'.$etapa->id, $this->iconoEtapa($etapa->etapa),
                    'Inicia '.mb_strtolower($etapa->nombre()), null, folio: $orden->folio, url: $url,
                    responsable: $this->nombreUsuario($etapa->iniciada_por)));
            }
            if ($etapa->terminada_en) {
                $ev->push($this->evento($etapa->terminada_en, 53, 'os-et-fin-'.$etapa->id, $this->iconoEtapa($etapa->etapa),
                    'Termina '.mb_strtolower($etapa->nombre()), null, folio: $orden->folio, url: $url,
                    responsable: $this->nombreUsuario($etapa->terminada_por)));
            }
        }

        if ($orden->preparada_en) {
            $ev->push($this->evento($orden->preparada_en, 54, 'os-prep-'.$orden->id, 'empaque',
                'Preparada · lista para salir', $cantidad, folio: $orden->folio, url: $url,
                responsable: $this->nombreUsuario($orden->preparada_por)));
        }

        if ($orden->entregada_en) {
            $ev->push($this->evento($orden->entregada_en, 60, 'os-ent-'.$orden->id, 'entregado',
                'Entregada', $cantidad,
                detalle: $orden->recibe_nombre ? 'Recibe: '.$orden->recibe_nombre : null,
                folio: $orden->folio, url: $url,
                responsable: $this->nombreUsuario($orden->entregada_por)));
        }

        if ($orden->estado === OrdenSalida::CANCELADA) {
            $ev->push($this->evento($orden->updated_at, 61, 'os-cancel-'.$orden->id, 'cancelado',
                'Orden de salida cancelada', null, folio: $orden->folio, url: $url));
        }

        return $ev;
    }

    private function eventosGarantia(int $piezaId): Collection
    {
        if (! $this->can('garantias.ver')) {
            return collect();
        }

        $ev = collect();
        $garantias = Garantia::where('producto_serial_id', $piezaId)->with('reclamaciones')->get();

        foreach ($garantias as $g) {
            $ev->push($this->evento($g->created_at, 70, 'gar-'.$g->id, 'ajuste',
                'Garantía registrada', null,
                detalle: $g->vence ? 'Vence el '.Carbon::parse($g->vence)->format('d/m/Y') : null,
                folio: $g->folio, url: route('garantias.show', $g)));

            foreach ($g->reclamaciones as $r) {
                $url = route('garantias.reclamaciones.show', $r);
                $ev->push($this->evento($r->recibida_en ?? $r->created_at, 71, 'rec-'.$r->id, 'devuelto',
                    'Reclamación de garantía recibida', null,
                    detalle: $r->sintomas ? \Illuminate\Support\Str::limit($r->sintomas, 120) : null,
                    folio: $r->folio, url: $url));
                if ($r->cerrada_en) {
                    $ev->push($this->evento($r->cerrada_en, 72, 'rec-fin-'.$r->id, 'entregado',
                        'Reclamación cerrada', null, folio: $r->folio, url: $url));
                }
            }
        }

        return $ev;
    }

    /**
     * Historial de un producto: lo que registra la bitácora de Entrada y
     * salida (entradas, ventas descontadas y salidas entregadas). La venta
     * y la salida son la misma operación en esa bitácora, así que cada
     * renglón aparece una sola vez.
     */
    private function eventosProducto(Producto $producto): Collection
    {
        $verInv = $this->can('inventario.ver');

        return $this->ordenar(InventoryMovement::where('item_type', InventoryMovement::ITEM_PRODUCT)
            ->where('item_id', $producto->id)
            ->with('creator:id,name')
            ->orderBy('id')
            ->limit(2000)
            ->get()
            ->flatMap(function (InventoryMovement $m) use ($verInv) {
                $url = $verInv ? route('inventory.movimientos.show', $m) : null;
                $out = collect();

                if ($m->movement_type === InventoryMovement::TYPE_ENTRY) {
                    $out->push($this->evento($this->momento($m->movement_date, $m->created_at), 10, 'mov-'.$m->id,
                        'recibido', 'Recibido', $m->quantity,
                        detalle: $m->supplier ? 'Proveedor: '.$m->supplier : null,
                        destino: $m->warehouse, folio: $m->folio, url: $url, responsable: $m->creator?->name));
                } elseif ($m->movement_type === InventoryMovement::TYPE_EXIT) {
                    $out->push($this->evento($this->momento($m->movement_date, $m->created_at), 40, 'mov-'.$m->id,
                        'venta', 'Vendido · pendiente de embarque', $m->quantity,
                        detalle: $m->reference, origen: $m->warehouse, folio: $m->folio, url: $url,
                        responsable: $m->creator?->name));

                    if ($m->entregado_en) {
                        $out->push($this->evento($m->entregado_en, 60, 'mov-ent-'.$m->id,
                            'entregado', 'Salida entregada', $m->quantity,
                            origen: $m->warehouse, folio: $m->folio, url: $url));
                    }
                } else {
                    $out->push($this->evento($this->momento($m->movement_date, $m->created_at), 30, 'mov-'.$m->id,
                        'traslado', 'Traslado', $m->quantity,
                        origen: $m->warehouse, destino: $m->destination_warehouse, folio: $m->folio, url: $url,
                        responsable: $m->creator?->name));
                }

                return $out;
            }));
    }

    /* =====================================================================
     |  Utilidades
     ===================================================================== */

    /**
     * Un evento de la línea de tiempo. "peso" desempata cuando dos cosas
     * pasan en el mismo instante (una entrada va antes que su venta) y
     * "clave" deja el orden estable y sirve para no repetir.
     */
    private function evento(
        ?Carbon $cuando, int $peso, string $clave, string $icono, string $accion, ?int $cantidad,
        ?string $detalle = null, ?string $origen = null, ?string $destino = null,
        ?string $folio = null, ?string $url = null, ?string $responsable = null,
    ): array {
        $local = $cuando?->copy()->setTimezone($this->zona());

        return [
            'clave' => $clave,
            'orden' => sprintf('%s|%03d|%s', $cuando?->utc()->format('YmdHis') ?? '00000000000000', $peso, $clave),
            'fecha' => $local?->format('d/m/Y'),
            'hora' => $local?->format('H:i'),
            'iso' => $cuando?->toIso8601String(),
            'icono' => $icono,
            'accion' => $accion,
            'cantidad' => $cantidad,
            'detalle' => $detalle,
            'origen' => $origen,
            'destino' => $destino,
            'folio' => $folio,
            'url' => $url,
            'responsable' => $responsable,
        ];
    }

    /** Del más viejo al más nuevo, estable y sin repetir. */
    private function ordenar(Collection $eventos): Collection
    {
        return $eventos->filter(fn ($e) => $e['iso'] !== null)
            ->unique('clave')
            ->sortBy('orden', SORT_STRING)
            ->values();
    }

    /**
     * La fecha del movimiento es solo día; la hora real es la de captura.
     * Si se capturó otro día (fecha atrasada), se usa el día del
     * movimiento a mediodía para no inventar una hora.
     */
    private function momento(?Carbon $fecha, ?Carbon $capturado): ?Carbon
    {
        if (! $fecha) {
            return $capturado;
        }

        if ($capturado && $capturado->copy()->setTimezone($this->zona())->toDateString() === $fecha->toDateString()) {
            return $capturado;
        }

        return Carbon::parse($fecha->toDateString().' 12:00', $this->zona());
    }

    private function iconoEtapa(string $etapa): string
    {
        return match ($etapa) {
            OrdenSalidaEtapa::INGENIERIA => 'inspeccion',
            OrdenSalidaEtapa::EMPAQUE => 'empaque',
            OrdenSalidaEtapa::ENTREGA => 'salida',
            default => 'picking',
        };
    }

    private function zona(): string
    {
        return config('agenda.timezone', 'America/Mexico_City');
    }

    private function nombreProducto(?Producto $p): string
    {
        if (! $p) {
            return 'Equipo';
        }

        return trim(collect([$p->tipo_equipo, $p->subtipo])->filter()->implode(' · ')) ?: 'Equipo';
    }

    private function cliente(Venta $venta): string
    {
        return trim(($venta->customer?->nombre ?? '').' '.($venta->customer?->apellido ?? ''));
    }

    /** Mismas reglas que Ventas: con permiso y, si no ve todas, solo las suyas. */
    private function puedeVerVenta(Venta $venta): bool
    {
        return $this->user !== null && $this->can('ventas.ver') && $venta->visiblePara($this->user);
    }

    private function can(string $permiso): bool
    {
        return (bool) $this->user?->can($permiso);
    }

    /** @var array<int, ?string> */
    private array $nombres = [];

    private function nombreUsuario(?int $id): ?string
    {
        if (! $id) {
            return null;
        }

        return $this->nombres[$id] ??= User::whereKey($id)->value('name');
    }
}
