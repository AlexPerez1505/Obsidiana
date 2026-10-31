<?php

namespace App\Http\Controllers\Api\V1\Garantias;

use App\Http\Controllers\Api\V1\Concerns\ListadoApi;
use App\Http\Controllers\Controller;
use App\Models\Garantia;
use App\Models\GarantiaReclamacion;
use App\Models\User;
use App\Services\Garantias\AtencionDeReclamaciones;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Reclamaciones de garantía: listado con los filtros de la web, registro
 * (folio REC-… automático), ficha, reasignación y avance de etapa.
 *
 * @tags Garantías
 */
class ReclamacionController extends Controller
{
    use FormatoGarantias;
    use ListadoApi;

    public function __construct(private AtencionDeReclamaciones $atencion) {}

    /**
     * Reclamaciones.
     *
     * `vista`: abiertas, mias, atrasadas, seguridad, cerradas, todas.
     * Filtros: `q`, `etapa`, `prioridad`, `responsable` (user id).
     */
    public function index(Request $request): JsonResponse
    {
        $vista = $request->get('vista', 'abiertas');

        $reclamaciones = GarantiaReclamacion::query()
            ->with(['garantia', 'customer', 'responsable'])
            ->when($vista === 'abiertas', fn ($q) => $q->abiertas())
            ->when($vista === 'atrasadas', fn ($q) => $q->atrasadas())
            ->when($vista === 'mias', fn ($q) => $q->abiertas()->where('responsable_id', $request->user()->id))
            ->when($vista === 'seguridad', fn ($q) => $q->where('es_seguridad', true))
            ->when($vista === 'cerradas', fn ($q) => $q->where('etapa', GarantiaReclamacion::CERRADA))
            ->when($request->get('etapa'), fn ($q, $e) => $q->where('etapa', $e))
            ->when($request->get('prioridad'), fn ($q, $p) => $q->where('prioridad', $p))
            ->when($request->get('responsable'), fn ($q, $r) => $q->where('responsable_id', $r))
            ->when(trim((string) $request->get('q')) !== '', function ($q) use ($request) {
                $like = '%'.trim((string) $request->get('q')).'%';
                $q->where(fn ($w) => $w->where('folio', 'like', $like)
                    ->orWhere('no_serie', 'like', $like)
                    ->orWhere('equipo', 'like', $like)
                    ->orWhere('contacto_nombre', 'like', $like)
                    ->orWhere('sintomas', 'like', $like)
                    ->orWhereHas('customer', fn ($c) => $c->whereRaw("CONCAT_WS(' ', nombre, apellido) like ?", [$like]))
                    ->orWhereHas('garantia', fn ($g) => $g->where('descripcion', 'like', $like)->orWhere('modelo', 'like', $like)));
            })
            ->orderByDesc('es_seguridad')
            ->orderByRaw("etapa = 'cerrada'")
            ->orderByRaw("FIELD(prioridad, 'critica', 'alta', 'media', 'baja')")
            ->latest('recibida_en')
            ->paginate($this->porPagina($request));

        return response()->json([
            'data' => $reclamaciones->getCollection()->map(fn (GarantiaReclamacion $r) => $this->reclamacionJson($r))->values(),
            'meta' => [
                'total' => $reclamaciones->total(),
                'pagina' => $reclamaciones->currentPage(),
                'por_pagina' => $reclamaciones->perPage(),
            ],
            'conteos' => [
                'abiertas' => GarantiaReclamacion::abiertas()->count(),
                'atrasadas' => GarantiaReclamacion::atrasadas()->count(),
                'mias' => GarantiaReclamacion::abiertas()->where('responsable_id', $request->user()->id)->count(),
                'seguridad' => GarantiaReclamacion::abiertas()->where('es_seguridad', true)->count(),
            ],
        ]);
    }

    /**
     * Catálogos de reclamaciones: canales, prioridades, etapas y
     * responsables registrados.
     */
    public function catalogos(): JsonResponse
    {
        return response()->json([
            'canales' => GarantiaReclamacion::CANALES,
            'prioridades' => collect(GarantiaReclamacion::PRIORIDADES)->map(fn ($p) => $p[0]),
            'etapas' => collect(GarantiaReclamacion::ETAPAS)->map(fn ($e) => $e[0]),
            'responsables' => User::where('status', User::STATUS_APPROVED)->orderBy('name')->get(['id', 'name'])
                ->map(fn ($u) => ['id' => $u->id, 'nombre' => $u->name])->values(),
        ]);
    }

    /**
     * Busca garantías por serie mientras se captura la reclamación.
     */
    public function buscarSerie(Request $request): JsonResponse
    {
        $q = trim((string) $request->get('q'));
        if (mb_strlen($q) < 2) {
            return response()->json([]);
        }

        return response()->json(
            Garantia::with('customer')
                ->buscar($q)
                ->where('estado', '!=', 'anulada')
                ->limit(8)->get()
                ->map(fn (Garantia $g) => [
                    'id' => $g->id,
                    'folio' => $g->folio,
                    'equipo' => $g->equipoNombre(),
                    'serie' => $g->no_serie,
                    'cliente' => $g->clienteNombre(),
                    'vence' => $g->vence->format('d/m/Y'),
                    'semaforo' => $g->semaforoTexto(),
                ])
                ->values()
        );
    }

