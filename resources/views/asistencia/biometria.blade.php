@extends('layouts.dashboard')

@section('title', 'Lectores y huellas')
@section('page-title', 'Asistencia')
@section('page-sub', 'Lectores de huella conectados y huellas registradas por empleado')

@section('content')
    @include('asistencia._tabs')

    @if ($llaveNueva)
        <div class="rh-card rh-pad rh-in bm-llave" style="--i:1">
            <b class="rh-h"><x-ui.icon name="key" /> Llave del lector «{{ $llaveNueva['dispositivo'] }}»</b>
            <p>Cópiala ahora y guárdala en la configuración del agente de esa computadora. <b>No se vuelve a mostrar</b>: si se pierde, se genera una nueva.</p>
            <code id="llave">{{ $llaveNueva['llave'] }}</code>
            <p class="rh-sub">Código del lector: <b>{{ $llaveNueva['code'] }}</b></p>
            <p style="margin-top:14px;">Contenido para el <b>config.json</b> del agente en la computadora del lector:</p>
            <pre id="cfg-agente" class="bm-cfg">{
  "api_url": "{{ url('/api/checador') }}",
  "api_key": "{{ $llaveNueva['llave'] }}",
  "verificar_ssl": {{ filter_var(request()->getHost(), FILTER_VALIDATE_IP) ? 'false' : 'true' }},
  "lector": { "tipo": "uareu" },
  "pantalla_url": "{{ $llaveNueva['pantalla'] ?? '' }}"
}</pre>
            <div class="rh-actions">
                <button class="btn btn--ghost" type="button" onclick="navigator.clipboard.writeText(document.getElementById('cfg-agente').textContent); window.showToast && showToast('Configuración copiada')"><x-ui.icon name="file" /> Copiar config.json</button>
                <button class="btn btn--ghost" type="button" onclick="navigator.clipboard.writeText(document.getElementById('llave').textContent); window.showToast && showToast('Llave copiada')"><x-ui.icon name="key" /> Copiar solo la llave</button>
            </div>
            <p class="rh-sub" style="margin-top:10px;"><b>"uareu"</b> = lector DigitalPersona U.are.U 4500. Para probar sin lector usa <b>"simulador"</b>; para ZKTeco, <b>"zkfinger"</b>. La guía completa está en el README del agente.</p>
        </div>
    @endif

    {{-- ============ Lectores ============ --}}
    <div class="rh-actions rh-in" style="--i:2; margin-bottom:12px;">
        <b class="rh-h">Lectores y computadoras de checado</b>
        <button type="button" class="btn" data-rh-toggle="bm-nuevo" style="margin-left:auto;"><x-ui.icon name="plus" /> Nuevo lector</button>
    </div>

    <div class="rh-acc rh-in {{ $errors->any() ? 'open' : '' }}" id="bm-nuevo" style="--i:3; margin-bottom:14px;">
        <div class="rh-acc-b"><div class="rh-acc-i"><div class="rh-acc-c">
            <form method="POST" action="{{ route('asistencia.biometria.dispositivos.store') }}" class="rh-form">@csrf
                <div class="rh-grid">
                    <label>Nombre <input type="text" name="name" placeholder="Checador Recepción" required></label>
                    <label>Código <input type="text" name="code" placeholder="RECEPCION-01" required></label>
                    <label>Ubicación <input type="text" name="location" placeholder="Oficina principal"></label>
                    <label>No. de serie (opcional) <input type="text" name="serial_number"></label>
                </div>
                <div class="rh-actions" style="margin-top:14px;"><button class="btn" type="submit">Crear lector</button><span class="rh-sub">Al crearlo se muestra su llave una sola vez.</span></div>
            </form>
        </div></div></div>
    </div>

    <div class="bm-lectores">
        @forelse ($dispositivos as $i => $d)
            <div class="rh-card rh-pad rh-in" style="--i:{{ $i + 4 }}">
                <div class="bm-l-cab">
                    <div><b>{{ $d->name }}</b> <span class="rh-sub">{{ $d->code }}</span><div class="rh-sub">{{ $d->location }}{{ $d->serial_number ? ' · S/N '.$d->serial_number : '' }}</div></div>
                    @if ($d->status !== 'active')<span class="rh-chip">Desactivado</span>
                    @elseif ($d->enLinea())<span class="rh-chip ok">En línea</span>
                    @else<span class="rh-chip bad">Sin señal</span>@endif
                </div>
                <div class="rh-sub" style="margin-top:8px;">{{ $d->last_seen_at ? 'Visto '.$d->last_seen_at->diffForHumans() : 'Nunca conectado' }}{{ $d->agent_version ? ' · agente '.$d->agent_version : '' }} · <b>{{ $d->checadas_hoy }}</b> checada(s) hoy</div>
                <div class="rh-actions" style="margin-top:12px;">
                    <a class="btn btn--ghost" href="{{ $d->urlPantalla() }}" target="_blank" rel="noopener">Abrir pantalla</a>
                    <button type="button" class="btn btn--ghost" data-rh-toggle="bm-url{{ $d->id }}">Ver URL</button>
                    <form method="POST" action="{{ route('asistencia.biometria.dispositivos.llave', $d) }}" data-confirm="El agente instalado dejará de funcionar hasta que se le ponga la llave nueva." data-confirm-title="Regenerar llave" data-confirm-ok="Regenerar" style="margin:0;">@csrf<button class="btn btn--ghost" type="submit">Nueva llave</button></form>
                    <form method="POST" action="{{ route('asistencia.biometria.dispositivos.alternar', $d) }}" style="margin:0;">@csrf<button class="btn btn--ghost" type="submit">{{ $d->status === 'active' ? 'Desactivar' : 'Activar' }}</button></form>
                </div>
                <div class="rh-acc plain" id="bm-url{{ $d->id }}" style="margin-top:10px;">
                    <div class="rh-acc-b"><div class="rh-acc-i"><div class="rh-acc-c"><div class="rh-sub" style="word-break:break-all;">Abre esta URL en pantalla completa (Chrome en modo kiosco) en la computadora del lector:<br><b>{{ $d->urlPantalla() }}</b></div></div></div></div>
                </div>
            </div>
        @empty
            <div class="rh-card rh-empty"><b>Todavía no hay lectores</b>Crea el de recepción con «Nuevo lector».</div>
        @endforelse
    </div>

    {{-- ============ Registros en curso ============ --}}
    <div id="en-curso" class="rh-card rh-pad bm-curso" @if (! $inscripciones->count()) hidden @endif>
        <b class="rh-h">Registros de huella en curso</b>
        <div id="en-curso-lista">
            @foreach ($inscripciones as $i)
                <div class="bm-ins" data-id="{{ $i->id }}">
                    <span><b>{{ $i->user->name }}</b> · {{ $i->device->name }}</span>
                    <span class="rh-sub" data-msg>{{ $i->message }}</span>
                    <span class="rh-bar" style="width:120px;"><i data-w="{{ round($i->step * 100 / 3) }}" style="--bc:var(--primary)"></i></span>
                    <form method="POST" action="{{ route('asistencia.biometria.inscripciones.cancelar', $i) }}" style="margin:0;">@csrf<button class="btn btn--ghost" type="submit" style="padding:4px 10px;">Cancelar</button></form>
                </div>
            @endforeach
        </div>
    </div>

    {{-- ============ Huellas por empleado ============ --}}
    <h3 class="bm-h3">Huellas por empleado</h3>
    <div class="rh-stack-gap">
        @foreach ($usuarios as $i => $u)
            @php $activas = $u->huellas->where('status', 'active'); @endphp
            <div class="rh-acc rh-in" id="e{{ $u->id }}" style="--i:{{ min($i, 10) + 5 }}">
                <div class="rh-acc-h">
                    <div class="rh-acc-main"><div class="rh-acc-t">{{ $u->name }}</div><div class="rh-acc-s">{{ $u->checador_id ?: 'Sin no. de checador' }}</div></div>
                    <div class="rh-acc-side">
                        @if ($activas->count())<span class="rh-chip ok">{{ $activas->count() }} huella(s)</span>@else<span class="rh-chip warn">Sin huella</span>@endif
                    </div>
                </div>
                <div class="rh-acc-b"><div class="rh-acc-i"><div class="rh-acc-c">
                    <div class="bm-dedos">
                        @foreach ($activas as $h)
                            <form method="POST" action="{{ route('asistencia.biometria.huellas.destroy', $h) }}" data-confirm="{{ $u->name }} tendrá que volver a registrar este dedo." data-confirm-title="Borrar huella" style="margin:0;">@csrf @method('DELETE')
                                <span class="bm-dedo" title="Registrada {{ $h->registered_at?->format('d/m/Y') }}"><x-ui.icon name="fingerprint" :size="14" /> {{ $h->dedoLabel() }} <button type="submit" title="Borrar" aria-label="Borrar huella"><x-ui.icon name="x" :size="13" /></button></span>
                            </form>
                        @endforeach
                    </div>
                    @if ($dispositivos->where('status', 'active')->count())
                        <form method="POST" action="{{ route('asistencia.biometria.registrar', $u) }}" class="rh-form bm-reg">@csrf
                            <label>Lector
                                <select name="device_id">@foreach ($dispositivos->where('status', 'active') as $d)<option value="{{ $d->id }}">{{ $d->name }}</option>@endforeach</select>
                            </label>
                            <label>Dedo
                                <select name="finger_index">
                                    @foreach ($dedos as $n => $t)<option value="{{ $n }}" @selected($n === 1 && ! $activas->contains('finger_index', 1) || ($activas->contains('finger_index', 1) && ! $activas->contains('finger_index', 6) && $n === 6))>{{ $t }}</option>@endforeach
                                </select>
                            </label>
                            <button class="btn" type="submit">Registrar huella</button>
                        </form>
                    @else
                        <p class="rh-sub">Crea un lector activo para poder registrar huellas.</p>
                    @endif
                </div></div></div>
            </div>
        @endforeach
    </div>

    <p class="rh-sub" style="margin-top:14px;">Al solicitar el registro, el lector de recepción guía al empleado (3 lecturas) y la pantalla del checador lo muestra. No se guarda ninguna imagen de la huella: solo la plantilla del lector, cifrada.</p>

    <style>
        .bm-llave { margin-bottom:16px; } .bm-llave p { margin:8px 0; font-size:13px; }
        .bm-cfg { margin:0 0 12px; padding:12px 14px; background:var(--surface-2); border:1px solid var(--border); border-radius:8px; font-size:12.5px; line-height:1.5; overflow-x:auto; white-space:pre; }
        .bm-llave code { display:block; padding:10px 12px; background:var(--surface-2); border:1px solid var(--border); border-radius:8px; word-break:break-all; font-size:13px; margin-bottom:10px; }
        .bm-lectores { display:grid; grid-template-columns:repeat(auto-fill, minmax(320px, 1fr)); gap:14px; }
        .bm-l-cab { display:flex; justify-content:space-between; gap:10px; align-items:flex-start; }
        .bm-curso { margin-top:16px; }
        .bm-ins { display:flex; flex-wrap:wrap; align-items:center; gap:14px; padding:10px 0; border-top:1px solid var(--border); margin-top:8px; }
        .bm-h3 { margin:26px 0 12px; font-size:15px; }
        .bm-dedos { display:flex; gap:8px; flex-wrap:wrap; margin-bottom:12px; }
        .bm-dedo { display:inline-flex; gap:8px; align-items:center; padding:5px 10px; border:1px solid var(--border); border-radius:8px; font-size:13px; background:var(--surface-2); }
        .bm-dedo button { border:0; background:none; color:var(--muted); cursor:pointer; padding:0; line-height:0; } .bm-dedo button:hover { color:var(--danger); }
        .bm-reg { display:flex; flex-wrap:wrap; gap:12px; align-items:flex-end; } .bm-reg label { min-width:170px; }
    </style>
