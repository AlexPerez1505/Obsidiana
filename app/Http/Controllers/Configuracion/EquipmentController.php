<?php

namespace App\Http\Controllers\Configuracion;

use App\Http\Controllers\Controller;

use App\Models\Brand;
use App\Models\Equipment;
use App\Models\EquipmentModel;
use App\Models\EquipmentType;
use App\Models\Subtype;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EquipmentController extends Controller
{
    public function index(): View
    {
        $equipmentTypes = EquipmentType::with('subtypes')->latest()->paginate(10);

        $equipmentTypes->getCollection()->transform(function (EquipmentType $type) {
            $type->subtypes_names = $type->subtypes->pluck('name')->sort()->values();

            $type->brands_names = Brand::whereHas('equipment', function ($query) use ($type) {
                $query->where('equipment_type_id', $type->id);
            })->orderBy('name')->pluck('name');

            $type->models_names = EquipmentModel::whereHas('equipment', function ($query) use ($type) {
                $query->where('equipment_type_id', $type->id);
            })->orderBy('name')->pluck('name');

            return $type;
        });

        return view('structure.Configuracion.tipos_equipo.menu_tipo_equipo', [
            'equipmentTypes' => $equipmentTypes,
            'totalTypes' => EquipmentType::count(),
            'totalSubtypes' => Subtype::count(),
            'totalBrands' => Brand::count(),
            'totalModels' => EquipmentModel::count(),
        ]);
    }

    public function create(): View
    {
        return view('structure.Configuracion.tipos_equipo.c_tipo_equipo', [
            'equipmentTypes' => EquipmentType::orderBy('name')->get(),
            'subtypes' => Subtype::orderBy('name')->get(),
            'brands' => Brand::orderBy('name')->get(),
            'equipmentModels' => EquipmentModel::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'equipment_type_name' => ['required', 'string', 'max:255'],
            'subtype_name' => ['required', 'string', 'max:255'],
            'brand_name' => ['required', 'string', 'max:255'],
            'equipment_model_name' => ['required', 'string', 'max:255'],
            'type_description' => ['nullable', 'string', 'max:120'],
            'subtype_description' => ['nullable', 'string', 'max:120'],
            'brand_description' => ['nullable', 'string', 'max:120'],
            'model_description' => ['nullable', 'string', 'max:120'],
        ]);

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

        Equipment::create([
            'equipment_type_id' => $equipmentType->id,
            'subtype_id' => $subtype->id,
            'brand_id' => $brand->id,
            'equipment_model_id' => $model->id,
            'type_description' => $data['type_description'] ?? null,
            'subtype_description' => $data['subtype_description'] ?? null,
            'brand_description' => $data['brand_description'] ?? null,
            'model_description' => $data['model_description'] ?? null,
        ]);

        return redirect()->route('configuracion.tipos_equipo.index')
            ->with('status', 'Equipo guardado correctamente.');
    }

    public function subtypes(Request $request): JsonResponse
    {
        $type = EquipmentType::where('name', $request->input('equipment_type_name'))->first();

        if (! $type) {
            return response()->json([]);
        }

        return response()->json(
            $type->subtypes()->orderBy('name')->get(['id', 'name'])
        );
    }

    public function models(Request $request): JsonResponse
    {
        $brand = Brand::where('name', $request->input('brand_name'))->first();

        if (! $brand) {
            return response()->json([]);
        }

        return response()->json(
            $brand->equipmentModels()->orderBy('name')->get(['id', 'name'])
        );
    }
}
