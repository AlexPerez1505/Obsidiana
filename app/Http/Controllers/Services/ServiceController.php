<?php

namespace App\Http\Controllers\Services;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Customer;
use App\Models\EquipmentModel;
use App\Models\EquipmentType;
use App\Models\ExternalTechnician;
use App\Models\Subtype;
use App\Models\Service;
use App\Models\ServiceEquipment;
use App\Models\ServiceInvitation;
use App\Models\ServiceStep;
use App\Models\ServiceTracking;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ServiceController extends Controller
{
    public function createFromInvitation(ServiceInvitation $invitation)
    {
        if (!$invitation->isValid()) {
            abort(403, 'La invitación no es válida o ya fue usada.');
        }

        $customers = Customer::with('asesor')->latest()->get();
        $equipmentTypes = EquipmentType::orderBy('name')->pluck('name')
            ->map(fn ($name) => (object) ['name' => $name]);
        $brands = Brand::orderBy('name')->pluck('name')
            ->map(fn ($name) => (object) ['name' => $name]);
        $externalTechnicians = \Illuminate\Support\Facades\DB::table('tecnico_externo')
            ->selectRaw("id, CONCAT(nombre, ' ', apellidos) as name, telefono as phone, correo as email, especialidad as specialty, empresa as company, domicilio as location, NULL as photo")
            ->whereNull('deleted_at')
            ->orderBy('nombre')
            ->get();
        $internalTechnicians = User::where('status', User::STATUS_APPROVED)->orderBy('name')->get();

        return view('structure.gestion_servicios.historial_servicios.registro_servicio.c_registro_serv', compact('customers', 'equipmentTypes', 'brands', 'externalTechnicians', 'internalTechnicians', 'invitation'));
    }

    public function publicStore(Request $request, ServiceInvitation $invitation)
    {
        if (!$invitation->isValid()) {
            return back()->with('error', 'La invitación no es válida o ya fue usada.');
        }

        $service = $this->persistService($request, $invitation->invited_by);

        $invitation->update([
            'status' => 'used',
            'used_at' => now(),
        ]);

        return redirect()->route('gestion.servicios.historial.show', $service)
            ->with('success', "Servicio {$service->service_number} creado.");
    }

    public function store(Request $request)
    {
        $service = $this->persistService($request, auth()->id());

        return redirect()->route('gestion.servicios.historial.show', $service)
            ->with('success', "Servicio {$service->service_number} creado.");
    }

    public function show(Service $service)
    {
        $service->load(['customer', 'serviceEquipment', 'serviceTrackings.serviceStep', 'currentStep', 'internalTechnician', 'externalTechnician']);

        return view('structure.gestion_servicios.historial_servicios.show', compact('service'));
    }

    public function invite()
    {
        $invitation = ServiceInvitation::create([
            'token' => $this->generateInvitationToken(),
            'invited_by' => auth()->id(),
            'expires_at' => now()->addDay(),
        ]);

        return response()->json([
            'link' => route('public.nueva_orden', $invitation),
            'token' => $invitation->token,
            'expires_at' => $invitation->expires_at,
        ]);
    }

    private function persistService(Request $request, int $registeredBy): Service
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:clientes,id',
            'mantenimiento_externo' => 'nullable|in:0,1',
            'mantenimiento_interno' => 'nullable|in:0,1',
            'internal_technician_id' => 'nullable|exists:users,id',
            'external_technician_id' => 'nullable|exists:tecnico_externo,id',
            'firma' => 'nullable|string',
            'tipo_equipo' => 'nullable|string|max:255',
            'subtipo' => 'nullable|string|max:255',
            'marca' => 'nullable|string|max:255',
            'modelo' => 'nullable|string|max:255',
            'serie' => 'nullable|string|max:255',
            'descripcion_equipo' => 'nullable|string',
            'observaciones' => 'nullable|string',
            'evidencia_1' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:10240',
            'evidencia_2' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:10240',
            'evidencia_3' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:10240',
            'evidencia_video' => 'nullable|mimetypes:video/mp4,video/quicktime,video/x-m4v|max:10240',
        ]);

        if ($request->input('mantenimiento_externo')) {
            $serviceType = 'externo';
        } elseif ($request->input('mantenimiento_interno')) {
            $serviceType = 'interno';
        } else {
            abort(422, 'Selecciona el tipo de servicio.');
        }

        $step = ServiceStep::where('service_type', $serviceType)
            ->orderBy('order')
            ->first();

        $service = Service::create([
            'service_number' => null,
            'customer_id' => $validated['customer_id'],
            'service_type' => $serviceType,
            'internal_technician_id' => $serviceType === 'interno' ? ($validated['internal_technician_id'] ?? null) : null,
            'external_technician_id' => $serviceType === 'externo' ? ($validated['external_technician_id'] ?? null) : null,
            'registered_by' => $registeredBy,
            'current_step_id' => $step?->id,
            'qr_token' => $this->generateQrToken(),
            'qr_expires_at' => now()->addDay(),
            'signature' => $validated['firma'] ?? null,
            'status' => 'registrado',
            'started_at' => now(),
        ]);

        $service->update(['service_number' => 'OS-' . $service->id]);

        $this->registerCatalogNames($validated);

        $serviceEquipment = ServiceEquipment::create([
            'service_id' => $service->id,
            'product_code' => null,
            'type_text' => $validated['tipo_equipo'] ?? null,
            'subtype_text' => $validated['subtipo'] ?? null,
            'brand_text' => $validated['marca'] ?? null,
            'model_text' => $validated['modelo'] ?? null,
            'serial_number' => $validated['serie'] ?? null,
            'description' => $validated['descripcion_equipo'] ?? null,
            'observations' => $validated['observaciones'] ?? null,
            'evidence_1_path' => $this->storeEvidence($request, 'evidencia_1'),
            'evidence_2_path' => $this->storeEvidence($request, 'evidencia_2'),
            'evidence_3_path' => $this->storeEvidence($request, 'evidencia_3'),
            'video_path' => $this->storeEvidence($request, 'evidencia_video'),
        ]);

        $serviceEquipment->update(['product_code' => 'PRD-' . $serviceEquipment->id]);

        ServiceTracking::create([
            'service_id' => $service->id,
            'service_step_id' => $step?->id,
            'status' => 'pendiente',
            'qr_token' => $service->qr_token,
            'qr_expires_at' => $service->qr_expires_at,
            'started_at' => now(),
        ]);

        return $service;
    }

    /**
     * Registra en los catalogos unificados los nombres de tipo/subtipo/marca/modelo
     * escritos en la orden, para que queden disponibles en inventario y servicios.
     */
    private function registerCatalogNames(array $validated): void
    {
        if ($typeName = trim((string) ($validated['tipo_equipo'] ?? ''))) {
            $type = EquipmentType::firstOrCreate(['name' => $typeName]);

            if ($subtypeName = trim((string) ($validated['subtipo'] ?? ''))) {
                Subtype::firstOrCreate([
                    'equipment_type_id' => $type->id,
                    'name' => $subtypeName,
                ]);
            }
        }

        if ($brandName = trim((string) ($validated['marca'] ?? ''))) {
            $brand = Brand::firstOrCreate(['name' => $brandName]);

            if ($modelName = trim((string) ($validated['modelo'] ?? ''))) {
                EquipmentModel::firstOrCreate([
                    'brand_id' => $brand->id,
                    'name' => $modelName,
                ]);
            }
        }
    }

    private function storeEvidence(Request $request, string $field): ?string
    {
        if (!$request->hasFile($field)) {
            return null;
        }

        return Storage::disk('public')->putFile('evidencias', $request->file($field));
    }

    private function generateQrToken(): string
    {
        do {
            $token = Str::random(32);
        } while (Service::where('qr_token', $token)->exists() || ServiceTracking::where('qr_token', $token)->exists());

        return $token;
    }

    private function generateInvitationToken(): string
    {
        do {
            $token = Str::random(32);
        } while (ServiceInvitation::where('token', $token)->exists());

        return $token;
    }
}
