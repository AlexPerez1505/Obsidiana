<?php

namespace App\Models\Concerns;

use App\Models\Brand;
use App\Models\EquipmentModel;
use App\Models\EquipmentType;
use App\Models\Subtype;

/**
 * Copia los nombres del catálogo a las columnas de texto del modelo.
 *
 * Guardar solo la llave foránea sería lo limpio, pero cotizaciones,
 * facturas, paquetes y servicios leen $modelo->marca directamente, y un
 * documento ya emitido debe conservar el nombre que tenía ese día aunque
 * después se renombre el catálogo. Por eso se guardan los dos.
 *
 * El modelo declara su propio mapa, porque no todos nombran igual sus
 * columnas: Producto usa "tipo_equipo" y Equipo usa "tipo".
 */
trait SincronizaNombresDeCatalogo
{
    public static function bootSincronizaNombresDeCatalogo(): void
    {
        static::saving(function ($modelo) {
            $modelo->sincronizarNombresDeCatalogo();
        });
    }

    /**
     * @return array<string, string>  columna del id => columna de texto
     */
    protected function mapaDeCatalogo(): array
    {
        return [
            'equipment_type_id' => 'tipo_equipo',
            'subtype_id' => 'subtipo',
            'brand_id' => 'marca',
            'equipment_model_id' => 'modelo',
        ];
    }

    public function sincronizarNombresDeCatalogo(): void
    {
        $clases = [
            'equipment_type_id' => EquipmentType::class,
            'subtype_id' => Subtype::class,
            'brand_id' => Brand::class,
            'equipment_model_id' => EquipmentModel::class,
        ];

        foreach ($this->mapaDeCatalogo() as $llave => $columnaTexto) {
            if (! $this->{$llave}) {
                continue;
            }

            // Solo se consulta cuando la llave cambió, para no pegarle a la
            // base en cada guardado.
            if (! $this->isDirty($llave) && $this->{$columnaTexto}) {
                continue;
            }

            $registro = $clases[$llave]::find($this->{$llave});

            if ($registro) {
                $this->{$columnaTexto} = $registro->name;
            }
        }
    }
}
