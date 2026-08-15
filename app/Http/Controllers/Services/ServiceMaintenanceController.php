<?php

namespace App\Http\Controllers\Services;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Services\Concerns\HasServiceHelpers;
use App\Models\Service;
use App\Models\ServiceMaintenance;
use App\Models\ServiceStep;
use App\Models\ServiceTracking;
use Illuminate\Http\Request;

class ServiceMaintenanceController extends Controller
{
    use HasServiceHelpers;

    public function maintenanceOrders()
    {
        $services = Service::with(['customer', 'externalTechnician', 'serviceEquipment', 'currentStep', 'serviceTrackings', 'maintenance'])
            ->whereIn('status', ['en_progreso', 'entregado'])
            ->latest()
            ->get();

        return view('structure.gestion_servicios.mantenimiento.menu_mantenimiento', [
            'services' => $services,
        ]);
    }

    public function maintenanceForm(Service $service)
    {
        $service->load(['customer', 'externalTechnician', 'serviceEquipment', 'currentStep', 'serviceTrackings', 'maintenance']);

        $currentTracking = $service->serviceTrackings
            ->where('service_step_id', $service->current_step_id)
            ->sortByDesc('created_at')
            ->first();

        $isFinalizado = $service->status === 'entregado' || ($currentTracking && $currentTracking->status === 'completado' && $service->currentStep?->slug === 'marcar-enviado-cliente');

        if ($isFinalizado) {
            return view('structure.gestion_servicios.mantenimiento.tecnico_externo.formulario_externo', [
                'service' => $service,
                'currentTracking' => $currentTracking,
                'readonly' => true,
                'finalizado' => true,
            ]);
        }

        $isAdmin = auth()->check() && auth()->user()?->isAdmin();
        $accessToken = request('token') ?: $request->cookie('service_access_' . $service->id);
        $hasValidToken = $currentTracking && $accessToken
            && hash_equals((string) $currentTracking->qr_token, (string) $accessToken);

        if (! $isAdmin && ! $hasValidToken) {
            if ($service->currentStep?->slug === 'notificacion-llegada-tecnico' && $currentTracking?->qr_token) {
                return redirect()->route('qr.show', ['token' => $currentTracking->qr_token]);
            }

            abort(403, 'Acceso no permitido.');
        }

        return view('structure.gestion_servicios.mantenimiento.tecnico_externo.formulario_externo', [
            'service' => $service,
            'currentTracking' => $currentTracking,
            'readonly' => $isAdmin,
        ]);
    }

