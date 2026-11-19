<?php

namespace App\Http\Controllers\Api\V1\Garantias;

use App\Http\Controllers\Api\V1\Concerns\ListadoApi;
use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Garantia;
use App\Models\GarantiaCobertura;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

/**
 * Expedientes de garantía (pólizas por equipo): listado con los mismos
 * filtros que la web, alta manual y ficha individual.
 *
 * @tags Garantías
 */
class ExpedienteController extends Controller
{
    use FormatoGarantias;
    use ListadoApi;

    /**
     * Garantías por equipo.
     *
     * Filtros: `q` (serie, folio, equipo, cliente o venta), `semaforo`
     * (vigente, por_vencer, vencida, reclamacion, en_reparacion, anulada),
     * `tipo` (medibuy, fabricante, reacondicionado, reparacion,
     * refacciones, extendida, instalacion).
     */
    public function index(Request $request): JsonResponse
    {
        $dias = (int) config('garantias.dias_por_vencer', 30);

        $garantias = Garantia::query()
            ->with(['customer', 'venta', 'cobertura', 'reclamacionesAbiertas'])
            ->buscar($request->get('q'))
            ->when($request->get('tipo'), fn ($q, $t) => $q->where('tipo', $t))
            ->when($request->get('cliente'), fn ($q, $c) => $q->where('customer_id', $c))
            ->when($request->get('semaforo'), function ($q, $s) use ($dias) {
                match ($s) {
                    'vigente' => $q->activas()->whereDate('vence', '>', today()->addDays($dias)),
                    'por_vencer' => $q->porVencer(),
                    'vencida' => $q->activas()->whereDate('vence', '<', today()),
                    'reclamacion' => $q->activas()->whereHas('reclamacionesAbiertas'),
                    'en_reparacion' => $q->whereHas('reclamacionesAbiertas', fn ($r) => $r->where('etapa', 'atencion')),
                    'anulada' => $q->whereIn('estado', ['anulada', 'sustituida']),
                    default => null,
                };
            })
            ->when($request->get('semaforo') !== 'anulada', fn ($q) => $q->where('estado', '!=', 'anulada'))
            ->orderByRaw('venta_id is null')
            ->orderByDesc('venta_id')
            ->orderBy('grupo')
            ->orderBy('id')
            ->paginate($this->porPagina($request));

        return response()->json([
            'data' => $garantias->getCollection()->map(fn (Garantia $g) => $this->garantiaJson($g))->values(),
            'meta' => [
                'total' => $garantias->total(),
                'pagina' => $garantias->currentPage(),
                'por_pagina' => $garantias->perPage(),
            ],
            'conteos' => [
                'activas' => Garantia::vigentes()->count(),
                'por_vencer' => Garantia::porVencer()->count(),
                'vencidas' => Garantia::activas()->whereDate('vence', '<', today())->count(),
            ],
        ]);
    }

    /**
     * Ficha de una garantía.
     */
    public function show(Garantia $garantia): JsonResponse
    {
        $garantia->load(['customer', 'venta', 'cobertura', 'responsable', 'reclamaciones.costos', 'reclamaciones.responsable']);

        return response()->json([
            'data' => $this->garantiaJson($garantia) + [
                'cubre' => $garantia->cubre,
                'exclusiones' => $garantia->exclusiones,
                'condiciones' => $garantia->condiciones,
                'responsable' => $garantia->responsable?->name,
                'venta' => $garantia->venta?->folio,
                'reclamaciones' => $garantia->reclamaciones->map(fn ($r) => $this->reclamacionJson($r))->values(),
            ],
        ]);
    }

    /**
     * Alta manual de garantía (equipos vendidos antes del sistema,
     * reparaciones, extendidas). El folio GAR-… lo asigna el sistema.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'customer_id' => ['required', 'exists:clientes,id'],
            'descripcion' => ['required', 'string', 'max:255'],
            'marca' => ['nullable', 'string', 'max:120'],
            'modelo' => ['nullable', 'string', 'max:120'],
            'no_serie' => ['nullable', 'string', 'max:120'],
            'condicion' => ['required', Rule::in(array_keys(Garantia::CONDICIONES))],
            'cobertura_id' => ['nullable', 'exists:garantia_coberturas,id'],
            'tipo' => ['required', Rule::in(array_keys(Garantia::TIPOS))],
            'meses' => ['required', 'integer', 'min:1', 'max:120'],
            'inicio' => ['required', 'date'],
            'proveedor' => ['nullable', 'string', 'max:160'],
            'precio_venta' => ['nullable', 'numeric', 'min:0'],
            'costo_adquisicion' => ['nullable', 'numeric', 'min:0'],
            'responsable_id' => ['nullable', 'exists:users,id'],
            'notas' => ['nullable', 'string', 'max:2000'],
        ]);

        $cobertura = isset($data['cobertura_id']) ? GarantiaCobertura::find($data['cobertura_id']) : null;
        $inicio = Carbon::parse($data['inicio'])->startOfDay();

        $garantia = Garantia::create($data + [
            'origen' => 'manual',
            'inicio' => $inicio,
            'vence' => Garantia::calcularVence($inicio, (int) $data['meses']),
            'fecha_entrega' => $inicio,
            'fechas_manual' => true,
            'cubre' => $cobertura?->cubre,
            'exclusiones' => $cobertura?->exclusiones,
            'condiciones' => $cobertura?->condiciones,
            'estado' => 'activa',
            'created_by' => $request->user()->id,
        ]);

        $garantia->registrar('creada', 'Garantía registrada a mano · '.$garantia->meses.' meses.');

        return response()->json([
            'data' => $this->garantiaJson($garantia->fresh(['customer', 'cobertura', 'reclamacionesAbiertas'])),
            'message' => 'Garantía '.$garantia->folio.' registrada.',
        ], 201);
    }

    /**
     * Catálogos del alta manual: clientes, coberturas activas y
     * responsables, en una sola llamada.
     */
    public function catalogos(): JsonResponse
    {
        return response()->json([
            'clientes' => Customer::orderBy('nombre')->get(['id', 'nombre', 'apellido'])
                ->map(fn ($c) => ['id' => $c->id, 'nombre' => trim($c->nombre.' '.$c->apellido)])->values(),
            'coberturas' => GarantiaCobertura::where('activo', true)->orderBy('nombre')->get()
                ->map(fn ($c) => ['id' => $c->id, 'nombre' => $c->nombre, 'tipo' => $c->tipo, 'meses' => $c->meses])->values(),
            'responsables' => User::where('status', User::STATUS_APPROVED)->orderBy('name')->get(['id', 'name'])
                ->map(fn ($u) => ['id' => $u->id, 'nombre' => $u->name])->values(),
            'tipos' => Garantia::TIPOS,
            'condiciones' => Garantia::CONDICIONES,
        ]);
    }
}
