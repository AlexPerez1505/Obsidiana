<?php

namespace App\Http\Controllers\Services;

use App\Http\Controllers\Controller;
use App\Models\GarantiaDocumento;
use App\Models\Service;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ServicioExternoController extends Controller
{
    public function show(Service $service)
    {
        $service->load([
            'customer',
            'serviceEquipment',
            'internalTechnician',
            'externalTechnician',
            'currentStep',
            'spareParts.refaccion',
            'garantiaDocumento',
        ]);

        $tipoEquipo = $service->serviceEquipment?->type_text;
        $cartas = GarantiaDocumento::orderByRaw(
            'CASE WHEN tipo_equipo = ? THEN 0 ELSE 1 END',
            [$tipoEquipo ?? '']
        )->orderBy('nombre')->get();

        return view('structure.gestion_servicios.Servicio_Externo.Ver', compact('service', 'cartas'));
    }

    public function updateGarantia(Request $request, Service $service)
    {
        $validated = $request->validate([
            'garantia_documento_id' => 'nullable|exists:garantia_documentos,id',
        ]);

        $service->update(['garantia_documento_id' => $validated['garantia_documento_id'] ?? null]);

        return back()->with('success', 'Carta de garantía actualizada.');
    }

    public function remision(Service $service)
    {
        $service->load(['customer', 'serviceEquipment', 'internalTechnician', 'externalTechnician', 'spareParts.refaccion']);

        $totalRefacciones = $service->spareParts->sum('subtotal');
        $total = $totalRefacciones + ($service->mano_obra ?? 0);

        $pdf = Pdf::loadView('structure.gestion_servicios.Servicio_Externo.RemisionPdf', compact('service', 'totalRefacciones', 'total'));

        return $pdf->download('remision-' . ($service->service_number ?? ('OS-' . $service->id)) . '.pdf');
    }

    public function ordenServicio(Service $service)
    {
        abort_unless($service->service_type === 'interno', 404);

        $service->load(['customer', 'serviceEquipment', 'internalTechnician', 'externalTechnician', 'currentStep']);

        return view('structure.gestion_servicios.Servicio_Externo.OrdenServicio', compact('service'));
    }

    public function ordenServicioPaso2(Request $request, Service $service)
    {
        abort_unless($service->service_type === 'interno', 404);

        $fechas = $request->validate([
            'fecha_ingreso' => 'required|date',
            'fecha_mantenimiento' => 'required|date',
        ]);

        $service->load(['customer', 'serviceEquipment', 'internalTechnician', 'serviceTrackings']);

        $registro = $service->serviceTrackings
            ->whereNotNull('registro_data')
            ->sortByDesc('finished_at')
            ->first()?->registro_data ?? [];

        $tipoMantenimiento = $registro['tipo_mantenimiento'] ?? null;

        return view('structure.gestion_servicios.Servicio_Externo.OrdenServicioPaso2', compact('service', 'fechas', 'tipoMantenimiento'));
    }

    public function ordenServicioPaso3(Request $request, Service $service)
    {
        abort_unless($service->service_type === 'interno', 404);

        $datos = $request->validate([
            'fecha_ingreso' => 'required|date',
            'fecha_mantenimiento' => 'required|date',
            'equipo' => 'nullable|string|max:255',
            'marca' => 'nullable|string|max:255',
            'modelo' => 'nullable|string|max:255',
            'serie' => 'nullable|string|max:255',
            'observaciones' => 'nullable|string',
            'proximo_mantenimiento' => 'nullable|in:3,6,9,12',
            'tipo_mantenimiento' => 'nullable|in:correctivo,preventivo',
        ]);

        $service->load(['serviceTrackings']);

        $registro = $service->serviceTrackings
            ->whereNotNull('registro_data')
            ->sortByDesc('finished_at')
            ->first()?->registro_data ?? [];

        $accionesPrevias = collect($registro['movimientos'] ?? []);
        if (! empty($registro['movimientos_otros'])) {
            $accionesPrevias->push($registro['movimientos_otros']);
        }

        return view('structure.gestion_servicios.Servicio_Externo.OrdenServicioPaso3', compact('service', 'datos', 'accionesPrevias'));
    }

    public function ordenServicioPaso4(Request $request, Service $service)
    {
        abort_unless($service->service_type === 'interno', 404);

        $datos = $request->validate([
            'fecha_ingreso' => 'required|date',
            'fecha_mantenimiento' => 'required|date',
            'equipo' => 'nullable|string|max:255',
            'marca' => 'nullable|string|max:255',
            'modelo' => 'nullable|string|max:255',
            'serie' => 'nullable|string|max:255',
            'observaciones' => 'nullable|string',
            'proximo_mantenimiento' => 'nullable|in:3,6,9,12',
            'tipo_mantenimiento' => 'nullable|in:correctivo,preventivo',
            'acciones' => 'nullable|array',
            'acciones.*' => 'string|max:255',
        ]);

        $service->load(['customer', 'serviceEquipment', 'internalTechnician', 'spareParts']);

        $partidas = $service->spareParts->map(fn ($p) => [
            'item' => $p->nombre,
            'descripcion' => $p->refaccion?->subtype ?? $p->nombre,
            'unidad' => 'PIEZA',
            'cantidad' => $p->cantidad,
            'precio' => $p->precio_unitario,
        ]);

        if (($service->mano_obra ?? 0) > 0) {
            $partidas->push([
                'item' => 'Mano de obra',
                'descripcion' => 'Mantenimiento ' . ($datos['tipo_mantenimiento'] ?? 'externo'),
                'unidad' => 'SERVICIO',
                'cantidad' => 1,
                'precio' => $service->mano_obra,
            ]);
        }

        if ($partidas->isEmpty()) {
            $partidas->push(['item' => 'Partida 1', 'descripcion' => 'Mantenimiento ' . ($datos['tipo_mantenimiento'] ?? ''), 'unidad' => 'SERVICIO', 'cantidad' => 1, 'precio' => 0]);
        }

        $descripcionGeneral = strtoupper(
            'Mantenimiento ' . ($datos['tipo_mantenimiento'] ?? '')
            . ' a ' . ($datos['equipo'] ?? $service->serviceEquipment?->type_text ?? '')
            . ' ' . ($datos['marca'] ?? $service->serviceEquipment?->brand_text ?? '')
            . ' con número de serie ' . ($datos['serie'] ?? $service->serviceEquipment?->serial_number ?? '')
            . '. ' . implode('. ', $datos['acciones'] ?? [])
        );

        return view('structure.gestion_servicios.Servicio_Externo.OrdenServicioPaso4', compact('service', 'datos', 'partidas', 'descripcionGeneral'));
    }

    public function ordenServicioGenerar(Request $request, Service $service)
    {
        abort_unless($service->service_type === 'interno', 404);

        $validated = $request->validate([
            'fecha_ingreso' => 'required|date',
            'fecha_mantenimiento' => 'required|date',
            'equipo' => 'nullable|string|max:255',
            'marca' => 'nullable|string|max:255',
            'modelo' => 'nullable|string|max:255',
            'serie' => 'nullable|string|max:255',
            'observaciones' => 'nullable|string',
            'proximo_mantenimiento' => 'nullable|in:3,6,9,12',
            'tipo_mantenimiento' => 'nullable|in:correctivo,preventivo',
            'acciones' => 'nullable|array',
            'acciones.*' => 'string|max:255',
            'partidas' => 'nullable|array',
            'partidas.*.item' => 'nullable|string|max:255',
            'partidas.*.descripcion' => 'nullable|string|max:255',
            'partidas.*.unidad' => 'nullable|string|max:50',
            'partidas.*.cantidad' => 'nullable|numeric|min:0',
            'partidas.*.precio' => 'nullable|numeric|min:0',
            'envio' => 'nullable|numeric|min:0',
            'anticipo' => 'nullable|numeric|min:0',
            'iva' => 'nullable|boolean',
            'descripcion_general' => 'nullable|string',
        ]);

        $service->load(['customer', 'serviceEquipment', 'internalTechnician', 'externalTechnician', 'currentStep', 'spareParts']);

        $fechaIngreso = \Illuminate\Support\Carbon::parse($validated['fecha_ingreso']);
        $fechaMantenimiento = \Illuminate\Support\Carbon::parse($validated['fecha_mantenimiento']);
        $folio = $service->service_number ?? ('OS-' . $service->id);

        $partidas = collect($validated['partidas'] ?? [])->map(fn ($p) => [
            'item' => $p['item'] ?? '',
            'descripcion' => $p['descripcion'] ?? '',
            'unidad' => $p['unidad'] ?? 'SERVICIO',
            'cantidad' => (float) ($p['cantidad'] ?? 0),
            'precio' => (float) ($p['precio'] ?? 0),
            'importe' => (float) ($p['cantidad'] ?? 0) * (float) ($p['precio'] ?? 0),
        ])->filter(fn ($p) => $p['item'] !== '' || $p['descripcion'] !== '')->values();

        $remisionData = [
            'service' => $service,
            'partidas' => $partidas,
            'envio' => (float) ($validated['envio'] ?? 0),
            'anticipo' => (float) ($validated['anticipo'] ?? 0),
            'conIva' => $request->boolean('iva'),
            'descripcionGeneral' => $validated['descripcion_general'] ?? null,
        ];

        $dir = "documentos/{$service->id}";
        $remisionPath = "{$dir}/remision-{$folio}.pdf";
        $ordenPath = "{$dir}/orden-servicio-{$folio}.pdf";

        Storage::disk('public')->put(
            $remisionPath,
            Pdf::loadView('structure.gestion_servicios.Servicio_Externo.RemisionPdf', $remisionData)->output()
        );

        Storage::disk('public')->put(
            $ordenPath,
            Pdf::loadView('structure.gestion_servicios.Servicio_Externo.OrdenServicioPdf', array_merge(
                compact('service', 'fechaIngreso', 'fechaMantenimiento'),
                ['orden' => $validated]
            ))->output()
        );

        $documentos = $service->documentos ?? [];
        $documentos['remision'] = $remisionPath;
        $documentos['orden_servicio'] = $ordenPath;
        $documentos['generado_at'] = now()->toDateTimeString();
        $service->forceFill(['documentos' => $documentos])->save();

        return redirect()->route('gestion.servicios.servicio_externo.ver', $service)
            ->with('success', "Remisión y orden de servicio generadas y guardadas en {$folio}.");
    }

    public function ordenServicioPdf(Request $request, Service $service)
    {
        abort_unless($service->service_type === 'interno', 404);

        $validated = $request->validate([
            'fecha_ingreso' => 'required|date',
            'fecha_mantenimiento' => 'required|date',
            'equipo' => 'nullable|string|max:255',
            'marca' => 'nullable|string|max:255',
            'modelo' => 'nullable|string|max:255',
            'serie' => 'nullable|string|max:255',
            'observaciones' => 'nullable|string',
            'proximo_mantenimiento' => 'nullable|in:3,6,9,12',
            'tipo_mantenimiento' => 'nullable|in:correctivo,preventivo',
            'acciones' => 'nullable|array',
            'acciones.*' => 'string|max:255',
        ]);

        $service->load(['customer', 'serviceEquipment', 'internalTechnician', 'externalTechnician', 'currentStep']);

        $fechaIngreso = \Illuminate\Support\Carbon::parse($validated['fecha_ingreso']);
        $fechaMantenimiento = \Illuminate\Support\Carbon::parse($validated['fecha_mantenimiento']);

        $pdf = Pdf::loadView('structure.gestion_servicios.Servicio_Externo.OrdenServicioPdf', array_merge(
            compact('service', 'fechaIngreso', 'fechaMantenimiento'),
            ['orden' => $validated]
        ));

        return $pdf->download('orden-servicio-' . ($service->service_number ?? ('OS-' . $service->id)) . '.pdf');
    }

    public function finish(Service $service)
    {
        $service->serviceTrackings()->where('status', '!=', 'completado')->update(['status' => 'completado']);

        $service->update([
            'status' => 'entregado',
            'finished_at' => now(),
            'current_step_id' => null,
            'qr_token' => null,
            'qr_expires_at' => null,
        ]);

        return redirect()->route('gestion.servicios.historial')
            ->with('success', "Servicio {$service->service_number} finalizado. Pendiente de validación de OS.");
    }

    public function validarOs(Service $service)
    {
        $service->update(['status' => 'salida_retorno']);

        return back()->with('success', "OS {$service->service_number} validada. Estado: salida de retorno.");
    }

    public function enviarCliente(Service $service)
    {
        $service->update(['status' => 'finalizado']);

        return back()->with('success', "Servicio {$service->service_number} enviado a cliente. Estado: servicio finalizado.");
    }
}
