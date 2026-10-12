@extends('layouts.dashboard')

@php $editando = $ruta->exists; @endphp

@section('title', $editando ? 'Editar '.$ruta->folio : 'Nueva ruta')
@section('page-title', $editando ? 'Editar '.$ruta->folio : 'Nueva ruta')
@section('page-sub', 'Captura las paradas; después podrás optimizar el orden')

@push('head')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css">
@endpush

@section('content')
    @if ($errors->any())
        <x-ui.alert type="err" style="margin-bottom:14px;">
            @foreach ($errors->all() as $e)<div>{{ $e }}</div>@endforeach
        </x-ui.alert>
    @endif

    <form method="POST" id="form-ruta"
          action="{{ $editando ? route('logistica.rutas.update', $ruta) : route('logistica.rutas.store') }}">
        @csrf
        @if ($editando) @method('PUT') @endif

        <div class="rf-grid">
            <div class="rf-col">
                {{-- ================= Datos de la ruta ================= --}}
                <div class="card rf-card">
                    <h3 class="rf-h">Datos de la ruta</h3>
                    <div class="rf-fila">
                        <label>Fecha
                            <input type="date" name="fecha_programada" value="{{ old('fecha_programada', $ruta->fecha_programada?->format('Y-m-d')) }}">
                        </label>
                        <label>Hora de salida
                            <input type="time" name="hora_salida" value="{{ old('hora_salida', $ruta->hora_salida ? substr($ruta->hora_salida, 0, 5) : '') }}">
                        </label>
                    </div>
                    <div class="rf-fila">
                        <label>Vehículo
                            <select name="vehicle_id">
                                <option value="">— Sin asignar —</option>
                                @foreach ($vehiculos as $v)
                                    <option value="{{ $v->id }}" @selected(old('vehicle_id', $ruta->vehicle_id) == $v->id)>{{ $v->plate_number }} · {{ trim($v->brand.' '.$v->model) }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label>Chofer
                            <select name="chofer_id">
                                <option value="">— Sin asignar —</option>
                                @foreach ($choferes as $c)
                                    <option value="{{ $c->id }}" @selected(old('chofer_id', $ruta->chofer_id) == $c->id)>{{ $c->name }}</option>
                                @endforeach
                            </select>
                        </label>
                    </div>
                    <p class="rf-nota">Con vehículo y chofer la ruta queda <b>programada</b>; sin ellos queda como borrador.</p>

                    <label class="rf-check">
                        <input type="checkbox" name="regresa_a_origen" value="1" @checked(old('regresa_a_origen', $ruta->regresa_a_origen))>
                        El vehículo regresa a la bodega al terminar
                    </label>
                    <label class="rf-check">
                        <input type="checkbox" name="exigir_evidencia" value="1" @checked(old('exigir_evidencia', $ruta->exigir_evidencia))>
                        Exigir ubicación, foto, receptor y firma para cerrar cada entrega
                    </label>

                    <label style="margin-top:10px;">Notas internas
                        <textarea name="notas" rows="2">{{ old('notas', $ruta->notas) }}</textarea>
                    </label>
                </div>

                {{-- ================= Punto de salida ================= --}}
                <div class="card rf-card" data-bloque="origen">
                    <h3 class="rf-h">Punto de salida</h3>
                    <div class="rf-fila">
                        <label>Nombre
                            <input type="text" name="origen_nombre" value="{{ old('origen_nombre', $ruta->origen_nombre) }}">
                        </label>
                    </div>
                    <label>Dirección
                        <div class="rf-buscador">
                            <input type="text" name="origen_direccion" id="origen_direccion" value="{{ old('origen_direccion', $ruta->origen_direccion) }}" autocomplete="off">
                            <button type="button" class="btn btn--ghost" data-buscar="origen">Buscar</button>
                        </div>
                    </label>
                    <div class="rf-resultados" id="res-origen" hidden></div>
                    <input type="hidden" name="origen_lat" id="origen_lat" value="{{ old('origen_lat', $ruta->origen_lat) }}">
                    <input type="hidden" name="origen_lng" id="origen_lng" value="{{ old('origen_lng', $ruta->origen_lng) }}">
                    <div class="rf-coord" id="coord-origen"></div>
                    <button type="button" class="rf-link" data-colocar="origen">Colocar en el mapa</button>
                </div>

                {{-- ================= Paradas ================= --}}
                <div class="rf-paradas-cab">
                    <h3 class="rf-h" style="margin:0;">Paradas</h3>
                    <button type="button" class="btn" id="agregar-parada">+ Agregar parada</button>
                </div>
                <div id="lista-paradas"></div>
            </div>

            {{-- ================= Mapa ================= --}}
            <div class="rf-mapa-col">
                <div class="card rf-mapa-card">
                    <div id="mapa-form"></div>
                    <div class="rf-mapa-pie" id="mapa-ayuda">Busca una dirección o toca «Colocar en el mapa» y haz clic donde va.</div>
                </div>
            </div>
        </div>

        <div class="rf-acciones">
            <a href="{{ $editando ? route('logistica.rutas.show', $ruta) : route('logistica.rutas.index') }}" class="btn btn--ghost">Cancelar</a>
            <button type="submit" class="btn">{{ $editando ? 'Guardar cambios' : 'Crear ruta' }}</button>
        </div>
    </form>

    {{-- Plantilla de una parada --}}
    <template id="tpl-parada">
        <div class="card rf-parada" data-parada>
            <div class="rf-parada-cab">
                <span class="rf-num" data-num></span>
                <input type="text" data-f="nombre" placeholder="Nombre del destino (ej. Hospital ABC)" required>
                <div class="rf-mov">
                    <button type="button" data-mover="-1" title="Subir" aria-label="Subir"><x-ui.icon name="arrow-up" :size="15" /></button>
                    <button type="button" data-mover="1" title="Bajar" aria-label="Bajar"><x-ui.icon name="arrow-down" :size="15" /></button>
                    <button type="button" data-quitar title="Quitar parada" aria-label="Quitar parada"><x-ui.icon name="x" :size="15" /></button>
                </div>
            </div>

            <label>Dirección
                <div class="rf-buscador">
                    <input type="text" data-f="direccion" autocomplete="off">
                    <button type="button" class="btn btn--ghost" data-buscar>Buscar</button>
                </div>
            </label>
            <div class="rf-resultados" data-resultados hidden></div>
            <input type="hidden" data-f="lat"><input type="hidden" data-f="lng">
            <div class="rf-coord" data-coord></div>
            <button type="button" class="rf-link" data-colocar>Colocar en el mapa</button>

            <div class="rf-fila">
                <label>Contacto <input type="text" data-f="contacto_nombre"></label>
                <label>Teléfono <input type="tel" data-f="contacto_telefono"></label>
            </div>
            <div class="rf-fila rf-fila--3">
                <label>Recibe desde <input type="time" data-f="ventana_desde"></label>
                <label>Hasta <input type="time" data-f="ventana_hasta"></label>
                <label>Geocerca (m) <input type="number" data-f="radio_m" min="30" max="2000" value="100"></label>
            </div>

            @if ($ordenes)
                <label>Traer de una orden de salida
                    <select data-f="orden_salida_id" data-orden>
                        <option value="">— Ninguna —</option>
                        @foreach ($ordenes as $o)
                            <option value="{{ $o['id'] }}">{{ $o['folio'] }} · {{ $o['cliente'] ?: 'Sin cliente' }}</option>
                        @endforeach
                    </select>
                </label>
            @endif

            <label>Mercancía <span class="rf-ayuda">una por línea — «2 Monitor Philips»</span>
                <textarea data-f="mercancia" rows="3"></textarea>
            </label>
            <label>Instrucciones para el chofer
                <input type="text" data-f="instrucciones" placeholder="Piso, andén, a quién preguntar…">
            </label>
            <label class="rf-check"><input type="checkbox" data-f="urgente" value="1"> Entrega urgente (se prioriza al optimizar)</label>
        </div>
    </template>

    <style>
        .rf-grid { display:grid; grid-template-columns:minmax(0,1.1fr) minmax(0,1fr); gap:18px; align-items:start; }
        .rf-col { display:flex; flex-direction:column; gap:16px; min-width:0; }
        .rf-mapa-col { position:sticky; top:16px; }
        .rf-mapa-card { padding:0; overflow:hidden; }
        #mapa-form { height:min(70vh,640px); min-height:360px; background:var(--surface-2); }
        .rf-mapa-pie { padding:10px 14px; font-size:12.5px; color:var(--muted); border-top:1px solid var(--border); }
        .rf-mapa-pie.is-activo { color:var(--accent); font-weight:600; }
        .rf-card, .rf-parada { padding:16px; }
        .rf-h { margin:0 0 12px; font-size:15px; }
        .rf-card label, .rf-parada label { display:block; font-size:12.5px; font-weight:600; color:var(--muted); margin-top:10px; }
        .rf-card label:first-of-type, .rf-parada label:first-of-type { margin-top:0; }
        .rf-card input[type=text], .rf-card input[type=date], .rf-card input[type=time], .rf-card select, .rf-card textarea,
        .rf-parada input[type=text], .rf-parada input[type=tel], .rf-parada input[type=time], .rf-parada input[type=number], .rf-parada select, .rf-parada textarea { width:100%; margin-top:5px; }
        .rf-fila { display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-top:10px; }
        .rf-fila--3 { grid-template-columns:1fr 1fr 1fr; }
        .rf-fila label { margin-top:0 !important; }
        .rf-nota { margin:10px 0 4px; font-size:12px; color:var(--muted); }
        .rf-check { display:flex !important; align-items:center; gap:8px; color:var(--text) !important; font-weight:500 !important; margin-top:10px; cursor:pointer; }
        .rf-check input { width:auto; margin:0; }
        .rf-ayuda { font-weight:400; color:var(--muted); }
        .rf-buscador { display:flex; gap:8px; margin-top:5px; }
        .rf-buscador input { margin-top:0 !important; flex:1; }
        .rf-resultados { margin-top:6px; border:1px solid var(--border); border-radius:8px; overflow:hidden; background:var(--surface); }
        .rf-resultados button { display:block; width:100%; text-align:left; padding:9px 12px; border:0; border-bottom:1px solid var(--border); background:transparent; color:var(--text); font:inherit; font-size:13px; cursor:pointer; }
        .rf-resultados button:last-child { border-bottom:0; }
        .rf-resultados button:hover { background:var(--surface-2); }
        .rf-resultados .vacio { padding:10px 12px; font-size:13px; color:var(--muted); }
        .rf-coord { margin-top:8px; font-size:12px; color:var(--muted); }
        .rf-coord.ok { color:var(--green); font-weight:600; }
        .rf-coord.falta { color:var(--accent); font-weight:600; }
        .rf-link { margin-top:6px; padding:0; border:0; background:none; color:var(--primary); font:inherit; font-size:12.5px; font-weight:600; cursor:pointer; }
        .rf-paradas-cab { display:flex; align-items:center; justify-content:space-between; }
        #lista-paradas { display:flex; flex-direction:column; gap:14px; }
        .rf-parada-cab { display:flex; align-items:center; gap:10px; margin-bottom:12px; }
        .rf-parada-cab input { flex:1; font-weight:600; }
        .rf-num { flex:0 0 28px; height:28px; border-radius:50%; background:var(--primary); color:#fff; display:flex; align-items:center; justify-content:center; font-size:13px; font-weight:700; }
        .rf-mov { display:flex; gap:4px; }
        .rf-mov button { width:30px; height:30px; border:1px solid var(--border); border-radius:7px; background:var(--surface); color:var(--text); cursor:pointer; }
        .rf-mov button:hover { background:var(--surface-2); }
        .rf-acciones { display:flex; justify-content:flex-end; gap:10px; margin:18px 0 8px; }
        .rf-pin { background:var(--primary); color:#fff; border:2px solid #fff; border-radius:50%; width:28px; height:28px; display:flex; align-items:center; justify-content:center; font-weight:700; font-size:12px; box-shadow:0 2px 6px rgba(0,0,0,.35); }
        .rf-pin.origen { background:#15803d; }
        @media (max-width: 960px) {
            .rf-grid { grid-template-columns:1fr; }
            .rf-mapa-col { position:static; order:-1; }
            #mapa-form { height:300px; min-height:0; }
            .rf-fila--3 { grid-template-columns:1fr 1fr; }
        }
    </style>
@endsection

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js"></script>
<script>
(function () {
    const urlGeo = @json(route('logistica.rutas.geocodificar'));
    const inicial = @json($paradasIniciales);
    const ordenes = @json(collect($ordenes)->keyBy('id'));

    const lista = document.getElementById('lista-paradas');
    const tpl = document.getElementById('tpl-parada');
    const ayuda = document.getElementById('mapa-ayuda');

    // ---------- Mapa ----------
    const mapa = L.map('mapa-form').setView([19.4, -99.5], 8);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19, attribution: '© OpenStreetMap' }).addTo(mapa);
    const capa = L.layerGroup().addTo(mapa);

    // Qué se está colocando con el siguiente clic: 'origen', un nodo de parada o null
    let colocando = null;

    const pin = (txt, clase) => L.divIcon({ className: '', html: '<div class="rf-pin ' + (clase || '') + '">' + txt + '</div>', iconSize: [28, 28], iconAnchor: [14, 14] });

    function num(v) { const n = parseFloat(v); return isNaN(n) ? null : n; }

    function pintar(reencuadrar) {
        capa.clearLayers();
        const pts = [];

        const olat = num(document.getElementById('origen_lat').value), olng = num(document.getElementById('origen_lng').value);
        const co = document.getElementById('coord-origen');
        if (olat !== null && olng !== null) {
            pts.push([olat, olng]);
            L.marker([olat, olng], { icon: pin('S', 'origen'), draggable: true }).addTo(capa)
                .on('dragend', e => fijar('origen', e.target.getLatLng().lat, e.target.getLatLng().lng));
            co.textContent = 'Ubicado en el mapa'; co.className = 'rf-coord ok';
        } else {
            co.textContent = 'Sin ubicar: busca la dirección o colócalo en el mapa'; co.className = 'rf-coord falta';
        }

        lista.querySelectorAll('[data-parada]').forEach((nodo, i) => {
            nodo.querySelector('[data-num]').textContent = i + 1;
            const lat = num(nodo.querySelector('[data-f=lat]').value), lng = num(nodo.querySelector('[data-f=lng]').value);
            const c = nodo.querySelector('[data-coord]');
            if (lat !== null && lng !== null) {
                pts.push([lat, lng]);
                const radio = num(nodo.querySelector('[data-f=radio_m]').value) || 100;
                L.marker([lat, lng], { icon: pin(i + 1), draggable: true }).addTo(capa)
                    .on('dragend', e => fijar(nodo, e.target.getLatLng().lat, e.target.getLatLng().lng));
                L.circle([lat, lng], { radius: radio, color: '#007aff', weight: 1, fillOpacity: .08 }).addTo(capa);
                c.textContent = 'Ubicada en el mapa'; c.className = 'rf-coord ok';
            } else {
                c.textContent = 'Sin ubicar: es necesaria para optimizar y para la geocerca'; c.className = 'rf-coord falta';
            }
        });

        if (reencuadrar && pts.length) mapa.fitBounds(pts, { padding: [40, 40], maxZoom: 15 });
    }

    function fijar(destino, lat, lng) {
        lat = +lat.toFixed(7); lng = +lng.toFixed(7);
        if (destino === 'origen') {
            document.getElementById('origen_lat').value = lat;
            document.getElementById('origen_lng').value = lng;
        } else {
            destino.querySelector('[data-f=lat]').value = lat;
            destino.querySelector('[data-f=lng]').value = lng;
        }
    }

    function esperarClic(destino) {
        colocando = destino;
        ayuda.textContent = 'Haz clic en el mapa para colocar el punto…';
        ayuda.classList.add('is-activo');
        mapa.getContainer().style.cursor = 'crosshair';
        mapa.getContainer().scrollIntoView({ behavior: 'smooth', block: 'center' });
    }

    mapa.on('click', e => {
        if (!colocando) return;
        fijar(colocando, e.latlng.lat, e.latlng.lng);
        colocando = null;
        ayuda.textContent = 'Punto colocado. Puedes arrastrar el marcador para afinarlo.';
        ayuda.classList.remove('is-activo');
        mapa.getContainer().style.cursor = '';
        pintar(false);
    });

    // ---------- Buscador de direcciones ----------
    async function buscar(inputDir, caja, destino) {
        const q = inputDir.value.trim();
        if (q.length < 4) { caja.hidden = false; caja.innerHTML = '<div class="vacio">Escribe al menos 4 caracteres.</div>'; return; }
        caja.hidden = false; caja.innerHTML = '<div class="vacio">Buscando…</div>';
        try {
            const r = await fetch(urlGeo + '?q=' + encodeURIComponent(q), { headers: { Accept: 'application/json' } });
            const datos = await r.json();
            if (!datos.length) { caja.innerHTML = '<div class="vacio">Sin resultados. Prueba con calle, colonia y ciudad, o colócalo en el mapa.</div>'; return; }
            caja.innerHTML = '';
            datos.forEach(d => {
                const b = document.createElement('button');
                b.type = 'button'; b.textContent = d.label;
                b.addEventListener('click', () => {
                    inputDir.value = d.label;
                    fijar(destino, d.lat, d.lng);
                    caja.hidden = true;
                    pintar(false);
                    mapa.setView([d.lat, d.lng], 16);
                });
                caja.appendChild(b);
            });
        } catch (e) { caja.innerHTML = '<div class="vacio">No se pudo buscar. Colócalo manualmente en el mapa.</div>'; }
    }

    // Origen
    document.querySelector('[data-buscar=origen]').addEventListener('click', () => buscar(document.getElementById('origen_direccion'), document.getElementById('res-origen'), 'origen'));
    document.querySelector('[data-colocar=origen]').addEventListener('click', () => esperarClic('origen'));

    // ---------- Paradas ----------
    function renumerar() {
        lista.querySelectorAll('[data-parada]').forEach((nodo, i) => {
            nodo.querySelectorAll('[data-f]').forEach(el => {
                el.name = 'paradas[' + i + '][' + el.dataset.f + ']';
            });
        });
        pintar(false);
    }

    function agregar(valores) {
        const nodo = tpl.content.firstElementChild.cloneNode(true);
        valores = valores || {};

        nodo.querySelectorAll('[data-f]').forEach(el => {
            const v = valores[el.dataset.f];
            if (el.type === 'checkbox') { el.checked = !!v; }
            else if (v !== undefined && v !== null) { el.value = v; }
        });

        nodo.querySelector('[data-buscar]').addEventListener('click', () =>
            buscar(nodo.querySelector('[data-f=direccion]'), nodo.querySelector('[data-resultados]'), nodo));
        nodo.querySelector('[data-colocar]').addEventListener('click', () => esperarClic(nodo));
        nodo.querySelector('[data-quitar]').addEventListener('click', () => { nodo.remove(); renumerar(); });
        nodo.querySelectorAll('[data-mover]').forEach(b => b.addEventListener('click', () => {
            const d = parseInt(b.dataset.mover, 10);
            if (d < 0 && nodo.previousElementSibling) nodo.parentNode.insertBefore(nodo, nodo.previousElementSibling);
            if (d > 0 && nodo.nextElementSibling) nodo.parentNode.insertBefore(nodo.nextElementSibling, nodo);
            renumerar();
        }));
        nodo.querySelector('[data-f=radio_m]').addEventListener('input', () => pintar(false));

        // Al ligar una orden de salida se llenan los datos del cliente y la mercancía
        const sel = nodo.querySelector('[data-orden]');
        if (sel) sel.addEventListener('change', () => {
            const o = ordenes[sel.value];
            if (!o) return;
            const poner = (campo, valor) => { const el = nodo.querySelector('[data-f=' + campo + ']'); if (valor && !el.value) el.value = valor; };
            poner('nombre', o.cliente); poner('direccion', o.direccion);
            poner('contacto_telefono', o.telefono);
            nodo.querySelector('[data-f=mercancia]').value = o.mercancia || '';
        });

        lista.appendChild(nodo);
        renumerar();
        return nodo;
    }

    document.getElementById('agregar-parada').addEventListener('click', () => {
        const n = agregar();
        n.querySelector('[data-f=nombre]').focus();
    });

    (inicial.length ? inicial : [{}]).forEach(agregar);
    pintar(true);

    // Una parada sin ubicar no bloquea el guardado (puede quedar como borrador), solo se avisa.
    const form = document.getElementById('form-ruta');
    let confirmado = false;
    form.addEventListener('submit', async e => {
        if (confirmado) return;
        const sin = [...lista.querySelectorAll('[data-parada]')].filter(n => !n.querySelector('[data-f=lat]').value).length;
        if (!sin) return;
        e.preventDefault();
        const ok = await window.confirmModal({
            title: 'Paradas sin ubicar',
            message: sin + ' parada(s) no tienen ubicación en el mapa. Se pueden guardar, pero no se podrá optimizar la ruta hasta ubicarlas. ¿Guardar así?',
            confirmText: 'Guardar así', danger: false,
        });
        if (ok) { confirmado = true; form.requestSubmit(); }
    });
})();
</script>
@endpush
