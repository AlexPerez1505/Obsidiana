@extends('structure.gestion_servicios.layout')

@section('title', 'Aprobaciones de Servicios')

@section('service_content')
    <style>
        .service-page-header {
            display: flex; align-items: center; justify-content: space-between; gap: 16px;
            flex-wrap: wrap; margin-bottom: 24px;
        }
        .service-page-header .title-group h2 {
            margin: 0; font-size: 28px; color: #fff;
        }
        :root[data-theme="light"] .service-page-header .title-group h2 { color: var(--text); }
        .service-page-header .title-group p {
            margin: 4px 0 0; color: rgba(255,255,255,0.55); font-size: 14px;
        }
        :root[data-theme="light"] .service-page-header .title-group p { color: var(--muted); }

        .modal-overlay {
            display: none; position: fixed; inset: 0; z-index: 1000;
            background: rgba(0,0,0,0.7); align-items: center; justify-content: center;
        }
        .modal-overlay.open { display: flex; }
        .modal-box {
            background: #0b1a35; border: 1px solid rgba(34,197,94,0.55); border-radius: 16px;
            padding: 28px; width: 90%; max-width: 360px; text-align: center;
            box-shadow: 0 12px 40px rgba(0,0,0,0.5);
        }
        :root[data-theme="light"] .modal-box { background: #fff; border-color: rgba(15,23,42,0.14); }
        .modal-box h3 { margin: 0 0 10px; color: #fff; font-size: 18px; }
        :root[data-theme="light"] .modal-box h3 { color: var(--text); }
        .modal-box p { margin: 0 0 18px; color: rgba(255,255,255,0.7); font-size: 13px; }
        :root[data-theme="light"] .modal-box p { color: var(--muted); }
        .modal-code {
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            font-size: 42px; font-weight: 800; color: #22C55E; letter-spacing: 6px;
            margin-bottom: 22px;
        }
        .modal-actions { display: flex; gap: 10px; justify-content: center; }
        .modal-btn {
            padding: 10px 16px; border-radius: 10px; border: none; font-size: 13px; font-weight: 700;
            cursor: pointer; text-decoration: none;
        }
        .modal-btn--primary { background: #22C55E; color: #fff; }
        .modal-btn--ghost { background: transparent; border: 1px solid rgba(255,255,255,0.2); color: #fff; }
        :root[data-theme="light"] .modal-btn--ghost { border-color: rgba(15,23,42,0.2); color: var(--text); }

        .service-tabs {
            display: flex;
            gap: 6px;
            margin-top: 12px;
            background: rgba(5, 11, 24, 0.55);
            border: 1px solid rgba(22, 119, 255, 0.47);
            border-radius: 14px;
            padding: 5px;
            box-shadow:
                inset 0 1px 3px rgba(0, 0, 0, 0.35),
                0 6px 16px rgba(0, 0, 0, 0.25);
        }
        .service-tab {
            flex: 1;
            justify-content: center;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 16px;
            border: none;
            border-bottom: none;
            border-radius: 10px;
            background: transparent;
            color: rgba(255, 255, 255, 0.6);
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            transition: all .16s ease;
            box-shadow: none;
        }
        .service-tab:hover {
            background: rgba(22, 119, 255, 0.43);
            color: #fff;
        }
        .service-tab.active {
            color: #fff;
            background: linear-gradient(135deg, rgba(22, 119, 255, 0.57), rgba(99, 91, 255, 0.51));
            border: 1px solid rgba(30, 125, 255, 0.9);
            box-shadow:
                0 0 20px rgba(30, 125, 255, 0.47),
                0 10px 35px rgba(0, 0, 0, 0.35),
                inset 0 1px 0 rgba(255, 255, 255, 0.08);
        }
        .service-tab svg { width: 18px; height: 18px; }
    </style>

    @if (session('success'))
        <div class="catalog-card" style="margin-bottom: 20px; color: #22C55E;">
            {{ session('success') }}
        </div>
    @endif
    @if (session('error'))
        <div class="catalog-card" style="margin-bottom: 20px; color: #ff4a4a;">
            {{ session('error') }}
        </div>
    @endif

    <div class="catalog-card service-section">
        <div class="service-page-header">
            <div class="title-group">
                <h2>Aprobaciones</h2>
                <p>Servicios pendientes y aprobados por autoridades.</p>
            </div>
            <a href="{{ route('gestion.servicios.validaciones.os') }}" class="catalog-create" style="width:auto; padding: 9px 16px; margin:0;">
                Validaciones OS
            </a>
        </div>

        <div class="service-tabs">
            <button type="button" class="service-tab active" data-type="all">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3h18v18H3zM3 9h18M9 21V9"/></svg>
                Todos
            </button>
            <button type="button" class="service-tab" data-type="interno">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                Servicio Interno
            </button>
            <button type="button" class="service-tab" data-type="externo">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                Servicio Externo
            </button>
        </div>

        <div class="service-table-wrap">
            <table class="service-table">
                <thead>
                    <tr>
                        <th>SERVICIO</th>
                        <th>PASO</th>
                        <th>CLIENTE</th>
                        <th>TÉCNICO</th>
                        <th>EQUIPO</th>
                        <th>ESTADO</th>
                        <th>ACCIONES</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($trackings as $tracking)
                        @php
                            $service = $tracking->service;
                            $customerName = trim(($service->customer->nombre ?? '') . ' ' . ($service->customer->apellido ?? ''));
                            $techName = trim(($service->externalTechnician->nombre ?? '') . ' ' . ($service->externalTechnician->apellidos ?? ''));
                            $equipment = $service->serviceEquipment;
                            $codeTracking = $service->serviceTrackings->firstWhere('serviceStep.slug', 'notificacion-llegada-tecnico');
                            $verificationCode = $codeTracking?->verification_code;
                        @endphp
                        <tr data-type="{{ $service->service_type }}">
                            <td>{{ $service->service_number ?? 'N/A' }}</td>
                            <td>{{ $tracking->serviceStep->name ?? 'N/A' }}</td>
                            <td>{{ $customerName ?: 'N/A' }}</td>
                            <td>{{ $techName ?: 'N/A' }}</td>
                            <td>{{ $equipment->type_text ?? 'N/A' }} {{ $equipment->brand_text ?? '' }}</td>
                            <td>
                                @if ($tracking->status === 'completado')
                                    <span class="service-badge active">Aprobado</span>
                                @else
                                    <span class="service-badge upcoming">Pendiente</span>
                                @endif
                            </td>
                            <td>
                                @if ($tracking->status === 'pendiente')
                                    <form action="{{ route('gestion.servicios.tracking.aprobar', $tracking) }}" method="POST" style="display:inline;">
                                        @csrf
                                        <button type="submit" class="service-action-btn" title="Aprobar">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                                        </button>
                                    </form>
                                @elseif ($verificationCode)
                                    <button type="button" class="service-action-btn" title="Ver código de verificación" onclick="showCode('{{ $verificationCode }}')">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                    </button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="service-table-empty">No hay servicios pendientes de aprobación.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div id="codeModal" class="modal-overlay" onclick="closeCode(event)">
        <div class="modal-box" onclick="event.stopPropagation()">
            <h3>Código de verificación</h3>
            <p>Comparte este código con el técnico externo para el paso de llegada.</p>
            <div class="modal-code" id="modalCode">----</div>
            <div class="modal-actions">
                <button type="button" class="modal-btn modal-btn--primary" onclick="copyCode()">Copiar</button>
                <button type="button" class="modal-btn modal-btn--ghost" onclick="closeCode()">Cerrar</button>
            </div>
        </div>
    </div>

    <script>
        function showCode(code) {
            const modal = document.getElementById('codeModal');
            document.getElementById('modalCode').textContent = code;
            modal.classList.add('open');
        }

        function closeCode(event) {
            if (event && event.target !== document.getElementById('codeModal')) return;
            document.getElementById('codeModal').classList.remove('open');
        }

        function copyCode() {
            const code = document.getElementById('modalCode').textContent.trim();
            navigator.clipboard.writeText(code).catch(() => {});
        }

        document.addEventListener('DOMContentLoaded', function () {
            const tabs = document.querySelectorAll('.service-tab');
            const rows = document.querySelectorAll('.service-table tbody tr[data-type]');

            tabs.forEach(function (tab) {
                tab.addEventListener('click', function () {
                    tabs.forEach(function (t) { t.classList.remove('active'); });
                    tab.classList.add('active');

                    const type = tab.dataset.type;
                    rows.forEach(function (row) {
                        row.style.display = (type === 'all' || row.dataset.type === type) ? '' : 'none';
                    });
                });
            });
        });
    </script>
@endsection
