<?php

namespace App\Http\Controllers\Api\V1\Comercial;

use App\Http\Controllers\Api\V1\Concerns\ListadoApi;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Comercial\SolicitudWebResource;
use App\Models\TiendaSolicitud;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

class SolicitudWebController extends Controller
{
    use ListadoApi;

    public function index(Request $request): AnonymousResourceCollection
    {
        $filtros = $request->validate([
            'buscar' => ['nullable', 'string', 'max:255'],
            'estado' => ['nullable', Rule::in(array_keys(TiendaSolicitud::ESTADOS))],
            'tipo' => ['nullable', Rule::in(array_keys(TiendaSolicitud::TIPOS))],
            'con_cliente' => ['nullable', 'boolean'],
            'desde' => ['nullable', 'date_format:Y-m-d'],
            'hasta' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:desde'],
        ]);

        $query = TiendaSolicitud::with(['cliente', 'atendidaPor'])
            ->when(filled($filtros['buscar'] ?? null), function ($query) use ($filtros) {
                $texto = '%'.trim($filtros['buscar']).'%';
                $query->where(function ($query) use ($texto) {
                    foreach (['folio', 'nombre', 'empresa', 'telefono', 'correo', 'ciudad', 'mensaje', 'articulos'] as $campo) {
                        $query->orWhere($campo, 'like', $texto);
                    }
                });
            })
            ->when(filled($filtros['estado'] ?? null), fn ($q) => $q->where('estado', $filtros['estado']))
            ->when(filled($filtros['tipo'] ?? null), fn ($q) => $q->where('tipo', $filtros['tipo']))
            ->when(isset($filtros['con_cliente']), fn ($q) => $request->boolean('con_cliente') ? $q->whereNotNull('customer_id') : $q->whereNull('customer_id'))
            ->when(filled($filtros['desde'] ?? null), fn ($q) => $q->whereDate('created_at', '>=', $filtros['desde']))
            ->when(filled($filtros['hasta'] ?? null), fn ($q) => $q->whereDate('created_at', '<=', $filtros['hasta']));

        $this->ordenar($query, $request, ['id', 'created_at', 'folio']);
        $conteos = TiendaSolicitud::query()->selectRaw('estado, COUNT(*) as total')->groupBy('estado')->pluck('total', 'estado');

        return SolicitudWebResource::collection($query->paginate($this->porPagina($request)))->additional([
            'resumen' => [
                'total' => (int) $conteos->sum(),
                'sin_atender' => (int) ($conteos['nueva'] ?? 0),
                'cotizadas' => (int) ($conteos['cotizada'] ?? 0),
            ],
            'estados' => collect(TiendaSolicitud::ESTADOS)->map(fn ($estado, $valor) => ['valor' => $valor, 'label' => $estado['texto']])->values(),
            'tipos' => collect(TiendaSolicitud::TIPOS)->map(fn ($label, $valor) => ['valor' => $valor, 'label' => $label])->values(),
        ]);
    }

    public function show(TiendaSolicitud $solicitud): SolicitudWebResource
    {
        return new SolicitudWebResource($solicitud->load(['cliente', 'atendidaPor']));
    }

    public function update(Request $request, TiendaSolicitud $solicitud): SolicitudWebResource
    {
        $datos = $request->validate([
            'estado' => ['required', Rule::in(array_keys(TiendaSolicitud::ESTADOS))],
            'nota_interna' => ['nullable', 'string', 'max:2000'],
        ]);
        $solicitud->fill($datos);
        if ($solicitud->isDirty('estado') && $solicitud->estado !== 'nueva') {
            $solicitud->atendida_por = $request->user()->id;
            $solicitud->atendida_en = now();
        }
        $solicitud->save();

        return (new SolicitudWebResource($solicitud->load(['cliente', 'atendidaPor'])))
            ->additional(['message' => 'Solicitud '.$solicitud->folio.' actualizada.']);
    }
}
