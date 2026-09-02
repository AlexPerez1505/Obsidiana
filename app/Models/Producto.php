<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\DB;

class Producto extends Model
{
    protected $table = 'productos';

    protected $fillable = [
        'equipment_type_id',
        'subtype_id',
        'brand_id',
        'equipment_model_id',
        'tipo_equipo',
        'subtipo',
        'marca',
        'modelo',
        'precio',
        'imagen_path',
        'stock',
        'descripcion',
        'proveedor',
        'no_serie',
    ];

    protected $casts = [
        'precio' => 'decimal:2',
        'stock' => 'integer',
    ];

    /**
     * Las columnas de texto se llenan solas desde el catalogo.
     *
     * Cotizaciones, facturas, paquetes y servicios leen $producto->marca y
     * companía directamente, y los PDF ya emitidos deben conservar el nombre
     * que tenia el equipo ese dia. Por eso el texto se guarda ademas del id.
     */
    protected static function booted(): void
    {
        static::saving(function (Producto $producto) {
            $producto->sincronizarNombresDeCatalogo();
        });
    }

    public function sincronizarNombresDeCatalogo(): void
    {
        $mapa = [
            'equipment_type_id' => ['tipo_equipo', EquipmentType::class],
            'subtype_id' => ['subtipo', Subtype::class],
            'brand_id' => ['marca', Brand::class],
            'equipment_model_id' => ['modelo', EquipmentModel::class],
        ];

        foreach ($mapa as $llave => [$columnaTexto, $clase]) {
            if (! $this->{$llave}) {
                continue;
            }

            // Solo se consulta cuando la llave cambio, para no pegarle a la
            // base en cada guardado.
            if (! $this->isDirty($llave) && $this->{$columnaTexto}) {
                continue;
            }

            $registro = $clase::find($this->{$llave});

            if ($registro) {
                $this->{$columnaTexto} = $registro->name;
            }
        }
    }

    public function equipmentType(): BelongsTo
    {
        return $this->belongsTo(EquipmentType::class);
    }

    public function subtype(): BelongsTo
    {
        return $this->belongsTo(Subtype::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function equipmentModel(): BelongsTo
    {
        return $this->belongsTo(EquipmentModel::class);
    }

    public function paquetes(): BelongsToMany
    {
        return $this->belongsToMany(Paquete::class, 'paquete_producto')
            ->withPivot('cantidad')
            ->withTimestamps();
    }

    public function cotizaciones(): HasMany
    {
        return $this->hasMany(Cotizacion::class, 'producto_id');
    }

    /** Cada producto solo puede tener una ficha técnica relacionada. */
    public function fichaTecnica(): HasOne
    {
        return $this->hasOne(FichaTecnica::class);
    }

    /** Cada unidad física de este modelo, con su propio número de serie. */
    public function seriales(): HasMany
    {
        return $this->hasMany(ProductoSerial::class);
    }

    /** Unidades que todavía no se han vendido. */
    public function serialesDisponibles(): HasMany
    {
        return $this->seriales()->where('vendido', false);
    }

    /**
     * Agrega unidades nuevas al inventario de este producto: una fila por
     * cada serial capturado, y filas sin serial para completar la cantidad
     * cuando no se capturó un serial por cada unidad.
     *
     * Si vienen de una entrada registrada en Entrada/Salida, se le pasa su
     * id para poder rastrear después con qué evidencia (fotos del lote)
     * llegó cada unidad.
     *
     * Bloquea la fila de este producto mientras dura la operación: si dos
     * altas llegan casi al mismo tiempo para el mismo producto (dos
     * usuarios, o un doble clic), la segunda espera a que la primera
     * termine, en vez de calcular/insertar el mismo serial dos veces.
     */
    public function agregarUnidades(int $cantidad, array $series = [], ?int $inventoryMovementId = null): void
    {
        DB::transaction(function () use ($cantidad, $series, $inventoryMovementId) {
            static::whereKey($this->id)->lockForUpdate()->first();

            $series = collect($series)->map(fn ($s) => trim((string) $s))->filter()->values();

            foreach ($series as $serie) {
                $this->seriales()->create([
                    'no_serie' => $serie,
                    'vendido' => false,
                    'inventory_movement_id' => $inventoryMovementId,
                ]);
            }

            for ($i = $series->count(); $i < $cantidad; $i++) {
                $this->seriales()->create([
                    'no_serie' => null,
                    'vendido' => false,
                    'inventory_movement_id' => $inventoryMovementId,
                ]);
            }

            $this->recalcularStock();
        });
    }

    /**
     * El stock siempre refleja las unidades no vendidas que de verdad
     * existen como fila, para no depender de sumar/restar el número a mano.
     * El texto de no_serie se conserva como caché de búsqueda (el listado y
     * la búsqueda en cotizaciones filtran por ese campo directamente).
     */
    public function recalcularStock(): void
    {
        $this->stock = $this->serialesDisponibles()->count();
        $this->no_serie = $this->seriales()
            ->where('vendido', false)
            ->whereNotNull('no_serie')
            ->pluck('no_serie')
            ->implode(', ');
        $this->save();
    }
}
