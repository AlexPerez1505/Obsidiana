<?php

namespace App\Http\Controllers\Concerns;

use App\Models\EquipmentModel;
use App\Models\EquipmentType;
use App\Models\Subtype;

/**
 * Árbol de Tipo -> Subtipo -> Marca -> Modelo para los selects encadenados
 * del catálogo. Lo usan tanto el alta directa de productos como el alta vía
 * Entrada de inventario: el formulario no necesita ir al servidor cada vez
 * que se cambia un select.
 */
trait ConstruyeCatalogoEquipo
{
    private function catalogoEquipo(): array
    {
        $subtipos = Subtype::with('brands')->orderBy('name')->get();

        return [
            'tipos' => EquipmentType::orderBy('name')->get(['id', 'name'])->all(),

            'subtipos' => $subtipos
                ->groupBy('equipment_type_id')
                ->map(fn ($lista) => $lista->map(fn ($s) => ['id' => $s->id, 'name' => $s->name])->values())
                ->all(),

            'marcas' => $subtipos
                ->mapWithKeys(fn ($s) => [
                    $s->id => $s->brands->sortBy('name')->map(fn ($b) => ['id' => $b->id, 'name' => $b->name])->values(),
                ])
                ->all(),

            // La llave es "subtipo-marca": el modelo depende de las dos cosas.
            'modelos' => EquipmentModel::orderBy('name')->get(['id', 'name', 'brand_id', 'subtype_id'])
                ->groupBy(fn ($m) => $m->subtype_id.'-'.$m->brand_id)
                ->map(fn ($lista) => $lista->map(fn ($m) => ['id' => $m->id, 'name' => $m->name])->values())
                ->all(),
        ];
    }
}
