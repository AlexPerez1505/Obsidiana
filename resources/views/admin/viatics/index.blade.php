@extends('layouts.dashboard')
@section('title', 'Viáticos y viajes')
@section('page-title', 'Viáticos y viajes')
@section('page-sub', 'Control de gastos y viajes de la empresa')

@php
    $total = $viatics->sum(fn ($v) => (float) $v->total_computed);
    $promedio = $viatics->count() ? $total / $viatics->count() : 0;
    $pendientes = $viatics->where('status', 'pending')->count();
    $sinVehiculo = $viatics->filter(fn ($v) => blank($v->vehicle_name))->count();
    $gastos = ['tolls' => ['Casetas', '#1683f7'], 'fuel' => ['Gasolina', '#ff9f0a'], 'meals' => ['Comidas', '#30d158'], 'lodging' => ['Hospedaje', '#bf5af2'], 'additional' => ['Otros', '#a1a1aa']];
    $tiposPorCampo = [
        'tolls' => ['toll'],
        'fuel' => ['fuel'],
        'meals' => ['meal'],
        'lodging' => ['lodging'],
        'additional' => ['other', 'parking', 'taxi', 'fixed_fund'],
    ];
    $totalesPorCampo = function ($items) use ($gastos, $tiposPorCampo) {
        return collect($gastos)->mapWithKeys(function ($meta, $campo) use ($items, $tiposPorCampo) {
            $tipos = $tiposPorCampo[$campo];
            $total = $items->sum(function ($viatic) use ($campo, $tipos) {
                return $viatic->expenses->isNotEmpty()
                    ? $viatic->expenses->whereIn('type', $tipos)->sum(fn ($gasto) => (float) $gasto->amount)
                    : (float) $viatic->{$campo};
            });

            return [$campo => $total];
        });
    };
    $gasolina = $totalesPorCampo($viatics)['fuel'];
    $viajeCaro = $viatics->sortByDesc(fn ($v) => (float) $v->total_computed)->first();
    $vehiculos = $viatics->pluck('vehicle_name')->filter()->unique()->sort()->values();
    $porLugar = $viatics->groupBy(fn ($v) => $v->place ?: 'Sin destino')->map(fn ($items) => $items->sum(fn ($v) => (float) $v->total_computed))->sortDesc();
    $porVehiculo = $viatics->groupBy(fn ($v) => $v->vehicle_name ?: 'Sin vehículo')->map(fn ($items) => $items->sum(fn ($v) => (float) $v->total_computed))->sortDesc();
    $porGasto = $totalesPorCampo($viatics);
    $fechaDelViatico = fn ($v) => collect([$v->fecha_ida, $v->expense_date, $v->created_at])
        ->first(fn ($fecha) => filled($fecha));
    $mesesGasto = $viatics->groupBy(function ($v) use ($fechaDelViatico) {
        $fecha = $fechaDelViatico($v);
        return $fecha ? \Illuminate\Support\Carbon::parse($fecha)->format('Y-m') : 'Sin fecha';
    })
        ->map(fn ($items) => $totalesPorCampo($items))
        ->sortKeys();
    $maxMesGasto = max((float) ($mesesGasto->map(fn ($categorias) => $categorias->sum())->max() ?: 1), 1);
    $maxLugar = max((float) ($porLugar->max() ?: 1), 1);
    $mesNombres = ['01' => 'Ene', '02' => 'Feb', '03' => 'Mar', '04' => 'Abr', '05' => 'May', '06' => 'Jun', '07' => 'Jul', '08' => 'Ago', '09' => 'Sep', '10' => 'Oct', '11' => 'Nov', '12' => 'Dic'];
    $estadoMeta = ['pending' => ['Pendiente', 'pending'], 'approved' => ['Aprobado', 'approved'], 'rejected' => ['Rechazado', 'rejected']];
@endphp

