<?php

namespace App\Http\Controllers\Configuracion;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\EquipmentModel;
use App\Models\EquipmentType;
use App\Models\Subtype;
use App\Services\Almacen\CatalogoDeEquipo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Catalogo de equipo: tipo -> subtipo -> marca -> modelo.
 *
 * Todo se administra desde Configuracion > Catalogos, sin tocar codigo.
 * Cada accion regresa al listado con un mensaje que el layout muestra
 * como toast. Las reglas (repetidos, marcas por subtipo) viven en
 * CatalogoDeEquipo, que comparte con la API: si algo choca lanza un error
 * de validacion y se regresa al formulario con lo capturado.
 */
class CatalogoEquipoController extends Controller
{
    public function __construct(private readonly CatalogoDeEquipo $catalogo)
    {
    }

    // ===================== Tipos de equipo =====================

    public function storeType(Request $request): RedirectResponse
    {
        $data = $request->validate(CatalogoDeEquipo::reglasTipo(), CatalogoDeEquipo::MENSAJES_TIPO);

        $this->catalogo->crearTipo($data['name'], $request->boolean('requiere_emplayado'));

        return $this->volver('Tipo de equipo guardado correctamente.');
    }

    public function updateType(Request $request, EquipmentType $type): RedirectResponse
    {
        $data = $request->validate(CatalogoDeEquipo::reglasTipo($type), CatalogoDeEquipo::MENSAJES_TIPO);

        $this->catalogo->actualizarTipo($type, $data['name'], $request->boolean('requiere_emplayado'));

        return $this->volver('Tipo de equipo actualizado correctamente.');
    }

    public function destroyType(EquipmentType $type): RedirectResponse
    {
        // Se borran en cascada sus subtipos y, con ellos, sus modelos.
        $type->delete();

        return $this->volver('Tipo de equipo eliminado correctamente.');
    }

    // ===================== Subtipos =====================

    public function storeSubtype(Request $request): RedirectResponse
    {
        $data = $request->validate(CatalogoDeEquipo::REGLAS_SUBTIPO);

        $this->catalogo->crearSubtipo($data);

        return $this->volver('Subtipo guardado correctamente.');
    }

    public function updateSubtype(Request $request, Subtype $subtype): RedirectResponse
    {
        $data = $request->validate(CatalogoDeEquipo::REGLAS_SUBTIPO);

        $this->catalogo->actualizarSubtipo($subtype, $data);

        return $this->volver('Subtipo actualizado correctamente.');
    }

    public function destroySubtype(Subtype $subtype): RedirectResponse
    {
        $subtype->delete();

        return $this->volver('Subtipo eliminado correctamente.');
    }

    // ===================== Marcas =====================
    // Una marca es global, pero se declara en que subtipo se ofrece.

    public function storeBrand(Request $request): RedirectResponse
    {
        $data = $request->validate(CatalogoDeEquipo::REGLAS_MARCA);

        $this->catalogo->crearMarca($data['subtype_id'], $data['name']);

        return $this->volver('Marca guardada correctamente.');
    }

    /** Renombra la marca en todo el sistema, o la mueve de subtipo. */
    public function updateBrand(Request $request, Brand $brand): RedirectResponse
    {
        $data = $request->validate(CatalogoDeEquipo::reglasActualizarMarca($brand), CatalogoDeEquipo::MENSAJES_ACTUALIZAR_MARCA);

        $this->catalogo->actualizarMarca($brand, $data['name'], $data['subtype_id'], $data['subtype_anterior']);

        return $this->volver('Marca actualizada correctamente.');
    }

    /** Quita la marca de un subtipo; sigue existiendo en los demas. */
    public function destroyBrand(Request $request, Brand $brand): RedirectResponse
    {
        $data = $request->validate(CatalogoDeEquipo::REGLAS_QUITAR_MARCA);

        $this->catalogo->quitarMarca($brand, $data['subtype_id']);

        return $this->volver('Marca eliminada correctamente.');
    }

    // ===================== Modelos =====================

    public function storeModel(Request $request): RedirectResponse
    {
        $data = $request->validate(CatalogoDeEquipo::REGLAS_MODELO);

        $this->catalogo->crearModelo($data);

        return $this->volver('Modelo guardado correctamente.');
    }

    public function updateModel(Request $request, EquipmentModel $model): RedirectResponse
    {
        $data = $request->validate(CatalogoDeEquipo::REGLAS_MODELO);

        $this->catalogo->actualizarModelo($model, $data);

        return $this->volver('Modelo actualizado correctamente.');
    }

    public function destroyModel(EquipmentModel $model): RedirectResponse
    {
        $model->delete();

        return $this->volver('Modelo eliminado correctamente.');
    }

    // ===================== Cascada para los formularios =====================

    /** Subtipos de un tipo. */
    public function subtypes(Request $request): JsonResponse
    {
        return response()->json(
            Subtype::where('equipment_type_id', $request->integer('equipment_type_id'))
                ->orderBy('name')
                ->get(['id', 'name'])
        );
    }

    /** Marcas ofrecidas en un subtipo. */
    public function brands(Request $request): JsonResponse
    {
        $subtype = Subtype::find($request->integer('subtype_id'));

        if (! $subtype) {
            return response()->json([]);
        }

        return response()->json(
            $subtype->brands()->orderBy('name')->get(['brands.id', 'brands.name'])
        );
    }

    /** Modelos de una marca dentro de un subtipo. */
    public function models(Request $request): JsonResponse
    {
        return response()->json(
            EquipmentModel::where('brand_id', $request->integer('brand_id'))
                ->where('subtype_id', $request->integer('subtype_id'))
                ->orderBy('name')
                ->get(['id', 'name'])
        );
    }

    private function volver(string $mensaje): RedirectResponse
    {
        return redirect()->route('configuracion.catalogos.index')->with('status', $mensaje);
    }
}
