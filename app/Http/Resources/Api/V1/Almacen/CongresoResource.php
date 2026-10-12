<?php

namespace App\Http\Resources\Api\V1\Almacen;

use App\Http\Resources\Api\V1\UsuarioResource;
use App\Services\Almacen\GestionDeCongresos;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Un congreso: fechas, stand, costos, archivos, qué piezas se llevaron y
 * quién fue. Los costos los ve quien ve congresos, igual que en la web.
 *
 * @mixin \App\Models\Congress
 */
class CongresoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nombre' => $this->nombre,
            'descripcion' => $this->descripcion,
            'categoria_id' => $this->categoria_id,
            'fecha_inicio' => $this->fecha_inicio?->toDateString(),
            'fecha_finalizacion' => $this->fecha_finalizacion?->toDateString(),
            'fecha_montaje' => $this->fecha_montaje?->toDateString(),
            'hora_montaje' => $this->hora_montaje?->format('H:i'),
            'hora_desmontaje' => $this->hora_desmontaje?->format('H:i'),
            'num_stand' => $this->num_stand,
            'direccion' => $this->direccion,
            'latitude' => $this->latitude !== null ? (float) $this->latitude : null,
            'longitud' => $this->longitud !== null ? (float) $this->longitud : null,
            'costo' => $this->costo !== null ? (float) $this->costo : null,
            'pagado' => (float) $this->pagado,
            'saldo_pendiente' => $this->saldoPendiente(),
            'liquidado' => $this->estaLiquidado(),
            'descarga_acceso' => $this->descarga_acceso,
            'descarga_texto' => $this->descarga_texto,
            'acceso_subir' => $this->acceso_subir,
            'subir_texto' => $this->subir_texto,
            'comments' => $this->comments,
            'archivos' => app(GestionDeCongresos::class)->archivosUrls($this->resource),
            // upcoming | active | finished, contra la fecha de hoy.
            'estado' => $this->estado(),
            'estado_label' => $this->estadoLabel(),
            'aviso_regreso_en' => $this->aviso_regreso_en,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            'unidades_presentes_count' => $this->whenCounted('unidadesPresentes'),
            // Ya terminó y quedan piezas marcadas como que están allá.
            'tiene_piezas_sin_regresar' => $this->whenLoaded('unidadesPresentes', fn () => $this->tienePiezasSinRegresar()),
            'unidades_vendidas_count' => $this->whenCounted('unidadesVendidas'),
            'category' => CategoriaResource::make($this->whenLoaded('category')),
            // Lo que sigue allá, agrupado por producto como en la pantalla.
            'productos' => $this->whenLoaded('unidadesPresentes', fn () => $this->unidadesPresentes
                ->groupBy('producto_id')
                ->map(fn ($unidades) => [
                    'producto_id' => $unidades->first()->producto_id,
                    'producto' => $unidades->first()->producto?->nombreVisible(),
                    'marca' => $unidades->first()->producto?->marca,
                    'modelo' => $unidades->first()->producto?->modelo,
                    'cantidad' => $unidades->count(),
                    'unidades' => PiezaResource::collection($unidades),
                ])
                ->values()),
            'participantes' => CongresoParticipanteResource::collection($this->whenLoaded('participantes')),
            'usuarios' => $this->whenLoaded('notifiedUsers', fn () => $this->notifiedUsers
                ->map(fn ($u) => (new UsuarioResource($u))->toArray($request) + [
                    'notified' => (bool) $u->pivot->notified,
                    'notified_at' => $u->pivot->notified_at,
                ])
                ->values()),
        ];
    }
}
