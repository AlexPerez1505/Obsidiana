<?php

namespace App\Http\Controllers\Services;

use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Models\ServiceStep;
use App\Models\ServiceTracking;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class QrController extends Controller
{
    public function show($token)
    {
        $tracking = ServiceTracking::with(['service', 'serviceStep'])
            ->where('qr_token', $token)
            ->first();

        if (! $tracking) {
            $service = Service::where('qr_token', $token)->firstOrFail();
            $tracking = $service->serviceTrackings()
                ->with('serviceStep')
                ->where('service_step_id', $service->current_step_id)
                ->first();
        }

        if (! $tracking) {
            abort(404);
        }

        $tracking->load('service.customer', 'service.serviceEquipment', 'service.externalTechnician');

        return view('structure.gestion_servicios.Historial_se.registro_NS.externo.qr.scan', [
            'tracking' => $tracking,
            'service' => $tracking->service,
        ]);
    }

    public function verifyCode(Request $request, $token)
    {
        $tracking = ServiceTracking::with('service', 'serviceStep')
            ->where('qr_token', $token)
            ->first();

        if (! $tracking) {
            $service = Service::where('qr_token', $token)->firstOrFail();
            $tracking = $service->serviceTrackings()
                ->with('serviceStep')
                ->where('service_step_id', $service->current_step_id)
                ->first();
        }

        if (! $tracking) {
            abort(404);
        }

        $request->validate([
            'verification_code' => 'required|string|size:4',
        ]);

        if ($tracking->status !== 'pendiente' || $tracking->verification_code !== $request->input('verification_code')) {
            return back()->with('error', 'Código de verificación incorrecto o paso ya procesado.');
        }

        $tracking->update([
            'status' => 'completado',
            'finished_at' => now(),
        ]);

        $service = $tracking->service;

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

        return redirect()->route('gestion.servicios.maintenance.form', ['service' => $tracking->service])
            ->with('success', 'Código correcto. Bienvenido, completa el mantenimiento.');
    }

    public function complete(Request $request, $token)
    {
        $tracking = ServiceTracking::with('service', 'serviceStep')
            ->where('qr_token', $token)
            ->first();

        if (! $tracking) {
            $service = Service::where('qr_token', $token)->firstOrFail();
            $tracking = $service->serviceTrackings()
                ->with('serviceStep')
                ->where('service_step_id', $service->current_step_id)
                ->first();
        }

        if (! $tracking) {
            abort(404);
        }

        if ($tracking->status !== 'pendiente') {
            return back()->with('error', 'Este paso ya fue procesado.');
        }

        $tracking->update([
            'status' => 'completado',
            'finished_at' => now(),
        ]);

        $service = $tracking->service;

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

        return redirect()->route('qr.show', ['token' => $token])
            ->with('success', 'Paso completado correctamente.');
    }

    private function generateQrToken(): string
    {
        do {
            $token = Str::random(32);
        } while (Service::where('qr_token', $token)->exists() || ServiceTracking::where('qr_token', $token)->exists());

        return $token;
    }
}
