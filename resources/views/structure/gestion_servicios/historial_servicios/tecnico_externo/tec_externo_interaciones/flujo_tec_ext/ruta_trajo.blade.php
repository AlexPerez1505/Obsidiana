@php
    $modoPreview = $modo_preview ?? false;
    $modoVer = $modo_ver ?? false;
    $hasService = isset($service) && $service instanceof \App\Models\Service;
    $isAdmin = auth()->check() && auth()->user()->isAdmin();

    $defaultSteps = [
        ['name' => 'Registro de servicio', 'slug' => 'registro', 'status' => 'completado'],
        ['name' => 'Llenado de informacion de equipo', 'slug' => 'llenado_informacion', 'status' => 'completado'],
        ['name' => 'Generacion de QR', 'slug' => 'generacion_qr', 'status' => 'activo'],
        ['name' => 'Validacion de Nuevo servicio', 'slug' => 'validacion_os', 'status' => 'pendiente'],
        ['name' => 'Entrada del equipo', 'slug' => 'entrada_equipo', 'status' => 'pendiente'],
        ['name' => 'Salida foranea', 'slug' => 'salida_foranea', 'status' => 'pendiente'],
        ['name' => 'Notificacion de llegada del tecnico externo', 'slug' => 'notificacion_llegada', 'status' => 'pendiente'],
        ['name' => 'Llenado de mantenimiento', 'slug' => 'llenado_mantenimiento', 'status' => 'pendiente'],
        ['name' => 'Notificacion de finalizado de mantenimiento externo', 'slug' => 'notificacion_finalizado', 'status' => 'pendiente'],
        ['name' => 'Regreso foranea', 'slug' => 'regreso_foranea', 'status' => 'pendiente'],
        ['name' => 'Generacion de OS por parte de Victor', 'slug' => 'generacion_os', 'status' => 'pendiente'],
        ['name' => 'Salida para cliente', 'slug' => 'salida_cliente', 'status' => 'pendiente'],
        ['name' => 'Escaneo antes de salir con el cliente', 'slug' => 'escaneo_salida', 'status' => 'pendiente'],
        ['name' => 'Cliente feliz', 'slug' => 'cliente_feliz', 'status' => 'pendiente'],
    ];

    $serviceSteps = collect();
    $tracksBySlug = [];
    $maxCompletedNumber = 0;
    $currentStepNumber = null;

    if ($hasService) {
        $serviceSteps = \App\Models\ServiceStep::where('service_type', 'externo')->get()->keyBy('slug');
        $tracksBySlug = $service->serviceTrackings
            ->mapWithKeys(fn ($t) => [($t->serviceStep?->slug ?? uniqid()) => $t])
            ->all();

        foreach ($defaultSteps as $index => $step) {
            $slug = $step['slug'];
            $realStep = $serviceSteps[$slug] ?? null;
            $track = $realStep ? ($tracksBySlug[$slug] ?? null) : null;

            if ($track?->status === 'completado') {
                $maxCompletedNumber = $index + 1;
            }
        }

        $currentSlug = $service->currentStep?->slug ?? null;
        foreach ($defaultSteps as $index => $step) {
            if ($step['slug'] === $currentSlug) {
                $currentStepNumber = $index + 1;
                break;
            }
        }
    }
@endphp

<style>
    /* Contenedor vertical con scroll para mostrar ~4 pasos a la vez */
    .ruta-vertical {
        display: flex;
        flex-direction: column;
        gap: 10px;
        max-height: 320px;
        overflow-y: auto;
        padding-right: 4px;
        scrollbar-width: thin;
    }
    .ruta-vertical::-webkit-scrollbar {
        width: 6px;
    }
    .ruta-vertical::-webkit-scrollbar-track {
        background: var(--surface-2);
        border-radius: 4px;
    }
    .ruta-vertical::-webkit-scrollbar-thumb {
        background: var(--border);
        border-radius: 4px;
    }
    .resumen-step--rechazado {
        border-color: var(--danger) !important;
        background: var(--danger-soft);
    }
    .ruta-step-actions {
        display: flex;
        gap: 6px;
        margin-left: auto;
    }
    .ruta-step-actions .btn--sm {
        padding: 4px 10px;
        font-size: 12px;
        border-radius: 6px;
        border: none;
        cursor: pointer;
        font-weight: 700;
    }
