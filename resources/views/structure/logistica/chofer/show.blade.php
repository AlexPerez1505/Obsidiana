@extends('layouts.dashboard')

@section('title', $ruta->folio)
@section('page-title', $ruta->folio)
@section('page-sub', $ruta->vehicle ? $ruta->vehicle->plate_number.' · '.trim($ruta->vehicle->brand.' '.$ruta->vehicle->model) : 'Sin vehículo')

@section('content')
    @if (session('status'))
        <x-ui.alert type="ok" style="margin-bottom:14px;">{{ session('status') }}</x-ui.alert>
    @endif
    @if ($errors->any())
        <x-ui.alert type="err" style="margin-bottom:14px;">{{ $errors->first() }}</x-ui.alert>
    @endif

    <a href="{{ route('logistica.chofer.index') }}" class="cs-volver"><x-ui.icon name="arrow-left" :size="14" /> Mis rutas</a>

    {{-- ================= Estado del GPS ================= --}}
    @if ($ruta->activa())
        <div class="card cs-gps" id="gps-card">
            <span class="cs-punto" id="gps-punto"></span>
            <div style="flex:1">
                <div class="cs-gps-t" id="gps-txt">Activando GPS…</div>
                <div class="cs-gps-s" id="gps-sub">Permite el acceso a tu ubicación cuando el navegador lo pida y deja esta pantalla abierta.</div>
            </div>
        </div>
    @elseif ($ruta->estado === \App\Models\Ruta::PROGRAMADA)
        <x-ui.alert type="default" style="margin-bottom:14px;">La oficina todavía no inicia esta ruta. Cuando salga podrás reportar tu posición y cerrar entregas.</x-ui.alert>
    @else
        <x-ui.alert type="default" style="margin-bottom:14px;">Esta ruta está {{ strtolower($ruta->estadoLabel()) }}.</x-ui.alert>
    @endif

    {{-- ================= Paradas ================= --}}
    @foreach ($ruta->paradas as $p)
        @php
            $esSiguiente = $siguiente && $siguiente->id === $p->id;
            $abierta = $ruta->activa() && ! $p->resuelta();
        @endphp
        <div class="card cs-par {{ $esSiguiente && $ruta->activa() ? 'is-siguiente' : '' }} {{ $p->resuelta() ? 'is-hecha' : '' }}" data-parada-id="{{ $p->id }}"
             @if ($p->tieneCoordenadas()) data-lat="{{ $p->lat }}" data-lng="{{ $p->lng }}" data-radio="{{ $p->radio_m }}" @endif>
            <div class="cs-par-cab">
                <span class="cs-n">{{ $p->orden }}</span>
                <div style="flex:1; min-width:0;">
                    <div class="cs-t">{{ $p->nombre }}@if ($p->urgente) <span class="cs-urg">URGENTE</span>@endif</div>
                    <div class="cs-s">{{ $p->direccion }}</div>
                </div>
                <span class="badge {{ $p->estadoTono() }}" data-estado>{{ $p->estadoLabel() }}</span>
            </div>

            <div class="cs-meta">
                @if ($p->eta && ! $p->resuelta())<span>ETA {{ $p->eta->format('H:i') }}</span>@endif
                @if ($p->ventana_desde || $p->ventana_hasta)<span>Recibe {{ $p->ventana_desde ? substr($p->ventana_desde, 0, 5) : '—' }}–{{ $p->ventana_hasta ? substr($p->ventana_hasta, 0, 5) : '—' }}</span>@endif
                <span data-distancia></span>
            </div>

            @if ($p->items->count())
                <div class="cs-items">
                    @foreach ($p->items as $it)<span>{{ $it->cantidad }} × {{ $it->descripcion }}</span>@endforeach
                </div>
            @endif
            @if ($p->instrucciones)<div class="cs-s" style="margin-top:8px;"><x-ui.icon name="note" :size="14" /> {{ $p->instrucciones }}</div>@endif

            <div class="cs-botones">
                @if ($p->tieneCoordenadas())
                    <a class="btn btn--ghost" target="_blank" rel="noopener"
                       href="https://www.google.com/maps/dir/?api=1&destination={{ $p->lat }},{{ $p->lng }}&travelmode=driving"><x-ui.icon name="navigation" :size="16" /> Navegar</a>
                @endif
                @if ($p->contacto_telefono)
                    <a class="btn btn--ghost" href="tel:{{ preg_replace('/[^0-9+]/', '', $p->contacto_telefono) }}"><x-ui.icon name="phone" :size="16" /> {{ $p->contacto_nombre ?: 'Llamar' }}</a>
                @endif
                @if ($abierta && $p->estado !== \App\Models\RutaParada::ARRIBO)
                    <form method="POST" action="{{ route('logistica.chofer.llegada', [$ruta, $p]) }}" data-llegada>@csrf
                        <input type="hidden" name="lat" data-pos-lat><input type="hidden" name="lng" data-pos-lng>
                        <button class="btn btn--ghost" type="submit"><x-ui.icon name="pin" :size="16" /> Ya llegué</button>
                    </form>
                @endif
            </div>

            @if ($abierta)
                {{-- ----- Registrar entrega ----- --}}
                <details class="cs-det" @if ($esSiguiente && $p->estado === \App\Models\RutaParada::ARRIBO) open @endif>
                    <summary>Registrar entrega</summary>
                    <form method="POST" action="{{ route('logistica.chofer.completar', [$ruta, $p]) }}" enctype="multipart/form-data" class="cs-form" data-entrega>
                        @csrf
                        <input type="hidden" name="lat" data-pos-lat><input type="hidden" name="lng" data-pos-lng>

                        <label>Fotos de la entrega
                            <input type="file" name="fotos[]" accept="image/*" capture="environment" multiple data-fotos>
                        </label>
                        <label>Remisión firmada (foto o PDF) <span class="cs-op">opcional</span>
                            <input type="file" name="remision" accept="image/*,application/pdf">
                        </label>
                        <label>Nombre de quien recibe
                            <input type="text" name="receptor_nombre" autocomplete="off">
                        </label>
                        <label>Firma de quien recibe
                            <canvas class="cs-pad" data-pad width="400" height="160"></canvas>
                            <input type="hidden" name="firma" data-firma>
                            <a href="#" class="cs-link" data-limpiar>Limpiar firma</a>
                        </label>
                        <div class="cs-gpsmini" data-gpsmini>Obteniendo ubicación…</div>
                        <button class="btn" type="submit" style="width:100%; padding:12px;"><x-ui.icon name="check" :size="16" /> Confirmar entrega</button>
                    </form>
                </details>

                {{-- ----- No se pudo entregar ----- --}}
                <details class="cs-det cs-det--no">
                    <summary>No se pudo entregar</summary>
                    <form method="POST" action="{{ route('logistica.chofer.no-entregada', [$ruta, $p]) }}" class="cs-form" data-confirm="La parada se marca como no entregada y se avisa a la oficina." data-confirm-title="No entregada" data-confirm-ok="Marcar">
                        @csrf
                        <input type="hidden" name="lat" data-pos-lat><input type="hidden" name="lng" data-pos-lng>
                        <label>Motivo
                            <input type="text" name="motivo" placeholder="Cerrado, no había quien recibiera, rechazó…" required>
                        </label>
                        <button class="btn btn--danger" type="submit" style="width:100%; padding:12px;">Marcar como no entregada</button>
                    </form>
                </details>
            @elseif ($p->estado === \App\Models\RutaParada::COMPLETADA)
                <div class="cs-ok"><x-ui.icon name="check" :size="15" /> Entregada {{ $p->completada_en?->format('H:i') }} · recibió {{ $p->receptor_nombre ?: '—' }}</div>
            @elseif ($p->estado === \App\Models\RutaParada::NO_ENTREGADA)
                <div class="cs-ok" style="color:var(--danger)">No entregada: {{ $p->motivo_no_entrega }}</div>
            @endif
        </div>
    @endforeach

    @if ($ruta->activa())
        <div class="card cs-par">
            <div class="cs-t" style="margin-bottom:4px;">¿Algún problema?</div>
            <details class="cs-det">
                <summary>Reportar incidencia</summary>
                <form method="POST" action="{{ route('logistica.chofer.incidencia', $ruta) }}" enctype="multipart/form-data" class="cs-form">
                    @csrf
                    <input type="hidden" name="lat" data-pos-lat><input type="hidden" name="lng" data-pos-lng>
                    <label>Qué pasó
                        <select name="tipo" required style="width:100%; margin-top:5px;">
                            <option value="">— Elige —</option>
                            @foreach ($tiposIncidencia as $k => $t)<option value="{{ $k }}">{{ $t }}</option>@endforeach
                        </select>
                    </label>
                    <label>Parada afectada <span class="cs-op">opcional</span>
                        <select name="ruta_parada_id" style="width:100%; margin-top:5px;">
                            <option value="">— Ninguna —</option>
                            @foreach ($ruta->paradas->reject->resuelta() as $p)<option value="{{ $p->id }}">{{ $p->orden }}. {{ $p->nombre }}</option>@endforeach
                        </select>
                    </label>
                    <label>Detalle <input type="text" name="descripcion" placeholder="Describe brevemente"></label>
                    <label>Foto <span class="cs-op">opcional</span> <input type="file" name="foto" accept="image/*" capture="environment" data-fotos></label>
                    <button class="btn" type="submit" style="width:100%; padding:12px;">Enviar a la oficina</button>
                </form>
            </details>
            @if ($ruta->vehicle)
                <details class="cs-det">
                    <summary>Registrar carga de combustible</summary>
                    <form method="POST" action="{{ route('logistica.chofer.combustible', $ruta) }}" enctype="multipart/form-data" class="cs-form">
                        @csrf
                        <label>Litros <input type="number" step="0.01" name="litros" required></label>
                        <label>Monto $ <input type="number" step="0.01" name="monto" required></label>
                        <label>Odómetro (km) <span class="cs-op">opcional</span> <input type="number" name="odometro_km"></label>
                        <label>Estación <span class="cs-op">opcional</span> <input type="text" name="estacion"></label>
                        <label>Foto del ticket <span class="cs-op">opcional</span> <input type="file" name="ticket" accept="image/*" capture="environment" data-fotos></label>
                        <label class="cs-chk"><input type="checkbox" name="tanque_lleno" value="1" checked> Tanque lleno</label>
                        <button class="btn" type="submit" style="width:100%; padding:12px;">Guardar carga</button>
                    </form>
                </details>
            @endif
        </div>
    @endif

    <style>
        .cs-form input[type=number], .cs-form select { width:100%; margin-top:5px; }
        .cs-chk { display:flex !important; align-items:center; gap:8px; color:var(--text) !important; }
        .cs-chk input { width:auto; margin:0; }
        .cs-volver { display:inline-block; margin-bottom:12px; color:var(--primary); font-weight:600; text-decoration:none; font-size:14px; }
        .cs-gps { display:flex; align-items:center; gap:12px; padding:14px 16px; margin-bottom:14px; }
        .cs-punto { flex:0 0 12px; height:12px; border-radius:50%; background:#94a3b8; }
        .cs-punto.ok { background:#15803d; animation:cs-pulso 1.8s infinite; }
        .cs-punto.mal { background:#dc2626; }
        @keyframes cs-pulso { 50% { opacity:.35; } }
        .cs-gps-t { font-weight:700; }
        .cs-gps-s { font-size:12.5px; color:var(--muted); margin-top:2px; }
        .cs-par { padding:16px; margin-bottom:14px; }
        .cs-par.is-siguiente { border-color:var(--muted); }
        .cs-par.is-hecha { opacity:.7; }
        .cs-par-cab { display:flex; align-items:flex-start; gap:12px; }
        .cs-n { flex:0 0 30px; height:30px; border-radius:50%; background:var(--primary); color:#fff; display:flex; align-items:center; justify-content:center; font-weight:700; font-size:13px; }
        .cs-t { font-weight:700; font-size:16px; }
        .cs-s { font-size:12.5px; color:var(--muted); margin-top:2px; }
        .cs-urg { margin-left:6px; background:var(--danger-soft); color:var(--danger); border-radius:5px; padding:1px 6px; font-size:10.5px; font-weight:700; }
        .cs-meta { display:flex; flex-wrap:wrap; gap:4px 14px; margin-top:10px; font-size:12.5px; color:var(--muted); }
        .cs-meta [data-distancia]:not(:empty) { color:var(--accent); font-weight:700; }
        .cs-items { display:flex; flex-wrap:wrap; gap:6px; margin-top:10px; }
        .cs-items span { background:var(--surface-2); border:1px solid var(--border); border-radius:6px; padding:4px 9px; font-size:13px; }
        .cs-botones { display:flex; flex-wrap:wrap; gap:8px; margin-top:14px; }
        .cs-botones form { margin:0; }
        .cs-botones .btn { padding:10px 14px; }
        .cs-det { margin-top:14px; border:1px solid var(--border); border-radius:10px; background:var(--surface-2); }
        .cs-det > summary { padding:12px 14px; font-weight:700; cursor:pointer; list-style:none; }
        .cs-det > summary::-webkit-details-marker { display:none; }
        .cs-det[open] > summary { border-bottom:1px solid var(--border); }
        .cs-det--no > summary { color:var(--danger); }
        .cs-form { padding:14px; }
        .cs-form label { display:block; font-size:12.5px; font-weight:600; color:var(--muted); margin-bottom:12px; }
        .cs-form input[type=text], .cs-form input[type=file] { width:100%; margin-top:5px; }
        .cs-op { font-weight:400; }
        .cs-pad { display:block; width:100%; height:160px; margin-top:5px; border:1px dashed var(--muted); border-radius:10px; background:#fff; touch-action:none; }
        .cs-link { display:inline-block; margin-top:6px; font-size:12.5px; color:var(--primary); }
        .cs-gpsmini { font-size:12px; color:var(--muted); margin-bottom:12px; }
        .cs-gpsmini.ok { color:var(--green); font-weight:600; }
        .cs-ok { margin-top:12px; font-size:13px; font-weight:600; color:var(--green); }
    </style>
@endsection

@push('scripts')
<script>
(function () {
    const activa = @json($ruta->activa());
    const urlUbic = @json(route('logistica.chofer.ubicacion', $ruta));
    const intervalo = Math.max(5, @json((int) $intervalo)) * 1000;
    const csrf = document.querySelector('meta[name=csrf-token]').content;
    const tonos = { pendiente: '', en_camino: 'badge--warn', arribo: 'badge--info', completada: 'badge--ok', no_entregada: 'badge--danger', cancelada: 'badge--danger' };

    let pos = null;          // última lectura del GPS
    let ultimoEnvio = 0;
    let pendientes = [];     // puntos que no se pudieron mandar (sin señal)

    function haversine(a, b, c, d) {
        const R = 6371000, r = x => x * Math.PI / 180;
        const dp = r(c - a), dl = r(d - b);
        const h = Math.sin(dp / 2) ** 2 + Math.cos(r(a)) * Math.cos(r(c)) * Math.sin(dl / 2) ** 2;
        return 2 * R * Math.asin(Math.sqrt(h));
    }
    const fmt = m => m >= 1000 ? (m / 1000).toFixed(1) + ' km' : Math.round(m) + ' m';

    // Cada formulario lleva las coordenadas más recientes
    function rellenarPos() {
        document.querySelectorAll('[data-pos-lat]').forEach(i => i.value = pos ? pos.lat : '');
        document.querySelectorAll('[data-pos-lng]').forEach(i => i.value = pos ? pos.lng : '');
        document.querySelectorAll('[data-gpsmini]').forEach(el => {
            el.textContent = pos ? 'Ubicación lista (±' + Math.round(pos.precision) + ' m)' : 'Obteniendo ubicación…';
            el.classList.toggle('ok', !!pos);
        });
        document.querySelectorAll('[data-parada-id][data-lat]').forEach(card => {
            const el = card.querySelector('[data-distancia]');
            if (!pos || card.classList.contains('is-hecha')) { el.textContent = ''; return; }
            el.textContent = 'A ' + fmt(haversine(pos.lat, pos.lng, +card.dataset.lat, +card.dataset.lng));
        });
    }

    // ---------- GPS ----------
    const punto = document.getElementById('gps-punto');
    const txt = document.getElementById('gps-txt');
    const sub = document.getElementById('gps-sub');

    function estadoGps(clase, t, s) {
        if (!punto) return;
        punto.className = 'cs-punto ' + clase; txt.textContent = t; if (s) sub.textContent = s;
    }

    async function enviar() {
        if (!pos) return;
        ultimoEnvio = Date.now();
        const lote = pendientes.concat([{ lat: pos.lat, lng: pos.lng, velocidad_ms: pos.velocidad, precision: pos.precision, rumbo: pos.rumbo, registrada_en: new Date(pos.t).toISOString() }]);
        pendientes = [];

        for (let i = 0; i < lote.length; i++) {
            try {
                const r = await fetch(urlUbic, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
                    body: JSON.stringify(lote[i]),
                });
                if (!r.ok) throw new Error(r.status);
                const v = await r.json();
                if (i === lote.length - 1) alResponder(v);
            } catch (e) {
                pendientes = lote.slice(i).slice(-40);   // se reintenta con el siguiente envío
                estadoGps('mal', 'Sin conexión con el servidor', 'Tu posición se guardará y se enviará al recuperar señal.');
                return;
            }
        }
        estadoGps('ok', 'GPS activo', 'Último envío ' + new Date().toLocaleTimeString('es-MX', { hour: '2-digit', minute: '2-digit', second: '2-digit' }));
    }

    function alResponder(v) {
        if (!v.activa) { location.reload(); return; }
        (v.avisos || []).forEach(a => window.showToast && window.showToast(a));
        v.paradas.forEach(p => {
            const card = document.querySelector('[data-parada-id="' + p.id + '"]');
            if (!card) return;
            const b = card.querySelector('[data-estado]');
            const cambio = b.textContent.trim() !== p.estadoLabel;
            b.textContent = p.estadoLabel; b.className = 'badge ' + (tonos[p.estado] || '');
            // Al llegar (automático) se abre el formulario de entrega y se oculta «Ya llegué»
            if (cambio && p.estado === 'arribo') {
                const f = card.querySelector('[data-llegada]'); if (f) f.remove();
                const det = card.querySelector('details'); if (det) det.open = true;
            }
        });
    }

    if (activa) {
        if (!('geolocation' in navigator)) {
            estadoGps('mal', 'Este dispositivo no tiene GPS', 'Abre la ruta desde un teléfono.');
        } else {
            estadoGps('', 'Activando GPS…');
            navigator.geolocation.watchPosition(p => {
                pos = { lat: p.coords.latitude, lng: p.coords.longitude, precision: p.coords.accuracy || 0, velocidad: p.coords.speed, rumbo: p.coords.heading, t: p.timestamp };
                rellenarPos();
                if (Date.now() - ultimoEnvio >= intervalo) enviar();
            }, err => {
                estadoGps('mal', err.code === 1 ? 'Permiso de ubicación denegado' : 'No se pudo obtener la ubicación',
                    err.code === 1 ? 'Activa la ubicación para este sitio en los ajustes del navegador y recarga.' : 'Revisa que el GPS del teléfono esté encendido.');
            }, { enableHighAccuracy: true, maximumAge: 5000, timeout: 20000 });

            // Aunque no haya movimiento, se manda un latido para que la oficina vea que sigue conectado
            setInterval(() => { if (pos && Date.now() - ultimoEnvio >= intervalo) enviar(); }, 5000);
        }

        // Evita que la pantalla se apague y corte el GPS
        let candado = null;
        async function mantener() { try { if ('wakeLock' in navigator) candado = await navigator.wakeLock.request('screen'); } catch (e) {} }
        mantener();
        document.addEventListener('visibilitychange', () => { if (document.visibilityState === 'visible') mantener(); });
    }

    // ---------- Firma ----------
    document.querySelectorAll('[data-pad]').forEach(lienzo => {
        const ctx = lienzo.getContext('2d');
        const input = lienzo.parentElement.querySelector('[data-firma]');
        let firmando = false;

        function ajustar() {
            const rect = lienzo.getBoundingClientRect();
            if (!rect.width) return;
            lienzo.width = Math.round(rect.width); lienzo.height = 160;
            ctx.lineWidth = 2.4; ctx.lineCap = 'round'; ctx.lineJoin = 'round'; ctx.strokeStyle = '#111827';
        }
        // El lienzo está dentro de un <details> cerrado: se ajusta al abrirlo
        const det = lienzo.closest('details');
        if (det) det.addEventListener('toggle', () => { if (det.open && !input.value) ajustar(); });
        ajustar();

        const punto = e => { const r = lienzo.getBoundingClientRect(); const p = e.touches ? e.touches[0] : e; return { x: (p.clientX - r.left) * (lienzo.width / r.width), y: (p.clientY - r.top) * (lienzo.height / r.height) }; };
        const empezar = e => { e.preventDefault(); firmando = true; const p = punto(e); ctx.beginPath(); ctx.moveTo(p.x, p.y); };
        const mover = e => { if (!firmando) return; e.preventDefault(); const p = punto(e); ctx.lineTo(p.x, p.y); ctx.stroke(); };
        const terminar = () => { if (firmando) { firmando = false; input.value = lienzo.toDataURL('image/png'); } };
        lienzo.addEventListener('mousedown', empezar); lienzo.addEventListener('mousemove', mover); window.addEventListener('mouseup', terminar);
        lienzo.addEventListener('touchstart', empezar, { passive: false }); lienzo.addEventListener('touchmove', mover, { passive: false }); lienzo.addEventListener('touchend', terminar);
        lienzo.parentElement.querySelector('[data-limpiar]').addEventListener('click', e => { e.preventDefault(); ctx.clearRect(0, 0, lienzo.width, lienzo.height); input.value = ''; });
    });

    // ---------- Fotos: se reducen antes de subir (las del teléfono pesan varios MB) ----------
    function reducir(archivo) {
        return new Promise(res => {
            if (!archivo.type.startsWith('image/')) return res(archivo);
            const img = new Image();
            img.onload = () => {
                const max = 1600, k = Math.min(1, max / Math.max(img.width, img.height));
                const c = document.createElement('canvas');
                c.width = Math.round(img.width * k); c.height = Math.round(img.height * k);
                c.getContext('2d').drawImage(img, 0, 0, c.width, c.height);
                c.toBlob(b => res(b ? new File([b], archivo.name.replace(/\.\w+$/, '') + '.jpg', { type: 'image/jpeg' }) : archivo), 'image/jpeg', 0.82);
                URL.revokeObjectURL(img.src);
            };
            img.onerror = () => res(archivo);
            img.src = URL.createObjectURL(archivo);
        });
    }
    document.querySelectorAll('[data-fotos]').forEach(input => {
        input.addEventListener('change', async () => {
            const dt = new DataTransfer();
            for (const f of input.files) dt.items.add(await reducir(f));
            input.files = dt.files;
        });
    });

    // ---------- Envío de la entrega: va con la ubicación del momento ----------
    document.querySelectorAll('[data-entrega]').forEach(form => {
        let listo = false;
        form.addEventListener('submit', e => {
            if (listo) return;
            rellenarPos();
            const falta = [];
            if (!form.querySelector('[data-firma]').value) falta.push('la firma');
            if (!form.querySelector('[name="fotos[]"]').files.length) falta.push('una foto');
            if (!form.receptor_nombre.value.trim()) falta.push('el nombre de quien recibe');
            if (falta.length) {
                e.preventDefault();
                window.showToast && window.showToast('Falta ' + falta.join(', '), 'warn');
                return;
            }
            if (!pos) {
                e.preventDefault();
                navigator.geolocation.getCurrentPosition(p => {
                    pos = { lat: p.coords.latitude, lng: p.coords.longitude, precision: p.coords.accuracy || 0 };
                    rellenarPos(); listo = true; form.requestSubmit();
                }, () => { window.showToast && window.showToast('Activa el GPS para cerrar la entrega', 'warn'); }, { enableHighAccuracy: true, timeout: 15000 });
            }
        });
    });

    rellenarPos();
})();
</script>
@endpush
