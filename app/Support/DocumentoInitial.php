<?php

namespace App\Support;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Model;

/**
 * Arma el arreglo de estado inicial que consume el formulario JS del cotizador,
 * a partir de un documento (Cotizacion o Venta) o de valores por defecto.
 * Cotización y venta comparten la misma estructura de items, pagos y fichas.
 */
class DocumentoInitial
{
    public static function build(?Model $doc, ?Customer $clientePre = null): array
    {
        $cliente = $clientePre ?? $doc?->customer;

        return [
            'customer' => $cliente ? [
                'id' => $cliente->id,
                'nombre' => trim($cliente->nombre.' '.$cliente->apellido),
                'correo' => $cliente->correo,
                'rfc' => $cliente->rfc,
            ] : null,
            'congreso_id' => $doc->congreso_id ?? '',
            'garantia_meses' => $doc->garantia_meses ?? 6,
            'nota_cliente' => $doc->nota_cliente ?? '',
            'modalidad' => $doc->modalidad ?? 'contado',
            'aplica_iva' => (bool) ($doc->aplica_iva ?? false),
            'envio' => (float) ($doc->envio ?? 0),
            'descuento_tipo' => $doc->descuento_tipo ?? 'porcentaje',
            'descuento_valor' => (float) ($doc->descuento_valor ?? 0),
            'valor_a_cuenta' => (float) ($doc->valor_a_cuenta ?? 0),
            'plan_nombre' => $doc->plan_nombre ?? 'Plan Personalizado',
            'num_meses' => (int) ($doc->num_meses ?? 2),
            'items' => $doc ? $doc->items->map(fn ($i) => [
                'tipo_item' => $i->tipo_item,
                'equipo_id' => $i->equipo_id,
                'paquete_id' => $i->paquete_id,
                'nombre' => $i->nombre,
                'modelo' => $i->modelo,
                'marca' => $i->marca,
                'imagen' => $i->imagen, // ya guardado como URL lista para <img src>
                'cantidad' => $i->cantidad,
                'precio_unitario' => (float) $i->precio_unitario,
                'sobreprecio' => (float) $i->sobreprecio,
                'es_regalo' => (bool) $i->es_regalo,
            ])->values() : [],
            'pagos' => $doc ? $doc->pagos->map(fn ($p) => [
                'nombre' => $p->nombre,
                'fecha' => optional($p->fecha)->toDateString(),
                'porcentaje' => (float) $p->porcentaje,
                'monto' => (float) $p->monto,
                'bloqueado' => (bool) $p->bloqueado,
            ])->values() : [],
            'fichas' => $doc ? $doc->fichas->map(fn ($f) => ['id' => $f->id, 'titulo' => $f->titulo])->values() : [],
        ];
    }
}
