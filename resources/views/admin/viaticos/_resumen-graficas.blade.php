@php
    $categorias = [
        'tolls' => ['label' => 'Casetas', 'color' => '#148CFF', 'tipos' => ['toll', 'tolls']],
        'fuel' => ['label' => 'Gasolina', 'color' => '#FF9F0A', 'tipos' => ['fuel']],
        'meals' => ['label' => 'Comidas', 'color' => '#30CE58', 'tipos' => ['meal', 'meals']],
        'lodging' => ['label' => 'Hospedaje', 'color' => '#BF5AF2', 'tipos' => ['lodging']],
        'other' => ['label' => 'Otros', 'color' => '#9699A3', 'tipos' => ['other', 'additional', 'parking', 'taxi', 'fixed_fund']],
    ];
    $fechaDe = fn ($v) => $v->fecha_ida ?: $v->expense_date ?: $v->created_at;
    $anioGrafica = $viatics->map(fn ($v) => $fechaDe($v)?->year)->filter()->max() ?: now()->year;
    $importeCategoria = function ($viatic, string $campo, array $tipos): float {
        if ($viatic->relationLoaded('expenses') && $viatic->expenses->isNotEmpty()) {
            return (float) $viatic->expenses
                ->filter(fn ($expense) => in_array($expense->type, $tipos, true))
                ->sum('amount');
        }

        if ($campo === 'other') {
            return (float) $viatic->additional + (float) ($viatic->parking ?? 0) + (float) ($viatic->taxi ?? 0) + (float) $viatic->fixed_fund;
        }

        return (float) ($viatic->{$campo} ?? 0);
    };
    $meses = collect(range(1, 12))->mapWithKeys(function ($mes) use ($viatics, $fechaDe, $categorias, $importeCategoria, $anioGrafica) {
        $clave = sprintf('%04d-%02d', $anioGrafica, $mes);
        $viajesDelMes = $viatics->filter(fn ($v) => $fechaDe($v)?->format('Y-m') === $clave);

        return [$clave => collect($categorias)->mapWithKeys(fn ($meta, $campo) => [
            $campo => $viajesDelMes->sum(fn ($v) => $importeCategoria($v, $campo, $meta['tipos'])),
        ])];
    });
    $totales = collect($categorias)->mapWithKeys(fn ($meta, $campo) => [
        $campo => $viatics->sum(fn ($v) => $importeCategoria($v, $campo, $meta['tipos'])),
    ]);
    $totalGraficas = (float) $totales->sum();
    $destinos = $porLugar->take(7);
    $mesLabels = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
@endphp

