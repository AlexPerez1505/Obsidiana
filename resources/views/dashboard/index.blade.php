@extends('layouts.dashboard')

@section('page-title', 'Dashboard')
@section('page-sub', 'Resumen general de tu cuenta')

@section('content')
@php
$icons = [
    'users' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="22" height="22"><path d="M17 21v-2a4 4 0 0 0-3-3.87"/><path d="M7 21v-2a4 4 0 0 1 3-3.87"/><circle cx="12" cy="7" r="4"/></svg>',
    'box' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="22" height="22"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg>',
    'cart' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="22" height="22"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>',
    'file-text' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="22" height="22"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><line x1="10" y1="9" x2="9" y2="9"/></svg>',
    'tool' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="22" height="22"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>',
    'package' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="22" height="22"><path d="M12.89 1.55 2 6.24v11.51l10.89 4.69 10.89-4.69V6.24L12.89 1.55z"/><polyline points="12 2.76 12 12 3.1 7.31"/><polyline points="12 12 21.7 7.31"/><line x1="7.5 4.65" x2="16.5" y1="9.03" y2="4.65"/></svg>',
    'login' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="22" height="22"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></svg>',
    'chevron' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><polyline points="9 18 15 12 9 6"/></svg>',
];

$modules = [
    [
        'title' => 'Gestión Comercial',
        'desc'  => 'Administra clientes, ventas, cotizaciones y promociones.',
        'color' => 'blue',
        'icon'  => 'users',
        'links' => [
            ['label' => 'Clientes', 'route' => route('commercial.clientes.index')],
            ['label' => 'Cotizaciones', 'route' => route('commercial.cotizaciones.index')],
            ['label' => 'Remisiones', 'route' => route('commercial.remisiones.index')],
            ['label' => 'Planes de Pago', 'route' => route('commercial.planesPago.index')],
            ['label' => 'Promociones', 'route' => route('commercial.promociones.index')],
        ],
    ],
    [
        'title' => 'Gestión de Inventario',
        'desc'  => 'Controla productos, equipos y niveles de stock.',
        'color' => 'green',
        'icon'  => 'box',
        'links' => [
            ['label' => 'Productos', 'route' => route('inventory.productos.index')],
            ['label' => 'Equipos', 'route' => route('inventory.equipos.index')],
            ['label' => 'Entrada / Salida', 'route' => route('inventory.movimientos.index')],
            ['label' => 'Paquetes', 'route' => route('inventory.paquetes.index')],
        ],
    ],
    [
        'title' => 'Gestión Administrativa',
        'desc'  => 'Administra compras, recursos humanos y flujos internos.',
        'color' => 'purple',
        'icon'  => 'file-text',
        'links' => [
            ['label' => 'Recursos Humanos', 'route' => route('admin.users.index')],
            ['label' => 'Viáticos', 'route' => route('admin.viatics.index')],
            ['label' => 'Vehículos', 'route' => route('admin.vehicles.index')],
            ['label' => 'Solicitud de materiales', 'route' => route('admin.materials.index')],
            ['label' => 'Agenda', 'route' => route('admin.agenda.index')],
        ],
    ],
    [
        'title' => 'Gestión de Servicios',
        'desc'  => 'Gestiona servicios, órdenes y mantenimiento.',
        'color' => 'teal',
        'icon'  => 'tool',
        'links' => [
            ['label' => 'Ordenes de servicio', 'route' => route('gestion.servicios.historial.nueva_orden')],
            ['label' => 'Historial de equipos', 'route' => route('gestion.servicios.historial')],
            ['label' => 'Técnicos', 'route' => route('gestion.servicios.historial')],
        ],
    ],
    [
        'title' => 'Gestión de Marketing',
        'desc'  => 'Gestiona campañas, contenido y análisis de resultados.',
        'color' => 'orange',
        'icon'  => 'cart',
        'links' => [
            ['label' => 'Inicio', 'route' => route('marketing.inicio')],
            ['label' => 'Calendario', 'route' => route('marketing.calendario.index')],
            ['label' => 'Guía de marca', 'route' => route('marketing.guia_de_marca.index')],
            ['label' => 'Aprobación de flyers', 'route' => route('marketing.aprobacion_flyers.index')],
            ['label' => 'Tareas', 'route' => route('marketing.tareas.index')],
            ['label' => 'Biblioteca & catálogo', 'route' => route('marketing.biblioteca_catalogo.index')],
        ],
    ],
    [
        'title' => 'Configuración',
        'desc'  => 'Personaliza el sistema y gestiona usuarios.',
        'color' => 'gray',
        'icon'  => 'login',
        'links' => [
            ['label' => 'Usuarios', 'route' => route('admin.users.index')],
            ['label' => 'Permisos', 'route' => route('admin.permissions.index')],
            ['label' => 'Catálogo', 'route' => route('configuracion.catalogos.index')],
            ['label' => 'Tipos de equipo', 'route' => route('configuracion.tipos_equipo.index')],
        ],
    ],
];
@endphp

