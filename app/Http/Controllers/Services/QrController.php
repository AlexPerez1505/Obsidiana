<?php

namespace App\Http\Controllers\Services;

use App\Http\Controllers\Controller;

use App\Models\Service;
use App\Models\ServiceStep;
use App\Models\ServiceTracking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class QrController extends Controller
{
    public function show(string $token)
    {
        $tracking = ServiceTracking::with(['service', 'serviceStep'])
            ->where('qr_token', $token)
            ->where('status', 'pendiente')
            ->firstOrFail();

        if ($tracking->qr_expires_at && $tracking->qr_expires_at->isPast()) {
            abort(403, 'Este QR ha expirado.');
        }

        $service = $tracking->service;

        return view('structure.gestion_servicios.historial_servicios.qr.scan', compact('tracking', 'service'));
    }

    public function update(Request $request, string $token)
    {
        $tracking = ServiceTracking::with(['service.serviceEquipment', 'serviceStep'])
            ->where('qr_token', $token)
            ->where('status', 'pendiente')
            ->firstOrFail();

        if ($tracking->qr_expires_at && $tracking->qr_expires_at->isPast()) {
            return back()->with('error', 'El QR ha expirado.');
        }

        $validated = $request->validate([
            'evidencia_1' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:10240',
            'evidencia_2' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:10240',
            'evidencia_3' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:10240',
            'evidencia_video' => 'nullable|mimetypes:video/mp4,video/quicktime,video/x-m4v|max:10240',
            'firma' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $tracking->update([
            'status' => 'completado',
            'finished_at' => now(),
            'performed_by' => auth()->id(),
            'notes' => $validated['notes'] ?? null,
            'evidence_1_path' => $this->storeEvidence($request, 'evidencia_1', $tracking->service_id),
            'evidence_2_path' => $this->storeEvidence($request, 'evidencia_2', $tracking->service_id),
            'evidence_3_path' => $this->storeEvidence($request, 'evidencia_3', $tracking->service_id),
            'video_path' => $this->storeEvidence($request, 'evidencia_video', $tracking->service_id),
            'signature' => $validated['firma'] ?? null,
        ]);

        $service = $tracking->service;
        $currentOrder = $tracking->serviceStep->order;

        $nextStep = ServiceStep::where('service_type', $service->service_type)
            ->where('order', '>', $currentOrder)
            ->orderBy('order')
            ->first();

        if ($nextStep) {
            $newToken = $this->generateQrToken();

            ServiceTracking::create([
                'service_id' => $service->id,
                'service_step_id' => $nextStep->id,
                'status' => 'pendiente',
                'qr_token' => $newToken,
                'qr_expires_at' => now()->addDay(),
                'started_at' => now(),
            ]);

            $service->update([
                'current_step_id' => $nextStep->id,
                'qr_token' => $newToken,
                'qr_expires_at' => now()->addDay(),
                'status' => 'en_progreso',
            ]);

            return redirect()->route('qr.show', $newToken)
                ->with('success', 'Paso completado. Escanea el nuevo QR para continuar.');
        }

        $service->update([
            'current_step_id' => null,
            'qr_token' => null,
            'qr_expires_at' => null,
            'status' => 'entregado',
            'finished_at' => now(),
        ]);

        return redirect()->route('gestion.servicios.historial')
            ->with('success', 'Servicio completado.');
    }

    private function storeEvidence(Request $request, string $field, int $serviceId): ?string
    {
        if (!$request->hasFile($field)) {
            return null;
        }

        $path = "evidencias/{$serviceId}/" . date('Ymd_His');

        return Storage::disk('public')->putFile($path, $request->file($field));
    }

    private function generateQrToken(): string
    {
        do {
            $token = Str::random(32);
        } while (Service::where('qr_token', $token)->exists() || ServiceTracking::where('qr_token', $token)->exists());

        return $token;
    }
}
