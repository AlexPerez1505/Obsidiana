<?php

namespace App\Services\Servicios;

use App\Models\ExternalTechnician;
use App\Models\Service;
use App\Models\ServiceEquipment;
use App\Models\ServiceInvitation;
use App\Models\ServiceSparePart;
use App\Models\ServiceStep;
use App\Models\ServiceTracking;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Lo que se hace con una orden de servicio desde que se registra hasta
 * que se aprueba o se deniega.
 *
 * Lo usan la web (Gestión de servicios y el enlace público de invitación)
 * y la API, para que una orden nazca y avance igual venga de donde venga:
 * mismo folio, mismo primer paso, mismo QR.
 */
class OrdenesDeServicio
{
    /**
     * Registra la orden con su equipo, refacciones y el primer paso de
     * seguimiento. Valida el request (los errores salen como en cualquier
     * formulario de Laravel).
     */
    public function registrar(Request $request, int $registradoPor): Service
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
            $this->rechazar($request, 'mantenimiento_interno', 'Selecciona el tipo de servicio.');
        }

        if ($serviceType === 'externo' && empty($validated['customer_id'])) {
            $this->rechazar($request, 'customer_id', 'Selecciona un cliente para el servicio externo.');
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
            'registered_by' => $registradoPor,
            'current_step_id' => $step?->id,
            'qr_token' => $this->generarTokenQr(),
            'qr_expires_at' => now()->addDay(),
            'signature' => $validated['firma'] ?? null,
            'status' => 'registrado',
            'started_at' => now(),
        ]);

        $service->update(['service_number' => 'OS-'.$service->id]);

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
            'evidence_1_path' => $this->guardarEvidencia($request, 'evidencia_1'),
            'evidence_2_path' => $this->guardarEvidencia($request, 'evidencia_2'),
            'evidence_3_path' => $this->guardarEvidencia($request, 'evidencia_3'),
            'video_path' => $this->guardarEvidencia($request, 'evidencia_video'),
        ]);

        $serviceEquipment->update(['product_code' => 'PRD-'.$serviceEquipment->id]);

        if (! empty($validated['refacciones']) && is_array($validated['refacciones'])) {
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

    /**
     * Pasa la orden a "en progreso". Devuelve false si ya no estaba
     * esperando decisión (aprobada, denegada o avanzada por QR).
     */
    public function aprobar(Service $service): bool
    {
        if ($service->status !== 'registrado') {
            return false;
        }

        $service->update(['status' => 'en_progreso']);

        return true;
    }

    /** Cancela la orden. False si ya no estaba esperando decisión. */
    public function denegar(Service $service): bool
    {
        if ($service->status !== 'registrado') {
            return false;
        }

        $service->update(['status' => 'cancelado']);

        return true;
    }

    /**
     * Invitación de un solo uso, válida 24 horas, para que alguien sin
     * cuenta registre la orden desde el enlace público.
     */
    public function invitar(int $invitadoPor): ServiceInvitation
    {
        return ServiceInvitation::create([
            'token' => $this->generarTokenInvitacion(),
            'invited_by' => $invitadoPor,
            'expires_at' => now()->addDay(),
        ]);
    }

    /**
     * Da otras 24 horas al QR de un mantenimiento externo. El paso
     * pendiente se lleva el token nuevo, así el QR impreso viejo deja de
     * servir. False si la orden no es externa (las internas no se mueven
     * con QR impreso).
     */
    public function renovarQr(Service $service): bool
    {
        if ($service->service_type !== 'externo') {
            return false;
        }

        $oldToken = $service->qr_token;
        $newToken = $this->generarTokenQr();
        $expires = now()->addDay();

        $service->update([
            'qr_token' => $newToken,
            'qr_expires_at' => $expires,
        ]);

        $tracking = ServiceTracking::where('service_id', $service->id)
            ->where('status', 'pendiente')
            ->where('qr_token', $oldToken)
            ->first();

        // Órdenes viejas pudieron perder el token en el paso; se busca por
        // el paso actual para no dejar el seguimiento con un QR muerto.
        if (! $tracking && $service->current_step_id) {
            $tracking = ServiceTracking::where('service_id', $service->id)
                ->where('status', 'pendiente')
                ->where('service_step_id', $service->current_step_id)
                ->first();
        }

        if ($tracking) {
            $tracking->update([
                'qr_token' => $newToken,
                'qr_expires_at' => $expires,
                'started_at' => now(),
            ]);
        }

        return true;
    }

    /** Alta rápida de un técnico externo desde el formulario de la orden. */
    public function registrarTecnicoExterno(Request $request): ExternalTechnician
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|regex:/^[0-9\s+\-()]{7,30}$/|max:255',
            'email' => 'nullable|email:filter|max:255',
            'company' => 'nullable|string|max:255',
            'specialty' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'location' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'photo' => 'nullable|mimetypes:image/*|max:2048',
        ], [
            'name.required' => 'El nombre del técnico es obligatorio.',
            'phone.regex' => 'El teléfono solo puede contener números, espacios y los caracteres + - ( ).',
            'email.email' => 'El correo electrónico no tiene un formato válido.',
            'photo.mimetypes' => 'La foto debe ser una imagen válida.',
            'photo.max' => 'La foto no debe pesar más de 2 MB.',
        ]);

        if ($request->hasFile('photo')) {
            $data['photo'] = Storage::disk('public')->putFile('external_technicians', $request->file('photo'));
        }

        return ExternalTechnician::create($data);
    }

    /**
     * Candidatos a técnico interno del formulario de nueva orden. Joel e
     * Icelda entran aunque su cuenta no esté aprobada: son los técnicos de
     * planta y la orden se les asigna aunque no usen el sistema.
     *
     * @return Collection<int, User>
     */
    public function candidatosATecnicoInterno(): Collection
    {
        return User::where(function ($q) {
            $q->where('status', User::STATUS_APPROVED)
                ->orWhereRaw('LOWER(name) LIKE ?', ['%joel%'])
                ->orWhereRaw('LOWER(name) LIKE ?', ['%icelda%']);
        })->orderBy('name')->get();
    }

    /**
     * De los candidatos, los que la pantalla ofrece de verdad como
     * técnicos (Joel e Icelda).
     *
     * @param  Collection<int, User>  $candidatos
     * @return Collection<int, User>
     */
    public function soloTecnicos(Collection $candidatos): Collection
    {
        return $candidatos->filter(function (User $t) {
            $name = strtolower($t->name);

            return str_contains($name, 'joel') || str_contains($name, 'icelda');
        })->values();
    }

    /**
     * La web siempre respondió estos dos casos con un 422 a secas (página
     * de error); a la API se le dan como error de validación del campo,
     * que es lo que una app sabe pintar junto al formulario.
     */
    private function rechazar(Request $request, string $campo, string $mensaje): never
    {
        if ($request->is('api/*')) {
            throw ValidationException::withMessages([$campo => $mensaje]);
        }

        abort(422, $mensaje);
    }

    private function guardarEvidencia(Request $request, string $field): ?string
    {
        if (! $request->hasFile($field)) {
            return null;
        }

        return Storage::disk('public')->putFile('evidencias', $request->file($field));
    }

    private function generarTokenQr(): string
    {
        do {
            $token = Str::random(32);
        } while (Service::where('qr_token', $token)->exists() || ServiceTracking::where('qr_token', $token)->exists());

        return $token;
    }

    private function generarTokenInvitacion(): string
    {
        do {
            $token = Str::random(32);
        } while (ServiceInvitation::where('token', $token)->exists());

        return $token;
    }
}
