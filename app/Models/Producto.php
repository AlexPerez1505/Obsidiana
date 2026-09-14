<?php

namespace App\Models;

use App\Models\Concerns\SincronizaNombresDeCatalogo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\DB;

class Producto extends Model
{
    use SincronizaNombresDeCatalogo;

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
        'no_serie',
        'es_serializado',
    ];

    protected $casts = [
        'precio' => 'decimal:2',
        'stock' => 'integer',
        'es_serializado' => 'boolean',
    ];

    // La sincronización con el catálogo vive en el trait, compartida con
    // Equipo. El mapa por omisión ya usa los nombres de esta tabla.

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
    /**
     * Las unidades que de verdad se pueden vender.
     *
     * Una pieza parada en hojalatería o mantenimiento existe, pero no se
     * puede ofrecer todavía: no cuenta como stock hasta que su ruta de
     * procesos queda limpia y pasa a "disponible".
     */
    public function serialesDisponibles(): HasMany
    {
        return $this->seriales()
            ->where('vendido', false)
            ->whereNotIn('estado', ProductoSerial::NO_VENDIBLES);
    }

    /** Las que están detenidas en algún proceso, para poder avisarlo. */
    public function serialesEnProceso(): HasMany
    {
        return $this->seriales()
            ->where('vendido', false)
            ->whereIn('estado', ProductoSerial::NO_VENDIBLES);
    }

    /**
     * Agrega unidades nuevas al inventario de este producto: una fila por
     * cada unidad capturada, y filas vacías para completar la cantidad
     * cuando no se capturó una unidad por cada una.
     *
     * $unidades acepta dos formatos, y se pueden mezclar: un string suelto
     * (solo el número de serie, como se ha hecho siempre) o un arreglo
     * ['no_serie' => ..., 'foto_path' => ...] cuando además se capturó la
     * foto individual de esa unidad (entradas de productos serializados).
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
    public function agregarUnidades(
        int $cantidad,
        array $unidades = [],
        ?int $inventoryMovementId = null,
        string $condicion = 'nuevo',
        string $estado = 'disponible'
    ): void {
        DB::transaction(function () use ($cantidad, $unidades, $inventoryMovementId, $condicion, $estado) {
            static::whereKey($this->id)->lockForUpdate()->first();

            $unidades = collect($unidades)
                ->map(function ($unidad) {
                    if (is_array($unidad)) {
                        return [
                            'no_serie' => trim((string) ($unidad['no_serie'] ?? '')) ?: null,
                            'foto_path' => $unidad['foto_path'] ?? null,
                        ];
                    }

                    return ['no_serie' => trim((string) $unidad) ?: null, 'foto_path' => null];
                })
                ->filter(fn (array $u) => $u['no_serie'] !== null || $u['foto_path'] !== null)
                ->values();

            $capturadoPor = auth()->id();

            // Cada pieza física lleva su etiqueta interna: es lo que se
            // imprime en el QR y lo que permite seguirla por los procesos.
            // Los códigos se reservan de una sola vez.
            $total = max($cantidad, $unidades->count());
            $codigos = ProductoSerial::generarCodigos($total);
            $n = 0;

            $comun = [
                'condicion' => $condicion,
                'estado' => $estado,
                'vendido' => false,
                'inventory_movement_id' => $inventoryMovementId,
                'capturado_por' => $capturadoPor,
            ];

            foreach ($unidades as $unidad) {
                $this->seriales()->create($comun + [
                    'codigo' => $codigos[$n++],
                    'no_serie' => $unidad['no_serie'],
                    'foto_path' => $unidad['foto_path'],
                ]);
            }

            for ($i = $unidades->count(); $i < $cantidad; $i++) {
                $this->seriales()->create($comun + [
                    'codigo' => $codigos[$n++],
                    'no_serie' => null,
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
