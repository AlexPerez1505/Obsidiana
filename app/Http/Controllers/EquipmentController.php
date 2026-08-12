<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Equipment;
use App\Models\EquipmentModel;
use App\Models\EquipmentType;
use App\Models\Subtype;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class EquipmentController extends Controller
{
    /**
     * Lista los equipos del inventario.
     */
    public function index(Request $request): View
    {
        $query = Equipment::query()
            ->with(['equipmentType', 'subtype', 'brand', 'equipmentModel'])
            ->latest();

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('serial_number', 'like', "%{$search}%")
                    ->orWhereHas('brand', fn ($b) => $b->where('name', 'like', "%{$search}%"));
            });
        }

        return view('structure.gestion_Inventario.equipos.menu_equipos', [
            'equipmentList' => $query->get(),
            'filters' => $request->only('search'),
        ]);
    }

    /**
     * Muestra el formulario de creación de equipo.
     */
    public function create(): View
    {
        return view('structure.gestion_Inventario.equipos.c_equipos', [
            'catalogs' => $this->catalogs(),
        ]);
    }

    /**
     * Guarda un nuevo equipo.
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data = $this->resolveCatalogs($request, $data);

        if (empty($data['code'])) {
            $data['code'] = $this->nextCode();
        }

        if ($request->hasFile('equipment_image')) {
            $data['image_path'] = $request->file('equipment_image')->store('equipos', 'public');
        }

        Equipment::create($data);

        return redirect()->route('inventory.equipos.index')->with('status', 'Equipo guardado correctamente.');
    }

    /**
     * Muestra el detalle de un equipo.
     */
    public function show(Equipment $equipo): View
    {
        return view('structure.gestion_Inventario.equipos.detalle_equipo', [
            'equipment' => $this->toFormData($equipo),
        ]);
    }

    /**
     * Muestra el formulario de edición de equipo.
     */
    public function edit(Equipment $equipo): View
    {
        return view('structure.gestion_Inventario.equipos.c_equipos', [
            'mode' => 'edit',
            'equipment' => $this->toFormData($equipo),
            'catalogs' => $this->catalogs(),
        ]);
    }

    /**
     * Actualiza un equipo existente.
     */
    public function update(Request $request, Equipment $equipo): RedirectResponse
    {
        $data = $this->validated($request, $equipo->id);
        $data = $this->resolveCatalogs($request, $data);

        if ($request->hasFile('equipment_image')) {
            if ($equipo->image_path) {
                Storage::disk('public')->delete($equipo->image_path);
            }
            $data['image_path'] = $request->file('equipment_image')->store('equipos', 'public');
        }

        $equipo->update($data);

        return redirect()->route('inventory.equipos.index')->with('status', 'Equipo actualizado correctamente.');
    }

    /**
     * Elimina un equipo.
     */
    public function destroy(Equipment $equipo): RedirectResponse
    {
        $equipo->delete();

        return redirect()->route('inventory.equipos.index')->with('status', 'Equipo eliminado correctamente.');
    }

    /**
     * Catalogos tipo => subtipos y marca => modelos para los selects en cascada.
     */
    private function catalogs(): array
    {
        return [
            'types' => EquipmentType::with('subtypes:id,equipment_type_id,name')
                ->orderBy('name')
                ->get(['id', 'name'])
                ->mapWithKeys(fn ($type) => [$type->name => $type->subtypes->pluck('name')->sort()->values()])
                ->toArray(),
            'brands' => Brand::with('equipmentModels:id,brand_id,name')
                ->orderBy('name')
                ->get(['id', 'name'])
                ->mapWithKeys(fn ($brand) => [$brand->name => $brand->equipmentModels->pluck('name')->sort()->values()])
                ->toArray(),
        ];
    }

    /**
     * Convierte los nombres de tipo/subtipo/marca/modelo en IDs, creandolos si no existen.
     */
    private function resolveCatalogs(Request $request, array $data): array
    {
        $typeId = null;
        $subtypeId = null;
        $brandId = null;
        $modelId = null;

        if ($typeName = trim((string) $request->input('category'))) {
            $type = EquipmentType::firstOrCreate(['name' => $typeName]);
            $typeId = $type->id;

            if ($subtypeName = trim((string) $request->input('subcategory'))) {
                $subtypeId = Subtype::firstOrCreate([
                    'equipment_type_id' => $type->id,
                    'name' => $subtypeName,
                ])->id;
            }
        }

        if ($brandName = trim((string) $request->input('brand'))) {
            $brand = Brand::firstOrCreate(['name' => $brandName]);
            $brandId = $brand->id;

            if ($modelName = trim((string) $request->input('model'))) {
                $modelId = EquipmentModel::firstOrCreate([
                    'brand_id' => $brand->id,
                    'name' => $modelName,
                ])->id;
            }
        }

        $data['equipment_type_id'] = $typeId;
        $data['subtype_id'] = $subtypeId;
        $data['brand_id'] = $brandId;
        $data['equipment_model_id'] = $modelId;

        unset($data['category'], $data['subcategory'], $data['brand'], $data['model']);

        return $data;
    }

    /**
     * Datos del equipo en el formato que espera la vista del formulario.
     */
    private function toFormData(Equipment $equipo): array
    {
        return [
            'id' => $equipo->id,
            'code' => $equipo->code,
            'name' => $equipo->name,
            'category' => $equipo->equipmentType?->name ?? '',
            'subcategory' => $equipo->subtype?->name ?? '',
            'brand' => $equipo->brand?->name ?? '',
            'model' => $equipo->equipmentModel?->name ?? '',
            'serial_number' => $equipo->serial_number ?? '',
            'description' => $equipo->description ?? '',
            'stock_current' => $equipo->stock_current,
            'stock_max' => $equipo->stock_max,
            'stock_min' => $equipo->stock_min,
            'warehouse' => $equipo->warehouse ?? '',
            'assigned_to' => $equipo->assigned_to ?? '',
            'department' => $equipo->department ?? '',
            'service_date' => $equipo->service_date?->format('Y-m-d') ?? '',
            'next_maintenance' => $equipo->next_maintenance?->format('Y-m-d') ?? '',
            'notes' => $equipo->notes ?? '',
            'voltage' => $equipo->voltage ?? '',
            'frequency' => $equipo->frequency ?? '',
            'power' => $equipo->power ?? '',
            'weight' => $equipo->weight ?? '',
            'dimensions' => $equipo->dimensions ?? '',
            'color' => $equipo->color ?? '',
            'technical_specs' => $equipo->technical_specs ?? '',
            'supplier' => $equipo->supplier ?? '',
            'contact' => $equipo->contact ?? '',
            'phone' => $equipo->phone ?? '',
            'email' => $equipo->email ?? '',
            'invoice_number' => $equipo->invoice_number ?? '',
            'invoice_date' => $equipo->invoice_date?->format('Y-m-d') ?? '',
            'image_path' => $equipo->image_path,
            'thumb' => $equipo->thumb ?? 'tower',
        ];
    }

    private function nextCode(): string
    {
        $last = Equipment::withTrashed()->max('id') ?? 0;

        return sprintf('EQP-%04d', $last + 1);
    }

    private function validated(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'code' => ['nullable', 'string', 'max:30', 'unique:equipment,code' . ($ignoreId ? ",{$ignoreId}" : '')],
            'name' => ['required', 'string', 'max:150'],
            'category' => ['required', 'string', 'max:150'],
            'subcategory' => ['nullable', 'string', 'max:150'],
            'brand' => ['nullable', 'string', 'max:150'],
            'model' => ['nullable', 'string', 'max:150'],
            'serial_number' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:1000'],
            'stock_current' => ['nullable', 'integer', 'min:0'],
            'stock_max' => ['nullable', 'integer', 'min:0'],
            'stock_min' => ['nullable', 'integer', 'min:0'],
            'warehouse' => ['nullable', 'string', 'max:100'],
            'assigned_to' => ['nullable', 'string', 'max:150'],
            'department' => ['nullable', 'string', 'max:100'],
            'service_date' => ['nullable', 'date'],
            'next_maintenance' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'voltage' => ['nullable', 'string', 'max:50'],
            'frequency' => ['nullable', 'string', 'max:50'],
            'power' => ['nullable', 'string', 'max:50'],
            'weight' => ['nullable', 'numeric', 'min:0'],
            'dimensions' => ['nullable', 'string', 'max:100'],
            'color' => ['nullable', 'string', 'max:50'],
            'technical_specs' => ['nullable', 'string', 'max:2000'],
            'supplier' => ['nullable', 'string', 'max:150'],
            'contact' => ['nullable', 'string', 'max:150'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:150'],
            'invoice_number' => ['nullable', 'string', 'max:100'],
            'invoice_date' => ['nullable', 'date'],
            'equipment_image' => ['nullable', 'image', 'max:5120'],
        ]);
    }
}
