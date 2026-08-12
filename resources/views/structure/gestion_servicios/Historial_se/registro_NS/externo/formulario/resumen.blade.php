@extends('structure.gestion_servicios.layout')

@section('title', 'Resumen de Orden')

@section('service_content')
    <style>
        .ns-page { max-width: 1100px; margin: 0 auto; }

        .ns-header {
            display: flex; align-items: center; justify-content: space-between; gap: 16px;
            flex-wrap: wrap; margin-bottom: 22px;
        }
        .ns-header-title { display: flex; align-items: center; gap: 14px; }
        .ns-icon {
            width: 48px; height: 48px; border-radius: 12px;
            background: rgba(0,122,255,0.12); color: #007AFF;
            display: flex; align-items: center; justify-content: center; flex-shrink: 0;
        }
        .ns-icon svg { width: 24px; height: 24px; }
        .ns-header-title h2 { margin: 0; font-size: 22px; color: #fff; }
        :root[data-theme="light"] .ns-header-title h2 { color: var(--text); }
        .ns-header-title p { margin: 4px 0 0; color: rgba(255,255,255,0.55); font-size: 13px; }
        :root[data-theme="light"] .ns-header-title p { color: var(--muted); }
        .ns-header-actions { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
        .ns-btn {
            display: inline-flex; align-items: center; justify-content: center; gap: 6px;
            padding: 10px 16px; border-radius: 10px; font-size: 13px; font-weight: 700;
            text-decoration: none; cursor: pointer; transition: all .16s ease; border: none;
        }
        .ns-btn svg { width: 16px; height: 16px; }
        .ns-btn--ghost { border: 1px solid rgba(255,255,255,0.12); background: transparent; color: #007AFF; }
        :root[data-theme="light"] .ns-btn--ghost { border-color: rgba(15,23,42,0.14); color: #007AFF; }
        .ns-btn--ghost:hover { border-color: #007AFF; background: rgba(0,122,255,0.08); }
        .ns-btn--primary { background: #007AFF; color: #fff; box-shadow: 0 0 14px rgba(0,122,255,0.35); }
        .ns-btn--primary:hover { background: #005FCC; box-shadow: 0 0 20px rgba(0,122,255,0.5); }
        .ns-btn--success { background: #22C55E; color: #fff; box-shadow: 0 0 14px rgba(34,197,94,0.35); }
        .ns-btn--success:hover { background: #16A34A; box-shadow: 0 0 20px rgba(34,197,94,0.5); }

        .ns-stepper {
            display: flex; align-items: center; gap: 12px;
            margin-bottom: 26px; padding-bottom: 20px;
            border-bottom: 1px solid rgba(255,255,255,0.08);
        }
        :root[data-theme="light"] .ns-stepper { border-color: rgba(15,23,42,0.08); }
        .ns-step { display: flex; align-items: center; gap: 10px; font-size: 14px; font-weight: 700; color: rgba(255,255,255,0.45); }
        :root[data-theme="light"] .ns-step { color: var(--muted); }
        .ns-step-number {
            width: 28px; height: 28px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 12px; font-weight: 800;
            background: rgba(255,255,255,0.08); color: rgba(255,255,255,0.55);
        }
        :root[data-theme="light"] .ns-step-number { background: rgba(15,23,42,0.08); color: var(--muted); }
        .ns-step.completed { color: #22C55E; }
        .ns-step.completed .ns-step-number { background: #22C55E; color: #fff; }
        .ns-step-line { flex: 1; height: 1px; min-width: 30px; background: rgba(255,255,255,0.08); }
        :root[data-theme="light"] .ns-step-line { background: rgba(15,23,42,0.08); }

        .ns-grid {
            display: grid; grid-template-columns: 1fr 1fr; gap: 20px;
            align-items: start;
        }
        @media (max-width: 860px) { .ns-grid { grid-template-columns: 1fr; } }

        .ns-card {
            background: rgba(8,18,40,0.45);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 16px;
            padding: 22px;
        }
        :root[data-theme="light"] .ns-card { background: rgba(15,23,42,0.04); border-color: rgba(15,23,42,0.1); }
        .ns-card-title {
            display: flex; align-items: center; gap: 10px;
            font-size: 15px; font-weight: 800; color: #fff; margin-bottom: 18px;
        }
        :root[data-theme="light"] .ns-card-title { color: var(--text); }
        .ns-card-title svg { width: 18px; height: 18px; color: #007AFF; }

        .ns-alert {
            display: flex; align-items: flex-start; gap: 10px;
            background: rgba(0,122,255,0.12);
            border: 1px solid rgba(0,122,255,0.2);
            border-radius: 12px;
            padding: 14px;
            font-size: 13px; color: #4aa3ff;
            margin-bottom: 16px;
        }
        .ns-alert svg { width: 18px; height: 18px; color: #007AFF; flex-shrink: 0; margin-top: 1px; }

        .ns-actions-row { display: flex; align-items: center; gap: 10px; margin-bottom: 22px; }
        .ns-actions-row .ns-btn { flex: 1; }

        .ns-qr-area { text-align: center; margin-bottom: 22px; }
        .ns-qr-label { font-size: 12px; color: rgba(255,255,255,0.55); margin-bottom: 10px; }
        :root[data-theme="light"] .ns-qr-label { color: var(--muted); }
        .ns-qr-img {
            width: 180px; height: 180px;
            border-radius: 12px;
            background: #fff;
            padding: 10px;
            margin: 0 auto 10px;
            display: block;
        }
        .ns-qr-token {
            font-size: 11px; color: rgba(255,255,255,0.45);
            word-break: break-all; margin-bottom: 14px;
        }
        :root[data-theme="light"] .ns-qr-token { color: var(--muted); }
        .ns-qr-buttons { display: flex; align-items: center; justify-content: center; gap: 10px; }

        .ns-bottom-actions { display: flex; align-items: center; gap: 10px; }
        .ns-bottom-actions .ns-btn { flex: 1; }

        .ns-specs { display: flex; flex-direction: column; gap: 2px; }
        .ns-spec-row {
            display: flex; align-items: center; justify-content: space-between; gap: 12px;
            padding: 14px 0;
            border-bottom: 1px solid rgba(255,255,255,0.06);
        }
        :root[data-theme="light"] .ns-spec-row { border-color: rgba(15,23,42,0.08); }
        .ns-spec-row:last-child { border-bottom: none; }
        .ns-spec-row span:first-child {
            font-size: 11px; font-weight: 700; color: rgba(255,255,255,0.6); letter-spacing: 0.5px;
        }
        :root[data-theme="light"] .ns-spec-row span:first-child { color: var(--muted); }
        .ns-spec-row span:last-child {
            font-size: 13px; color: #fff; text-align: right;
        }
        :root[data-theme="light"] .ns-spec-row span:last-child { color: var(--text); }
        .ns-status-pending {
            display: inline-flex; align-items: center; gap: 6px;
            color: #F59E0B !important; font-weight: 700;
        }
        .ns-status-pending svg { width: 14px; height: 14px; }
    </style>

    @php
        $customerName = trim(($service->customer->nombre ?? '') . ' ' . ($service->customer->apellido ?? ''));
        $techName = trim(($service->externalTechnician->nombre ?? '') . ' ' . ($service->externalTechnician->apellidos ?? ''));
    @endphp

    <div class="ns-page">
        <div class="ns-header">
            <div class="ns-header-title">
                <div class="ns-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                </div>
                <div>
                    <h2>Resumen de Orden</h2>
                    <p>Revisa la información antes de guardar</p>
                </div>
            </div>
            <div class="ns-header-actions">
                <a href="{{ route('gestion.servicios.historial') }}" class="ns-btn ns-btn--primary">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                    Salir
                </a>
            </div>
        </div>

        <div class="ns-stepper">
            <div class="ns-step completed">
                <div class="ns-step-number">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" width="12" height="12"><polyline points="20 6 9 17 4 12"/></svg>
                </div>
                <span>Cliente</span>
            </div>
            <div class="ns-step-line"></div>
            <div class="ns-step completed">
                <div class="ns-step-number">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" width="12" height="12"><polyline points="20 6 9 17 4 12"/></svg>
                </div>
                <span>Equipo</span>
            </div>
            <div class="ns-step-line"></div>
            <div class="ns-step completed">
                <div class="ns-step-number">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" width="12" height="12"><polyline points="20 6 9 17 4 12"/></svg>
                </div>
                <span>Tecnico</span>
            </div>
        </div>

        <div class="ns-grid">
            <div class="ns-card">
                <div class="ns-card-title">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>
                    Acción Requerida
                </div>

                <div class="ns-alert">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
                    <span>Registro protegido. Requiere captura via formulario QR para asegurar identidad y firmas.</span>
                </div>

                <div class="ns-actions-row">
                    <a href="{{ $qrUrl }}" target="_blank" class="ns-btn ns-btn--primary">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                        QR generado
                    </a>
                    <button type="button" class="ns-btn ns-btn--ghost" onclick="window.print()">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
                        Imprimir
                    </button>
                </div>

                <div class="ns-qr-area">
                    <p class="ns-qr-label">QR generado:</p>
                    @include('structure.gestion_servicios.Historial_se.registro_NS.externo.qr.qr', ['qrUrl' => $qrUrl])
                    <p class="ns-qr-token">{{ $service->qr_token }}</p>
                    <div class="ns-qr-buttons">
                        <a href="#" id="downloadQrBtn" download="qr-{{ $service->service_number }}.png" class="ns-btn ns-btn--primary">Descargar QR</a>
                        <a href="{{ $qrUrl }}" target="_blank" class="ns-btn ns-btn--ghost">Abrir enlace</a>
                    </div>
                </div>

                <script>
                    (function() {
                        const qrImg = document.getElementById('qrImage');
                        const downloadBtn = document.getElementById('downloadQrBtn');
                        if (qrImg && downloadBtn) {
                            qrImg.addEventListener('load', function() {
                                downloadBtn.href = qrImg.src;
                            });
                            if (qrImg.src && qrImg.src !== window.location.href) {
                                downloadBtn.href = qrImg.src;
                            }
                        }
                    })();
                </script>

                <div class="ns-bottom-actions">
                    <a href="{{ route('gestion.servicios.historial') }}" class="ns-btn ns-btn--ghost">Ver orden</a>
                    <a href="{{ route('gestion.servicios.historial') }}" class="ns-btn ns-btn--ghost">Aprobaciones</a>
                </div>
            </div>

            <div class="ns-card">
                <div class="ns-card-title">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
                    Ficha Técnica del Servicio
                </div>

                <div class="ns-specs">
                    <div class="ns-spec-row">
                        <span>IDENTIFICACIÓN</span>
                        <span>{{ $service->serviceEquipment->type_text ?? 'N/A' }} | {{ $service->serviceEquipment->subtype_text ?? 'N/A' }}</span>
                    </div>
                    <div class="ns-spec-row">
                        <span>NO. DE SERIE</span>
                        <span>{{ $service->serviceEquipment->serial_number ?? 'N/A' }}</span>
                    </div>
                    <div class="ns-spec-row">
                        <span>MARCA / MODELO</span>
                        <span>{{ trim(($service->serviceEquipment->brand_text ?? '') . ' ' . ($service->serviceEquipment->model_text ?? '')) ?: 'N/A' }}</span>
                    </div>
                    <div class="ns-spec-row">
                        <span>MÉDICO / TITULAR</span>
                        <span>{{ $customerName ?: 'N/A' }}</span>
                    </div>
                    <div class="ns-spec-row">
                        <span>RESPONSABLE</span>
                        <span>{{ $techName ?: 'N/A' }}</span>
                    </div>
                    <div class="ns-spec-row">
                        <span>VALIDACIÓN OS</span>
                        <span class="ns-status-pending">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                            Pendiente
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
