<?php

namespace App\Http\Controllers\Services;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Refaccion;
use App\Models\Service;
use App\Models\ServiceEquipment;
use App\Models\User;

use App\Models\ServiceSparePart;
use App\Models\ServiceStep;
use App\Models\ServiceTracking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class ServiceController extends Controller
{


    public function store(Request $request)
    {
        $service = $this->persistService($request, auth()->id());

        $equipment = $service->serviceEquipment;
        $isEndoscopia = $equipment && str_contains(strtolower($equipment->type_text ?? ''), 'endoscop');

        $route = $isEndoscopia ? 'gestion.servicios.area_endoscopia' : 'gestion.servicios.historial';

        return redirect()->route($route)
            ->with('success', "Servicio {$service->service_number} creado.");
    }

    public function show(Service $service)
    {
        return redirect()->route('gestion.servicios.historial');
    }

    public function approve(Service $service)
    {
        if ($service->status !== 'registrado') {
            return back()->with('error', 'La orden no puede aprobarse en su estado actual.');
        }

        $service->update(['status' => 'en_progreso']);
        $service->loadMissing('serviceEquipment');

        if ($service->service_type === 'externo') {
            return redirect()->route('gestion.servicios.externo')
                ->with('success', "Orden {$service->service_number} aprobada y enviada a mantenimiento externo.");
        }

        $isEndoscopia = str_contains(strtolower($service->serviceEquipment?->type_text ?? ''), 'endoscop');
        $route = $isEndoscopia ? 'gestion.servicios.area_endoscopia' : 'gestion.servicios.area';

        return redirect()->route($route)
            ->with('success', "Orden {$service->service_number} aprobada y enviada a su área.");
    }

    public function deny(Service $service)
    {
        if ($service->status !== 'registrado') {
            return back()->with('error', 'La orden no puede denegarse en su estado actual.');
        }

        $service->update(['status' => 'cancelado']);

        return back()->with('success', "Orden {$service->service_number} denegada.");
    }

    public function customerShow(Service $service)
    {
        $service->load(['customer', 'serviceEquipment', 'internalTechnician', 'externalTechnician', 'spareParts.refaccion']);

        $decisionUrl = URL::signedRoute(
            'gestion.servicios.historial.aprobaciones.cliente.decision',
            $service,
            now()->addDays(7)
        );

        return view('structure.gestion_servicios.historial_servicios.aprobaciones.cliente', compact('service', 'decisionUrl'));
    }

    public function customerDecide(Request $request, Service $service)
    {
        if ($service->customer_decision !== null) {
            return back()->with('error', 'Ya se registró una decisión para esta orden.');
        }

        $decision = $request->input('decision');
        if (! in_array($decision, ['aprobado', 'rechazado'])) {
            return back()->with('error', 'Decisión no válida.');
        }

        $service->update([
            'customer_decision' => $decision,
            'customer_decision_at' => now(),
        ]);

        $mensaje = $decision === 'aprobado'
            ? 'Has aprobado la orden. El administrador revisará tu respuesta.'
            : 'Has rechazado la orden. El administrador revisará tu respuesta.';

        return redirect()->route('gestion.servicios.historial.aprobaciones.cliente', $service)
            ->with('success', $mensaje);
    }

    public function edit(Service $service)
    {
        $service->load(['customer', 'serviceEquipment', 'internalTechnician', 'externalTechnician']);
        $customers = Customer::with('asesor')->latest()->get();
        $technicians = User::where('status', User::STATUS_APPROVED)->orderBy('name')->get();
        $statuses = ['registrado', 'pendiente', 'aprobado', 'en_progreso', 'completado', 'entregado', 'cancelado', 'rechazado'];

        return view('structure.gestion_servicios.Area_Endoscopia.Editar', compact('service', 'customers', 'technicians', 'statuses'));
    }

    public function update(Request $request, Service $service)
    {
        $validated = $request->validate([
            'customer_id' => 'nullable|exists:clientes,id',
            'internal_technician_id' => 'nullable|exists:users,id',
            'external_technician_id' => 'nullable|exists:external_technicians,id',
            'status' => 'nullable|string',
            'tipo_equipo' => 'nullable|string|max:255',
            'subtipo' => 'nullable|string|max:255',
            'marca' => 'nullable|string|max:255',
            'modelo' => 'nullable|string|max:255',
            'serie' => 'nullable|string|max:255',
            'descripcion_equipo' => 'nullable|string',
            'observaciones' => 'nullable|string',
        ]);

        $service->update([
            'customer_id' => $validated['customer_id'] ?? $service->customer_id,
            'internal_technician_id' => $validated['internal_technician_id'] ?? $service->internal_technician_id,
            'external_technician_id' => $validated['external_technician_id'] ?? $service->external_technician_id,
            'status' => $validated['status'] ?? $service->status,
        ]);

        $service->serviceEquipment?->update([
            'type_text' => $validated['tipo_equipo'] ?? $service->serviceEquipment?->type_text,
            'subtype_text' => $validated['subtipo'] ?? $service->serviceEquipment?->subtype_text,
            'brand_text' => $validated['marca'] ?? $service->serviceEquipment?->brand_text,
            'model_text' => $validated['modelo'] ?? $service->serviceEquipment?->model_text,
            'serial_number' => $validated['serie'] ?? $service->serviceEquipment?->serial_number,
            'description' => $validated['descripcion_equipo'] ?? $service->serviceEquipment?->description,
            'observations' => $validated['observaciones'] ?? $service->serviceEquipment?->observations,
        ]);

        return redirect()->route('gestion.servicios.area_endoscopia')
            ->with('success', "Servicio {$service->service_number} actualizado.");
    }

    public function destroy(Service $service)
    {
        $service->serviceEquipment?->delete();
        $service->serviceTrackings()->delete();
        $service->spareParts()->delete();
        $service->delete();

        return redirect()->route('gestion.servicios.area_endoscopia')
            ->with('success', "Servicio {$service->service_number} eliminado.");
    }

    public function cotizacion(Service $service)
    {
        $service->load(['customer', 'serviceEquipment', 'internalTechnician', 'externalTechnician', 'currentStep', 'spareParts.refaccion']);
        $refacciones = Refaccion::orderBy('name')->get();

        return view('structure.gestion_servicios.Area_Endoscopia.Cotizacion', compact('service', 'refacciones'));
    }

    public function storeCotizacion(Request $request, Service $service)
    {
        $request->validate([
            'cantidad' => 'nullable|array',
            'cantidad.*' => 'integer|min:0',
            'precio' => 'nullable|array',
            'precio.*' => 'numeric|min:0',
            'mano_obra' => 'nullable|numeric|min:0',
        ]);

        $service->spareParts()->delete();

        $cantidades = $request->input('cantidad', []);
        $precios = $request->input('precio', []);

        foreach ($cantidades as $refaccionId => $cantidad) {
            if ((int) $cantidad <= 0) {
                continue;
            }

            $refaccion = Refaccion::find($refaccionId);
            if (!$refaccion) {
                continue;
            }

            $cantidad = min((int) $cantidad, max(0, $refaccion->stock));
            $precio = (float) ($precios[$refaccionId] ?? $refaccion->price ?? 0);

            ServiceSparePart::create([
                'service_id' => $service->id,
                'refaccion_id' => $refaccion->id,
                'nombre' => $refaccion->name,
                'cantidad' => $cantidad,
                'precio_unitario' => $precio,
                'subtotal' => $cantidad * $precio,
            ]);
        }

        $service->update(['mano_obra' => $request->input('mano_obra', 0)]);

        return back()->with('success', 'Cotización guardada correctamente.');
    }


    private function persistService(Request $request, int $registeredBy): Service
    {
        $validated = $request->validate([
            'customer_id' => 'nullable|exists:clientes,id',
            'mantenimiento_externo' => 'nullable|in:0,1',
            'mantenimiento_interno' => 'nullable|in:0,1',
            'internal_technician_id' => 'nullable|exists:users,id',
            'external_technician_id' => 'nullable|exists:external_technicians,id',
            'firma' => 'nullable|string',
            'tipo_equipo' => 'required|string|max:255',
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
            'refacciones' => 'nullable|array',
            'refacciones.*.nombre' => 'required_with:refacciones|string|max:255',
            'refacciones.*.cantidad' => 'required_with:refacciones|integer|min:1',
            'refacciones.*.precio_unitario' => 'required_with:refacciones|numeric|min:0',
        ]);

        if ($request->input('mantenimiento_externo')) {
            $serviceType = 'externo';
        } elseif ($request->input('mantenimiento_interno')) {
            $serviceType = 'interno';
        } else {
            abort(422, 'Selecciona el tipo de servicio.');
        }

        if ($serviceType === 'externo' && empty($validated['customer_id'])) {
            abort(422, 'Selecciona un cliente para el servicio externo.');
        }

        $step = ServiceStep::where('service_type', $serviceType)
            ->orderBy('order')
            ->first();

        $service = Service::create([
            'service_number' => null,
            'customer_id' => $validated['customer_id'] ?? null,
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

        if (!empty($validated['refacciones']) && is_array($validated['refacciones'])) {
            foreach ($validated['refacciones'] as $refaccion) {
                ServiceSparePart::create([
                    'service_id' => $service->id,
                    'nombre' => $refaccion['nombre'],
                    'cantidad' => (int) $refaccion['cantidad'],
                    'precio_unitario' => $refaccion['precio_unitario'],
                    'subtotal' => (int) $refaccion['cantidad'] * $refaccion['precio_unitario'],
                ]);
            }
        }

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
}
