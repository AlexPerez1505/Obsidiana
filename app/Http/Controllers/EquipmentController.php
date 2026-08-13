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
            $term = "%{$search}%";
            $query->where(function ($q) use ($term) {
                $q->where('code', 'like', $term)
                    ->orWhere('name', 'like', $term)
                    ->orWhere('serial_number', 'like', $term)
                    ->orWhere('base_serial', 'like', $term)
                    ->orWhereHas('equipmentType', fn ($b) => $b->where('name', 'like', $term))
                    ->orWhereHas('brand', fn ($b) => $b->where('name', 'like', $term))
                    ->orWhereHas('equipmentModel', fn ($b) => $b->where('name', 'like', $term));
            });
        }

        if ($tipo = $request->get('tipo')) {
            $query->whereHas('equipmentType', fn ($q) => $q->where('name', $tipo));
        }

        if ($marca = $request->get('marca')) {
            $query->whereHas('brand', fn ($q) => $q->where('name', $marca));
        }

        if ($estado = $request->get('estado')) {
            $query->where('status', $estado);
        }

        return view('structure.gestion_Inventario.equipos.menu_equipos', [
            'equipmentList' => $query->get(),
            'tipos' => EquipmentType::orderBy('name')->pluck('name'),
            'marcas' => Brand::orderBy('name')->pluck('name'),
            'estados' => Equipment::select('status')->distinct()->orderBy('status')->pluck('status')->filter()->values(),
            'filters' => $request->only('search', 'tipo', 'marca', 'estado'),
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
        $data['name'] = $this->generateName($data);
        $data['base_serial'] = $this->generateBaseSerial($request);
        $data['registered_by'] = auth()->user()->name ?? 'Sistema';
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
        $data['name'] = $this->generateName($data);
        $data['base_serial'] = $this->generateBaseSerial($request);
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
    public function destroy(Request $request, Equipment $equipo): RedirectResponse
    {
        if ($request->input('pin') !== '123456') {
            return back()->with('error', 'PIN incorrecto. El equipo no se elimino.');
        }

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
            'acquisition_date' => $equipo->acquisition_date?->format('Y-m-d') ?? '',
            'registered_by' => $equipo->registered_by ?? '',
            'observations' => $equipo->observations ?? '',
            'base_serial' => $equipo->base_serial ?? '',
            'image_path' => $equipo->image_path,
            'thumb' => $equipo->thumb ?? 'tower',
        ];
    }

    private function nextCode(): string
    {
        $last = Equipment::withTrashed()->max('id') ?? 0;

        return sprintf('EQP-%04d', $last + 1);
    }

    private function generateBaseSerial(Request $request): string
    {
        $type = $request->input('category', '');
        $brand = $request->input('brand', '');
        $model = $request->input('model', '');
        $serial = $request->input('serial_number', '');

        $clean = fn ($value) => substr(preg_replace('/[^A-Za-z0-9]/', '', $value), 0, 4);

        return strtoupper($clean($type) . '-' . $clean($brand) . '-' . $clean($model) . '-' . $clean($serial));
    }

    private function generateName(array $data): string
    {
        $type = $data['category'] ?? '';
        $brand = $data['brand'] ?? '';
        $model = $data['model'] ?? '';

        $name = trim("{$type} {$brand} {$model}");

        return $name ?: 'Equipo sin nombre';
    }

    public function download(Equipment $equipo)
    {
        $content = "Equipo: {$equipo->name}\n";
        $content .= "Codigo: {$equipo->code}\n";
        $content .= "Serie: {$equipo->serial_number}\n";
        $content .= "Serie base: {$equipo->base_serial}\n";
        $content .= "Estado: {$equipo->status}\n";
        $content .= "Fecha de adquisicion: " . ($equipo->acquisition_date?->format('d/m/Y') ?? '') . "\n";
        $content .= "Registrado por: {$equipo->registered_by}\n";

        $filename = 'equipo-' . $equipo->code . '.txt';

        return response($content, 200, [
            'Content-Type' => 'text/plain; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    private function validated(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'name' => ['nullable', 'string', 'max:150'],
            'category' => ['required', 'string', 'max:150'],
            'subcategory' => ['nullable', 'string', 'max:150'],
            'brand' => ['required', 'string', 'max:150'],
            'model' => ['required', 'string', 'max:150'],
            'serial_number' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:1000'],
            'acquisition_date' => ['nullable', 'date'],
            'observations' => ['nullable', 'string', 'max:2000'],
            'equipment_image' => ['nullable', 'image', 'max:5120'],
        ]);
    }
}