@push('head')
<style>
.vt-page{display:grid;gap:18px;padding-bottom:30px}.vt-hero{display:flex;justify-content:space-between;align-items:flex-end;gap:20px;padding:24px;border:1px solid var(--border);border-radius:18px;background:linear-gradient(135deg,var(--surface),color-mix(in srgb,var(--surface) 82%,var(--primary)));box-shadow:var(--shadow)}.vt-kicker{margin:0 0 5px;color:var(--primary);font-size:12px;font-weight:800;text-transform:uppercase;letter-spacing:.08em}.vt-hero h1{margin:0;color:var(--text);font-size:clamp(28px,4vw,44px);line-height:1.05}.vt-hero p{margin:8px 0 0;color:var(--muted);font-size:14px}.vt-total{text-align:right;color:var(--text);font-size:clamp(28px,4vw,44px);font-weight:850}.vt-total small{display:block;color:var(--muted);font-size:12px}.vt-btn{display:inline-flex;align-items:center;gap:8px;min-height:40px;padding:0 15px;border-radius:10px;text-decoration:none;font:inherit;font-weight:750;cursor:pointer}.vt-btn.primary{border:1px solid var(--primary);background:var(--primary);color:white}.vt-tabs{display:flex;gap:8px;border-bottom:1px solid var(--border)}.vt-tab{border:0;border-bottom:2px solid transparent;padding:11px 17px;background:transparent;color:var(--muted);font:inherit;font-weight:750;cursor:pointer}.vt-tab[aria-selected=true]{border-color:var(--primary);color:var(--primary)}.vt-panel[hidden]{display:none}.vt-kpis{display:grid;grid-template-columns:repeat(6,minmax(0,1fr));gap:12px}.vt-kpi,.vt-card,.vt-row{border:1px solid var(--border);border-radius:14px;background:var(--surface);box-shadow:var(--shadow)}.vt-kpi{min-height:120px;padding:16px}.vt-kpi label{color:var(--muted);font-size:12px;font-weight:700}.vt-kpi strong{display:block;margin-top:8px;color:var(--text);font-size:25px}.vt-kpi small{display:block;margin-top:5px;color:var(--muted);font-size:11px;line-height:1.35}.vt-card{padding:19px}.vt-card h2{margin:0;color:var(--text);font-size:17px}.vt-sub{margin:5px 0 16px;color:var(--muted);font-size:12px}.vt-chart{display:flex;align-items:flex-end;gap:18px;height:260px;padding:12px 8px 0;border-top:1px solid var(--border);border-bottom:1px solid var(--border)}.vt-month{display:flex;flex:1;height:100%;min-width:30px;flex-direction:column;align-items:center;justify-content:flex-end;gap:6px}.vt-month b{font-size:10px;color:var(--text)}.vt-stack{position:relative;width:min(44px,80%);height:215px}.vt-stack i{position:absolute;left:0;width:100%;min-height:1px;border-radius:3px 3px 0 0}.vt-month span{color:var(--muted);font-size:11px}.vt-legend{display:flex;flex-wrap:wrap;gap:8px 16px;margin-top:14px;color:var(--muted);font-size:12px}.vt-legend i{display:inline-block;width:10px;height:10px;margin-right:5px;border-radius:3px}.vt-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px;margin-top:16px}.vt-bars{display:grid;gap:11px}.vt-bar{display:grid;grid-template-columns:110px 1fr auto;align-items:center;gap:9px;color:var(--muted);font-size:12px}.vt-track{height:8px;border-radius:99px;background:var(--surface-2);overflow:hidden}.vt-track span{display:block;height:100%;border-radius:inherit;background:var(--primary)}.vt-bar strong{color:var(--text);font-size:12px}.vt-filter{display:grid;grid-template-columns:1.6fr 1fr 1fr auto;gap:12px;align-items:end;padding:16px;border:1px solid var(--border);border-radius:16px;background:var(--surface)}.vt-filter label{display:grid;gap:6px;color:var(--text);font-size:12px;font-weight:700}.vt-filter input,.vt-filter select{min-height:42px;border:1px solid var(--border);border-radius:10px;background:var(--surface);color:var(--text);padding:0 12px;font:inherit}.vt-list{display:grid;gap:10px;margin-top:16px}.vt-row{display:grid;grid-template-columns:1.5fr .8fr 1fr auto auto;align-items:center;gap:14px;padding:15px 16px}.vt-row:hover{border-color:var(--primary)}.vt-name{color:var(--text);font-weight:800}.vt-meta{display:flex;gap:12px;margin-top:5px;color:var(--muted);font-size:12px}.vt-money{color:var(--text);font-weight:850}.vt-status{display:inline-flex;padding:4px 9px;border-radius:99px;font-size:11px;font-weight:800}.vt-status.pending{background:#fef3c7;color:#92400e}.vt-status.approved{background:#dcfce7;color:#166534}.vt-status.rejected{background:#fee2e2;color:#991b1b}.vt-actions{display:flex;gap:7px}.vt-actions a{display:grid;place-items:center;width:34px;height:34px;border:1px solid var(--border);border-radius:9px;color:var(--muted)}.vt-actions a:hover{border-color:var(--primary);color:var(--primary)}.vt-empty{padding:40px;border:1px dashed var(--border);border-radius:14px;color:var(--muted);text-align:center}@media(max-width:1100px){.vt-kpis{grid-template-columns:repeat(3,1fr)}}@media(max-width:850px){.vt-kpis{grid-template-columns:repeat(2,1fr)}.vt-filter{grid-template-columns:1fr 1fr}.vt-filter .vt-btn{grid-column:1/-1}.vt-row{grid-template-columns:1fr auto}.vt-row>div:nth-child(2),.vt-row>div:nth-child(3){display:none}}@media(max-width:600px){.vt-hero{display:grid;padding:18px}.vt-total{text-align:left}.vt-kpis,.vt-grid,.vt-filter{grid-template-columns:1fr}.vt-filter .vt-btn{grid-column:auto}.vt-row{grid-template-columns:1fr auto}.vt-chart{gap:5px}.vt-month span{font-size:9px}}
/* Ajuste visual de la gráfica mensual */
.vt-chart{align-items:stretch;height:330px;padding:20px 10px 0;gap:10px}
.vt-chart{position:relative;padding-left:74px;background:repeating-linear-gradient(to bottom,transparent 0,transparent 24.5%,color-mix(in srgb,var(--border) 80%,white) 24.5%,color-mix(in srgb,var(--border) 80%,white) 25%)}
.vt-chart:before{content:'$30,000\A\A$22,500\A\A$15,000\A\A$7,500\A\A$0';position:absolute;left:0;top:17px;bottom:24px;width:60px;white-space:pre;color:var(--muted);font-size:11px;line-height:1;text-align:right;display:flex;flex-direction:column;justify-content:space-between}
.vt-chart:before{content:'$30,000\A$22,500\A$15,000\A$7,500\A$0';top:17px;bottom:auto;width:68px;font-size:13px;line-height:72px;display:block;white-space:pre}
.vt-yaxis{display:flex;flex:0 0 58px;flex-direction:column;justify-content:space-between;padding:0 0 25px;color:var(--muted);font-size:11px;text-align:right}
.vt-plot{position:relative;flex:1;min-width:0;height:100%;padding-bottom:25px}
.vt-gridlines{position:absolute;inset:0 0 25px;display:flex;flex-direction:column;justify-content:space-between}
.vt-gridlines span{display:block;border-top:1px solid color-mix(in srgb,var(--border) 80%,white);opacity:.8}
.vt-columns{position:relative;z-index:1;display:flex;align-items:flex-end;justify-content:space-around;gap:18px;height:100%}
.vt-month{max-width:72px}
.vt-month b{font-size:11px;white-space:nowrap}
.vt-stack{width:min(52px,80%);height:260px}
.vt-stack i{border-radius:0}
.vt-stack i:last-child{border-radius:3px 3px 0 0}
.vt-month span{font-size:12px}
.vt-legend{margin-top:16px;font-size:13px}
.vt-grid .vt-card:first-child .vt-bars{grid-template-columns:minmax(190px,240px) 1fr;align-items:center;column-gap:24px}
.vt-grid .vt-card:first-child .vt-bars:before{content:'';grid-row:1 / span 5;width:190px;height:190px;border-radius:50%;background:conic-gradient(var(--donut, #1683f7 0 100%));-webkit-mask:radial-gradient(circle,transparent 0 54%,#000 55%);mask:radial-gradient(circle,transparent 0 54%,#000 55%)}
.vt-grid .vt-card:first-child .vt-bars{position:relative}
.vt-grid .vt-card:first-child .vt-bars:after{content:var(--donut-total,'Total');position:absolute;left:95px;top:95px;width:190px;color:var(--text);font-size:14px;font-weight:700;line-height:1.35;text-align:center;white-space:pre;transform:translate(-50%,-50%)}
.vt-grid .vt-card:first-child .vt-bar{grid-column:2;grid-template-columns:95px 1fr auto}
.vt-grid .vt-card:first-child .vt-bar:after{content:''}
@media(max-width:700px){.vt-grid .vt-card:first-child .vt-bars{grid-template-columns:1fr}.vt-grid .vt-card:first-child .vt-bars:before{grid-row:auto;justify-self:center}.vt-grid .vt-card:first-child .vt-bar{grid-column:auto}}
</style>
@endpush

@section('content')
<div class="vt-page">
    <section class="vt-hero"><div><p class="vt-kicker">Gastos de viaje · {{ now()->year }}</p><h1>Viáticos y viajes</h1><p>Gastado en {{ $viatics->count() }} viajes. Promedio de ${{ number_format($promedio, 0) }} por viaje.</p></div><div><div class="vt-total">${{ number_format($total, 2) }}<small>Gasto acumulado</small></div><a class="vt-btn primary" href="{{ route('admin.viatics.create') }}">+ Nuevo viaje</a></div></section>
    <nav class="vt-tabs" role="tablist"><button class="vt-tab" type="button" aria-selected="true" data-vt-tab="resumen">Resumen</button><button class="vt-tab" type="button" aria-selected="false" data-vt-tab="viajes">Viajes</button><button class="vt-tab" type="button" aria-selected="false" data-vt-tab="camionetas">Camionetas</button></nav>
    <section class="vt-panel" data-vt-panel="resumen">
        <div class="vt-kpis"><div class="vt-kpi"><label>Viajes registrados</label><strong>{{ $viatics->count() }}</strong><small>Registros en el sistema</small></div><div class="vt-kpi"><label>Gasto acumulado</label><strong>${{ number_format($total, 2) }}</strong><small>Todos los gastos registrados</small></div><div class="vt-kpi"><label>Por comprobar</label><strong>{{ $pendientes }}</strong><small>Viajes pendientes</small></div><div class="vt-kpi"><label>Aprobados</label><strong>{{ $viatics->where('status', 'approved')->count() }}</strong><small>Viajes autorizados</small></div><div class="vt-kpi"><label>Gasolina</label><strong>${{ number_format($gasolina, 2) }}</strong><small>Gasto registrado</small></div><div class="vt-kpi"><label>Viaje más caro</label><strong>${{ number_format($viajeCaro?->total_computed ?? 0, 2) }}</strong><small>{{ $viajeCaro?->place ?: 'Sin destino' }}</small></div></div>
        <section class="vt-card" style="margin-top:16px"><h2>Gasto por mes</h2><p class="vt-sub">Cada barra muestra el total mensual dividido por tipo de gasto.</p><div class="vt-chart">@forelse($mesesGasto as $mes => $categorias)@php($acumulado=0)<div class="vt-month"><b>${{ number_format($categorias->sum()/1000,1) }}k</b><div class="vt-stack" title="${{ number_format($categorias->sum(),2) }}">@foreach($gastos as $campo => $meta)@php($parte=(float) $categorias[$campo])<i style="height:{{ ($parte/$maxMesGasto)*100 }}%;bottom:{{ $acumulado }}%;background:{{ $meta[1] }}"></i>@php($acumulado += ($parte/$maxMesGasto)*100)@endforeach</div><span>{{ $mes === 'Sin fecha' ? $mes : ($mesNombres[substr($mes,5)] ?? $mes) }}</span></div>@empty<div class="vt-empty">Todavía no hay gastos.</div>@endforelse</div><div class="vt-legend">@foreach($gastos as $meta)<span><i style="background:{{ $meta[1] }}"></i>{{ $meta[0] }}</span>@endforeach</div></section>
        <div class="vt-grid"><section class="vt-card"><h2>En qué se gasta</h2><p class="vt-sub">Reparto del total por tipo.</p><div class="vt-bars">@foreach($gastos as $campo => $meta)<div class="vt-bar"><span>{{ $meta[0] }}</span><div class="vt-track"><span style="width:{{ $total ? ($porGasto[$campo]/$total)*100 : 0 }}%;background:{{ $meta[1] }}"></span></div><strong>${{ number_format($porGasto[$campo],2) }}</strong></div>@endforeach</div></section><section class="vt-card"><h2>Destinos que más cuestan</h2><p class="vt-sub">Total acumulado por ciudad.</p><div class="vt-bars">@forelse($porLugar->take(8) as $nombre => $monto)<div class="vt-bar"><span>{{ $nombre }}</span><div class="vt-track"><span style="width:{{ ($monto/$maxLugar)*100 }}%"></span></div><strong>${{ number_format($monto,2) }}</strong></div>@empty<div class="vt-empty">Todavía no hay destinos.</div>@endforelse</div></section></div>
    </section>
    <section class="vt-panel" data-vt-panel="viajes" hidden><form class="vt-filter" method="GET"><label>Buscar<input type="search" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Ciudad, persona o vehículo"></label><label>Vehículo<select name="vehicle"><option value="">Todos</option>@foreach($vehiculos as $vehiculo)<option value="{{ $vehiculo }}" @selected(($filters['vehicle'] ?? '') === $vehiculo)>{{ $vehiculo }}</option>@endforeach</select></label><label>Estatus<select name="status"><option value="">Todos</option>@foreach(\App\Services\Administracion\Viaticos::ESTADOS as $valor => $texto)<option value="{{ $valor }}" @selected(($filters['status'] ?? '') === $valor)>{{ $texto }}</option>@endforeach</select></label><button class="vt-btn primary" type="submit">Aplicar filtros</button></form><div class="vt-list">@forelse($viatics as $viatic)@php($estado=$estadoMeta[$viatic->status]??['Pendiente','pending']) @php($fecha=$fechaDelViatico($viatic))<article class="vt-row"><div><div class="vt-name">{{ $viatic->place ?: 'Sin destino' }}</div><div class="vt-meta"><span>{{ $fecha ? \Illuminate\Support\Carbon::parse($fecha)->format('d/m/Y') : 'Sin fecha' }}</span><span>{{ $viatic->user?->name ?: 'Sin usuario' }}</span></div></div><div>{{ $viatic->vehicle_name ?: 'Sin vehículo' }}</div><div>{{ $viatic->description ?: 'Sin descripción' }}</div><div><div class="vt-money">${{ $viatic->total }}</div><span class="vt-status {{ $estado[1] }}">{{ $estado[0] }}</span></div><div class="vt-actions"><a href="{{ route('admin.viatics.show',$viatic) }}" aria-label="Ver">›</a><a href="{{ route('admin.viatics.edit',$viatic) }}" aria-label="Editar">✎</a></div></article>@empty<div class="vt-empty">No hay viáticos con esos filtros.</div>@endforelse</div></section>
    <section class="vt-panel" data-vt-panel="camionetas" hidden><div class="vt-grid">@forelse($porVehiculo as $nombre => $monto)<article class="vt-card"><h2>{{ $nombre }}</h2><p class="vt-sub">{{ $viatics->filter(fn($v)=>($v->vehicle_name?:'Sin vehículo')===$nombre)->count() }} viajes registrados</p><strong class="vt-money">${{ number_format($monto,2) }}</strong></article>@empty<div class="vt-empty">No hay datos de vehículos.</div>@endforelse</div></section>
</div>
<script>document.querySelectorAll('[data-vt-tab]').forEach(function(t){t.addEventListener('click',function(){document.querySelectorAll('[data-vt-tab]').forEach(x=>x.setAttribute('aria-selected',x===t?'true':'false'));document.querySelectorAll('[data-vt-panel]').forEach(x=>x.hidden=x.dataset.vtPanel!==t.dataset.vtTab)})});(function(){var chart=document.querySelector('.vt-grid .vt-card:first-child .vt-bars');if(!chart)return;var segments=[],start=0,total=0;chart.querySelectorAll('.vt-bar').forEach(function(row){var fill=row.querySelector('.vt-track span');var width=parseFloat(fill&&fill.style.width)||0;var color=fill&&fill.style.background||'#1683f7';var amount=parseFloat((row.querySelector('strong')||{}).textContent||'0')||0;segments.push(color+' '+start+'% '+(start+width)+'%');start+=width;total+=amount});chart.style.setProperty('--donut',segments.join(','));chart.style.setProperty('--donut-total','"Total\\A$'+total.toLocaleString('en-US',{maximumFractionDigits:0})+'"');})();</script>
@endsection
