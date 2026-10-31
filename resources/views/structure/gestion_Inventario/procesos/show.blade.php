@extends('layouts.dashboard')

@php
    $prod = $pieza->producto;
    $equipo = trim(collect([$prod?->tipo_equipo, $prod?->marca, $prod?->modelo])->filter()->implode(' ')) ?: 'Equipo';
    $enCurso = $paso->estado === 'en_curso';
    // Se puede ir guardando avance mientras el paso no se cierre; si nadie
    // la había tomado, quien guarda el primer avance la toma.
    $abierto = in_array($paso->estado, ['pendiente', 'en_curso'], true);
    // Lo contestado en avances anteriores se queda marcado.
    $borrador = $paso->checklist_salida ?? [];
@endphp

@section('title', $paso->nombre().' · '.$pieza->codigo)
@section('page-title', $paso->nombre())
@section('page-sub', $pieza->codigo.' · '.$equipo)

@push('head')
    <style>
        .pv-cab { display:flex; align-items:center; gap:14px; flex-wrap:wrap; }
        .pv-cab .cod { font-family:ui-monospace,Consolas,monospace; font-size:16px; font-weight:800; letter-spacing:.03em; }
        .pv-cab .eq { flex:1; min-width:160px; color:var(--muted); font-size:14px; }

        .pv-datos { display:grid; grid-template-columns:repeat(auto-fit,minmax(150px,1fr)); gap:1px;
                    background:var(--border); border:1px solid var(--border); border-radius:11px;
                    overflow:hidden; margin-top:16px; }
        .pv-datos > div { padding:13px 15px; background:var(--surface); }
        .pv-datos .e { display:block; color:var(--muted); font-size:11px; font-weight:700;
                       letter-spacing:.06em; text-transform:uppercase; }
        .pv-datos .v { display:block; margin-top:3px; font-size:14.5px; font-weight:600; }

        .pv-porque { margin-top:16px; padding:12px 14px; border-radius:10px;
                     background:var(--ambar-soft, #fdf3e3); color:var(--ambar, #b45309);
                     font-size:13.5px; line-height:1.5; }

        /* Checklist de salida: sí o no, sin medias tintas. */
        .cs-grupo + .cs-grupo { margin-top:20px; }
        .cs-grupo > h4 { margin:0 0 10px; font-size:11px; font-weight:800; letter-spacing:.07em;
                         text-transform:uppercase; color:var(--muted); }
        .cs-punto { display:flex; align-items:center; gap:12px; padding:11px 0;
                    border-bottom:1px solid var(--border); flex-wrap:wrap; }
        .cs-punto:last-child { border-bottom:none; }
        .cs-punto .txt { flex:1; min-width:150px; font-size:14px; line-height:1.4; }
        .cs-punto .nota { display:none; width:100%; margin-top:8px; }
        .cs-punto.con-nota .nota { display:block; }

        .cs-punto.falla { background:var(--danger-soft); margin:0 -10px; padding:11px 10px; border-radius:8px; }

        .duo { display:inline-flex; flex:0 0 auto; border:1px solid var(--border);
               border-radius:8px; overflow:hidden; background:var(--surface); }
        .duo label { display:inline-flex; align-items:center; justify-content:center; margin:0;
                     padding:7px 16px; font-size:13px; font-weight:700; color:var(--muted);
                     cursor:pointer; border-left:1px solid var(--border); transition:background .14s ease; }
        .duo label:first-of-type { border-left:0; }
        .duo input { position:absolute; opacity:0; pointer-events:none; }
        .duo label:hover { background:var(--surface-2); }
        .duo label:has(input[value="si"]:checked) { background:var(--green); color:#fff; }
        .duo label:has(input[value="no"]:checked) { background:var(--danger); color:#fff; }

        .cs-avance { margin:0 0 4px; font-size:13.5px; }

        /* Evidencia */
        .ev-soltar { display:flex; flex-direction:column; align-items:center; justify-content:center;
                     gap:8px; padding:24px 18px; border:1.5px dashed var(--border); border-radius:12px;
                     background:var(--surface-2); color:var(--muted); text-align:center; cursor:pointer;
                     transition:border-color .16s ease, background .16s ease; }
        .ev-soltar:hover, .ev-soltar.encima { border-color:var(--primary); background:var(--primary-soft); }
        .ev-soltar .t { color:var(--text); font-weight:600; font-size:14px; }
        .ev-soltar .d { font-size:12.5px; }
        .ev-soltar input { display:none; }
        .ev-minis { display:grid; grid-template-columns:repeat(auto-fill,minmax(110px,1fr)); gap:10px; margin-top:12px; }
        .ev-mini { position:relative; aspect-ratio:1; border-radius:10px; overflow:hidden;
                   border:1px solid var(--border); background:var(--surface-2); }
        .ev-mini img { width:100%; height:100%; object-fit:cover; display:block; }
        .ev-mini .quitar { position:absolute; top:5px; right:5px; width:24px; height:24px;
                           display:flex; align-items:center; justify-content:center; border:0;
                           border-radius:50%; background:rgba(15,23,42,.72); color:#fff;
                           font-size:14px; line-height:1; cursor:pointer; }

        /* El bloqueo: por qué no puede cerrarse todavía */
        .pv-bloqueo { display:flex; gap:11px; padding:13px 15px; border-radius:10px;
                      background:var(--danger-soft); color:var(--danger); font-size:13.5px; line-height:1.5; }
        .pv-bloqueo svg { width:18px; height:18px; flex:0 0 18px; margin-top:1px; }
        .pv-listo { display:flex; gap:11px; padding:13px 15px; border-radius:10px;
                    background:var(--green-soft); color:var(--green); font-size:13.5px; line-height:1.5; }
        .pv-listo svg { width:18px; height:18px; flex:0 0 18px; margin-top:1px; }

        .pv-hist { display:flex; gap:12px; padding:11px 0; border-bottom:1px solid var(--border); }
        .pv-hist:last-child { border-bottom:none; }
        .pv-hist .e { flex:0 0 110px; color:var(--muted); font-size:13px; }

        /* Bitácora: un avance por día, con su nota y sus fotos */
        .pv-avance { padding:13px 0; border-bottom:1px solid var(--border); }
        .pv-avance:last-child { border-bottom:none; padding-bottom:0; }
        .pv-avance-cab { display:flex; gap:10px; flex-wrap:wrap; align-items:baseline; font-size:13.5px; }
        .pv-avance-cab b { text-transform:capitalize; }
        .pv-avance-cab span { color:var(--muted); font-size:12.5px; }
        .pv-avance-nota { margin:6px 0 0; font-size:14px; line-height:1.5; white-space:pre-line; }
        .ev-mini .ev-ext { display:flex; align-items:center; justify-content:center; width:100%; height:100%;
                           color:var(--muted); font-size:12px; font-weight:700; }
        .ev-mini .ev-estado { position:absolute; left:0; right:0; bottom:0; padding:3px 0; text-align:center;
                              background:rgba(0,0,0,.55); color:#fff; font-size:11px; font-weight:600; }
        .ev-mini.subiendo img { opacity:.6; }
        .ev-mini.con-error { outline:2px solid var(--danger); }
    </style>
@endpush

@section('content')
    <x-ui.page-header :title="$paso->nombre()" :back="route('inventory.procesos.index')">
        <a href="{{ route('inventory.productos.show', $prod) }}" class="btn btn--ghost">Ver el producto</a>
        @if ($pieza->urlPublica())
            <a href="{{ $pieza->urlPublica() }}" target="_blank" class="btn btn--ghost">Ficha pública</a>
        @endif
    </x-ui.page-header>

    @if (session('status'))
        <x-ui.alert type="ok">{{ session('status') }}</x-ui.alert>
    @endif

    @error('cierre')
        <div class="pv-bloqueo" style="margin-bottom:18px;">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            <span><b>No se puede cerrar todavía.</b> {{ $message }}</span>
        </div>
    @enderror

    <div class="rgrid-sidebar">
        <div>
            {{-- ===================== La pieza ===================== --}}
            <x-ui.card style="margin-bottom:18px;">
                <div class="pv-cab">
                    <span class="cod">{{ $pieza->codigo }}</span>
                    <span class="eq">{{ $equipo }}</span>
                    <span class="badge {{ $enCurso ? 'badge--info' : '' }}">
                        {{ $enCurso ? 'En curso' : 'Esperando turno' }}
                    </span>
                </div>

                <div class="pv-datos">
                    <div><span class="e">No. de serie</span><span class="v">{{ $pieza->no_serie ?: 'Sin serie' }}</span></div>
                    <div><span class="e">Condición</span><span class="v">{{ ucfirst($pieza->condicion) }}</span></div>
                    <div>
                        <span class="e">Entró al proceso</span>
                        <span class="v">{{ ($paso->iniciado_en ?? $paso->created_at)?->format('d/m/Y') ?? '—' }}</span>
                    </div>
                    <div>
                        <span class="e">Lleva</span>
                        <span class="v">{{ $paso->diasDetenida() === 0 ? 'Menos de un día' : $paso->diasDetenida().' día(s)' }}</span>
                    </div>
                    <div><span class="e">Responsable</span><span class="v">{{ $paso->responsable?->name ?: 'Sin asignar' }}</span></div>
                </div>

                @if ($paso->motivo)
                    <p class="pv-porque"><b>Llegó aquí por:</b> {{ $paso->motivo }}</p>
                @endif
            </x-ui.card>

            {{-- ===================== Todavía no la toma nadie ===================== --}}
            @unless ($enCurso)
                <x-ui.card style="margin-bottom:18px;">
                    <x-ui.section-title style="margin:0 0 6px;">Nadie la está trabajando</x-ui.section-title>
                    <p class="campo-nota" style="margin:0 0 14px;">
                        Tómala para que quede registrado quién la trabajó y desde cuándo.
                        Si guardas un avance, también queda a tu nombre.
                    </p>

                    <form method="POST" action="{{ route('inventory.procesos.iniciar', $paso) }}">
                        @csrf
                        <button type="submit" class="btn">Empezar a trabajarla</button>
                    </form>
                </x-ui.card>
            @endunless

            {{-- ===================== La verificación ===================== --}}
            <form method="POST" action="{{ route('inventory.procesos.terminar', $paso) }}"
                  enctype="multipart/form-data" id="form-cierre" @disabled(! $enCurso)>
                @csrf

                <x-ui.card style="margin-bottom:18px;">
                    <x-ui.section-title style="margin:0 0 6px;">Verificación de salida</x-ui.section-title>
                    <p class="campo-nota" style="margin:0 0 6px;">
                        Todos los puntos tienen que quedar en <b>Sí</b>. Si algo sigue mal, la pieza no sale
                        del proceso: se queda aquí hasta que quede. No hace falta hacerlo todo hoy:
                        con <b>Guardar avance</b> se queda lo que llevas y sigues otro día.
                    </p>
                    <p class="cs-avance">
                        <b data-cs-si>0</b> de {{ collect($checklist)->sum(fn ($s) => count($s['puntos'])) }} verificados
                        · <b data-cs-no>0</b> siguen mal
                    </p>

                    @foreach ($checklist as $llaveGrupo => $grupo)
                        <div class="cs-grupo">
                            <h4>{{ $grupo['titulo'] }}</h4>

                            @foreach ($grupo['puntos'] as $llave => $texto)
                                <div class="cs-punto" data-cs-punto>
                                    <span class="txt">{{ $texto }}</span>

                                    <span class="duo">
                                        @foreach (['si' => 'Sí', 'no' => 'No'] as $r => $etiqueta)
                                            <label>
                                                <input type="radio" name="checklist[{{ $llave }}][r]" value="{{ $r }}"
                                                       @checked(old("checklist.$llave.r", $borrador[$llave]['r'] ?? null) === $r) @disabled(! $abierto)>
                                                <span>{{ $etiqueta }}</span>
                                            </label>
                                        @endforeach
                                    </span>

                                    <input type="text" class="nota" name="checklist[{{ $llave }}][nota]"
                                           maxlength="300" placeholder="¿Qué falta? (queda anotado)"
                                           value="{{ old("checklist.$llave.nota", $borrador[$llave]['nota'] ?? '') }}" @disabled(! $abierto)>
                                </div>
                            @endforeach
                        </div>
                    @endforeach
                </x-ui.card>

                {{-- ===================== Avance de hoy =====================
                     Un proceso se trabaja por partes: cada día se guarda lo
                     que se hizo, con sus fotos, sin límite. Al terminar
                     cuentan las fotos de todos los días. --}}
                <x-ui.card style="margin-bottom:18px;">
                    <x-ui.section-title style="margin:0 0 6px;">Lo que se hizo hoy</x-ui.section-title>
                    <p class="campo-nota" style="margin:0 0 14px;">
                        Anota lo que avanzaste y sube las fotos que quieras, de cualquier formato y tamaño.
                        Se guarda en la bitácora con tu nombre y la fecha. Para terminar el proceso hace falta
                        al menos una foto en total.
                    </p>

                    <x-ui.form-group label="Nota del avance" for="nota">
                        <textarea id="nota" name="nota" rows="3" maxlength="3000"
                                  placeholder="Ej. se desarmó la carcasa y se lijó el golpe; mañana va la pintura"
                                  @disabled(! $abierto)>{{ old('nota') }}</textarea>
                    </x-ui.form-group>

                    <label class="ev-soltar" data-ev-soltar>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" width="26" height="26"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                        <span class="t">Arrastra las fotos o toca para elegir</span>
                        <span class="d" data-ev-cuenta>Sin límite · cualquier formato y tamaño</span>
                        <input type="file" multiple data-ev-input @disabled(! $abierto)>
                    </label>

                    <div class="ev-minis" data-ev-minis>
                        {{-- Si el servidor regresó el formulario, las fotos ya subidas se conservan. --}}
                        @foreach ((array) old('fotos_paths', []) as $ruta)
                            <div class="ev-mini" data-ev-previa="{{ $ruta }}"></div>
                        @endforeach
                    </div>

                    <x-ui.form-group label="Resumen de lo que se le hizo" for="trabajo_realizado" style="margin-top:14px;">
                        <textarea id="trabajo_realizado" name="trabajo_realizado" rows="2"
                                  placeholder="Ej. se cambió la fuente de poder y se enderezó la tapa derecha"
                                  @disabled(! $abierto)>{{ old('trabajo_realizado', $paso->trabajo_realizado) }}</textarea>
                    </x-ui.form-group>
                </x-ui.card>

                {{-- ===================== Guardar o terminar ===================== --}}
                <x-ui.card>
                    <div data-bloqueo class="pv-bloqueo" style="margin-bottom:14px;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                        <span data-bloqueo-txt></span>
                    </div>

                    <div data-listo class="pv-listo" style="display:none; margin-bottom:14px;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6 9 17l-5-5"/></svg>
                        <span>
                            Todo verificado.
                            @if ($siguientes->isNotEmpty())
                                Al cerrar, la pieza pasa a <b>{{ $siguientes->map->nombre()->implode(', ') }}</b>.
                            @else
                                Al cerrar, la pieza queda <b>disponible para venta</b>.
                            @endif
                        </span>
                    </div>

                    <div style="display:flex; gap:10px; flex-wrap:wrap;">
                        {{-- Guardar avance no exige nada: guarda lo que haya. --}}
                        <button type="submit" class="btn btn--ghost" data-guardar-avance
                                formaction="{{ route('inventory.procesos.avance', $paso) }}" @disabled(! $abierto) @if (! $abierto) data-cerrado @endif>
                            Guardar avance
                        </button>
                        <button type="submit" class="btn" data-cerrar @disabled(! $enCurso)>
                            Terminar {{ mb_strtolower($paso->nombre()) }}
                        </button>
                    </div>
                </x-ui.card>
            </form>

            {{-- ===================== Bitácora ===================== --}}
            <x-ui.card style="margin-top:18px;">
                <x-ui.section-title style="margin:0 0 12px;">
                    Bitácora de avances
                    <span class="campo-nota" style="font-weight:500;">· {{ $paso->avances->count() }}</span>
                </x-ui.section-title>

                @forelse ($paso->avances as $avance)
                    <div class="pv-avance">
                        <div class="pv-avance-cab">
                            <b>{{ $avance->created_at->translatedFormat('l d/m/Y') }}</b>
                            <span>{{ $avance->created_at->format('H:i') }} · {{ $avance->autor?->name ?? 'Sin nombre' }}</span>
                        </div>
                        @if ($avance->nota)
                            <p class="pv-avance-nota">{{ $avance->nota }}</p>
                        @endif
                        @if ($avance->evidencias)
                            <div class="ev-minis" style="margin-top:8px;">
                                @foreach ($avance->fotos() as $foto)
                                    <a class="ev-mini" href="{{ $foto['url'] }}" target="_blank" rel="noopener">
                                        @if (in_array(strtolower(pathinfo($foto['path'], PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'gif', 'webp', 'avif', 'bmp', 'jfif'], true))
                                            <img src="{{ $foto['url'] }}" alt="Foto del avance" loading="lazy">
                                        @else
                                            <span class="ev-ext">{{ strtoupper(pathinfo($foto['path'], PATHINFO_EXTENSION)) }}</span>
                                        @endif
                                    </a>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @empty
                    <p class="campo-nota" style="margin:0;">
                        Todavía no hay avances. Cada vez que guardes uno aparece aquí con la fecha, quién lo hizo y sus fotos.
                    </p>
                @endforelse
            </x-ui.card>
        </div>

        {{-- ===================== Lateral ===================== --}}
        <div style="display:flex; flex-direction:column; gap:18px;">
            <x-ui.card>
                <x-ui.section-title style="margin:0 0 12px;">La ruta de esta pieza</x-ui.section-title>

                @foreach ($pieza->procesos as $p)
                    <div class="pv-hist">
                        <span class="e">{{ $p->nombre() }}</span>
                        <span style="flex:1; min-width:0;">
                            <span class="badge {{ $p->estado === 'terminado' ? 'badge--ok' : ($p->id === $paso->id ? 'badge--info' : '') }}">
                                {{ ucfirst(str_replace('_', ' ', $p->estado)) }}
                            </span>
                            @if ($p->terminado_en)
                                <span style="display:block; margin-top:3px; color:var(--muted); font-size:12.5px;">
                                    {{ $p->terminado_en->format('d/m/Y') }}
                                    @if ($p->cerradoPor) · {{ $p->cerradoPor->name }} @endif
                                </span>
                            @endif
                        </span>
                    </div>
                @endforeach

                <p class="campo-nota" style="margin:12px 0 0;">
                    La pieza pasa a stock cuando no le quede ningún paso pendiente.
                </p>
            </x-ui.card>

            @if ($enCurso)
                <x-ui.card>
                    <x-ui.section-title style="margin:0 0 6px;">¿No hacía falta?</x-ui.section-title>
                    <p class="campo-nota" style="margin:0 0 12px;">
                        Si al verla de cerca resulta que este proceso no aplicaba, descártalo.
                        Queda registrado quién lo decidió y por qué.
                    </p>

                    <form method="POST" action="{{ route('inventory.procesos.omitir', $paso) }}">
                        @csrf
                        <x-ui.form-group label="Por qué se descarta" for="motivo">
                            <textarea id="motivo" name="motivo" rows="3" minlength="10" maxlength="1000"
                                      placeholder="Ej. el golpe era solo tierra pegada, se limpió y quedó bien">{{ old('motivo') }}</textarea>
                        </x-ui.form-group>
                        @error('motivo')<p class="err">{{ $message }}</p>@enderror

                        <button type="submit" class="btn btn--ghost" style="margin-top:10px;">Descartar este proceso</button>
                    </form>
                </x-ui.card>
            @endif
        </div>
    </div>

    @include('structure.gestion_Inventario._subir_fotos')

    @push('scripts')
        <script>
        document.addEventListener('DOMContentLoaded', function () {
            const form = document.getElementById('form-cierre');
            if (!form) return;

            const URL_FOTO = @json(route('inventory.procesos.fotoChunk'));
            const puntos = Array.from(form.querySelectorAll('[data-cs-punto]'));
            const contSi = form.querySelector('[data-cs-si]');
            const contNo = form.querySelector('[data-cs-no]');
            const bloqueo = form.querySelector('[data-bloqueo]');
            const bloqueoTxt = form.querySelector('[data-bloqueo-txt]');
            const listo = form.querySelector('[data-listo]');
            const botonCerrar = form.querySelector('[data-cerrar]');
            const botonAvance = form.querySelector('[data-guardar-avance]');
            // Fotos que ya respaldan el paso: las del cierre y las de todos sus avances.
            const yaHabia = {{ $paso->totalFotos() }};
            const enCurso = @json($enCurso);

            /* ==========================================================
               Fotos del día: se suben al elegirlas, sin límite de
               cantidad, tamaño ni formato. Solo viaja la ruta de cada una.
            ========================================================== */
            const zona = form.querySelector('[data-ev-soltar]');
            const input = form.querySelector('[data-ev-input]');
            const minis = form.querySelector('[data-ev-minis]');
            const cuenta = form.querySelector('[data-ev-cuenta]');
            const textoBase = cuenta.textContent;
            input.setAttribute('accept', ObsFotos.ACEPTA);

            // [{ path, url, nombre, subiendo, progreso, error }]
            let fotos = Array.from(minis.querySelectorAll('[data-ev-previa]')).map(function (el) {
                const path = el.dataset.evPrevia;
                return { path, url: ObsFotos.urlDisco + path, nombre: path.split('/').pop() };
            });

            function pintarFotos() {
                minis.innerHTML = '';

                fotos.forEach(function (f, i) {
                    const caja = document.createElement('div');
                    caja.className = 'ev-mini' + (f.subiendo ? ' subiendo' : '') + (f.error ? ' con-error' : '');

                    if (f.url && ObsFotos.seVe(f.nombre)) {
                        const img = document.createElement('img');
                        img.src = f.url;
                        img.alt = f.nombre;
                        caja.appendChild(img);
                    } else {
                        const ext = document.createElement('span');
                        ext.className = 'ev-ext';
                        ext.textContent = ObsFotos.extension(f.nombre).toUpperCase();
                        caja.appendChild(ext);
                    }

                    if (f.subiendo || f.error) {
                        const estado = document.createElement('span');
                        estado.className = 'ev-estado';
                        estado.textContent = f.error ? 'Error' : (f.progreso ? f.progreso + '%' : 'Subiendo');
                        if (f.error) estado.title = f.error;
                        caja.appendChild(estado);
                    }

                    if (f.path) {
                        const oculto = document.createElement('input');
                        oculto.type = 'hidden';
                        oculto.name = 'fotos_paths[]';
                        oculto.value = f.path;
                        caja.appendChild(oculto);
                    }

                    const quitar = document.createElement('button');
                    quitar.type = 'button';
                    quitar.className = 'quitar';
                    quitar.textContent = '×';
                    quitar.setAttribute('aria-label', 'Quitar ' + f.nombre);
                    quitar.addEventListener('click', function (e) {
                        e.preventDefault();
                        e.stopPropagation();
                        fotos.splice(i, 1);
                        pintarFotos();
                    });

                    caja.appendChild(quitar);
                    minis.appendChild(caja);
                });

                const n = fotos.length;
                cuenta.textContent = n === 0 ? textoBase : (n === 1 ? '1 foto' : n + ' fotos') + ' de hoy · toca para agregar más';

                revisar();
            }

            async function subirUna(f, archivo) {
                try {
                    const lista = await ObsFotos.preparar(archivo);
                    f.nombre = lista.name;
                    if (ObsFotos.seVe(lista.name)) f.url = URL.createObjectURL(lista);
                    pintarFotos();

                    const json = await ObsFotos.subir(lista, URL_FOTO, function (pct) { f.progreso = pct; pintarFotos(); });
                    f.path = json.foto_path;
                } catch (e) {
                    f.error = e.message || 'No se pudo subir.';
                    if (window.showToast) window.showToast(f.error, 'err');
                }
                f.subiendo = false;
                pintarFotos();
            }

            function sumar(lista) {
                Array.from(lista || []).forEach(function (archivo) {
                    const f = { path: null, url: null, nombre: archivo.name, subiendo: true, progreso: 0 };
                    fotos.push(f);
                    subirUna(f, archivo);
                });
                pintarFotos();
            }

            ['dragenter', 'dragover'].forEach(ev => zona.addEventListener(ev, function (e) {
                e.preventDefault(); zona.classList.add('encima');
            }));
            ['dragleave', 'drop'].forEach(ev => zona.addEventListener(ev, function (e) {
                e.preventDefault(); zona.classList.remove('encima');
            }));
            zona.addEventListener('drop', function (e) {
                if (e.dataTransfer?.files?.length) sumar(e.dataTransfer.files);
            });
            input.addEventListener('change', function () {
                sumar(input.files);
                input.value = '';
            });

            /* ==========================================================
               La revisión: qué falta para poder terminar.

               Se calcula en pantalla para que se vea en el momento, pero
               el servidor la vuelve a hacer: esto es una ayuda, no la
               cerradura. Guardar avance no la necesita.
            ========================================================== */
            function revisar() {
                let si = 0, no = 0, sinResponder = 0;

                puntos.forEach(function (punto) {
                    const elegido = punto.querySelector('input[type=radio]:checked');

                    if (!elegido) { sinResponder++; punto.classList.remove('falla', 'con-nota'); return; }

                    const malo = elegido.value === 'no';
                    if (malo) no++; else si++;

                    punto.classList.toggle('falla', malo);
                    punto.classList.toggle('con-nota', malo);
                });

                contSi.textContent = si;
                contNo.textContent = no;

                const subiendo = fotos.some(f => f.subiendo);
                const totalFotos = yaHabia + fotos.filter(f => f.path).length;
                const faltas = [];

                if (sinResponder) faltas.push('faltan ' + sinResponder + ' punto(s) por responder');
                if (no) faltas.push(no + ' punto(s) siguen mal');
                if (totalFotos < 1) faltas.push('falta al menos una foto');
                if (subiendo) faltas.push('espera a que terminen de subir las fotos');

                const puede = faltas.length === 0;

                bloqueo.style.display = puede ? 'none' : '';
                listo.style.display = puede ? '' : 'none';
                botonCerrar.disabled = !puede || !enCurso;
                if (botonAvance) botonAvance.disabled = subiendo || botonAvance.hasAttribute('data-cerrado');

                if (!puede) {
                    bloqueoTxt.innerHTML = '<b>Todavía no se puede terminar</b> (sí puedes guardar el avance): '
                        + faltas.join(', ') + '.';
                }
            }

            puntos.forEach(p => p.addEventListener('change', revisar));
            pintarFotos();
        });
        </script>
    @endpush
@endsection
