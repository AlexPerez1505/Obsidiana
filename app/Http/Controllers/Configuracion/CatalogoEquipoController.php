<?php

namespace App\Http\Controllers\Configuracion;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\EquipmentModel;
use App\Models\EquipmentType;
use App\Models\Subtype;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Catalogo de equipo: tipo -> subtipo -> marca -> modelo.
 *
 * Todo se administra desde Configuracion > Catalogos, sin tocar codigo.
 * Cada accion regresa al listado con un mensaje que el layout muestra
 * como toast.
 */
class CatalogoEquipoController extends Controller
{
    // ===================== Tipos de equipo =====================

    public function storeType(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('equipment_types', 'name')],
        ], ['name.unique' => 'Ya existe un tipo de equipo con ese nombre.']);

        EquipmentType::create($data);

        return $this->volver('Tipo de equipo guardado correctamente.');
    }

    public function updateType(Request $request, EquipmentType $type): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('equipment_types', 'name')->ignore($type->id)],
        ], ['name.unique' => 'Ya existe un tipo de equipo con ese nombre.']);

        $type->update($data);

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
        $data = $request->validate([
            'equipment_type_id' => ['required', 'exists:equipment_types,id'],
            'name' => ['required', 'string', 'max:255'],
        ]);

        if ($this->subtipoRepetido($data['equipment_type_id'], $data['name'])) {
            return back()->withInput()->withErrors(['name' => 'Ese subtipo ya existe dentro del tipo elegido.']);
        }

        Subtype::create($data);

        return $this->volver('Subtipo guardado correctamente.');
    }

    public function updateSubtype(Request $request, Subtype $subtype): RedirectResponse
    {
        $data = $request->validate([
            'equipment_type_id' => ['required', 'exists:equipment_types,id'],
            'name' => ['required', 'string', 'max:255'],
        ]);

        if ($this->subtipoRepetido($data['equipment_type_id'], $data['name'], $subtype->id)) {
            return back()->withInput()->withErrors(['name' => 'Ese subtipo ya existe dentro del tipo elegido.']);
        }

        $subtype->update($data);

        return $this->volver('Subtipo actualizado correctamente.');
    }

    public function destroySubtype(Subtype $subtype): RedirectResponse
    {
        $subtype->delete();

        return $this->volver('Subtipo eliminado correctamente.');
    }

    private function subtipoRepetido(int|string $typeId, string $name, ?int $ignorar = null): bool
    {
        return Subtype::where('equipment_type_id', $typeId)
            ->where('name', $name)
            ->when($ignorar, fn ($q) => $q->where('id', '!=', $ignorar))
            ->exists();
    }

    // ===================== Marcas =====================
    // Una marca es global, pero se declara en que subtipo se ofrece.

    public function storeBrand(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'subtype_id' => ['required', 'exists:subtypes,id'],
            'name' => ['required', 'string', 'max:255'],
        ]);

        $brand = Brand::firstOrCreate(['name' => $data['name']]);
        $subtype = Subtype::findOrFail($data['subtype_id']);

        if ($subtype->brands()->where('brands.id', $brand->id)->exists()) {
            return back()->withInput()->withErrors(['name' => 'Esa marca ya está dada de alta en ese subtipo.']);
        }

        $subtype->brands()->attach($brand->id);

        return $this->volver('Marca guardada correctamente.');
    }

    /** Renombra la marca en todo el sistema, o la mueve de subtipo. */
    public function updateBrand(Request $request, Brand $brand): RedirectResponse
    {
        $data = $request->validate([
            'subtype_id' => ['required', 'exists:subtypes,id'],
            'subtype_anterior' => ['required', 'exists:subtypes,id'],
            'name' => ['required', 'string', 'max:255', Rule::unique('brands', 'name')->ignore($brand->id)],
        ], ['name.unique' => 'Ya existe otra marca con ese nombre.']);

        $brand->update(['name' => $data['name']]);

        if ((int) $data['subtype_id'] !== (int) $data['subtype_anterior']) {
            $brand->subtypes()->detach($data['subtype_anterior']);
            $brand->subtypes()->syncWithoutDetaching([$data['subtype_id']]);

            // Sus modelos se mudan con ella.
            EquipmentModel::where('brand_id', $brand->id)
                ->where('subtype_id', $data['subtype_anterior'])
                ->update(['subtype_id' => $data['subtype_id']]);
        }

        return $this->volver('Marca actualizada correctamente.');
    }

    /** Quita la marca de un subtipo; sigue existiendo en los demas. */
    public function destroyBrand(Request $request, Brand $brand): RedirectResponse
    {
        $data = $request->validate([
            'subtype_id' => ['required', 'exists:subtypes,id'],
        ]);

        $brand->subtypes()->detach($data['subtype_id']);

        EquipmentModel::where('brand_id', $brand->id)
            ->where('subtype_id', $data['subtype_id'])
            ->delete();

        // Si ya no se ofrece en ningun subtipo ni tiene modelos, se retira del catalogo.
        if ($brand->subtypes()->count() === 0 && $brand->equipmentModels()->count() === 0) {
            $brand->delete();
        }

        return $this->volver('Marca eliminada correctamente.');
    }

    // ===================== Modelos =====================

    public function storeModel(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'subtype_id' => ['required', 'exists:subtypes,id'],
            'brand_id' => ['required', 'exists:brands,id'],
            'name' => ['required', 'string', 'max:255'],
        ]);

        if ($this->modeloRepetido($data)) {
            return back()->withInput()->withErrors(['name' => 'Ese modelo ya existe para esa marca y subtipo.']);
        }

        // Alta del modelo implica que la marca se ofrece en ese subtipo.
        Subtype::find($data['subtype_id'])?->brands()->syncWithoutDetaching([$data['brand_id']]);

        EquipmentModel::create($data);

        return $this->volver('Modelo guardado correctamente.');
    }

    public function updateModel(Request $request, EquipmentModel $model): RedirectResponse
    {
        $data = $request->validate([
            'subtype_id' => ['required', 'exists:subtypes,id'],
            'brand_id' => ['required', 'exists:brands,id'],
            'name' => ['required', 'string', 'max:255'],
        ]);

        if ($this->modeloRepetido($data, $model->id)) {
            return back()->withInput()->withErrors(['name' => 'Ese modelo ya existe para esa marca y subtipo.']);
        }

        Subtype::find($data['subtype_id'])?->brands()->syncWithoutDetaching([$data['brand_id']]);

        $model->update($data);

        return $this->volver('Modelo actualizado correctamente.');
    }

    public function destroyModel(EquipmentModel $model): RedirectResponse
    {
        $model->delete();

        return $this->volver('Modelo eliminado correctamente.');
    }

    private function modeloRepetido(array $data, ?int $ignorar = null): bool
    {
        return EquipmentModel::where('brand_id', $data['brand_id'])
            ->where('subtype_id', $data['subtype_id'])
            ->where('name', $data['name'])
            ->when($ignorar, fn ($q) => $q->where('id', '!=', $ignorar))
            ->exists();
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