    public function storeMaintenance(Request $request, Service $service)
    {
        $service->load(['currentStep', 'serviceTrackings']);
        $currentTracking = $service->serviceTrackings
            ->where('service_step_id', $service->current_step_id)
            ->sortByDesc('created_at')
            ->first();

        $isAdmin = auth()->check() && auth()->user()?->isAdmin();
        $accessToken = $request->input('token') ?: $request->query('token') ?: $request->cookie('service_access_' . $service->id);
        $hasValidToken = $currentTracking && $accessToken
            && hash_equals((string) $currentTracking->qr_token, (string) $accessToken);

        if (! $isAdmin && ! $hasValidToken) {
            return redirect()->back()->with('error', 'Token de acceso inválido o faltante. Recibido: ' . ($accessToken ? 'sí' : 'no') . ', Paso: ' . ($service->currentStep?->slug ?? 'ninguno'));
        }

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

            // La notificación de finalizado se completa automáticamente al guardar mantenimiento
            if ($nextStep && $nextStep->slug === 'notificacion-finalizado') {
                $finishedTracking = ServiceTracking::firstOrNew([
                    'service_id' => $service->id,
                    'service_step_id' => $nextStep->id,
                ]);
                $finishedTracking->fill([
                    'status' => 'completado',
                    'started_at' => $finishedTracking->exists ? $finishedTracking->started_at : now(),
                    'finished_at' => now(),
                ]);
                $finishedTracking->save();

                $nextStep = ServiceStep::where('service_type', $service->service_type)
                    ->where('order', '>', $nextStep->order)
                    ->orderBy('order')
                    ->first();
            }

            if ($nextStep) {
                $nextTracking = ServiceTracking::firstOrNew([
                    'service_id' => $service->id,
                    'service_step_id' => $nextStep->id,
                ]);
                $nextTracking->fill([
                    'status' => 'pendiente',
                    'qr_token' => $nextTracking->exists && $nextTracking->qr_token ? $nextTracking->qr_token : $this->generateQrToken(),
                    'qr_expires_at' => $nextTracking->exists && $nextTracking->qr_expires_at ? $nextTracking->qr_expires_at : now()->addDay(),
                    'started_at' => $nextTracking->exists ? $nextTracking->started_at : now(),
                    'finished_at' => null,
                ]);
                $nextTracking->save();

                $service->update(['current_step_id' => $nextStep->id]);

                return redirect()->route('gestion.servicios.maintenance.form', [
                    'service' => $service,
                    'token' => $nextTracking->qr_token,
                ])->with('success', 'Mantenimiento guardado. Confirma el envío del servicio.')
                    ->withCookie(cookie('service_access_' . $service->id, $nextTracking->qr_token, 1440));
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

    public function confirmEnvio(Request $request, Service $service)
    {
        $service->load(['currentStep', 'serviceTrackings']);
        $currentTracking = $service->serviceTrackings
            ->where('service_step_id', $service->current_step_id)
            ->sortByDesc('created_at')
            ->first();

        $isAdmin = auth()->check() && auth()->user()?->isAdmin();
        $accessToken = $request->input('token') ?: $request->query('token') ?: $request->cookie('service_access_' . $service->id);
        $hasValidToken = $currentTracking && $accessToken
            && hash_equals((string) $currentTracking->qr_token, (string) $accessToken);

        if (! $isAdmin && ! $hasValidToken) {
            return redirect()->back()->with('error', 'Token de acceso inválido o faltante.');
        }

        if ($service->currentStep?->slug !== 'notificacion-envio-servicio') {
            return redirect()->back()->with('error', 'Paso no disponible. Estado actual: ' . ($service->currentStep?->slug ?? 'ninguno'));
        }

        if (! $currentTracking || $currentTracking->status !== 'pendiente') {
            return redirect()->back()->with('error', 'El paso de envío ya fue completado o no existe.');
        }

        $currentTracking->update([
            'status' => 'completado',
            'finished_at' => now(),
        ]);

        $nextStep = ServiceStep::where('service_type', $service->service_type)
            ->where('order', '>', $service->currentStep->order)
            ->orderBy('order')
            ->first();

        if ($nextStep) {
            $nextTracking = ServiceTracking::firstOrNew([
                'service_id' => $service->id,
                'service_step_id' => $nextStep->id,
            ]);
            $nextTracking->fill([
                'status' => 'pendiente',
                'qr_token' => $nextTracking->exists && $nextTracking->qr_token ? $nextTracking->qr_token : $this->generateQrToken(),
                'qr_expires_at' => $nextTracking->exists && $nextTracking->qr_expires_at ? $nextTracking->qr_expires_at : now()->addDay(),
                'started_at' => $nextTracking->exists ? $nextTracking->started_at : now(),
                'finished_at' => null,
            ]);
            $nextTracking->save();

            $service->update(['current_step_id' => $nextStep->id]);

            return redirect()->route('gestion.servicios.maintenance.form', [
                'service' => $service,
                'token' => $nextTracking->qr_token,
            ])->with('success', 'Envío confirmado. Ahora escanea el QR de regreso a instalaciones.')
                ->withCookie(cookie('service_access_' . $service->id, $nextTracking->qr_token, 1440));
        }

        $service->update([
            'status' => 'entregado',
            'finished_at' => now(),
        ]);

        return redirect()->route('gestion.servicios.maintenance.form', ['service' => $service])
            ->with('success', 'Envío confirmado.');
    }
}