<style>
    .dash-welcome {
        background: linear-gradient(135deg, var(--primary-strong), var(--primary));
        color: #fff;
        border-radius: 18px;
        padding: 24px 28px;
        margin-bottom: 20px;
        box-shadow: var(--shadow);
    }
    .dash-welcome h1 { margin:0 0 4px; font-size:22px; font-weight:700; }
    .dash-welcome p { margin:0; opacity:.92; font-size:15px; }
    .dash-welcome a { color:#fff; text-decoration:underline; }
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
        gap: 18px;
        margin-bottom: 24px;
    }
    .stat-card-link {
        text-decoration: none;
        color: inherit;
        display: block;
        transition: transform .15s ease;
    }
    .stat-card-link:hover { transform: translateY(-3px); }
    .stat-card {
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: 18px;
        padding: 18px 20px;
        box-shadow: var(--shadow);
        display: flex;
        flex-direction: column;
        gap: 12px;
    }
    .stat-card-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
    }
    .stat-card-ico {
        width: 44px; height: 44px;
        border-radius: 12px;
        display: flex; align-items: center; justify-content: center;
    }
    .stat-card-ico.blue { background: var(--primary-soft); color: var(--primary); }
    .stat-card-ico.green { background: var(--green-soft); color: var(--green); }
    .stat-card-ico.orange { background: var(--accent-soft); color: var(--accent); }
    .stat-card-ico.purple { background: rgba(139,92,246,.15); color: #8b5cf6; }
    .stat-card-trend {
        display: inline-flex; align-items: center; gap: 4px;
        font-size: 12px; font-weight: 700; padding: 3px 8px;
        border-radius: 999px;
    }
    .stat-card-trend.up { background: var(--green-soft); color: var(--green); }
    .stat-card-trend.down { background: var(--danger-soft); color: var(--danger); }
    .stat-card-num {
        font-size: 28px; font-weight: 800; color: var(--text);
        margin: 0 0 2px;
    }
    .stat-card-lbl {
        font-size: 13px; color: var(--muted); text-transform: uppercase;
        letter-spacing: .04em; font-weight: 600;
    }
    .stat-card-chart { height: 70px; position: relative; }
    .modules-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 18px;
        margin-bottom: 24px;
    }
    .module-card { padding: 20px; }
    .module-header {
        display: flex; align-items: center; gap: 12px; margin-bottom: 10px;
    }
    .module-ico {
        width: 46px; height: 46px; border-radius: 12px;
        display: flex; align-items: center; justify-content: center;
    }
    .module-ico.blue { background: var(--primary-soft); color: var(--primary); }
    .module-ico.green { background: var(--green-soft); color: var(--green); }
    .module-ico.purple { background: rgba(139,92,246,.15); color: #8b5cf6; }
    .module-ico.teal { background: rgba(20,184,166,.15); color: #14b8a6; }
    .module-ico.orange { background: var(--accent-soft); color: var(--accent); }
    .module-ico.gray { background: var(--surface-2); color: var(--muted); }
    .module-title { font-size: 16px; font-weight: 700; margin: 0; }
    .module-desc { font-size: 13px; color: var(--muted); margin: 0 0 14px; line-height: 1.4; }
    .module-links { list-style: none; padding: 0; margin: 0; }
    .module-links li { border-top: 1px solid var(--border); }
    .module-links a {
        display: flex; align-items: center; justify-content: space-between;
        padding: 10px 0; font-size: 13.5px; color: var(--text);
        text-decoration: none; font-weight: 600;
        transition: color .12s ease, padding .12s ease;
    }
    .module-links a:hover { color: var(--primary); padding-left: 4px; }
    .bottom-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 18px;
        align-items: start;
    }
    .panel-list { list-style: none; padding: 0; margin: 0; }
    .panel-list li { display: flex; align-items: center; gap: 12px; padding: 11px 0; border-bottom: 1px solid var(--border); }
    .panel-list li:last-child { border-bottom: none; }
    .panel-list .ico { width: 34px; height: 34px; border-radius: 10px; display: flex; align-items: center; justify-content: center; flex: 0 0 auto; }
    .panel-list .ico.blue { background: var(--primary-soft); color: var(--primary); }
    .panel-list .ico.green { background: var(--green-soft); color: var(--green); }
    .panel-list .ico.orange { background: var(--accent-soft); color: var(--accent); }
    .panel-list .ico.danger { background: var(--danger-soft); color: var(--danger); }
    .panel-list .ico.warn { background: var(--accent-soft); color: var(--accent); }
    .panel-list .ico.muted { background: var(--surface-2); color: var(--muted); }
    .panel-list .panel-text { flex: 1; min-width: 0; }
    .panel-list .panel-text b { display: block; font-size: 13.5px; font-weight: 700; color: var(--text); }
    .panel-list .panel-text small { display: block; font-size: 12px; color: var(--muted); }
    .panel-list .panel-meta { font-size: 12px; color: var(--muted); white-space: nowrap; }
    .alert-count { min-width: 20px; height: 20px; border-radius: 999px; background: var(--primary); color: #fff; font-size: 11px; font-weight: 800; display: inline-flex; align-items: center; justify-content: center; padding: 0 6px; margin-left: auto; }
    .agenda-date { font-size: 12px; color: var(--primary); font-weight: 700; }
    .empty-note { padding: 24px 0; text-align: center; color: var(--muted); font-size: 14px; }
    .dash-refresh { font-size: 12px; color: var(--muted); margin-top: 8px; }
</style>

<div class="dash-welcome">
    <h1>¡Hola, {{ $user->name }}! Bienvenido a {{ config('app.name') }}</h1>
    <p>Este es el resumen general. Toda la información se actualiza en tiempo real desde la base de datos.</p>
</div>

{{-- Estadísticas principales --}}
<div class="stats-grid">
    <a class="stat-card-link" href="{{ route('commercial.clientes.index') }}">
        <div class="stat-card">
            <div class="stat-card-header">
                <div class="stat-card-ico blue">{!! $icons['users'] !!}</div>
                <span class="stat-card-trend {{ $customerChange['positive'] ? 'up' : 'down' }}">
                    {{ $customerChange['positive'] ? '↑' : '↓' }} {{ $customerChange['value'] }}%
                </span>
            </div>
            <div class="stat-card-num" data-stat="customersCount">{{ $customersCount }}</div>
            <div class="stat-card-lbl">Clientes</div>
            <div class="stat-card-chart"><canvas id="clientsChart"></canvas></div>
        </div>
    </a>

    <a class="stat-card-link" href="{{ route('inventory.productos.index') }}">
        <div class="stat-card">
            <div class="stat-card-header">
                <div class="stat-card-ico green">{!! $icons['box'] !!}</div>
                <span class="stat-card-trend {{ $productChange['positive'] ? 'up' : 'down' }}">
                    {{ $productChange['positive'] ? '↑' : '↓' }} {{ $productChange['value'] }}%
                </span>
            </div>
            <div class="stat-card-num" data-stat="productsCount">{{ $productsCount }}</div>
            <div class="stat-card-lbl">Productos</div>
            <div class="stat-card-chart"><canvas id="productsChart"></canvas></div>
        </div>
    </a>

    <a class="stat-card-link" href="{{ route('commercial.remisiones.index') }}">
        <div class="stat-card">
            <div class="stat-card-header">
                <div class="stat-card-ico orange">{!! $icons['cart'] !!}</div>
                <span class="stat-card-trend {{ $salesChange['positive'] ? 'up' : 'down' }}">
                    {{ $salesChange['positive'] ? '↑' : '↓' }} {{ $salesChange['value'] }}%
                </span>
            </div>
            <div class="stat-card-num" data-stat="salesTotal">${{ number_format($salesTotal, 2) }}</div>
            <div class="stat-card-lbl">Ventas</div>
            <div class="stat-card-chart"><canvas id="salesChart"></canvas></div>
        </div>
    </a>

    <a class="stat-card-link" href="{{ route('commercial.cotizaciones.index') }}">
        <div class="stat-card">
            <div class="stat-card-header">
                <div class="stat-card-ico purple">{!! $icons['file-text'] !!}</div>
                <span class="stat-card-trend {{ $quoteChange['positive'] ? 'up' : 'down' }}">
                    {{ $quoteChange['positive'] ? '↑' : '↓' }} {{ $quoteChange['value'] }}%
                </span>
            </div>
            <div class="stat-card-num" data-stat="quotesCount">{{ $quotesCount }}</div>
            <div class="stat-card-lbl">Cotizaciones</div>
            <div class="stat-card-chart"><canvas id="quotesChart"></canvas></div>
        </div>
    </a>
</div>

{{-- Módulos con accesos directos --}}
<x-ui.section-title style="margin:0 0 14px;">Módulos del sistema</x-ui.section-title>
<div class="modules-grid">
    @foreach ($modules as $m)
        <x-ui.card class="module-card">
            <div class="module-header">
                <div class="module-ico {{ $m['color'] }}">{!! $icons[$m['icon']] !!}</div>
                <div>
                    <h3 class="module-title">{{ $m['title'] }}</h3>
                </div>
            </div>
            <p class="module-desc">{{ $m['desc'] }}</p>
            <ul class="module-links">
                @foreach ($m['links'] as $link)
                    <li>
                        <a href="{{ $link['route'] }}">
                            <span>{{ $link['label'] }}</span>
                            {!! $icons['chevron'] !!}
                        </a>
                    </li>
                @endforeach
            </ul>
        </x-ui.card>
    @endforeach
</div>

{{-- Actividad, alertas, agenda --}}
<div class="bottom-grid">
    <x-ui.card>
        <x-ui.section-title style="margin:0 0 14px;">Actividad reciente</x-ui.section-title>
        @if ($activities->isEmpty())
            <p class="empty-note">No hay actividad reciente.</p>
        @else
            <ul class="panel-list">
                @foreach ($activities as $a)
                    <li>
                        <div class="ico {{ $a['color'] }}">{!! $icons[$a['icon']] ?? $icons['file-text'] !!}</div>
                        <div class="panel-text">
                            <b>{{ $a['message'] }}</b>
                            <small>{{ $a['detail'] }}</small>
                        </div>
                        <span class="panel-meta">{{ $a['time']->diffForHumans() }}</span>
                    </li>
                @endforeach
            </ul>
            <p class="dash-refresh">Se actualiza automáticamente.</p>
        @endif
    </x-ui.card>

    <x-ui.card>
        <x-ui.section-title style="margin:0 0 14px;">Alertas importantes</x-ui.section-title>
        @php $hasAlert = collect($alerts)->sum('count') > 0; @endphp
        @if (!$hasAlert)
            <p class="empty-note">Todo en orden. No hay alertas activas.</p>
        @else
            <ul class="panel-list">
                @foreach ($alerts as $alert)
                    @if ($alert['count'] > 0)
                        <li>
                            <a href="{{ $alert['route'] }}" style="display:flex;align-items:center;gap:12px;text-decoration:none;color:inherit;width:100%;">
                                <div class="ico {{ $alert['color'] }}">{!! $icons[$alert['icon']] ?? $icons['file-text'] !!}</div>
                                <div class="panel-text">
                                    <b>{{ $alert['count'] }} {{ $alert['message'] }}</b>
                                    <small>Requiere atención</small>
                                </div>
                                <span class="alert-count">{{ $alert['count'] }}</span>
                            </a>
                        </li>
                    @endif
                @endforeach
            </ul>
        @endif
    </x-ui.card>

    <x-ui.card>
        <x-ui.section-title style="margin:0 0 14px;">Agenda de la semana</x-ui.section-title>
        @if ($agenda->isEmpty())
            <p class="empty-note">No hay eventos próximos.</p>
        @else
            <ul class="panel-list">
                @foreach ($agenda as $event)
                    <li>
                        <div class="ico blue">{!! $icons['file-text'] !!}</div>
                        <div class="panel-text">
                            <b>{{ $event->title }}</b>
                            <small>{{ $event->start_date->format('d M') }} {{ $event->start_time ? '· ' . $event->start_time : '' }}</small>
                        </div>
                    </li>
                @endforeach
            </ul>
            <a href="{{ route('admin.agenda.index') }}" class="link" style="display:inline-block;margin-top:10px;">Ver agenda completa</a>
        @endif
    </x-ui.card>
</div>

@push('head')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
@endpush

<script>
    const dashData = {
        labels: @json($days),
        customerTrend: @json($customerTrend),
        productTrend: @json($productTrend),
        salesTrend: @json($salesTrend),
        quotesTrend: @json($quotesTrend),
    };

    const root = getComputedStyle(document.documentElement);
    const colors = {
        primary: root.getPropertyValue('--primary').trim() || '#007aff',
        green: root.getPropertyValue('--green').trim() || '#15803d',
        orange: root.getPropertyValue('--accent').trim() || '#f97316',
        purple: '#8b5cf6',
        text: root.getPropertyValue('--text').trim() || '#333',
        muted: root.getPropertyValue('--muted').trim() || '#888',
    };

    function makeChart(ctx, data, color, fill = true) {
        return new Chart(ctx, {
            type: 'line',
            data: {
                labels: dashData.labels,
                datasets: [{
                    data: data,
                    borderColor: color,
                    backgroundColor: fill ? color + '20' : 'transparent',
                    borderWidth: 2,
                    pointRadius: 0,
                    pointHoverRadius: 4,
                    fill: fill,
                    tension: 0.4,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false }, tooltip: { enabled: true } },
                scales: {
                    x: { display: false },
                    y: { display: false, beginAtZero: true }
                },
                interaction: { intersect: false, mode: 'index' }
            }
        });
    }

    const charts = {};
    document.addEventListener('DOMContentLoaded', function () {
        charts.clients  = makeChart(document.getElementById('clientsChart'),  dashData.customerTrend, colors.primary);
        charts.products = makeChart(document.getElementById('productsChart'), dashData.productTrend,  colors.green);
        charts.sales    = makeChart(document.getElementById('salesChart'),    dashData.salesTrend,    colors.orange);
        charts.quotes   = makeChart(document.getElementById('quotesChart'),   dashData.quotesTrend,   colors.purple);

        // Actualizar datos cada 60 segundos para reflejar cambios en tiempo real
        setInterval(refreshMetrics, 60000);
    });

    function refreshMetrics() {
        fetch('{{ route('dashboard.metrics') }}', {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => r.json())
        .then(data => {
            document.querySelector('[data-stat="customersCount"]').textContent = data.customersCount;
            document.querySelector('[data-stat="productsCount"]').textContent  = data.productsCount;
            document.querySelector('[data-stat="salesTotal"]').textContent     = '$' + parseFloat(data.salesTotal).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
            document.querySelector('[data-stat="quotesCount"]').textContent    = data.quotesCount;

            charts.clients.data.datasets[0].data  = data.customerTrend;
            charts.products.data.datasets[0].data = data.productTrend;
            charts.sales.data.datasets[0].data    = data.salesTrend;
            charts.quotes.data.datasets[0].data   = data.quotesTrend;

            Object.values(charts).forEach(c => c.update());
        })
        .catch(() => { /* Si falla el refresco, no interrumpe la experiencia */ });
    }
</script>
@endsection
