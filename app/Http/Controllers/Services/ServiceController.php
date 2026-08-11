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

        if ($request->ajax()) {
            return response()->json([
                'id' => $service->id,
                'service_number' => $service->service_number,
                'qr_token' => $service->qr_token,
                'qr_url' => $service->qr_token ? route('qr.show', $service->qr_token) : null,
                'show_url' => route('gestion.servicios.historial.show', $service),
                'approvals_url' => route('service-tracking.approvals'),
                'menu_url' => route('gestion.servicios.historial'),
            ]);
        }

        return redirect()->route('gestion.servicios.historial.show', $service)
            ->with('success', "Servicio {$service->service_number} creado.");
    }

    public function show(Service $service)
    {
        // Cargar todas las relaciones necesarias
        $service->load([
            'customer',
            'serviceEquipment',
            'serviceTrackings' => function ($query) {
                $query->with('serviceStep')->orderBy('created_at');
            },
            'currentStep',
            'internalTechnician',
            'externalTechnician'
        ]);

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

        $generateQr = $request->boolean('generate_qr');

        // Prevenir duplicados: verificar si ya existe un servicio reciente para este cliente
        $recentService = Service::where('customer_id', $validated['customer_id'])
            ->where('created_at', '>=', now()->subSeconds(5))
            ->first();

        if ($recentService) {
            if ($generateQr && ! $recentService->qr_token) {
                $token = $this->generateQrToken();
                $recentService->update(['qr_token' => $token, 'qr_expires_at' => now()->addDay()]);
                $firstTracking = ServiceTracking::where('service_id', $recentService->id)
                    ->where('service_step_id', $step?->id)
                    ->first();
                if ($firstTracking) {
                    $firstTracking->update(['qr_token' => $token, 'qr_expires_at' => now()->addDay()]);
                }
            }
            return $recentService;
        }

        $service = Service::create([
            'service_number' => null,
            'customer_id' => $validated['customer_id'],
            'service_type' => $serviceType,
            'internal_technician_id' => $serviceType === 'interno' ? ($validated['internal_technician_id'] ?? null) : null,
            'external_technician_id' => $serviceType === 'externo' ? ($validated['external_technician_id'] ?? null) : null,
            'registered_by' => $registeredBy,
            'current_step_id' => $step?->id,
            'qr_token' => $generateQr ? $this->generateQrToken() : null,
            'qr_expires_at' => $generateQr ? now()->addDay() : null,
            'signature' => $validated['firma'] ?? null,
            'status' => 'registrado',
            'started_at' => now(),
        ]);

        $service->update(['service_number' => 'NS-' . $service->id]);

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

        // Crear trackings para todos los pasos iniciales hasta el primer paso que requiere aprobación
        $allSteps = ServiceStep::where('service_type', $serviceType)
            ->orderBy('order')
            ->get();
        
        $firstApprovalStep = $allSteps->firstWhere('requires_approval', true);
        $stepsToCreate = $allSteps->filter(function ($s) use ($firstApprovalStep) {
            // Crear pasos hasta e incluyendo el primer paso que requiere aprobación
            return !$firstApprovalStep || $s->order <= $firstApprovalStep->order;
        });
        
        foreach ($stepsToCreate as $stepToCreate) {
            $qrToken = null;
            $qrExpires = null;
            
            // Solo el primer paso tiene QR si se generó
            if ($stepToCreate->id === $step?->id) {
                $qrToken = $service->qr_token;
                $qrExpires = $service->qr_expires_at;
            }
            
            ServiceTracking::create([
                'service_id' => $service->id,
                'service_step_id' => $stepToCreate->id,
                'status' => 'pendiente',
                'qr_token' => $qrToken,
                'qr_expires_at' => $qrExpires,
                'started_at' => now(),
                'notes' => $stepToCreate->requires_approval ? 'Pendiente de aprobación del administrador' : null,
            ]);
        }

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

    public function approveStep($id)
    {
        abort_unless(auth()->user()?->isAdmin(), 403);

        $tracking = ServiceTracking::findOrFail($id);

        if (! in_array($tracking->status, ['pendiente', 'rechazado'])) {
            return back()->with('error', 'El paso no puede aprobarse en su estado actual.');
        }

        try {
            // Actualizar el tracking con status completado
            $tracking->status = 'completado';
            $tracking->finished_at = now();
            $tracking->performed_by = auth()->id();
            $tracking->save();

            $service = $tracking->service;
            $currentOrder = $tracking->serviceStep->order;

            $nextStep = ServiceStep::where('service_type', $service->service_type)
                ->where('order', '>', $currentOrder)
                ->orderBy('order')
                ->first();

            if ($nextStep) {
                $newToken = $nextStep->requires_qr ? $this->generateQrToken() : null;

                ServiceTracking::create([
                    'service_id' => $service->id,
                    'service_step_id' => $nextStep->id,
                    'status' => 'pendiente',
                    'qr_token' => $newToken,
                    'qr_expires_at' => $nextStep->requires_qr ? now()->addDay() : null,
                    'started_at' => now(),
                ]);

                $service->update([
                    'current_step_id' => $nextStep->id,
                    'qr_token' => $newToken,
                    'qr_expires_at' => $nextStep->requires_qr ? now()->addDay() : null,
                    'status' => 'en_progreso',
                ]);
            } else {
                $service->update([
                    'current_step_id' => null,
                    'qr_token' => null,
                    'qr_expires_at' => null,
                    'status' => 'entregado',
                    'finished_at' => now(),
                ]);
            }

            return redirect()->route('gestion.servicios.historial.show', $service)->with('success', 'Paso aprobado y avanzado.');
        } catch (\Exception $e) {
            \Log::error('Error approving step', ['error' => $e->getMessage(), 'tracking_id' => $id]);
            return back()->with('error', 'Error al aprobar el paso: ' . $e->getMessage());
        }
    }

    public function pendingApprovals()
    {
        abort_unless(auth()->user()?->isAdmin(), 403);

        $approvals = ServiceTracking::with(['service.customer', 'serviceStep'])
            ->whereIn('status', ['pendiente', 'rechazado'])
            ->whereHas('serviceStep', fn ($q) => $q->where('requires_approval', true))
            ->orderByDesc('created_at')
            ->get();

        return view('structure.gestion_servicios.historial_servicios.admin_approve.menu_aprovaciones_admin', compact('approvals'));
    }

    public function approvedMaintenance()
    {
        $approved = ServiceTracking::with(['service.customer', 'serviceStep'])
            ->where('status', 'completado')
            ->whereHas('serviceStep', fn ($q) => $q->where('requires_approval', true))
            ->orderByDesc('finished_at')
            ->get();

        return view('structure.gestion_servicios.mantenimineto.menu_mantenimiento', compact('approved'));
    }

    public function rejectStep($id)
    {
        abort_unless(auth()->user()?->isAdmin(), 403);

        $tracking = ServiceTracking::findOrFail($id);

        try {
            $tracking->status = 'rechazado';
            $tracking->finished_at = now();
            $tracking->performed_by = auth()->id();
            $tracking->save();

            return redirect()->route('gestion.servicios.historial.show', $tracking->service)->with('error', 'Paso rechazado.');
        } catch (\Exception $e) {
            \Log::error('Error rejecting step', ['error' => $e->getMessage(), 'tracking_id' => $id]);
            return back()->with('error', 'Error al rechazar el paso: ' . $e->getMessage());
        }
    }

    public function deliver(Service $service)
    {
        $service->update([
            'status' => 'entregado',
            'finished_at' => now(),
            'current_step_id' => null,
            'qr_token' => null,
            'qr_expires_at' => null,
        ]);

        return redirect()->route('service-tracking.maintenance')
            ->with('success', "Servicio {$service->service_number} marcado como entregado.");
    }
}