</style>

<div class="resumen-card" id="ruta-trabajo-externo" @if($wide ?? false) style="grid-column:1/-1;" @endif>
    <h3 class="resumen-title">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="3 11 22 2 13 21 11 13 3 11"/></svg>
        Ruta de Trabajo
    </h3>

    <div class="ruta-vertical" id="ruta-pasos">
        @foreach($defaultSteps as $index => $step)
            @php
                $stepNumber = $index + 1;
                $stepName = $step['name'];
                $stepSlug = $step['slug'];

                $realStep = $serviceSteps[$stepSlug] ?? null;
                $track = $realStep ? ($tracksBySlug[$stepSlug] ?? null) : null;

                $rawStatus = $track?->status;

                $isCurrent = $hasService && $currentStepNumber === $stepNumber;
                $isCompleted = $hasService && $stepNumber <= $maxCompletedNumber;
                $isRejected = $track?->status === 'rechazado';

                $display = match (true) {
                    !$hasService => $step['status'],
                    $isRejected => 'rechazado',
                    $isCurrent => 'activo',
                    $isCompleted => 'completado',
                    default => 'pendiente',
                };

                $stateClass = match ($display) {
                    'completado' => 'resumen-step--done',
                    'activo' => 'resumen-step--active',
                    'rechazado' => 'resumen-step--rechazado',
                    default => 'resumen-step--pending',
                };

                $statusColor = match ($display) {
                    'completado' => 'var(--green)',
                    'activo' => 'var(--primary)',
                    'rechazado' => 'var(--danger)',
                    default => 'var(--muted)',
                };

                $statusText = match ($display) {
                    'completado' => 'COMPLETADO',
                    'activo' => 'EN PROCESO',
                    'rechazado' => 'RECHAZADO',
                    default => 'PENDIENTE',
                };

                $canValidate = !$modoVer
                    && $isAdmin
                    && $track
                    && ($realStep?->requires_approval ?? false)
                    && in_array($rawStatus, ['pendiente', 'en_progreso', 'rechazado']);
            @endphp

            {{-- Cada tarjeta representa un paso del flujo externo --}}
            <div class="resumen-step {{ $stateClass }}" data-step-index="{{ $stepNumber }}">
                <div class="resumen-step-icon">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        @if($display === 'completado')
                            {{-- Icono de check para pasos completados --}}
                            <path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>
                        @elseif($display === 'activo')
                            {{-- Icono de maletin para el paso activo --}}
                            <rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/>
                        @elseif($display === 'rechazado')
                            {{-- Icono de X para pasos rechazados --}}
                            <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                        @else
                            {{-- Icono de reloj para pasos pendientes --}}
                            <circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/>
                        @endif
                    </svg>
                </div>
                <div class="resumen-step-body" style="flex:1;">
                    <div class="resumen-step-name">Paso {{ $stepNumber }}: {{ $stepName }}</div>
                    <div class="resumen-step-status" style="color:{{ $statusColor }};">{{ $statusText }}</div>
                </div>
                @if($canValidate)
                    <div class="ruta-step-actions">
                        <form method="POST" action="{{ route('service-tracking.approve', $track) }}">
                            @csrf
                            <button type="submit" class="btn--sm" style="background:var(--green); color:#fff;">Aprobar</button>
                        </form>
                        <form method="POST" action="{{ route('service-tracking.reject', $track) }}">
                            @csrf
                            <button type="submit" class="btn--sm" style="background:var(--danger); color:#fff;">Rechazar</button>
                        </form>
                    </div>
                @elseif(!$modoVer && $track?->qr_token)
                    <a href="{{ route('qr.show', $track->qr_token) }}" target="_blank" class="btn btn--ghost" style="padding:6px 12px; font-size:12px;">QR</a>
                @endif
            </div>
        @endforeach
    </div>
</div>
