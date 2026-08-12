@php
    $modoPreview = $modo_preview ?? false;
    $modoVer = $modo_ver ?? false;
    $hasService = isset($service) && $service instanceof \App\Models\Service;
    $isAdmin = auth()->check() && auth()->user()->isAdmin();

    // Cargar los pasos reales de la BD
    $defaultSteps = [];
    $serviceType = $service->service_type ?? 'externo';
    
    $allSteps = \App\Models\ServiceStep::where('service_type', $serviceType)
        ->orderBy('order')
        ->get();
    
    foreach ($allSteps as $step) {
        $defaultSteps[] = [
            'name' => $step->name,
            'slug' => str_replace('_', '-', $step->slug),
            'status' => 'pendiente',
        ];
    }

    $serviceSteps = collect();
    $tracksBySlug = [];
    $completedSteps = [];
    $currentStepNumber = null;

    if ($hasService) {
        $serviceSteps = \App\Models\ServiceStep::where('service_type', 'externo')->get()->keyBy('slug');
        
        // Mapear trackings por slug del paso - Forzar recarga desde BD
        $service->refresh();
        $service->load(['serviceTrackings' => function ($query) {
            $query->with('serviceStep')->orderBy('created_at');
        }]);
        
        if ($service->serviceTrackings && count($service->serviceTrackings) > 0) {
            foreach ($service->serviceTrackings as $tracking) {
                // Cargar serviceStep si no está cargado
                if (!$tracking->serviceStep) {
                    $tracking->load('serviceStep');
                }
                
                if ($tracking->serviceStep && $tracking->serviceStep->slug) {
                    // Normalizar slug: convertir guiones bajos a guiones
                    $normalizedSlug = str_replace('_', '-', $tracking->serviceStep->slug);
                    $tracksBySlug[$normalizedSlug] = $tracking;
                }
            }
        }

        // Recopilar todos los pasos completados (sin importar el orden)
        foreach ($defaultSteps as $index => $step) {
            $slug = $step['slug'];
            $track = $tracksBySlug[$slug] ?? null;

            // Si hay un tracking para este paso y está completado, agregarlo
            if ($track && $track->status === 'completado') {
                $completedSteps[] = $index + 1;
            }
        }

        


        $currentSlug = $service->currentStep?->slug ?? null;
        // Normalizar el slug actual también
        if ($currentSlug) {
            $currentSlug = str_replace('_', '-', $currentSlug);
        }
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

                $track = $tracksBySlug[$stepSlug] ?? null;
                $rawStatus = $track?->status;

                $isCurrent = $hasService && $currentStepNumber === $stepNumber;
                $isCompleted = $hasService && in_array($stepNumber, $completedSteps);
                $isRejected = $track?->status === 'rechazado';

                $display = match (true) {
                    !$hasService => $step['status'],
                    $isRejected => 'rechazado',
                    $isCompleted => 'completado',
                    $isCurrent => 'activo',
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
                    && ($track->serviceStep?->requires_approval ?? false)
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
