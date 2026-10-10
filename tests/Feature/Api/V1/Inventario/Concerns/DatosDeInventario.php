<?php

namespace Tests\Feature\Api\V1\Inventario\Concerns;

use App\Models\Brand;
use App\Models\EquipmentModel;
use App\Models\EquipmentType;
use App\Models\Producto;
use App\Models\Subtype;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Datos de prueba de inventario: catálogo, una firma válida, renglones de
 * pieza con evidencia y productos con piezas.
 */
trait DatosDeInventario
{
    /**
     * Como Storage::fake('public'), pero en una carpeta propia: la de
     * Storage::fake la comparten todos los procesos de pruebas que corren
     * a la vez en este árbol, y uno la borra mientras otro la limpia.
     */
    protected function discoPublicoDePrueba(): void
    {
        $raiz = storage_path('framework/testing/disks/public-api-inventario');

        (new Filesystem)->cleanDirectory($raiz);

        Storage::set('public', Storage::createLocalDriver([
            'root' => $raiz,
            'url' => rtrim((string) config('app.url'), '/').'/storage',
        ]));
    }

    /** Data URL base64 mínima (PNG 1x1) para simular una firma capturada. */
    protected function firmaValida(): string
    {
        return 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=';
    }

    /** @return array{tipo: EquipmentType, subtipo: Subtype, marca: Brand, modelo: EquipmentModel} */
    protected function catalogo(string $modelo = 'CV-190'): array
    {
        $tipo = EquipmentType::firstOrCreate(['name' => 'TORRE DE ENDOSCOPIA']);
        $subtipo = Subtype::firstOrCreate(['equipment_type_id' => $tipo->id, 'name' => 'PROCESADOR']);
        $marca = Brand::firstOrCreate(['name' => 'OLYMPUS']);
        $modeloM = EquipmentModel::firstOrCreate(['brand_id' => $marca->id, 'subtype_id' => $subtipo->id, 'name' => $modelo]);

        return ['tipo' => $tipo, 'subtipo' => $subtipo, 'marca' => $marca, 'modelo' => $modeloM];
    }

    /** Un renglón de pieza con 1 foto de evidencia y sin video. */
    protected function unidad(array $overrides = []): array
    {
        return array_merge([
            'no_serie' => null,
            'evidencias' => [UploadedFile::fake()->create('u.jpg', 50, 'image/jpeg')],
        ], $overrides);
    }

    protected function datosEntrada(array $overrides = []): array
    {
        $cat = $this->catalogo();

        return array_merge([
            'equipment_type_id' => $cat['tipo']->id,
            'subtype_id' => $cat['subtipo']->id,
            'brand_id' => $cat['marca']->id,
            'equipment_model_id' => $cat['modelo']->id,
            'precio' => 750,
            'cantidad' => 1,
            'movement_date' => now()->format('Y-m-d'),
            'firma' => $this->firmaValida(),
            'unidades' => [$this->unidad()],
        ], $overrides);
    }

    /** Producto con piezas ya dadas de alta (sin pasar por una entrada). */
    protected function productoConPiezas(array $series = ['SN-1', 'SN-2'], array $atributos = []): Producto
    {
        $producto = Producto::create(array_merge([
            'tipo_equipo' => 'Endoscopio',
            'marca' => 'Olympus',
            'modelo' => 'X1',
            'precio' => 1500,
            'stock' => 0,
        ], $atributos));

        $producto->agregarUnidades(count($series), $series);

        return $producto->fresh();
    }
}
