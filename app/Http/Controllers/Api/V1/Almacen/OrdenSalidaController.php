<?php

namespace App\Http\Controllers\Api\V1\Almacen;

use App\Http\Controllers\Api\V1\Concerns\ListadoApi;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Almacen\OrdenSalidaResource;
use App\Models\OrdenSalida;
use App\Models\OrdenSalidaItem;
use App\Services\Almacen\PreparacionDeSalida;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Órdenes de salida: lo que almacén prepara, emplaya y firma cuando se
 * vende. Nacen solas al registrar la venta; aquí no se crean ni se borran.
 *
 * El recorrido es: marcar cada partida (preparado y, si aplica,
 * emplayado) → confirmar la preparación → firmar la entrega. Cada paso
 * rechaza con 409 lo que no toca según el estado de la orden.
 *
 * @tags Almacén · Órdenes de salida
 */
class OrdenSalidaController extends Controller
{
    use ListadoApi;

    /** Lo que trae el detalle (y la respuesta de cada acción). */
    private const DETALLE = [
        'venta.customer', 'venta.seller', 'preparadaPor', 'entregadaPor',
        'items.ventaItem.seriales.congress',
    ];

    public function __construct(private readonly PreparacionDeSalida $salidas)
    {
    }

    /**
     * Listar órdenes de salida.
     *
     * Por omisión, las abiertas (pendientes, en preparación y listas para
     * salir). `estado=todas` quita el filtro. `conteos` trae los números de
     * las pestañas de la web: abiertas, listas y entregadas.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $filtros = $request->validate([
            // abiertas (por omisión), todas, o un estado: pendiente, en_preparacion, lista, entregada, cancelada.
            'estado' => ['nullable', Rule::in([PreparacionDeSalida::ABIERTAS, 'todas', ...array_keys(OrdenSalida::ESTADOS)])],
            // Folio de la orden o de la venta, o nombre del cliente.
            'buscar' => ['nullable', 'string', 'max:100'],
            // Fecha de creación de la orden, Y-m-d.
            'desde' => ['nullable', 'date_format:Y-m-d'],
            'hasta' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:desde'],
            'venta_id' => ['nullable', 'integer'],
        ]);

        $estado = $filtros['estado'] ?? PreparacionDeSalida::ABIERTAS;
        $query = OrdenSalida::with(['venta.customer', 'venta.seller', 'items']);

        if ($estado !== 'todas') {
            $this->salidas->filtrarPorEstado($query, $estado);
        }

        $query
            ->when($filtros['buscar'] ?? null, function ($q, string $texto) {
                $q->where(fn ($w) => $w
                    ->where('folio', 'like', "%{$texto}%")
                    ->orWhereHas('venta', fn ($v) => $v
                        ->where('folio', 'like', "%{$texto}%")
                        ->orWhereHas('customer', fn ($c) => $c
                            ->where('nombre', 'like', "%{$texto}%")
                            ->orWhere('apellido', 'like', "%{$texto}%"))));
            })
            ->when($filtros['desde'] ?? null, fn ($q, $d) => $q->whereDate('created_at', '>=', $d))
            ->when($filtros['hasta'] ?? null, fn ($q, $d) => $q->whereDate('created_at', '<=', $d))
            ->when($filtros['venta_id'] ?? null, fn ($q, $id) => $q->where('venta_id', $id));

        $this->ordenar($query, $request, ['id', 'folio', 'estado', 'created_at', 'preparada_en', 'entregada_en'], '-created_at');

        return OrdenSalidaResource::collection($query->paginate($this->porPagina($request)))
            ->additional(['conteos' => $this->salidas->conteos()]);
    }

    /**
     * Ver una orden de salida.
     *
     * Con sus partidas (y el congreso de origen si el equipo se vendió en
     * uno: no hay que buscarlo en el anaquel), el avance del checklist,
     * quién la preparó y quién la entregó, y las firmas.
     */
    public function show(OrdenSalida $orden): OrdenSalidaResource
    {
        return new OrdenSalidaResource($orden->load(self::DETALLE));
    }

