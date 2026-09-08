@extends('structure.gestion_servicios.layout')

@section('title', 'Reporte ' . ($service->service_number ?? 'OS-'.$service->id))

@section('service_content')
    <style>
        html, body {
            scrollbar-width: none;
        }
        ::-webkit-scrollbar {
            width: 0;
            height: 0;
            display: none;
        }
        .reporte-card {
            padding: 0;
            overflow: hidden;
            background: transparent;
            border: 0;
            box-shadow: none;
            backdrop-filter: none;
            -webkit-backdrop-filter: none;
            border-radius: 0;
        }
        .reporte-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
            padding: 6px 2px 12px;
        }
        .reporte-head h2 {
            margin: 0;
            font-size: 17px;
            color: #fff;
        }
        .reporte-head small {
            display: block;
            color: var(--muted, #8ba3c7);
            font-size: 12px;
            margin-top: 2px;
        }
        .reporte-head-actions {
            display: flex;
            gap: 10px;
            flex: 0 0 auto;
        }
        .reporte-btn {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            min-height: 36px;
            padding: 0 14px;
            border-radius: 9px;
            border: 1px solid rgba(255,255,255,.14);
            background: transparent;
            color: inherit;
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
        }
        .reporte-btn:hover {
            border-color: #22C55E;
        }
        .reporte-frame-wrap {
            height: calc(100vh - 150px);
            min-height: 480px;
        }
        .reporte-frame {
            width: 100%;
            height: 100%;
            border: 0;
            display: block;
            background: #070c17;
        }
        :root[data-theme="light"] .reporte-head h2 {
            color: #0f172a;
        }
        :root[data-theme="light"] .reporte-frame {
            background: #f4f6fa;
        }
        :root[data-theme="light"] .reporte-head small {
            color: #64748b;
        }
        :root[data-theme="light"] .reporte-btn {
            border-color: #dbe4f0;
            color: #475569;
        }
        :root[data-theme="light"] .reporte-btn:hover {
            border-color: #22C55E;
            color: #15803d;
        }
    </style>

    <div class="card catalog-card reporte-card">
        <div class="reporte-head">
            <div>
                <h2>Reporte de mantenimiento — {{ $service->service_number ?? 'OS-'.$service->id }}</h2>
                <small>{{ trim(($service->customer?->nombre ?? '').' '.($service->customer?->apellido ?? '')) ?: 'Servicio interno' }}</small>
            </div>
            <div class="reporte-head-actions">
                <a class="reporte-btn" href="{{ route('gestion.servicios.mantenimiento.show', $service) }}">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
                    Volver al servicio
                </a>
            </div>
        </div>
        <div class="reporte-frame-wrap">
            <iframe class="reporte-frame" id="reporteFrame" src="{{ route('gestion.servicios.mantenimiento.reporte.raw', $service) }}" title="Hoja de mantenimiento"></iframe>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    (function () {
        const frame = document.getElementById('reporteFrame');

        function temaActual() {
            return document.documentElement.getAttribute('data-theme') === 'light' ? 'light' : 'dark';
        }

        function aplicarEnHoja() {
            try {
                if (frame.contentWindow && typeof frame.contentWindow.aplicarTema === 'function') {
                    frame.contentWindow.aplicarTema(temaActual());
                }
            } catch (e) {}
        }

        frame.addEventListener('load', aplicarEnHoja);

        new MutationObserver(aplicarEnHoja)
            .observe(document.documentElement, { attributes: true, attributeFilter: ['data-theme'] });
    })();
</script>
@endpush
