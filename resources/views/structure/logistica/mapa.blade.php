@extends('layouts.dashboard')

@section('title', 'Mapa en vivo')
@section('page-title', 'Flota en tiempo real')
@section('page-sub', 'Dónde va cada unidad y cuántas paradas lleva')

@push('head')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css">
@endpush

@section('content')
    <div class="mp-grid">
        <div class="card mp-mapa-card">
            <div id="mapa-flota"></div>
            <div class="mp-pie"><span id="mp-estado">Conectando…</span></div>
        </div>

        <div class="mp-lateral">
            <div class="card" style="padding:0;">
                <div class="mp-cab">Unidades en ruta <span id="mp-n" class="mp-n">0</span></div>
                <div id="mp-lista"></div>
            </div>
            <div class="card" style="padding:0;">
                <div class="mp-cab">Disponibles <span id="mp-nl" class="mp-n">0</span></div>
                <div id="mp-libres"></div>
            </div>
        </div>
    </div>

    <style>
        .mp-grid { display:grid; grid-template-columns:minmax(0,2fr) minmax(300px,1fr); gap:18px; align-items:start; }
        .mp-mapa-card { padding:0; overflow:hidden; }
        #mapa-flota { height:min(76vh,720px); min-height:380px; background:var(--surface-2); }
        .mp-pie { padding:9px 14px; font-size:12.5px; color:var(--muted); border-top:1px solid var(--border); }
        .mp-lateral { display:flex; flex-direction:column; gap:16px; }
        .mp-cab { display:flex; align-items:center; justify-content:space-between; padding:13px 16px; font-weight:700; border-bottom:1px solid var(--border); }
        .mp-n { background:var(--surface-2); border:1px solid var(--border); border-radius:10px; padding:0 8px; font-size:12px; color:var(--muted); }
        .mp-u { display:block; padding:13px 16px; border-bottom:1px solid var(--border); color:inherit; text-decoration:none; cursor:pointer; }
        .mp-u:last-child { border-bottom:0; }
        .mp-u:hover { background:var(--surface-2); }
        .mp-u .t { display:flex; justify-content:space-between; gap:8px; font-weight:600; }
        .mp-u .s { margin-top:3px; font-size:12.5px; color:var(--muted); }
        .mp-u .riel { height:5px; border-radius:3px; background:var(--surface-2); overflow:hidden; margin-top:8px; }
        .mp-u .riel span { display:block; height:100%; background:var(--green); }
        .mp-u .ab { font-size:12px; color:var(--primary); font-weight:600; }
        .mp-vacio { padding:18px 16px; font-size:13px; color:var(--muted); }
        .mp-libre { display:flex; justify-content:space-between; padding:10px 16px; border-bottom:1px solid var(--border); font-size:13px; }
        .mp-libre:last-child { border-bottom:0; }
        .mp-libre span:last-child { color:var(--muted); }
        .mk-camion { width:30px; height:30px; border-radius:50%; background:var(--primary); color:#fff; border:2px solid #fff; display:flex; align-items:center; justify-content:center; }
        .mk-camion.viejo { opacity:.45; filter:grayscale(1); }
        .mk-pin { width:22px; height:22px; border-radius:50%; color:#fff; border:2px solid #fff; display:flex; align-items:center; justify-content:center; font-weight:700; font-size:11px; box-shadow:0 2px 6px rgba(0,0,0,.35); }
        @media (max-width: 960px) {
            .mp-grid { grid-template-columns:1fr; }
            #mapa-flota { height:380px; min-height:0; }
        }
    </style>
@endsection

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js"></script>
<script>
(function () {
    const urlFeed = @json(route('logistica.mapa.feed'));
    const origen = @json($origen);
    const colores = { pendiente: '#94a3b8', en_camino: '#f97316', arribo: '#007aff', completada: '#15803d', no_entregada: '#dc2626', cancelada: '#dc2626' };

    const mapa = L.map('mapa-flota').setView([origen.lat, origen.lng], 9);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19, attribution: '© OpenStreetMap' }).addTo(mapa);
    L.marker([origen.lat, origen.lng], { icon: L.divIcon({ className: '', html: '<div class="mk-pin" style="background:#15803d">S</div>', iconSize: [22, 22], iconAnchor: [11, 11] }) })
        .addTo(mapa).bindTooltip(origen.nombre);

    const TRUCK = '<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>';
    const camiones = {};   // id de ruta → marcador
    const capaParadas = L.layerGroup().addTo(mapa);
    let primera = true;

    const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

    function pintar(datos) {
        const lista = document.getElementById('mp-lista');
        document.getElementById('mp-n').textContent = datos.rutas.length;
        document.getElementById('mp-nl').textContent = datos.libres.length;

        lista.innerHTML = datos.rutas.length ? '' : '<div class="mp-vacio">No hay rutas en curso.</div>';
        capaParadas.clearLayers();
        const vistos = new Set();
        const pts = [];

        datos.rutas.forEach(r => {
            vistos.add(r.id);
            const pct = r.avance.pct;

            const a = document.createElement('a');
            a.className = 'mp-u';
            a.href = r.url;
            a.innerHTML =
                '<div class="t"><span>' + esc(r.vehiculo) + '</span><span class="ab">' + esc(r.folio) + '</span></div>' +
                '<div class="s">' + esc(r.chofer) + (r.posicion ? ' · ' + (r.posicion.velocidad !== null ? Math.round(r.posicion.velocidad) + ' km/h · ' : '') + esc(r.posicion.hace) : ' · sin señal') + '</div>' +
                '<div class="s">' + (r.siguiente ? esc(r.siguienteEstado) + ': <b>' + esc(r.siguiente) + '</b>' + (r.eta ? ' · ETA ' + esc(r.eta) : '') : 'Todas las paradas resueltas') + '</div>' +
                '<div class="riel"><span style="width:' + pct + '%"></span></div>' +
                '<div class="s">' + r.avance.hechas + ' de ' + r.avance.total + ' paradas</div>';
            lista.appendChild(a);

            r.paradas.forEach(p => {
                if (p.lat === null) return;
                L.marker([p.lat, p.lng], { icon: L.divIcon({ className: '', html: '<div class="mk-pin" style="background:' + (colores[p.estado] || '#94a3b8') + '">' + p.n + '</div>', iconSize: [22, 22], iconAnchor: [11, 11] }) })
                    .addTo(capaParadas).bindTooltip(esc(r.folio) + ' · ' + esc(p.nombre));
            });

            if (r.posicion) {
                const ll = [r.posicion.lat, r.posicion.lng];
                const viejo = r.posicion.segundos > 120;   // más de 2 min sin reporte
                const html = '<div class="mk-camion' + (viejo ? ' viejo' : '') + '">' + TRUCK + '</div>';
                if (!camiones[r.id]) {
                    camiones[r.id] = L.marker(ll, { icon: L.divIcon({ className: '', html, iconSize: [28, 28], iconAnchor: [14, 14] }), zIndexOffset: 1000 }).addTo(mapa);
                    camiones[r.id].on('click', () => { location.href = r.url; });
                } else {
                    camiones[r.id].setLatLng(ll);
                    camiones[r.id].setIcon(L.divIcon({ className: '', html, iconSize: [28, 28], iconAnchor: [14, 14] }));
                }
                camiones[r.id].bindTooltip(esc(r.placas || r.vehiculo) + ' · ' + esc(r.chofer));
                pts.push(ll);
            }
        });

        // Quita camiones de rutas que ya terminaron
        Object.keys(camiones).forEach(id => { if (!vistos.has(+id)) { mapa.removeLayer(camiones[id]); delete camiones[id]; } });

        const libres = document.getElementById('mp-libres');
        libres.innerHTML = datos.libres.length ? '' : '<div class="mp-vacio">Todas las unidades están en ruta.</div>';
        datos.libres.forEach(v => {
            const d = document.createElement('div');
            d.className = 'mp-libre';
            d.innerHTML = '<span>' + esc(v.placas) + '</span><span>' + esc(v.nombre) + '</span>';
            libres.appendChild(d);
        });

        if (primera && pts.length) { mapa.fitBounds(pts, { padding: [60, 60], maxZoom: 14 }); primera = false; }
    }

    async function cargar() {
        const est = document.getElementById('mp-estado');
        try {
            const r = await fetch(urlFeed, { headers: { Accept: 'application/json' } });
            if (!r.ok) throw new Error(r.status);
            pintar(await r.json());
            est.textContent = 'En vivo · actualizado ' + new Date().toLocaleTimeString('es-MX', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
        } catch (e) { est.textContent = 'Sin conexión, reintentando…'; }
    }

    cargar();
    setInterval(cargar, 5000);
})();
</script>
@endpush
