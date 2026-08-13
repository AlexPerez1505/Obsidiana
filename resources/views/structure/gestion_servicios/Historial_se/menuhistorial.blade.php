@extends('structure.gestion_servicios.layout')

@section('title', 'Historial de Servicios')

@section('service_content')
    <style>
        .service-page-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            flex-wrap: wrap;
            margin-bottom: 0;
        }
        .service-page-header .title-group h2 {
            margin: 0;
            font-size: 28px;
            color: #fff;
        }
        :root[data-theme="light"] .service-page-header .title-group h2 { color: var(--text); }
        .service-page-header .title-group p {
            margin: 4px 0 0;
            color: rgba(255,255,255,0.55);
            font-size: 14px;
        }
        :root[data-theme="light"] .service-page-header .title-group p { color: var(--muted); }
        .service-page-actions {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
        }
        .service-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 12px 20px;
            border: none;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 700;
            text-decoration: none;
            cursor: pointer;
            transition: background .16s ease, box-shadow .16s ease, transform .16s ease;
        }
        .service-btn:hover { transform: translateY(-1px); }
        .service-btn--green {
            background: #1677ff;
            color: #fff;
            box-shadow: 0 0 16px rgba(22,119,255, 0.7);
        }
        .service-btn--green:hover { background: #0e5ce0; box-shadow: 0 0 22px rgba(22,119,255, 0.85); }
        .service-btn--blue {
            background: linear-gradient(135deg, #007AFF, #6366F1);
            color: #fff;
            box-shadow: 0 0 16px rgba(0,122,255,0.35);
        }
        .service-btn--blue:hover { background: linear-gradient(135deg, #005FCC, #4F46E5); box-shadow: 0 0 22px rgba(0,122,255,0.5); }
        .service-btn svg { width: 18px; height: 18px; }
        .service-tabs {
            display: flex;
            gap: 8px;
            margin-top: 20px;
            border-bottom: 1px solid rgba(22, 119, 255, 0.6);
        }
        .service-tab {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 18px;
            border: none;
            background: transparent;
            color: rgba(255, 255, 255, 0.6);
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            border-bottom: 2px solid transparent;
            transition: color .16s ease, border-color .16s ease;
        }
        .service-tab:hover { color: #fff; }
        .service-tab.active { color: #1677ff; border-bottom-color: #1677ff; }
        .service-tab svg { width: 18px; height: 18px; }
        :root[data-theme="light"] .service-tab { color: var(--muted); }
        :root[data-theme="light"] .service-tab.active { color: var(--primary); border-bottom-color: var(--primary); }
        .service-table-empty {
            padding: 36px 14px;
            text-align: center;
            color: rgba(255,255,255,0.55);
            font-size: 14px;
        }
        :root[data-theme="light"] .service-table-empty { color: var(--muted); }

        /* ===== Volumen visual: elevation y glow ===== */
        .service-page-header {
            padding-bottom: 16px;
            border-bottom: 1px solid rgba(22, 119, 255, 0.45);
        }

        .service-btn {
            box-shadow:
                0 8px 22px rgba(31, 105, 255, 0.25),
                inset 0 1px 0 rgba(255, 255, 255, 0.08);
        }
        .service-btn:hover {
            transform: translateY(-1px);
        }

        .service-btn--green, .service-btn--blue {
            transition: all 0.2s ease;
        }
        .service-btn--green {
            background: linear-gradient(135deg, #1677ff, #3b8cff);
            box-shadow:
                0 8px 22px rgba(31, 105, 255, 0.25),
                0 0 18px rgba(22, 119, 255, 0.5),
                inset 0 1px 0 rgba(255, 255, 255, 0.10);
        }
        .service-btn--green:hover {
            background: linear-gradient(135deg, #0e5ce0, #2a75ff);
            box-shadow:
                0 10px 28px rgba(31, 105, 255, 0.40),
                0 0 24px rgba(22, 119, 255, 0.6),
                inset 0 1px 0 rgba(255, 255, 255, 0.12);
        }
        .service-btn--blue {
            background: linear-gradient(135deg, #1677ff, #635bff);
            box-shadow:
                0 8px 22px rgba(99, 91, 255, 0.6),
                0 0 18px rgba(99, 91, 255, 0.5),
                inset 0 1px 0 rgba(255, 255, 255, 0.10);
        }
        .service-btn--blue:hover {
            background: linear-gradient(135deg, #0e5ce0, #4f46e5);
            box-shadow:
                0 10px 28px rgba(99, 91, 255, 0.75),
                0 0 24px rgba(99, 91, 255, 0.6),
                inset 0 1px 0 rgba(255, 255, 255, 0.12);
        }

        .service-tabs {
            background: rgba(5, 11, 24, 0.55);
            border: 1px solid rgba(22, 119, 255, 0.47);
            border-radius: 14px;
            padding: 5px;
            gap: 6px;
            margin-top: 12px;
            box-shadow:
                inset 0 1px 3px rgba(0, 0, 0, 0.35),
                0 6px 16px rgba(0, 0, 0, 0.25);
        }
        .service-tab {
            flex: 1;
            justify-content: center;
            border-bottom: none;
            border-radius: 10px;
            padding: 12px 16px;
            background: transparent;
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
    </style>

    <div class="catalog-card service-section">
        <div class="service-page-header">
            <div class="title-group">
                <h2>Historial de Servicios</h2>
                <p>Consulta y da seguimiento a los servicios realizados.</p>
            </div>
            <div class="service-page-actions">
                <a href="{{ route('gestion.servicios.aprobaciones') }}" class="service-btn service-btn--green">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
                    Aprobaciones
                </a>
                <a href="{{ route('gestion.servicios.nuevo') }}" class="service-btn service-btn--blue">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    Nuevo Servicio
                </a>
            </div>
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
    </div>

    <div class="catalog-card service-section">
        <div class="service-table-wrap">
            <table class="service-table">
                <thead>
                    <tr>
                        <th>SERVICIO</th>
                        <th>CLIENTE</th>
                        <th>TIPO DE TÉCNICO</th>
                        <th>ESTADO</th>
                        <th>TOTAL</th>
                        <th>FECHA</th>
                        <th>ACCIONES</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($services as $service)
                        @php
                            $customerName = trim(($service->customer->nombre ?? '') . ' ' . ($service->customer->apellido ?? ''));
                            $techName = trim(($service->externalTechnician->nombre ?? '') . ' ' . ($service->externalTechnician->apellidos ?? ''));
                        @endphp
                        <tr data-type="{{ $service->service_type }}">
                            <td>{{ $service->service_number ?? 'N/A' }}</td>
                            <td>{{ $customerName ?: 'N/A' }}</td>
                            <td>{{ $techName ?: 'N/A' }}</td>
                            <td>{{ ucfirst($service->status) }}</td>
                            <td>N/A</td>
                            <td>{{ $service->created_at?->format('d/m/Y H:i') }}</td>
                            <td>
                                <a href="{{ $service->service_type === 'interno' ? route('gestion.servicios.nuevo.interno.resumen', $service) : route('gestion.servicios.nuevo.externo.resumen', $service) }}" class="service-action-btn" title="Ver resumen">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                </a>
                                <a href="{{ $service->service_type === 'interno' ? route('gestion.servicios.ruta.interno', $service) : route('gestion.servicios.ruta', $service) }}" class="service-action-btn" title="Ver ruta de trabajo">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="service-table-empty">No hay servicios registrados.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <script>
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