    /**
     * Hoja PDF de la orden.
     *
     * La misma hoja que se imprime en la web: qué salió, quién preparó,
     * quién entregó y quién recibió, con las firmas. Antes de firmar sale
     * marcada como avance.
     */
    public function pdf(OrdenSalida $orden): Response
    {
        return $this->salidas->pdf($orden)->stream("{$orden->folio}.pdf");
    }

    /**
     * Marcar una partida.
     *
     * Marca o desmarca preparado / emplayado, cambia si se emplaya o no, o
     * guarda una observación. Solo se toca lo que se manda. Si se desmarca
     * algo de una orden ya preparada, la preparación deja de valer y la
     * orden regresa a "en preparación".
     */
    public function partida(Request $request, OrdenSalida $orden, OrdenSalidaItem $item): OrdenSalidaResource
    {
        abort_if($item->orden_salida_id !== $orden->id, 404, 'Esa partida no es de esta orden.');

        if ($razon = $this->salidas->razonParaNoMarcar($orden)) {
            abort(409, $razon);
        }

        $data = $request->validate(PreparacionDeSalida::REGLAS_PARTIDA);

        $this->salidas->marcarPartida($orden, $item, $this->salidas->cambiosDePartida($request, $data));

        return $this->respuesta($orden, 'Partida actualizada.');
    }

    /**
     * Guardar notas de la orden.
     *
     * Instrucciones generales para almacén o transporte. Vacío las borra.
     */
    public function notas(Request $request, OrdenSalida $orden): OrdenSalidaResource
    {
        $data = $request->validate(PreparacionDeSalida::REGLAS_NOTAS);

        $this->salidas->guardarNotas($orden, $data['notas'] ?? null);

        return $this->respuesta($orden, 'Notas guardadas.');
    }

    /**
     * Confirmar la preparación.
     *
     * Con el checklist completo, la orden pasa a "Lista para salir" y queda
     * registrado quién la preparó. Es el relevo con quien entrega: la firma
     * puede hacerla otra persona, otro día.
     */
    public function confirmarPreparacion(Request $request, OrdenSalida $orden): OrdenSalidaResource
    {
        if ($razon = $this->salidas->razonParaNoConfirmar($orden)) {
            abort(409, $razon);
        }

        $orden->confirmarPreparacion($request->user()->id);

        return $this->respuesta($orden, "Orden {$orden->folio} lista para salir. Ya solo falta que alguien firme la salida.");
    }

    /**
     * Firmar la salida.
     *
     * Solo con la preparación confirmada. Firman quien entrega por almacén
     * y quien se lleva el equipo (chofer, cliente, paquetería). Las firmas
     * van como data URL de imagen (`data:image/png;base64,...`), lo que da
     * un canvas. Cierra la orden y registra la fecha real de salida en el
     * inventario.
     */
    public function entregar(Request $request, OrdenSalida $orden): OrdenSalidaResource
    {
        if ($razon = $this->salidas->razonParaNoEntregar($orden)) {
            abort(409, $razon);
        }

        $data = $request->validate(PreparacionDeSalida::REGLAS_ENTREGA, PreparacionDeSalida::MENSAJES_ENTREGA);

        $firmado = $this->salidas->entregar($orden, $request->user()->id, $data['recibe_nombre'], $data['firma_entrega'], $data['firma_recibe']);

        if (! $firmado) {
            throw ValidationException::withMessages(['firma_recibe' => PreparacionDeSalida::FIRMA_INVALIDA]);
        }

        return $this->respuesta($orden, "Salida {$orden->folio} firmada. El equipo ya salió.");
    }

    private function respuesta(OrdenSalida $orden, string $mensaje): OrdenSalidaResource
    {
        return (new OrdenSalidaResource($orden->refresh()->load(self::DETALLE)))
            ->additional(['message' => $mensaje]);
    }
}
