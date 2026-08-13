<?php

namespace App\Http\Controllers\Services;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Customer;
use App\Models\EquipmentModel;
use App\Models\EquipmentType;
use App\Models\ExternalTechnician;
use App\Models\Service;
use App\Models\ServiceEquipment;
use App\Models\ServiceMaintenance;
use App\Models\ServiceStep;
use App\Models\ServiceTracking;
use App\Models\Subtype;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ServiceController extends Controller
{
    public function index()
    {
        $services = Service::with(['customer', 'externalTechnician', 'serviceEquipment', 'currentStep'])
            ->where('status', 'registrado')
            ->latest()
            ->get();

        return view('structure.gestion_servicios.Historial_se.menuhistorial', compact('services'));
    }

    public function create()
    {
        return view('structure.gestion_servicios.Historial_se.tipo_m');
    }

    public function createExternal(Request $request)
    {
        $search = $request->input('search');

        $customers = Customer::query()
            ->when($search, function ($query, $search) {
                $term = '%' . $search . '%';
                $query->whereRaw("CONCAT(nombre, ' ', COALESCE(apellido, '')) LIKE ?", [$term])
                    ->orWhere('telefono', 'LIKE', $term)
                    ->orWhere('gmail', 'LIKE', $term);
            })
            ->orderBy('nombre')
            ->limit(50)
            ->get();

        return view('structure.gestion_servicios.Historial_se.registro_NS.externo.formulario.formulario_ext', [
            'customers' => $customers,
            'search' => $search,
        ]);
    }

    public function createInternal(Request $request)
    {
        $search = $request->input('search');

        $customers = Customer::query()
            ->when($search, function ($query, $search) {
                $term = '%' . $search . '%';
                $query->whereRaw("CONCAT(nombre, ' ', COALESCE(apellido, '')) LIKE ?", [$term])
                    ->orWhere('telefono', 'LIKE', $term)
                    ->orWhere('gmail', 'LIKE', $term);
            })
            ->orderBy('nombre')
            ->limit(50)
            ->get();

        return view('structure.gestion_servicios.Historial_se.registro_NS.Interno.formulario_int', [
            'customers' => $customers,
            'search' => $search,
        ]);
    }

    public function createEquipment(Request $request)
    {
        $customer = Customer::findOrFail($request->input('customer_id'));

        return view('structure.gestion_servicios.Historial_se.registro_NS.externo.formulario.equipo', [
            'customer' => $customer,
            'equipmentTypes' => EquipmentType::orderBy('name')->get(),
            'subtypes' => Subtype::with('equipmentType')->orderBy('name')->get(),
            'brands' => Brand::orderBy('name')->get(),
            'models' => EquipmentModel::with('brand')->orderBy('name')->get(),
        ]);
    }

    public function createInternalEquipment(Request $request)
    {
        $customer = Customer::findOrFail($request->input('customer_id'));

        return view('structure.gestion_servicios.Historial_se.registro_NS.Interno.formulario.equipo', [
            'customer' => $customer,
            'equipmentTypes' => EquipmentType::orderBy('name')->get(),
            'subtypes' => Subtype::with('equipmentType')->orderBy('name')->get(),
            'brands' => Brand::orderBy('name')->get(),
            'models' => EquipmentModel::with('brand')->orderBy('name')->get(),
        ]);
    }

    public function storeEquipment(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:clientes,id',
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
            'firma' => 'nullable|string',
        ]);

        $validated['evidencia_1_path'] = $this->storeEvidence($request->file('evidencia_1'));
        $validated['evidencia_2_path'] = $this->storeEvidence($request->file('evidencia_2'));
        $validated['evidencia_3_path'] = $this->storeEvidence($request->file('evidencia_3'));
        $validated['video_path'] = $this->storeEvidence($request->file('evidencia_video'));

        session(['service_new.equipment' => $validated]);

        return redirect()->route('gestion.servicios.nuevo.externo.tecnico', [
            'customer_id' => $validated['customer_id'],
        ]);
    }

    public function storeInternalEquipment(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:clientes,id',
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
            'firma' => 'nullable|string',
        ]);

        $validated['evidencia_1_path'] = $this->storeEvidence($request->file('evidencia_1'));
        $validated['evidencia_2_path'] = $this->storeEvidence($request->file('evidencia_2'));
        $validated['evidencia_3_path'] = $this->storeEvidence($request->file('evidencia_3'));
        $validated['video_path'] = $this->storeEvidence($request->file('evidencia_video'));

        session(['service_new.equipment' => $validated]);

        return redirect()->route('gestion.servicios.nuevo.interno.tecnico', [
            'customer_id' => $validated['customer_id'],
        ]);
    }

    public function createTechnician(Request $request)
    {
        $customer = Customer::find($request->input('customer_id'));

        if (! session('service_new.equipment') || ! $customer) {
            return redirect()->route('gestion.servicios.nuevo.externo.equipo', [
                'customer_id' => $request->input('customer_id'),
            ])->with('error', 'Debes registrar el equipo primero.');
        }

        return view('structure.gestion_servicios.Historial_se.registro_NS.externo.formulario.eleccion_tecnico', [
            'customer' => $customer,
            'technicians' => \Illuminate\Support\Facades\DB::table('tecnico_externo')
                ->whereNull('deleted_at')
                ->orderBy('nombre')
                ->get(),
        ]);
    }

    public function createInternalTechnician(Request $request)
    {
        $customer = Customer::find($request->input('customer_id'));

        if (! session('service_new.equipment') || ! $customer) {
            return redirect()->route('gestion.servicios.nuevo.interno.equipo', [
                'customer_id' => $request->input('customer_id'),
            ])->with('error', 'Debes registrar el equipo primero.');
        }

        $technicians = \Illuminate\Support\Facades\DB::table('tecnico_externo')
            ->whereNull('deleted_at')
            ->orderBy('nombre')
            ->get();

        $servicesByTech = Service::whereIn('external_technician_id', $technicians->pluck('id'))
            ->whereIn('status', ['registrado', 'en_progreso'])
            ->with('customer')
            ->get()
            ->groupBy('external_technician_id');

        return view('structure.gestion_servicios.Historial_se.registro_NS.Interno.formulario.eleccion_tecnico', [
            'customer' => $customer,
            'technicians' => $technicians,
            'servicesByTech' => $servicesByTech,
        ]);
    }

    public function createInternalCotizacion(Request $request)
    {
        $customer = Customer::findOrFail($request->input('customer_id'));
        $technician = ExternalTechnician::findOrFail($request->input('technician_id'));

        if (! session('service_new.equipment')) {
            return redirect()->route('gestion.servicios.nuevo.interno.equipo', [
                'customer_id' => $customer->id,
            ])->with('error', 'Debes registrar el equipo primero.');
        }

        return view('structure.gestion_servicios.Historial_se.registro_NS.Interno.formulario.Cotizacion', [
            'customer' => $customer,
            'technician' => $technician,
        ]);
    }

    public function storeTechnician(Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:100',
            'apellidos' => 'nullable|string|max:100',
            'telefono' => 'nullable|string|max:10',
            'domicilio' => 'nullable|string|max:100',
            'correo' => 'nullable|email|max:100',
            'especialidad' => 'nullable|string|max:100',
            'empresa' => 'nullable|string|max:100',
        ]);

        $technician = ExternalTechnician::create($validated);

        return redirect()->route('gestion.servicios.nuevo.externo.tecnico', [
            'customer_id' => $request->input('customer_id'),
        ])->with('success', 'Técnico agregado correctamente.');
    }

    public function storeInternalTechnician(Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:100',
            'apellidos' => 'nullable|string|max:100',
            'telefono' => 'nullable|string|max:10',
            'domicilio' => 'nullable|string|max:100',
            'correo' => 'nullable|email|max:100',
            'especialidad' => 'nullable|string|max:100',
            'empresa' => 'nullable|string|max:100',
        ]);

        ExternalTechnician::create($validated);

        return redirect()->route('gestion.servicios.nuevo.interno.tecnico', [
            'customer_id' => $request->input('customer_id'),
        ])->with('success', 'Técnico agregado correctamente.');
    }

    public function storeService(Request $request)
    {
        $customer = Customer::findOrFail($request->input('customer_id'));
        $equipment = session('service_new.equipment');

        if (! $equipment) {
            return redirect()->route('gestion.servicios.nuevo.externo.equipo', [
                'customer_id' => $customer->id,
            ])->with('error', 'Debes registrar el equipo primero.');
        }

        $validated = $request->validate([
            'external_technician_id' => 'required_without:nuevo_tecnico|exists:tecnico_externo,id',
            'nuevo_tecnico' => 'nullable|string',
            'nombre' => 'required_with:nuevo_tecnico|string|max:100',
            'apellidos' => 'nullable|string|max:100',
            'telefono' => 'nullable|string|max:10',
            'domicilio' => 'nullable|string|max:100',
            'correo' => 'nullable|email|max:100',
            'especialidad' => 'nullable|string|max:100',
            'empresa' => 'nullable|string|max:100',
        ]);

        $externalTechnicianId = $validated['external_technician_id'] ?? null;

        if ($request->input('nuevo_tecnico')) {
            $technician = ExternalTechnician::create([
                'nombre' => $validated['nombre'],
                'apellidos' => $validated['apellidos'],
                'telefono' => $validated['telefono'],
                'domicilio' => $validated['domicilio'],
                'correo' => $validated['correo'],
                'especialidad' => $validated['especialidad'],
                'empresa' => $validated['empresa'],
            ]);
            $externalTechnicianId = $technician->id;
        }

        $firstStep = ServiceStep::where('service_type', 'externo')
            ->orderBy('order')
            ->first();

        $service = Service::create([
            'service_number' => null,
            'customer_id' => $customer->id,
            'service_type' => 'externo',
            'external_technician_id' => $externalTechnicianId,
            'registered_by' => auth()->id(),
            'current_step_id' => $firstStep?->id,
            'signature' => $equipment['firma'] ?? null,
            'status' => 'registrado',
            'started_at' => now(),
        ]);

        $service->update(['service_number' => 'NS-' . $service->id]);

        $serviceEquipment = ServiceEquipment::create([
            'service_id' => $service->id,
            'product_code' => null,
            'type_text' => $equipment['tipo_equipo'] ?? null,
            'subtype_text' => $equipment['subtipo'] ?? null,
            'brand_text' => $equipment['marca'] ?? null,
            'model_text' => $equipment['modelo'] ?? null,
            'serial_number' => $equipment['serie'] ?? null,
            'description' => $equipment['descripcion_equipo'] ?? null,
            'observations' => $equipment['observaciones'] ?? null,
            'evidence_1_path' => $equipment['evidencia_1_path'] ?? null,
            'evidence_2_path' => $equipment['evidencia_2_path'] ?? null,
            'evidence_3_path' => $equipment['evidencia_3_path'] ?? null,
            'video_path' => $equipment['video_path'] ?? null,
        ]);

        $serviceEquipment->update(['product_code' => 'PRD-' . $serviceEquipment->id]);

        $allSteps = ServiceStep::where('service_type', 'externo')
            ->orderBy('order')
            ->get();

        $firstApprovalStep = $allSteps->firstWhere('requires_approval', true);

        foreach ($allSteps as $step) {
            if ($firstApprovalStep && $step->order > $firstApprovalStep->order) {
                break;
            }

            $isBeforeApproval = $firstApprovalStep && $step->order < $firstApprovalStep->order;

            $qrToken = $step->requires_qr ? $this->generateQrToken() : null;
            $verificationCode = $step->slug === 'notificacion-llegada-tecnico'
                ? str_pad(random_int(0, 9999), 4, '0', STR_PAD_LEFT)
                : null;

            ServiceTracking::create([
                'service_id' => $service->id,
                'service_step_id' => $step->id,
                'status' => $isBeforeApproval ? 'completado' : 'pendiente',
                'qr_token' => $qrToken,
                'qr_expires_at' => $qrToken ? now()->addDay() : null,
                'verification_code' => $verificationCode,
                'started_at' => now(),
                'finished_at' => $isBeforeApproval ? now() : null,
                'notes' => $step->requires_approval ? 'Pendiente de aprobación del administrador' : null,
            ]);
        }

        $service->update([
            'current_step_id' => $firstApprovalStep?->id ?? $firstStep?->id,
            'qr_token' => $this->generateQrToken(),
            'qr_expires_at' => now()->addDay(),
        ]);

        session()->forget('service_new');

        return redirect()->route('gestion.servicios.nuevo.externo.resumen', $service);
    }

    public function storeInternalService(Request $request)
    {
        $customer = Customer::findOrFail($request->input('customer_id'));
        $equipment = session('service_new.equipment');

        if (! $equipment) {
            return redirect()->route('gestion.servicios.nuevo.interno.equipo', [
                'customer_id' => $customer->id,
            ])->with('error', 'Debes registrar el equipo primero.');
        }

        $validated = $request->validate([
            'technician_id' => 'required|exists:tecnico_externo,id',
        ]);

        $service = Service::create([
            'service_number' => null,
            'customer_id' => $customer->id,
            'service_type' => 'interno',
            'external_technician_id' => $validated['technician_id'],
            'registered_by' => auth()->id(),
            'current_step_id' => null,
            'signature' => $equipment['firma'] ?? null,
            'status' => 'registrado',
            'started_at' => now(),
        ]);

        $service->update(['service_number' => 'NS-' . $service->id]);

        ServiceEquipment::create([
            'service_id' => $service->id,
            'product_code' => null,
            'type_text' => $equipment['tipo_equipo'] ?? null,
            'subtype_text' => $equipment['subtipo'] ?? null,
            'brand_text' => $equipment['marca'] ?? null,
            'model_text' => $equipment['modelo'] ?? null,
            'serial_number' => $equipment['serie'] ?? null,
            'description' => $equipment['descripcion_equipo'] ?? null,
            'observations' => $equipment['observaciones'] ?? null,
            'evidence_1_path' => $equipment['evidencia_1_path'] ?? null,
            'evidence_2_path' => $equipment['evidencia_2_path'] ?? null,
            'evidence_3_path' => $equipment['evidencia_3_path'] ?? null,
            'video_path' => $equipment['video_path'] ?? null,
        ]);

        $approvalStep = ServiceStep::firstOrCreate(
            ['service_type' => 'interno', 'slug' => 'aprobacion-interna'],
            [
                'name' => 'Aprobacion de Servicio Interno',
                'purpose' => 'aprobacion',
                'order' => 1,
                'requires_qr' => false,
                'requires_approval' => true,
                'description' => 'Pendiente de aprobacion del administrador',
            ]
        );

        ServiceTracking::create([
            'service_id' => $service->id,
            'service_step_id' => $approvalStep->id,
            'status' => 'pendiente',
            'started_at' => now(),
            'notes' => 'Pendiente de aprobacion del administrador',
        ]);

        $service->update(['current_step_id' => $approvalStep->id]);

        session()->forget('service_new');

        return redirect()->route('gestion.servicios.nuevo.interno.resumen', $service)->withInput();
    }

    public function showSummary(Service $service)
    {
        $service->load(['customer', 'externalTechnician', 'serviceEquipment']);

        $qrUrl = url('/qr/' . $service->qr_token);

        return view('structure.gestion_servicios.Historial_se.registro_NS.externo.formulario.resumen', [
            'service' => $service,
            'qrUrl' => $qrUrl,
        ]);
    }

    public function showInternalSummary(Service $service)
    {
        $service->load(['customer', 'externalTechnician', 'serviceEquipment']);

        return view('structure.gestion_servicios.Historial_se.registro_NS.Interno.formulario.resumen', [
            'service' => $service,
        ]);
    }

    public function pendingApprovals()
    {
        $trackings = ServiceTracking::with([
                'service.customer',
                'service.externalTechnician',
                'service.serviceEquipment',
                'service.serviceTrackings.serviceStep',
                'serviceStep',
            ])
            ->whereHas('serviceStep', function ($query) {
                $query->where('requires_approval', true);
            })
            ->whereIn('status', ['pendiente', 'completado'])
            ->orderByRaw("FIELD(status, 'pendiente', 'completado')")
            ->latest('service_trackings.created_at')
            ->get();

        return view('structure.gestion_servicios.aprovaciones_admin.menu_aprobaciones', [
            'trackings' => $trackings,
        ]);
    }

    public function approveService(ServiceTracking $tracking)
    {
        $tracking->load('serviceStep', 'service');

        if ($tracking->status !== 'pendiente') {
            return redirect()->route('gestion.servicios.aprobaciones')
                ->with('error', 'Este paso ya fue procesado.');
        }

        $tracking->update([
            'status' => 'completado',
            'finished_at' => now(),
            'performed_by' => auth()->id(),
        ]);

        $service = $tracking->service;
        $currentStep = $tracking->serviceStep;

        $nextSteps = ServiceStep::where('service_type', $service->service_type)
            ->where('order', '>', $currentStep->order)
            ->orderBy('order')
            ->get();

        if ($currentStep->slug === 'aprobacion-autoridades') {
            $stepsToCreate = $nextSteps->take(3);
            $firstNextId = null;

            foreach ($stepsToCreate as $step) {
                $verificationCode = $step->slug === 'notificacion-llegada-tecnico'
                    ? str_pad(random_int(0, 9999), 4, '0', STR_PAD_LEFT)
                    : null;

                $created = ServiceTracking::create([
                    'service_id' => $service->id,
                    'service_step_id' => $step->id,
                    'status' => 'pendiente',
                    'qr_token' => $step->requires_qr ? $this->generateQrToken() : null,
                    'qr_expires_at' => $step->requires_qr ? now()->addDay() : null,
                    'verification_code' => $verificationCode,
                    'started_at' => now(),
                ]);

                if ($firstNextId === null) {
                    $firstNextId = $step->id;
                }
            }

            $service->update([
                'status' => 'en_progreso',
                'current_step_id' => $firstNextId ?? $service->current_step_id,
            ]);
        } elseif ($currentStep->slug === 'validacion-os') {
            $nextStep = $nextSteps->first();

            if ($nextStep) {
                ServiceTracking::create([
                    'service_id' => $service->id,
                    'service_step_id' => $nextStep->id,
                    'status' => 'pendiente',
                    'started_at' => now(),
                ]);

                $service->update([
                    'status' => 'en_progreso',
                    'current_step_id' => $nextStep->id,
                ]);
            } else {
                $service->update([
                    'status' => 'entregado',
                    'finished_at' => now(),
                ]);
            }
        } else {
            $nextStep = $nextSteps->first();

            if ($nextStep) {
                ServiceTracking::create([
                    'service_id' => $service->id,
                    'service_step_id' => $nextStep->id,
                    'status' => 'pendiente',
                    'started_at' => now(),
                ]);

                $service->update(['current_step_id' => $nextStep->id]);
            } else {
                $service->update([
                    'status' => 'entregado',
                    'finished_at' => now(),
                ]);
            }
        }

        return redirect()->route('gestion.servicios.aprobaciones')
            ->with('success', 'Servicio aprobado correctamente.');
    }

    public function maintenanceOrders()
    {
        $services = Service::with(['customer', 'externalTechnician', 'serviceEquipment', 'currentStep', 'serviceTrackings', 'maintenance'])
            ->where('status', 'en_progreso')
            ->latest()
            ->get();

        return view('structure.gestion_servicios.mantenimiento.menu_mantenimiento', [
            'services' => $services,
        ]);
    }

    public function destroy(Service $service)
    {
        $service->load('serviceEquipment');

        DB::transaction(function () use ($service) {
            if ($service->serviceEquipment) {
                $service->serviceEquipment->delete();
            }
            $service->serviceTrackings()->delete();
            $service->delete();
        });

        return redirect()->route('gestion.servicios.mantenimiento')
            ->with('success', 'Orden de servicio eliminada correctamente.');
    }

    public function completeCurrentStep(Request $request, Service $service)
    {
        $tracking = $service->serviceTrackings()
            ->with('serviceStep')
            ->where('service_step_id', $service->current_step_id)
            ->first();

        if (! $tracking || $tracking->status !== 'pendiente') {
            return response()->json([
                'success' => false,
                'message' => 'Paso no disponible o ya completado.',
            ], 422);
        }

        $tracking->update([
            'status' => 'completado',
            'finished_at' => now(),
        ]);

        if ($tracking->serviceStep->slug === 'salida-tecnico-externo') {
            ServiceMaintenance::firstOrCreate(
                ['service_id' => $service->id],
                [
                    'tipo_mantenimiento' => 'externo',
                    'tecnico_externo_id' => $service->external_technician_id,
                ]
            );
        }

        $nextStep = ServiceStep::where('service_type', $service->service_type)
            ->where('order', '>', $tracking->serviceStep->order)
            ->orderBy('order')
            ->first();

        if ($nextStep) {
            ServiceTracking::create([
                'service_id' => $service->id,
                'service_step_id' => $nextStep->id,
                'status' => 'pendiente',
                'qr_token' => $nextStep->requires_qr ? $this->generateQrToken() : null,
                'qr_expires_at' => $nextStep->requires_qr ? now()->addDay() : null,
                'started_at' => now(),
            ]);

            $service->update(['current_step_id' => $nextStep->id]);
        } else {
            $service->update([
                'status' => 'entregado',
                'finished_at' => now(),
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Paso completado correctamente.',
            'service_number' => $service->service_number,
            'step_name' => $tracking->serviceStep->name,
            'status' => 'Completado',
            'new_step_name' => $nextStep?->name,
        ]);
    }

    public function maintenanceForm(Service $service)
    {
        $service->load(['customer', 'externalTechnician', 'serviceEquipment', 'currentStep', 'serviceTrackings', 'maintenance']);

        $currentTracking = $service->serviceTrackings
            ->firstWhere('service_step_id', $service->current_step_id);

        return view('structure.gestion_servicios.mantenimiento.tecnico_externo.formulario_externo', [
            'service' => $service,
            'currentTracking' => $currentTracking,
        ]);
    }

    public function storeMaintenance(Request $request, Service $service)
    {
        $service->load(['currentStep', 'serviceTrackings']);
        $currentTracking = $service->serviceTrackings
            ->firstWhere('service_step_id', $service->current_step_id);

        $data = $request->validate([
            'tipo_mantenimiento' => 'required|in:interno,externo',
            'tipo_reparacion' => 'nullable|in:preventivo,correctivo,mixto',
            'descripcion' => 'nullable|string',
            'fallas_encontradas' => 'nullable|string',
            'checklist' => 'nullable|array',
            'refacciones' => 'nullable|array',
            'proximo_mantenimiento' => 'nullable|date',
            'carta_garantia' => 'nullable|string',
            'evidencia_1' => 'nullable|file|mimes:jpg,jpeg,png|max:5120',
            'evidencia_2' => 'nullable|file|mimes:jpg,jpeg,png|max:5120',
            'evidencia_3' => 'nullable|file|mimes:jpg,jpeg,png|max:5120',
        ]);

        $evidences = [];
        foreach (['evidencia_1', 'evidencia_2', 'evidencia_3'] as $field) {
            if ($request->hasFile($field)) {
                $evidences[$field] = $request->file($field)->store('evidencias', 'public');
            }
        }

        ServiceMaintenance::updateOrCreate(
            ['service_id' => $service->id],
            array_merge($data, $evidences, [
                'tecnico_externo_id' => $service->external_technician_id,
            ])
        );

        if ($currentTracking && $currentTracking->status === 'pendiente') {
            $currentTracking->update([
                'status' => 'completado',
                'finished_at' => now(),
            ]);

            $nextStep = ServiceStep::where('service_type', $service->service_type)
                ->where('order', '>', $service->currentStep->order)
                ->orderBy('order')
                ->first();

            if ($nextStep) {
                ServiceTracking::create([
                    'service_id' => $service->id,
                    'service_step_id' => $nextStep->id,
                    'status' => 'pendiente',
                    'qr_token' => $nextStep->requires_qr ? $this->generateQrToken() : null,
                    'qr_expires_at' => $nextStep->requires_qr ? now()->addDay() : null,
                    'started_at' => now(),
                ]);

                $service->update(['current_step_id' => $nextStep->id]);
            } else {
                $service->update([
                    'status' => 'entregado',
                    'finished_at' => now(),
                ]);
            }
        }

        return redirect()->route('gestion.servicios.maintenance.form', ['service' => $service])
            ->with('success', 'Mantenimiento guardado.');
    }

    public function osForm(Service $service)
    {
        $service->load(['customer', 'externalTechnician', 'internalTechnician', 'serviceEquipment', 'currentStep', 'serviceTrackings', 'maintenance']);

        $maintenance = $service->maintenance ?? ServiceMaintenance::firstOrNew(['service_id' => $service->id]);

        return view('structure.gestion_servicios.mantenimiento.OS.formulario_OS', [
            'service' => $service,
            'maintenance' => $maintenance,
        ]);
    }

    public function storeOs(Request $request, Service $service)
    {
        $service->load(['currentStep', 'serviceTrackings']);

        $data = $request->validate([
            'partidas_remision' => 'nullable|array',
            'partidas_remision.*.item' => 'nullable|string',
            'partidas_remision.*.descripcion' => 'nullable|string',
            'partidas_remision.*.unidad' => 'nullable|string',
            'partidas_remision.*.cantidad' => 'nullable|numeric|min:0',
            'partidas_remision.*.precio_unitario' => 'nullable|numeric|min:0',
            'envio' => 'nullable|numeric|min:0',
            'anticipo' => 'nullable|numeric|min:0',
            'requiere_iva' => 'nullable|boolean',
            'descripcion_general' => 'nullable|string',
            'action' => 'nullable|string|in:save,generate-pdf',
        ]);

        $data['requiere_iva'] = $request->boolean('requiere_iva');

        $maintenance = ServiceMaintenance::updateOrCreate(
            ['service_id' => $service->id],
            array_merge($data, [
                'tipo_mantenimiento' => $service->service_type ?? 'externo',
                'tecnico_externo_id' => $service->external_technician_id,
            ])
        );

        $currentTracking = $service->serviceTrackings
            ->firstWhere('service_step_id', $service->current_step_id);

        if ($currentTracking && $currentTracking->status === 'pendiente' && $service->currentStep?->slug === 'generacion-os') {
            $currentTracking->update([
                'status' => 'completado',
                'finished_at' => now(),
            ]);

            $nextStep = ServiceStep::where('service_type', $service->service_type)
                ->where('order', '>', $service->currentStep->order)
                ->orderBy('order')
                ->first();

            if ($nextStep) {
                ServiceTracking::create([
                    'service_id' => $service->id,
                    'service_step_id' => $nextStep->id,
                    'status' => 'pendiente',
                    'started_at' => now(),
                ]);

                $service->update(['current_step_id' => $nextStep->id]);
            }
        }

        if ($request->input('action') === 'generate-pdf') {
            return $this->generateOsPdf($service, $maintenance);
        }

        return redirect()->route('gestion.servicios.os.form', ['service' => $service])
            ->with('success', 'Orden de servicio guardada correctamente.');
    }

    private function generateOsPdf(Service $service, ServiceMaintenance $maintenance)
    {
        $service->load(['customer', 'externalTechnician', 'internalTechnician', 'serviceEquipment', 'currentStep']);

        $pdf = Pdf::loadView('structure.gestion_servicios.mantenimiento.OS.os_pdf', [
            'service' => $service,
            'maintenance' => $maintenance,
        ]);

        $filename = 'OS-' . ($service->service_number ?? $service->id) . '.pdf';

        return $pdf->download($filename);
    }

    private function storeEvidence($file): ?string
    {
        if (! $file) {
            return null;
        }

        return Storage::disk('public')->putFile('evidencias', $file);
    }

    private function generateQrToken(): string
    {
        do {
            $token = Str::random(32);
        } while (Service::where('qr_token', $token)->exists() || ServiceTracking::where('qr_token', $token)->exists());

        return $token;
    }
}
