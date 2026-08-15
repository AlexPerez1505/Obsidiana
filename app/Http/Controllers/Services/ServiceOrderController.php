<?php

namespace App\Http\Controllers\Services;

use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Models\ServiceMaintenance;
use App\Models\ServiceStep;
use App\Models\ServiceTracking;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class ServiceOrderController extends Controller
{
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
            'action' => 'nullable|string|in:save,generate-pdf,generate-remision-pdf',
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
            ->where('service_step_id', $service->current_step_id)
            ->sortByDesc('created_at')
            ->first();

        if ($currentTracking && $currentTracking->status === 'pendiente' && str_contains($service->currentStep?->slug ?? '', 'generacion-os')) {
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
            if (str_contains($service->currentStep?->slug ?? '', 'generacion-os')) {
                return redirect()->route('gestion.servicios.os.form', ['service' => $service])
                    ->with('error', 'La OS debe ser validada antes de generar el PDF.');
            }

            return $this->generateOsPdf($service, $maintenance);
        }

        if ($request->input('action') === 'generate-remision-pdf') {
            if (str_contains($service->currentStep?->slug ?? '', 'generacion-os')) {
                return redirect()->route('gestion.servicios.os.form', ['service' => $service])
                    ->with('error', 'La remisión debe ser validada antes de generar el PDF.');
            }

            return $this->generateRemisionPdf($service, $maintenance);
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
        ])->setPaper('A4', 'portrait');

        $filename = 'OS-' . ($service->service_number ?? $service->id) . '.pdf';

        return $pdf->download($filename);
    }

    private function generateRemisionPdf(Service $service, ServiceMaintenance $maintenance)
    {
        $service->load(['customer', 'externalTechnician', 'internalTechnician', 'serviceEquipment', 'currentStep']);

        $pdf = Pdf::loadView('structure.gestion_servicios.mantenimiento.OS.remision_pdf', [
            'service' => $service,
            'maintenance' => $maintenance,
        ])->setPaper('A4', 'portrait');

        $filename = 'REM-' . ($service->service_number ?? $service->id) . '.pdf';

        return $pdf->download($filename);
    }
}