@endsection

@push('scripts')
<script>
    // Avance de los registros en curso sin recargar
    (function () {
        var url = @json(route('asistencia.biometria.estado'));
        var caja = document.getElementById('en-curso'), lista = document.getElementById('en-curso-lista');
        var hayActivos = !!lista.children.length;
        var previos = {};
        async function tick() {
            try {
                var r = await fetch(url, { headers: { Accept: 'application/json' } });
                var d = await r.json();
                var activos = d.filter(function (x) { return x.status === 'pending' || x.status === 'capturing'; });
                d.forEach(function (x) {
                    if (previos[x.id] && previos[x.id] !== x.status && x.status === 'done') { window.showToast && showToast('Huella de ' + x.nombre + ' registrada'); setTimeout(function () { location.reload(); }, 800); }
                    if (previos[x.id] && previos[x.id] !== x.status && x.status === 'failed') { window.showToast && showToast(x.nombre + ': ' + x.message, 'warn'); }
                    previos[x.id] = x.status;
                });
                lista.querySelectorAll('.bm-ins').forEach(function (el) {
                    var x = d.find(function (y) { return String(y.id) === el.dataset.id; });
                    if (!x) return;
                    el.querySelector('[data-msg]').textContent = x.message || '';
                    el.querySelector('.rh-bar > i').style.width = Math.round(x.step * 100 / 3) + '%';
                });
                if (hayActivos && !activos.length) location.reload();
                hayActivos = activos.length > 0 || hayActivos;
            } catch (e) {}
        }
        if (!caja.hidden) { tick(); setInterval(tick, 2000); }
    })();
</script>
@endpush
