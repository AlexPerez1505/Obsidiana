<?php

namespace App\Services\Almacen;

use App\Models\Brand;
use App\Models\Equipment;
use App\Models\EquipmentModel;
use App\Models\EquipmentType;
use App\Models\Subtype;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Catálogo de equipo: tipo -> subtipo -> marca -> modelo.
 *
 * Las reglas que no caben en una validación (un subtipo no se repite
 * dentro de su tipo, una marca es global pero se declara por subtipo, sus
 * modelos se mudan con ella) viven aquí para que la web y la API digan lo
 * mismo. Cuando algo choca se lanza ValidationException sobre `name`, que
 * en la web regresa al formulario y en la API es un 422.
 */
class CatalogoDeEquipo
{
    // ===================== Tipos de equipo =====================

    public static function reglasTipo(?EquipmentType $ignorar = null): array
    {
        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('equipment_types', 'name')->ignore($ignorar?->id)],
            'requiere_emplayado' => ['nullable', 'boolean'],
        ];
    }

    public const MENSAJES_TIPO = ['name.unique' => 'Ya existe un tipo de equipo con ese nombre.'];

    public function crearTipo(string $nombre, bool $requiereEmplayado): EquipmentType
    {
        return EquipmentType::create(['name' => $nombre, 'requiere_emplayado' => $requiereEmplayado]);
    }

    public function actualizarTipo(EquipmentType $type, string $nombre, bool $requiereEmplayado): void
    {
        $type->update(['name' => $nombre, 'requiere_emplayado' => $requiereEmplayado]);
    }

    // ===================== Subtipos =====================

    public const REGLAS_SUBTIPO = [
        'equipment_type_id' => ['required', 'exists:equipment_types,id'],
        'name' => ['required', 'string', 'max:255'],
    ];

    /** @throws ValidationException si ya existe dentro de ese tipo */
    public function crearSubtipo(array $data): Subtype
    {
        $this->exigirSubtipoNuevo($data['equipment_type_id'], $data['name']);

        return Subtype::create($data);
    }

    /** @throws ValidationException si ya existe dentro de ese tipo */
    public function actualizarSubtipo(Subtype $subtype, array $data): void
    {
        $this->exigirSubtipoNuevo($data['equipment_type_id'], $data['name'], $subtype->id);

        $subtype->update($data);
    }

    private function exigirSubtipoNuevo(int|string $typeId, string $name, ?int $ignorar = null): void
    {
        $repetido = Subtype::where('equipment_type_id', $typeId)
            ->where('name', $name)
            ->when($ignorar, fn ($q) => $q->where('id', '!=', $ignorar))
            ->exists();

        if ($repetido) {
            throw ValidationException::withMessages(['name' => 'Ese subtipo ya existe dentro del tipo elegido.']);
        }
    }

    // ===================== Marcas =====================
    // Una marca es global, pero se declara en qué subtipo se ofrece.

    public const REGLAS_MARCA = [
        'subtype_id' => ['required', 'exists:subtypes,id'],
        'name' => ['required', 'string', 'max:255'],
    ];

    public static function reglasActualizarMarca(Brand $brand): array
    {
        return [
            'subtype_id' => ['required', 'exists:subtypes,id'],
            'subtype_anterior' => ['required', 'exists:subtypes,id'],
            'name' => ['required', 'string', 'max:255', Rule::unique('brands', 'name')->ignore($brand->id)],
        ];
    }

    public const MENSAJES_ACTUALIZAR_MARCA = ['name.unique' => 'Ya existe otra marca con ese nombre.'];

    public const REGLAS_QUITAR_MARCA = [
        'subtype_id' => ['required', 'exists:subtypes,id'],
    ];

    /**
     * Da de alta la marca en un subtipo. Si la marca ya existe en otro
     * subtipo se reutiliza: no se duplica el nombre.
     *
     * @throws ValidationException si ya estaba en ese subtipo
     */
    public function crearMarca(int|string $subtypeId, string $nombre): Brand
    {
        $brand = Brand::firstOrCreate(['name' => $nombre]);
        $subtype = Subtype::findOrFail($subtypeId);

        if ($subtype->brands()->where('brands.id', $brand->id)->exists()) {
            throw ValidationException::withMessages(['name' => 'Esa marca ya está dada de alta en ese subtipo.']);
        }

        $subtype->brands()->attach($brand->id);

        return $brand;
    }

    /** Renombra la marca en todo el sistema, o la mueve de subtipo. */
    public function actualizarMarca(Brand $brand, string $nombre, int|string $subtypeId, int|string $subtypeAnterior): void
    {
        $brand->update(['name' => $nombre]);

        if ((int) $subtypeId !== (int) $subtypeAnterior) {
            $brand->subtypes()->detach($subtypeAnterior);
            $brand->subtypes()->syncWithoutDetaching([$subtypeId]);

            // Sus modelos se mudan con ella.
            EquipmentModel::where('brand_id', $brand->id)
                ->where('subtype_id', $subtypeAnterior)
                ->update(['subtype_id' => $subtypeId]);
        }
    }

    /**
     * Quita la marca de un subtipo (y sus modelos ahí); sigue existiendo en
     * los demás. Devuelve true si con eso la marca desapareció del catálogo.
     */
    public function quitarMarca(Brand $brand, int|string $subtypeId): bool
    {
        $brand->subtypes()->detach($subtypeId);

        EquipmentModel::where('brand_id', $brand->id)
            ->where('subtype_id', $subtypeId)
            ->delete();

        // Si ya no se ofrece en ningún subtipo ni tiene modelos, se retira del catálogo.
        if ($brand->subtypes()->count() === 0 && $brand->equipmentModels()->count() === 0) {
            $brand->delete();

            return true;
        }

        return false;
    }

    // ===================== Modelos =====================

    public const REGLAS_MODELO = [
        'subtype_id' => ['required', 'exists:subtypes,id'],
        'brand_id' => ['required', 'exists:brands,id'],
        'name' => ['required', 'string', 'max:255'],
    ];

    /** @throws ValidationException si ya existe para esa marca y subtipo */
    public function crearModelo(array $data): EquipmentModel
    {
        $this->exigirModeloNuevo($data);

        // Alta del modelo implica que la marca se ofrece en ese subtipo.
        Subtype::find($data['subtype_id'])?->brands()->syncWithoutDetaching([$data['brand_id']]);

        return EquipmentModel::create($data);
    }

    /** @throws ValidationException si ya existe para esa marca y subtipo */
    public function actualizarModelo(EquipmentModel $model, array $data): void
    {
        $this->exigirModeloNuevo($data, $model->id);

        Subtype::find($data['subtype_id'])?->brands()->syncWithoutDetaching([$data['brand_id']]);

        $model->update($data);
    }

    private function exigirModeloNuevo(array $data, ?int $ignorar = null): void
    {
        $repetido = EquipmentModel::where('brand_id', $data['brand_id'])
            ->where('subtype_id', $data['subtype_id'])
            ->where('name', $data['name'])
            ->when($ignorar, fn ($q) => $q->where('id', '!=', $ignorar))
            ->exists();

        if ($repetido) {
            throw ValidationException::withMessages(['name' => 'Ese modelo ya existe para esa marca y subtipo.']);
        }
    }

    // ===================== Alta rápida (Configuración > Tipos de equipo) =====================

    public const REGLAS_ALTA_RAPIDA = [
        'equipment_type_name' => ['required', 'string', 'max:255'],
        'subtype_name' => ['required', 'string', 'max:255'],
        'brand_name' => ['required', 'string', 'max:255'],
        'equipment_model_name' => ['required', 'string', 'max:255'],
        'type_description' => ['nullable', 'string', 'max:120'],
        'subtype_description' => ['nullable', 'string', 'max:120'],
        'brand_description' => ['nullable', 'string', 'max:120'],
        'model_description' => ['nullable', 'string', 'max:120'],
    ];

    /**
     * Da de alta un equipo escribiendo los cuatro nombres: lo que ya existe
     * se reutiliza y lo que no, se crea.
     */
    public function altaRapida(array $data): Equipment
    {
        $equipmentType = EquipmentType::firstOrCreate(
            ['name' => $data['equipment_type_name']],
            ['description' => $data['type_description'] ?? null]
        );

        $subtype = Subtype::firstOrCreate(
            ['equipment_type_id' => $equipmentType->id, 'name' => $data['subtype_name']],
            ['description' => $data['subtype_description'] ?? null]
        );

        $brand = Brand::firstOrCreate(
            ['name' => $data['brand_name']],
            ['description' => $data['brand_description'] ?? null]
        );

        $model = EquipmentModel::firstOrCreate(
            ['brand_id' => $brand->id, 'name' => $data['equipment_model_name']],
            ['description' => $data['model_description'] ?? null]
        );

        return Equipment::create([
            'equipment_type_id' => $equipmentType->id,
            'subtype_id' => $subtype->id,
            'brand_id' => $brand->id,
            'equipment_model_id' => $model->id,
            'type_description' => $data['type_description'] ?? null,
            'subtype_description' => $data['subtype_description'] ?? null,
            'brand_description' => $data['brand_description'] ?? null,
            'model_description' => $data['model_description'] ?? null,
        ]);
    }
}
