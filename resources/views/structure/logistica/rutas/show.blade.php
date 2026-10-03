@extends('layouts.dashboard')

@section('title', $ruta->folio)
@section('page-title', $ruta->folio)
@section('page-sub', ($ruta->vehicle ? $ruta->vehicle->plate_number.' · ' : '').($ruta->chofer?->name ?? 'Sin chofer').' · '.($ruta->fecha_programada?->format('d/m/Y') ?? 'Sin fecha'))

@push('head')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css">
@endpush

@section('content')
    @if (session('status'))
        <x-ui.alert type="ok" style="margin-bottom:14px;">{{ session('status') }}</x-ui.alert>
    @endif
    @if ($errors->any())
        <x-ui.alert type="err" style="margin-bottom:14px;">{{ $errors->first() }}</x-ui.alert>
    @endif

    {{-- ================= Acciones ================= --}}
    <div class="rs-acciones">
        <span class="badge {{ $ruta->estadoTono() }}" id="estado-ruta">{{ $ruta->estadoLabel() }}</span>
        <a href="{{ route('logistica.rutas.index') }}" class="btn btn--ghost"><x-ui.icon name="arrow-left" :size="16" /> Rutas</a>
        <span style="flex:1"></span>

        @if (! $ruta->cerrada())
            @can('rutas.crear')
                <form method="POST" action="{{ route('logistica.rutas.optimizar', $ruta) }}">@csrf
                    <button class="btn" type="submit"><x-ui.icon name="zap" :size="16" /> {{ $ruta->activa() ? 'Reoptimizar pendientes' : 'Optimizar ruta' }}</button>
                </form>
            @endcan
        @endif
        @if ($ruta->editable())
            @can('rutas.editar')<a href="{{ route('logistica.rutas.edit', $ruta) }}" class="btn btn--ghost">Editar</a>@endcan
        @endif
        @if ($ruta->estado === \App\Models\Ruta::PROGRAMADA)
            @can('rutas.operar')
                <form method="POST" action="{{ route('logistica.rutas.iniciar', $ruta) }}"
                      data-confirm="Se marca la ruta como en curso y el chofer podrá reportar su ubicación." data-confirm-title="Iniciar ruta" data-confirm-ok="Iniciar">@csrf
                    <button class="btn" type="submit"><x-ui.icon name="play" :size="14" /> Iniciar ruta</button>
                </form>
            @endcan
        @endif
        @if ($ruta->activa())
            @can('rutas.operar')
                <form method="POST" action="{{ route('logistica.rutas.finalizar', $ruta) }}"
                      data-confirm="Las paradas que sigan pendientes quedarán sin resolver." data-confirm-title="Finalizar ruta" data-confirm-ok="Finalizar">@csrf
                    <button class="btn btn--ghost" type="submit">Finalizar</button>
                </form>
            @endcan
        @endif
        @if (! $ruta->cerrada())
            @can('rutas.operar')
                <form method="POST" action="{{ route('logistica.rutas.cancelar', $ruta) }}"
                      data-confirm="La ruta queda cancelada y conserva su historial." data-confirm-title="Cancelar ruta" data-confirm-ok="Cancelar ruta">@csrf
                    <button class="btn btn--ghost" type="submit">Cancelar ruta</button>
                </form>
            @endcan
        @endif
        @if (! $ruta->activa())
            @can('rutas.eliminar')
                <form method="POST" action="{{ route('logistica.rutas.destroy', $ruta) }}"
                      data-confirm="Se elimina la ruta con sus paradas, evidencias y recorrido. No se puede deshacer." data-confirm-title="Eliminar ruta">@csrf @method('DELETE')
                    <button class="btn btn--danger" type="submit">Eliminar</button>
                </form>
            @endcan
        @endif
    </div>

    {{-- ================= Resumen ================= --}}
    <div class="rs-resumen">
        <div class="rs-dato"><div class="k">Distancia</div><div class="v">{{ $ruta->distancia_m !== null ? $ruta->distanciaKm().' km' : '—' }}</div></div>
        <div class="rs-dato"><div class="k">Duración estimada</div><div class="v">{{ $ruta->duracionTexto() ?? '—' }}</div></div>
        <div class="rs-dato"><div class="k">Salida</div><div class="v">{{ $ruta->hora_salida ? substr($ruta->hora_salida, 0, 5) : '—' }}</div></div>
        <div class="rs-dato"><div class="k">Avance</div><div class="v" id="avance-txt">{{ $ruta->avance()['hechas'] }}/{{ $ruta->avance()['total'] }}</div></div>
        @if ($ruta->km_recorridos !== null)
            <div class="rs-dato"><div class="k">Km reales (GPS)</div><div class="v">{{ $ruta->km_recorridos }} km</div></div>
        @endif
        <div class="rs-dato"><div class="k">Cálculo</div><div class="v" style="font-size:14px;">{{ $ruta->optimizada_con === 'google' ? 'Google · con tráfico' : ($ruta->optimizada_con === 'local' ? 'Local · estimado' : 'Sin optimizar') }}</div></div>
    </div>

    <div class="rs-grid">
        <div class="rs-col">
            {{-- ================= Paradas ================= --}}
            <div class="card" style="padding:0;">
                <div class="rs-cab">Recorrido</div>

                <div class="rs-par rs-par--origen">
                    <span class="rs-pin origen">S</span>
                    <div class="rs-par-cuerpo">
                        <div class="rs-par-t">{{ $ruta->origen_nombre ?: 'Punto de salida' }}</div>
                        <div class="rs-par-s">{{ $ruta->origen_direccion ?: 'Sin dirección' }}@if ($ruta->iniciada_en) · salió {{ $ruta->iniciada_en->format('H:i') }}@endif</div>
                    </div>
                </div>

                @foreach ($ruta->paradas as $p)
                    <div class="rs-par" data-parada-id="{{ $p->id }}">
                        <span class="rs-pin est-{{ $p->estado }}">{{ $p->orden }}</span>
                        <div class="rs-par-cuerpo">
                            <div class="rs-par-fila">
                                <div class="rs-par-t">{{ $p->nombre }}@if ($p->urgente) <span class="rs-urg">URGENTE</span>@endif</div>
                                <span class="badge {{ $p->estadoTono() }}" data-estado>{{ $p->estadoLabel() }}</span>
                            </div>
                            <div class="rs-par-s">{{ $p->direccion ?: 'Sin dirección' }}@unless ($p->tieneCoordenadas()) · <b style="color:var(--accent)">sin ubicar</b>@endunless</div>

                            <div class="rs-par-meta">
                                @if ($p->distancia_desde_anterior_m !== null)
                                    <span>{{ round($p->distancia_desde_anterior_m / 1000, 1) }} km desde el tramo anterior</span>
                                @endif
                                @if ($p->eta && ! $p->resuelta())<span>ETA {{ $p->eta->format('H:i') }}</span>@endif
                                @if ($p->ventana_desde || $p->ventana_hasta)
                                    <span>Recibe {{ $p->ventana_desde ? substr($p->ventana_desde, 0, 5) : '—' }}–{{ $p->ventana_hasta ? substr($p->ventana_hasta, 0, 5) : '—' }}</span>
                                @endif
                                @if ($p->contacto_nombre || $p->contacto_telefono)
                                    <span>{{ $p->contacto_nombre }} {{ $p->contacto_telefono }}</span>
                                @endif
                                @if ($p->ordenSalida)
                                    <a href="{{ route('inventory.salidas.show', $p->ordenSalida) }}">{{ $p->ordenSalida->folio }}</a>
                                @endif
                            </div>

                            @if ($p->items->count())
                                <div class="rs-items">
                                    @foreach ($p->items as $it)<span>{{ $it->cantidad }} × {{ $it->descripcion }}</span>@endforeach
                                </div>
                            @endif
                            @if ($p->instrucciones)<div class="rs-par-s" style="margin-top:6px;"><x-ui.icon name="note" :size="14" /> {{ $p->instrucciones }}</div>@endif

                            @if ($p->llegada_en || $p->completada_en)
                                <div class="rs-tiempos" data-tiempos>
                                    @if ($p->llegada_en)Llegada {{ $p->llegada_en->format('H:i') }} ({{ $p->llegada_origen === 'auto' ? 'automática' : 'manual' }})@endif
                                    @if ($p->salida_en) · Salida {{ $p->salida_en->format('H:i') }}@endif
                                </div>
                            @endif

                            @if ($p->estado === \App\Models\RutaParada::COMPLETADA)
                                <div class="rs-evid">
                                    <div class="rs-par-s">Recibió: <b>{{ $p->receptor_nombre ?: '—' }}</b></div>
                                    <div class="rs-fotos">
                                        @foreach ($p->evidencias as $ev)
                                            <a href="{{ $ev->url() }}" target="_blank" class="rs-foto">
                                                @if ($ev->tipo === 'remision' && str_ends_with(strtolower($ev->archivo), '.pdf'))
                                                    <span class="rs-pdf"><x-ui.icon name="file" :size="16" /> Remisión</span>
                                                @else
                                                    <img src="{{ $ev->url() }}" alt="{{ $ev->tipo === 'remision' ? 'Remisión' : 'Foto' }}" loading="lazy">
                                                @endif
                                            </a>
                                        @endforeach
                                        @if ($p->firmaUrl())
                                            <a href="{{ $p->firmaUrl() }}" target="_blank" class="rs-foto rs-foto--firma"><img src="{{ $p->firmaUrl() }}" alt="Firma" loading="lazy"></a>
                                        @endif
                                    </div>
                                    @if ($p->cierre_lat)
                                        <div class="rs-par-s"><x-ui.icon name="pin" :size="14" /> {{ number_format($p->cierre_lat, 5) }}, {{ number_format($p->cierre_lng, 5) }}</div>
                                    @endif
                                </div>
                            @elseif ($p->estado === \App\Models\RutaParada::NO_ENTREGADA)
                                <div class="rs-par-s" style="margin-top:6px; color:var(--danger);">No entregada: {{ $p->motivo_no_entrega }}</div>
                            @endif
                        </div>
                    </div>
                @endforeach

                @if ($ruta->regresa_a_origen && $ruta->tieneOrigen())
                    <div class="rs-par rs-par--origen">
                        <span class="rs-pin origen"><x-ui.icon name="undo" :size="15" /></span>
                        <div class="rs-par-cuerpo"><div class="rs-par-t">Regreso a {{ $ruta->origen_nombre ?: 'la bodega' }}</div></div>
                    </div>
                @endif
            </div>

            {{-- ================= Desviaciones e incidencias ================= --}}
            @if ($ruta->desviaciones->count() || $ruta->incidencias->count())
                <div class="card" style="padding:0;">
                    <div class="rs-cab">Alertas de la ruta</div>
                    @foreach ($ruta->desviaciones as $x)
                        <div class="rs-ev">
                            <span class="h">{{ $x->inicio_en->format('H:i') }}</span>
                            <span><b style="color:var(--accent)">Desviación</b> de hasta {{ $x->max_distancia_m }} m ·
                                {{ $x->abierta() ? 'sigue fuera de la ruta' : 'duró '.$x->inicio_en->diffInMinutes($x->fin_en).' min' }}</span>
                        </div>
                    @endforeach
                    @foreach ($ruta->incidencias as $i)
                        <div class="rs-ev">
                            <span class="h">{{ $i->created_at->format('H:i') }}</span>
                            <span><b style="color:var(--danger)">{{ $i->tipoLabel() }}</b>@if ($i->descripcion) — {{ $i->descripcion }}@endif
                                <em>· {{ $i->usuario?->name }} · {{ $i->resuelta_en ? 'resuelta' : 'abierta' }}</em>
                                @if ($i->fotoUrl()) · <a href="{{ $i->fotoUrl() }}" target="_blank">foto</a>@endif</span>
                        </div>
                    @endforeach
                </div>
            @endif

            {{-- ================= Bitácora ================= --}}
            <div class="card" style="padding:0;">
                <div class="rs-cab">Bitácora</div>
                <div class="rs-bit">
                    @forelse ($ruta->eventos->sortByDesc('ocurrio_en') as $ev)
                        <div class="rs-ev">
                            <span class="h">{{ $ev->ocurrio_en->format('d/m H:i') }}</span>
                            <span>{{ $ev->detalle ?? $ev->tipo }}@if ($ev->usuario) <em>· {{ $ev->usuario->name }}</em>@endif</span>
                        </div>
                    @empty
                        <div class="rs-par-s" style="padding:14px 16px;">Sin eventos todavía.</div>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- ================= Mapa ================= --}}
        <div class="rs-mapa-col">
            <div class="card" style="padding:0; overflow:hidden;">
                <div id="mapa-ruta"></div>
                <div class="rs-leyenda">
                    <span><i style="background:#007aff"></i> Planeada</span>
                    <span><i style="background:#f97316"></i> Recorrido real</span>
                    <span id="pos-txt" class="rs-pos">@if ($ruta->ultimaUbicacion) Último reporte {{ $ruta->ultimaUbicacion->registrada_en->diffForHumans() }}@else Sin posición del vehículo @endif</span>
                </div>
            </div>
        </div>
    </div>

    <style>
        .rs-acciones { display:flex; flex-wrap:wrap; align-items:center; gap:10px; margin-bottom:16px; }
        .rs-acciones form { margin:0; }
        .rs-resumen { display:grid; grid-template-columns:repeat(auto-fit, minmax(150px, 1fr)); gap:12px; margin-bottom:16px; }
        .rs-dato { background:var(--surface); border:1px solid var(--border); border-radius:10px; padding:12px 14px; }
        .rs-dato .k { font-size:11.5px; color:var(--muted); text-transform:uppercase; letter-spacing:.03em; font-weight:600; }
        .rs-dato .v { margin-top:4px; font-size:20px; font-weight:700; }
        .rs-grid { display:grid; grid-template-columns:minmax(0,1fr) minmax(0,1.1fr); gap:18px; align-items:start; }
        .rs-col { display:flex; flex-direction:column; gap:16px; min-width:0; }
        .rs-mapa-col { position:sticky; top:16px; }
        #mapa-ruta { height:min(72vh,680px); min-height:360px; background:var(--surface-2); }
        .rs-leyenda { display:flex; flex-wrap:wrap; gap:14px; padding:10px 14px; border-top:1px solid var(--border); font-size:12.5px; color:var(--muted); }
        .rs-leyenda i { display:inline-block; width:18px; height:4px; border-radius:2px; margin-right:5px; vertical-align:middle; }
        .rs-pos { margin-left:auto; }
        .rs-cab { padding:13px 16px; font-weight:700; border-bottom:1px solid var(--border); }
        .rs-par { display:flex; gap:12px; padding:14px 16px; border-bottom:1px solid var(--border); }
        .rs-par:last-child { border-bottom:0; }
        .rs-par-cuerpo { flex:1; min-width:0; }
        .rs-par-fila { display:flex; align-items:center; justify-content:space-between; gap:10px; }
        .rs-par-t { font-weight:600; }
        .rs-par-s { margin-top:2px; font-size:12.5px; color:var(--muted); }
        .rs-par-meta { display:flex; flex-wrap:wrap; gap:4px 14px; margin-top:6px; font-size:12px; color:var(--muted); }
        .rs-items { display:flex; flex-wrap:wrap; gap:6px; margin-top:8px; }
        .rs-items span { background:var(--surface-2); border:1px solid var(--border); border-radius:6px; padding:3px 8px; font-size:12px; }
        .rs-urg { margin-left:6px; background:var(--danger-soft); color:var(--danger); border-radius:5px; padding:1px 6px; font-size:10.5px; font-weight:700; }
        .rs-tiempos { margin-top:6px; font-size:12px; color:var(--green); font-weight:600; }
        .rs-evid { margin-top:8px; padding-top:8px; border-top:1px dashed var(--border); }
        .rs-fotos { display:flex; flex-wrap:wrap; gap:8px; margin-top:6px; }
        .rs-foto { display:block; width:76px; height:76px; border:1px solid var(--border); border-radius:8px; overflow:hidden; background:#fff; }
        .rs-foto img { width:100%; height:100%; object-fit:cover; display:block; }
        .rs-foto--firma { width:120px; }
        .rs-foto--firma img { object-fit:contain; }
        .rs-pdf { display:flex; height:100%; align-items:center; justify-content:center; font-size:11px; color:var(--text); text-align:center; }
        .rs-pin { flex:0 0 28px; height:28px; border-radius:50%; background:#94a3b8; color:#fff; display:flex; align-items:center; justify-content:center; font-weight:700; font-size:12px; }
        .rs-pin.origen { background:#15803d; }
        .rs-pin.est-en_camino { background:#f97316; }
        .rs-pin.est-arribo { background:#007aff; }
        .rs-pin.est-completada { background:#15803d; }
        .rs-pin.est-no_entregada, .rs-pin.est-cancelada { background:#dc2626; }
        .rs-bit { max-height:320px; overflow:auto; }
        .rs-ev { display:flex; gap:12px; padding:9px 16px; border-bottom:1px solid var(--border); font-size:13px; }
        .rs-ev:last-child { border-bottom:0; }
        .rs-ev .h { flex:0 0 78px; color:var(--muted); font-size:12px; }
        .rs-ev em { color:var(--muted); font-style:normal; }
        .mk-pin { width:28px; height:28px; border-radius:50%; color:#fff; border:2px solid #fff; display:flex; align-items:center; justify-content:center; font-weight:700; font-size:12px; box-shadow:0 2px 6px rgba(0,0,0,.35); }
        .mk-camion { width:30px; height:30px; border-radius:50%; background:var(--primary); color:#fff; border:2px solid #fff; display:flex; align-items:center; justify-content:center; }
        @media (max-width: 960px) {
            .rs-grid { grid-template-columns:1fr; }
            .rs-mapa-col { position:static; order:-1; }
            #mapa-ruta { height:320px; min-height:0; }
        }
    </style>
@endsection

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js"></script>
<script>
(function () {
    const d = @json($datosMapa);
    const urlFeed = @json(route('logistica.rutas.feed', $ruta));
    const estadoInicial = @json($ruta->estado);
    const TRUCK = '<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>';
    const colores = { pendiente: '#94a3b8', en_camino: '#f97316', arribo: '#007aff', completada: '#15803d', no_entregada: '#dc2626', cancelada: '#dc2626' };

    const mapa = L.map('mapa-ruta').setView([19.4, -99.5], 9);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19, attribution: '© OpenStreetMap' }).addTo(mapa);

    const icono = (txt, color) => L.divIcon({ className: '', html: '<div class="mk-pin" style="background:' + color + '">' + txt + '</div>', iconSize: [28, 28], iconAnchor: [14, 14] });
    const pts = [];
    const marcadores = {};

    if (d.origen) {
        L.marker([d.origen.lat, d.origen.lng], { icon: icono('S', '#15803d') }).addTo(mapa).bindTooltip(d.origen.nombre);
        pts.push([d.origen.lat, d.origen.lng]);
    }

    d.paradas.forEach(p => {
        if (p.lat === null) return;
        marcadores[p.id] = L.marker([p.lat, p.lng], { icon: icono(p.n, colores[p.estado] || '#94a3b8') }).addTo(mapa).bindTooltip(p.nombre);
        L.circle([p.lat, p.lng], { radius: p.radio, color: colores[p.estado], weight: 1, fillOpacity: .07 }).addTo(mapa);
        pts.push([p.lat, p.lng]);
    });

    if (d.trazoPlaneado.length > 1) {
        L.polyline(d.trazoPlaneado, { color: '#007aff', weight: 5, opacity: .75 }).addTo(mapa);
        d.trazoPlaneado.forEach(x => pts.push(x));
    }

    (d.desviaciones || []).forEach(x => {
        L.circleMarker([x.lat, x.lng], { radius: 9, color: '#dc2626', fillColor: '#f97316', fillOpacity: .9, weight: 2 }).addTo(mapa)
            .bindTooltip('Desviación ' + x.m + ' m · ' + x.hora);
        pts.push([x.lat, x.lng]);
    });

    const real = L.polyline(d.trazoReal, { color: '#f97316', weight: 4, opacity: .95 }).addTo(mapa);
    d.trazoReal.forEach(x => pts.push(x));

    let camion = null;
    function ponerCamion(pos) {
        if (!pos) return;
        const ll = [pos.lat, pos.lng];
        if (!camion) {
            camion = L.marker(ll, { icon: L.divIcon({ className: '', html: '<div class="mk-camion">' + TRUCK + '</div>', iconSize: [28, 28], iconAnchor: [14, 14] }), zIndexOffset: 1000 }).addTo(mapa);
        } else { camion.setLatLng(ll); }
        camion.bindTooltip((pos.velocidad !== null ? Math.round(pos.velocidad) + ' km/h · ' : '') + pos.hace);
    }
    ponerCamion(d.vivo.posicion);
    if (d.vivo.posicion) pts.push([d.vivo.posicion.lat, d.vivo.posicion.lng]);

    if (pts.length) mapa.fitBounds(pts, { padding: [40, 40], maxZoom: 15 });

    // ---------- Seguimiento en vivo (solo mientras la ruta está en curso) ----------
    if (estadoInicial !== 'en_ruta') return;

    const tonos = { pendiente: '', en_camino: 'badge--warn', arribo: 'badge--info', completada: 'badge--ok', no_entregada: 'badge--danger', cancelada: 'badge--danger' };

    async function refrescar() {
        try {
            const r = await fetch(urlFeed, { headers: { Accept: 'application/json' } });
            if (!r.ok) return;
            const v = await r.json();

            if (v.estado !== estadoInicial) { location.reload(); return; }

            if (v.posicion) {
                ponerCamion(v.posicion);
                real.addLatLng([v.posicion.lat, v.posicion.lng]);
                document.getElementById('pos-txt').textContent = 'Último reporte ' + v.posicion.hace;
            }

            document.getElementById('avance-txt').textContent = v.avance.hechas + '/' + v.avance.total;

            let cambio = false;
            v.paradas.forEach(p => {
                const fila = document.querySelector('[data-parada-id="' + p.id + '"]');
                if (!fila) return;
                const b = fila.querySelector('[data-estado]');
                if (b.textContent.trim() !== p.estadoLabel) cambio = true;
                b.textContent = p.estadoLabel;
                b.className = 'badge ' + (tonos[p.estado] || '');
                fila.querySelector('.rs-pin').className = 'rs-pin est-' + p.estado;
                if (marcadores[p.id]) marcadores[p.id].setIcon(icono(fila.querySelector('.rs-pin').textContent, colores[p.estado] || '#94a3b8'));
            });
            // Una entrega nueva trae fotos y firma: se recarga para mostrarlas
            if (cambio) setTimeout(() => location.reload(), 600);
        } catch (e) { /* sin red: se reintenta */ }
    }
    setInterval(refrescar, 5000);
})();
</script>
@endpush