    /**
     * Registrar reclamación. El folio REC-… lo asigna el sistema; si la
     * serie coincide con una garantía se liga sola.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'garantia_id' => ['nullable', 'exists:garantias,id'],
            'no_serie' => ['nullable', 'string', 'max:120'],
            'equipo' => ['nullable', 'string', 'max:255'],
            'customer_id' => ['nullable', 'exists:clientes,id'],
            'canal' => ['required', Rule::in(array_keys(GarantiaReclamacion::CANALES))],
            'contacto_nombre' => ['nullable', 'string', 'max:160'],
            'contacto_telefono' => ['nullable', 'string', 'max:40'],
            'contacto_correo' => ['nullable', 'email', 'max:160'],
            'sintomas' => ['required', 'string', 'min:5', 'max:5000'],
            'prioridad' => ['required', Rule::in(array_keys(GarantiaReclamacion::PRIORIDADES))],
            'es_seguridad' => ['nullable', 'boolean'],
            'responsable_id' => ['nullable', 'exists:users,id'],
            'archivos' => ['nullable', 'array', 'max:10'],
            'archivos.*' => ['file', 'max:51200', 'mimes:jpg,jpeg,png,webp,heic,pdf,mp4,mov'],
        ]);

        if (empty($data['garantia_id']) && blank($data['no_serie'] ?? null) && blank($data['equipo'] ?? null)) {
            throw ValidationException::withMessages(['no_serie' => 'Indica la serie o al menos qué equipo es.']);
        }

        $rec = $this->atencion->abrir($data, $request->user(), $request->file('archivos', []));

        return response()->json([
            'data' => $this->reclamacionJson($rec->fresh(['garantia', 'customer', 'responsable']), true),
            'message' => 'Reclamación '.$rec->folio.' registrada.',
        ], 201);
    }

    /**
     * Ficha de una reclamación.
     */
    public function show(GarantiaReclamacion $reclamacion): JsonResponse
    {
        $reclamacion->load(['garantia.customer', 'customer', 'responsable', 'costos', 'eventos.usuario']);

        return response()->json(['data' => $this->reclamacionJson($reclamacion, true)]);
    }

    /**
     * Reasignar responsable, cambiar prioridad o marcar incidente de
     * seguridad (igual que el formulario de la ficha web).
     */
    public function update(Request $request, GarantiaReclamacion $reclamacion): JsonResponse
    {
        $data = $request->validate([
            'responsable_id' => ['nullable', 'exists:users,id'],
            'prioridad' => ['required', Rule::in(array_keys(GarantiaReclamacion::PRIORIDADES))],
            'es_seguridad' => ['nullable', 'boolean'],
        ]);
        $data['es_seguridad'] = (bool) ($data['es_seguridad'] ?? false);

        $reclamacion->fill($data);
        if (! $reclamacion->isDirty()) {
            return response()->json(['data' => $this->reclamacionJson($reclamacion, true)]);
        }

        $cambios = [];
        if ($reclamacion->isDirty('responsable_id')) {
            $cambios[] = 'responsable: '.(User::find($data['responsable_id'])?->name ?? 'nadie');
        }
        if ($reclamacion->isDirty('prioridad')) {
            $cambios[] = 'prioridad: '.$reclamacion->prioridadLabel();
            $reclamacion->limite_en = $reclamacion->recibida_en->copy()
                ->addHours((int) (config('garantias.horas_resolucion')[$data['prioridad']] ?? 120));
        }
        if ($reclamacion->isDirty('es_seguridad')) {
            $cambios[] = $data['es_seguridad'] ? 'marcada como posible incidente de seguridad' : 'ya no se considera incidente de seguridad';
        }

        $reclamacion->save();
        $reclamacion->registrar('editada', ucfirst(implode(' · ', $cambios)).'.');

        return response()->json([
            'data' => $this->reclamacionJson($reclamacion->fresh(['garantia', 'customer', 'responsable', 'costos', 'eventos.usuario']), true),
            'message' => 'Reclamación actualizada.',
        ]);
    }

    /**
     * Avanzar a otra etapa del flujo (recibida → revisión → diagnóstico →
     * atención → entrega). El cierre va por su propio flujo en la web.
     */
    public function etapa(Request $request, GarantiaReclamacion $reclamacion): JsonResponse
    {
        $data = $request->validate([
            'etapa' => ['required', Rule::in(array_keys(GarantiaReclamacion::ETAPAS))],
            'nota' => ['nullable', 'string', 'max:1000'],
        ]);
        $this->atencion->avanzar($reclamacion, $data['etapa'], $data['nota'] ?? null);

        return response()->json([
            'data' => $this->reclamacionJson($reclamacion->fresh(['garantia', 'customer', 'responsable', 'costos', 'eventos.usuario']), true),
            'message' => 'Etapa: '.$reclamacion->etapaLabel().'.',
        ]);
    }
}
