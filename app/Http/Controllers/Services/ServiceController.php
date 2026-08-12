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
use App\Models\ServiceStep;
use App\Models\ServiceTracking;
use App\Models\Subtype;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ServiceController extends Controller
{
    public function index()
    {
        return view('structure.gestion_servicios.Historial_se.menuhistorial');
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

    public function showSummary(Service $service)
    {
        $service->load(['customer', 'externalTechnician', 'serviceEquipment']);

        $qrUrl = url('/qr/' . $service->qr_token);

        return view('structure.gestion_servicios.Historial_se.registro_NS.externo.formulario.resumen', [
            'service' => $service,
            'qrUrl' => $qrUrl,
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
        $services = Service::with(['customer', 'externalTechnician', 'serviceEquipment', 'currentStep'])
            ->where('status', 'en_progreso')
            ->latest()
            ->get();

        return view('structure.gestion_servicios.mantenimiento.menu_mantenimiento', [
            'services' => $services,
        ]);
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