@push('head')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js?v=20261119"></script>
    <style>
        .vt2-summary{display:grid;gap:14px}
        .vt-panel[data-vt-panel="resumen-legacy"]{display:none!important;visibility:hidden!important;height:0!important;overflow:hidden!important;margin:0!important;padding:0!important}
        .vt2-kpis{display:grid;grid-template-columns:repeat(6,minmax(0,1fr));gap:10px}
        .vt2-kpi,.vt2-card{border:1px solid #203652;border-radius:10px;background:#111B2E;color:#F1F5F9;box-shadow:none}
        .vt2-kpi{min-height:108px;padding:14px;position:relative}
        .vt2-kpi-icon{position:absolute;right:12px;top:12px;display:grid;place-items:center;width:30px;height:30px;border-radius:9px;background:#123A68;color:#69B4FF}
        .vt2-kpi-icon svg{display:block;width:18px;height:18px}
        .vt2-kpis .vt2-kpi:nth-child(3) .vt2-kpi-icon{color:#FF9F0A;background:#352B16}
        .vt2-kpis .vt2-kpi:nth-child(4) .vt2-kpi-icon{color:#30CE78;background:#12392F}
        .vt2-kpis .vt2-kpi:nth-child(5) .vt2-kpi-icon{color:#BF8AF2;background:#30254A}
        .vt2-kpis .vt2-kpi:nth-child(6) .vt2-kpi-icon{color:#FF6467;background:#3B222B}
        .vt2-kpi-label,.vt2-kpi-value,.vt2-kpi-note{position:relative;z-index:1}
        .vt2-kpi-label,.vt2-card-sub{color:#94A9C4;font-size:10px;font-weight:600}
        .vt2-kpi-value{margin-top:10px;font-size:20px;font-weight:800;line-height:1.1}
        .vt2-kpi-note{margin-top:6px;color:#94A9C4;font-size:9px}
        .vt2-card{padding:14px}
        .vt2-card-header{display:flex;align-items:flex-start;gap:8px;margin-bottom:10px}
        .vt2-card-header svg{width:16px;height:16px;flex:none;color:#69B4FF;margin-top:1px}
        .vt2-card h2{margin:0;font-size:13px;line-height:1.15}
        .vt2-card-sub{margin:4px 0 0}
        .vt2-wide{min-height:330px}
        .vt2-chart-wrap{height:252px;position:relative}
        .vt2-chart-wrap canvas{width:100%!important;height:100%!important}
        .vt2-chart-caption{margin:7px 0 0;color:#94A9C4;font-size:9px}
        .vt2-lower{display:grid;grid-template-columns:1fr 1fr;gap:14px}
        .vt2-donut-layout{display:grid;grid-template-columns:145px 1fr;gap:14px;align-items:center;min-height:170px}
        .vt2-donut-wrap{height:150px;position:relative}
        .vt2-donut-wrap canvas{width:100%!important;height:100%!important}
        .vt2-breakdown{display:grid;gap:8px}
        .vt2-breakdown-row{display:grid;grid-template-columns:9px 1fr auto;align-items:center;gap:7px;font-size:10px}
        .vt2-breakdown-row i{width:9px;height:9px;border-radius:3px}
        .vt2-breakdown-row span{color:#F1F5F9}
        .vt2-breakdown-row strong{font-size:10px;text-align:right}
        .vt2-breakdown-row small{grid-column:3;color:#94A9C4;font-size:9px;text-align:right;margin-top:-4px}
        .vt2-destinos{display:grid;gap:11px;margin-top:12px}
        .vt2-destino{display:grid;gap:5px}
        .vt2-destino-head{display:flex;justify-content:space-between;gap:12px;color:#F1F5F9;font-size:10px}
        .vt2-destino-head strong{white-space:nowrap}
        .vt2-destino-track{height:10px;border-radius:99px;background:#202D41;overflow:hidden}
        .vt2-destino-track span{display:block;height:100%;border-radius:inherit;background:#168FF6}
        [data-theme="light"] .vt2-kpi,[data-theme="light"] .vt2-card{background:var(--surface);border-color:var(--border);color:var(--text)}
        [data-theme="light"] .vt2-breakdown-row span,[data-theme="light"] .vt2-destino-head{color:var(--text)}
        [data-theme="light"] .vt2-destino-track{background:#e6edf7}
        @media(max-width:1100px){.vt2-kpis{grid-template-columns:repeat(3,minmax(0,1fr))}}
        @media(max-width:720px){.vt2-kpis,.vt2-lower{grid-template-columns:1fr 1fr}.vt2-donut-layout{grid-template-columns:1fr}.vt2-donut-wrap{max-width:170px;margin:auto}}
        @media(max-width:480px){.vt2-kpis{grid-template-columns:1fr}.vt2-card{padding:12px}.vt2-chart-wrap{height:235px}}
    </style>
@endpush

<section class="vt-panel vt2-summary" data-vt-panel="resumen">
    <div class="vt2-kpis">
        @foreach ([
            ['label' => 'Viajes registrados', 'value' => $viatics->count(), 'note' => 'Registros en el sistema', 'icon' => 'route'],
            ['label' => 'Gasto acumulado', 'value' => '$'.number_format($total, 2), 'note' => 'Todos los gastos registrados', 'icon' => 'wallet'],
            ['label' => 'Por comprobar', 'value' => $pendientes, 'note' => 'Viajes pendientes', 'icon' => 'clipboard'],
            ['label' => 'Aprobados', 'value' => $viatics->where('status', 'approved')->count(), 'note' => 'Viajes autorizados', 'icon' => 'check'],
            ['label' => 'Gasolina', 'value' => '$'.number_format($totales['fuel'], 2), 'note' => 'Gasto registrado', 'icon' => 'fuel'],
            ['label' => 'Viaje más caro', 'value' => '$'.number_format($viajeCaro?->total_computed ?? 0, 2), 'note' => $viajeCaro?->place ?: 'Sin destino', 'icon' => 'pin'],
        ] as $kpi)
            <article class="vt2-kpi">
                <span class="vt2-kpi-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    @switch($kpi['icon'])
                        @case('route') <path d="M4 19c4-8 8-8 12-14"/><circle cx="4" cy="19" r="2"/><circle cx="16" cy="5" r="2"/> @break
                        @case('wallet') <path d="M4 7h15a1 1 0 0 1 1 1v10a1 1 0 0 1-1 1H4a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h13"/><path d="M16 13h4"/> @break
                        @case('clipboard') <rect x="5" y="4" width="14" height="17" rx="2"/><path d="M9 4V2h6v2M8 10h8M8 14h6"/> @break
                        @case('check') <circle cx="12" cy="12" r="9"/><path d="m8 12 3 3 5-6"/> @break
                        @case('fuel') <path d="M6 21V4h9v17M6 8h9M15 7h2l3 3v7a2 2 0 0 1-4 0v-4"/> @break
                        @default <path d="M20 10c0 5-8 11-8 11S4 15 4 10a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="2.5"/> @break
                    @endswitch
                </svg></span>
                <div class="vt2-kpi-label">{{ $kpi['label'] }}</div>
                <div class="vt2-kpi-value">{{ $kpi['value'] }}</div>
                <div class="vt2-kpi-note">{{ $kpi['note'] }}</div>
            </article>
        @endforeach
    </div>

    <section class="vt2-card vt2-wide">
        <div class="vt2-card-header"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 19V5M4 19h16"/><path d="M7 16v-5M12 16V7M17 16v-9"/></svg><div><h2>Gasto por mes</h2><p class="vt2-card-sub">Cada barra muestra el total mensual dividido por tipo de gasto.</p></div></div>
        <div class="vt2-chart-wrap"><canvas id="vt2-month-chart" aria-label="Gasto por mes"></canvas></div>
        <p class="vt2-chart-caption">Vista de totales mensuales agrupados. El desglose se obtiene de los gastos reales.</p>
    </section>

    <div class="vt2-lower">
        <section class="vt2-card">
            <div class="vt2-card-header"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 3a9 9 0 1 0 9 9"/><path d="M12 3v9h9"/></svg><div><h2>En qué se gasta</h2><p class="vt2-card-sub">Reparto del total por tipo.</p></div></div>
            <div class="vt2-donut-layout"><div class="vt2-donut-wrap"><canvas id="vt2-donut-chart" aria-label="Distribución de gastos"></canvas></div><div class="vt2-breakdown">@foreach($categorias as $campo => $meta) @php($porcentaje = $totalGraficas > 0 ? ($totales[$campo] / $totalGraficas) * 100 : 0)<div class="vt2-breakdown-row"><i style="background:{{ $meta['color'] }}"></i><span>{{ $meta['label'] }}</span><strong>${{ number_format($totales[$campo], 2) }}</strong><small>{{ number_format($porcentaje, 1) }}%</small></div>@endforeach</div></div>
        </section>
        <section class="vt2-card">
            <div class="vt2-card-header"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M20 10c0 5-8 11-8 11S4 15 4 10a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="2.5"/></svg><div><h2>Destinos que más cuestan</h2><p class="vt2-card-sub">Total acumulado por ciudad.</p></div></div>
            <div class="vt2-destinos">@forelse($destinos as $nombre => $monto)<div class="vt2-destino"><div class="vt2-destino-head"><span>{{ $nombre }}</span><strong>${{ number_format($monto, 2) }}</strong></div><div class="vt2-destino-track"><span style="width:{{ $destinos->max() > 0 ? ($monto / $destinos->max()) * 100 : 0 }}%"></span></div></div>@empty<p class="vt2-card-sub">Todavía no hay destinos registrados.</p>@endforelse</div>
        </section>
    </div>
</section>

<script>
(function () {
    const meses = @json($mesLabels);
    const categorias = @json($categorias->map(fn ($meta) => $meta['label'])->values());
    const colores = @json($categorias->map(fn ($meta) => $meta['color'])->values());
    const datos = @json($categorias->keys()->map(fn ($campo) => $meses->map(fn ($mes) => (float) $mes[$campo])->values())->values());
    const moneda = value => new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(value || 0);
    const css = name => getComputedStyle(document.documentElement).getPropertyValue(name).trim();
    let charts = [];

    function iniciar() {
        if (!window.Chart || charts.length) return;
        const text = css('--text') || '#F1F5F9';
        const muted = css('--muted') || '#94A9C4';
        const grid = document.documentElement.dataset.theme === 'dark' ? 'rgba(148,169,196,.16)' : 'rgba(80,100,130,.16)';
        const etiquetaTotal = { id: 'vt2-total-labels', afterDatasetsDraw(chart) { const { ctx, scales } = chart; ctx.save(); ctx.fillStyle = text; ctx.font = '700 10px Quicksand, sans-serif'; ctx.textAlign = 'center'; chart.data.labels.forEach((_, index) => { const total = chart.data.datasets.reduce((sum, ds) => sum + (Number(ds.data[index]) || 0), 0); if (!total) return; const x = chart.getDatasetMeta(chart.data.datasets.length - 1).data[index].x; const y = scales.y.getPixelForValue(total) - 7; ctx.fillText(total >= 1000 ? '$' + (total / 1000).toFixed(1) + 'k' : moneda(total).replace(' ', ' '), x, y); }); ctx.restore(); } };
        const centro = { id: 'vt2-donut-center', afterDraw(chart) { const { ctx, chartArea } = chart; if (!chartArea) return; const x = (chartArea.left + chartArea.right) / 2; const y = (chartArea.top + chartArea.bottom) / 2; ctx.save(); ctx.textAlign = 'center'; ctx.fillStyle = muted; ctx.font = '600 11px Quicksand, sans-serif'; ctx.fillText('Total', x, y - 5); ctx.fillStyle = text; ctx.font = '800 14px Quicksand, sans-serif'; ctx.fillText(moneda({{ $totalGraficas }}), x, y + 14); ctx.restore(); } };
        charts.push(new Chart(document.getElementById('vt2-month-chart'), { type: 'bar', data: { labels: meses, datasets: categorias.map((label, i) => ({ label, data: datos[i], backgroundColor: colores[i], borderRadius: 3, borderSkipped: false, maxBarThickness: 34, stack: 'gastos' })) }, plugins: [etiquetaTotal], options: { responsive: true, maintainAspectRatio: false, animation: { duration: 500 }, scales: { x: { stacked: true, grid: { display: false }, ticks: { color: muted, font: { size: 9 } } }, y: { beginAtZero: true, stacked: true, grid: { color: grid, drawBorder: false }, ticks: { color: muted, font: { size: 9 }, callback: value => value >= 1000 ? '$' + (value / 1000) + 'k' : '$' + value } } }, plugins: { legend: { position: 'bottom', align: 'start', labels: { color: muted, boxWidth: 9, boxHeight: 9, usePointStyle: true, pointStyle: 'rectRounded', font: { size: 9 } } }, tooltip: { callbacks: { label: context => ' ' + context.dataset.label + ': ' + moneda(context.raw), footer: items => 'Total mensual: ' + moneda(items.reduce((sum, item) => sum + Number(item.raw || 0), 0)) } } } } }));
        charts.push(new Chart(document.getElementById('vt2-donut-chart'), { type: 'doughnut', data: { labels: categorias, datasets: [{ data: @json($totales->values()), backgroundColor: colores, borderWidth: 0, hoverOffset: 4 }] }, plugins: [centro], options: { cutout: '70%', responsive: true, maintainAspectRatio: false, animation: { duration: 500 }, plugins: { legend: { display: false }, tooltip: { callbacks: { label: context => ' ' + context.label + ': ' + moneda(context.raw) } } } } }));
    }
    iniciar();
    new MutationObserver(() => { charts.forEach(chart => chart.destroy()); charts = []; iniciar(); }).observe(document.documentElement, { attributes: true, attributeFilter: ['data-theme'] });
})();
</script>
