<?php

namespace App\Http\Controllers\Api\V1\Garantias;

use App\Models\Garantia;
use App\Models\GarantiaReclamacion;

/**
 * Formato JSON compartido por los endpoints de garantías: el móvil recibe
 * los mismos textos (labels) que muestra la web, no los slugs crudos.
 */
trait FormatoGarantias
{
    protected function garantiaJson(Garantia $g): array
    {
        return [
            'id' => $g->id,
            'folio' => $g->folio,
            'equipo' => $g->equipoNombre(),
            'marca' => $g->marca,
            'modelo' => $g->modelo,
            'serie' => $g->no_serie,
            'cliente' => $g->clienteNombre(),
            'tipo' => $g->tipo,
            'tipo_label' => $g->tipoLabel(),
            'condicion' => $g->condicion,
            'condicion_label' => $g->condicionLabel(),
            'cobertura' => $g->cobertura?->nombre,
            'meses' => $g->meses,
            'inicio' => $g->inicio?->format('d/m/Y'),
            'vence' => $g->vence?->format('d/m/Y'),
            'dias_restantes' => $g->anulada() ? null : $g->diasRestantes(),
            'semaforo' => $g->semaforo(),
            'semaforo_label' => $g->semaforoTexto(),
            'proveedor' => $g->proveedor,
            'precio_venta' => $g->precio_venta !== null ? (float) $g->precio_venta : null,
            'costo_adquisicion' => $g->costo_adquisicion !== null ? (float) $g->costo_adquisicion : null,
            'origen' => $g->origen,
            'notas' => $g->notas,
        ];
    }

    protected function reclamacionJson(GarantiaReclamacion $r, bool $detalle = false): array
    {
        $base = [
            'id' => $r->id,
            'folio' => $r->folio,
            'equipo' => $r->equipoNombre(),
            'serie' => $r->no_serie,
            'cliente' => $r->clienteNombre(),
            'etapa' => $r->etapa,
            'etapa_label' => $r->etapaLabel(),
            'etapa_numero' => $r->etapaNumero(),
            'etapa_detalle' => GarantiaReclamacion::ETAPAS[$r->etapa][1] ?? null,
            'prioridad' => $r->prioridad,
            'prioridad_label' => $r->prioridadLabel(),
            'dictamen' => $r->dictamen,
            'dictamen_label' => $r->dictamenLabel(),
            'canal' => $r->canal,
            'canal_label' => $r->canalLabel(),
            'responsable' => $r->responsable?->name,
            'responsable_id' => $r->responsable_id,
            'es_seguridad' => (bool) $r->es_seguridad,
            'atrasada' => $r->atrasada(),
            'recibida_en' => $r->recibida_en?->format('d/m/Y H:i'),
            'primera_respuesta_en' => $r->primera_respuesta_en?->format('d/m/Y H:i'),
            'limite_en' => $r->limite_en?->format('d/m/Y H:i'),
            'cerrada_en' => $r->cerrada_en?->format('d/m/Y H:i'),
        ];

        if (! $detalle) {
            return $base;
        }

        return $base + [
            'sintomas' => $r->sintomas,
            'contacto_nombre' => $r->contacto_nombre,
            'contacto_telefono' => $r->contacto_telefono,
            'contacto_correo' => $r->contacto_correo,
            'diagnostico' => $r->diagnostico,
            'pruebas' => $r->pruebas,
            'causa' => $r->causa,
            'resolucion' => $r->resolucion,
            'resolucion_detalle' => $r->resolucion_detalle,
            'satisfaccion' => $r->satisfaccion,
            'entregado_a' => $r->entregado_a,
            'garantia' => $r->garantia ? $this->garantiaJson($r->garantia) : null,
            'costos' => $r->costos->map(fn ($c) => [
                'id' => $c->id,
                'concepto' => $c->concepto,
                'descripcion' => $c->descripcion,
                'monto' => (float) $c->monto,
                'absorbe' => $c->absorbe,
                'recuperado' => (float) ($c->recuperado ?? 0),
                'estado' => $c->estado,
            ])->values(),
            'eventos' => $r->eventos->take(50)->map(fn ($e) => [
                'tipo' => $e->tipo,
                'descripcion' => $e->descripcion,
                'usuario' => $e->usuario?->name,
                'fecha' => $e->created_at?->format('d/m/Y H:i'),
            ])->values(),
        ];
    }
}
