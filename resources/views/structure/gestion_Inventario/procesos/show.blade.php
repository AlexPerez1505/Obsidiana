@extends('layouts.dashboard')

@php
    $prod = $pieza->producto;
    $equipo = trim(collect([$prod?->tipo_equipo, $prod?->marca, $prod?->modelo])->filter()->implode(' ')) ?: 'Equipo';
    $enCurso = $paso->estado === 'en_curso';
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
                        Hasta entonces no se puede cerrar.
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
                        del proceso: se queda aquí hasta que quede.
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
                                                       @checked(old("checklist.$llave.r") === $r) @disabled(! $enCurso)>
                                                <span>{{ $etiqueta }}</span>
                                            </label>
                                        @endforeach
                                    </span>

                                    <input type="text" class="nota" name="checklist[{{ $llave }}][nota]"
                                           maxlength="300" placeholder="¿Qué falta? (queda anotado)"
                                           value="{{ old("checklist.$llave.nota") }}" @disabled(! $enCurso)>
                                </div>
                            @endforeach
                        </div>
                    @endforeach
                </x-ui.card>

                {{-- ===================== Evidencia ===================== --}}
                <x-ui.card style="margin-bottom:18px;">
                    <x-ui.section-title style="margin:0 0 6px;">Fotos de que quedó funcionando *</x-ui.section-title>
                    <p class="campo-nota" style="margin:0 0 14px;">
                        Al menos una. Es lo que respalda que el equipo salió bien de aquí, y se ve
                        en la ficha de la pieza.
                    </p>

                    @if ($paso->evidencias)
                        <div class="ev-minis" style="margin-bottom:12px;">
                            @foreach ($paso->evidenciaUrls() as $url)
                                <a class="ev-mini" href="{{ $url }}" target="_blank"><img src="{{ $url }}" alt="Evidencia"></a>
                            @endforeach
                        </div>
                    @endif

                    <label class="ev-soltar" data-ev-soltar>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" width="26" height="26"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                        <span class="t">Arrastra las fotos o toca para elegir</span>
                        <span class="d" data-ev-cuenta>Ninguna todavía · máximo 5 · JPG o PNG de hasta 5 MB</span>
                        <input type="file" name="evidencias[]" accept="image/*" multiple @disabled(! $enCurso)>
                    </label>

                    <div class="ev-minis" data-ev-minis></div>
                    @error('evidencias.*')<p class="err">{{ $message }}</p>@enderror

                    <x-ui.form-group label="¿Qué se le hizo?" for="trabajo_realizado">
                        <textarea id="trabajo_realizado" name="trabajo_realizado" rows="3"
                                  placeholder="Ej. se cambió la fuente de poder y se enderezó la tapa derecha"
                                  @disabled(! $enCurso)>{{ old('trabajo_realizado', $paso->trabajo_realizado) }}</textarea>
                    </x-ui.form-group>
                </x-ui.card>

                {{-- ===================== Cerrar ===================== --}}
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

                    <button type="submit" class="btn" data-cerrar @disabled(! $enCurso)>
                        Terminar {{ mb_strtolower($paso->nombre()) }}
                    </button>
                </x-ui.card>
            </form>
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

    @push('scripts')
        <script>
        document.addEventListener('DOMContentLoaded', function () {
            const form = document.getElementById('form-cierre');
            if (!form) return;

            const puntos = Array.from(form.querySelectorAll('[data-cs-punto]'));
            const totalPuntos = puntos.length;
            const contSi = form.querySelector('[data-cs-si]');
            const contNo = form.querySelector('[data-cs-no]');
            const bloqueo = form.querySelector('[data-bloqueo]');
            const bloqueoTxt = form.querySelector('[data-bloqueo-txt]');
            const listo = form.querySelector('[data-listo]');
            const botonCerrar = form.querySelector('[data-cerrar]');
            const inputFotos = form.querySelector('[data-ev-soltar] input[type=file]');
            const yaHabia = {{ count($paso->evidencias ?? []) }};

            /* ==========================================================
               Fotos: se ven antes de mandarlas y se pueden quitar.
            ========================================================== */
            const zona = form.querySelector('[data-ev-soltar]');
            const minis = form.querySelector('[data-ev-minis]');
            const cuenta = form.querySelector('[data-ev-cuenta]');
            const textoBase = cuenta.textContent;

            function pintarFotos() {
                minis.innerHTML = '';

                Array.from(inputFotos.files || []).forEach(function (archivo, i) {
                    const caja = document.createElement('div');
                    caja.className = 'ev-mini';

                    const img = document.createElement('img');
                    img.src = URL.createObjectURL(archivo);

                    const quitar = document.createElement('button');
                    quitar.type = 'button';
                    quitar.className = 'quitar';
                    quitar.textContent = '×';
                    quitar.setAttribute('aria-label', 'Quitar ' + archivo.name);
                    quitar.addEventListener('click', function (e) {
                        e.preventDefault();
                        e.stopPropagation();
                        const dt = new DataTransfer();
                        Array.from(inputFotos.files).forEach((a, j) => { if (j !== i) dt.items.add(a); });
                        inputFotos.files = dt.files;
                        pintarFotos();
                    });

                    caja.append(img, quitar);
                    minis.appendChild(caja);
                });

                const n = inputFotos.files ? inputFotos.files.length : 0;
                cuenta.textContent = n === 0 ? textoBase : n + ' foto(s) elegida(s) · toca para cambiar';

                revisar();
            }

            ['dragenter', 'dragover'].forEach(ev => zona.addEventListener(ev, function (e) {
                e.preventDefault(); zona.classList.add('encima');
            }));
            ['dragleave', 'drop'].forEach(ev => zona.addEventListener(ev, function (e) {
                e.preventDefault(); zona.classList.remove('encima');
            }));
            zona.addEventListener('drop', function (e) {
                if (!e.dataTransfer?.files?.length) return;
                const dt = new DataTransfer();
                Array.from(e.dataTransfer.files).slice(0, 5).forEach(a => dt.items.add(a));
                inputFotos.files = dt.files;
                pintarFotos();
            });
            inputFotos.addEventListener('change', pintarFotos);

            /* ==========================================================
               La revisión: qué falta para poder cerrar.

               Se calcula en pantalla para que se vea en el momento, pero
               el servidor la vuelve a hacer: esto es una ayuda, no la
               cerradura.
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

                const fotos = (inputFotos.files ? inputFotos.files.length : 0) + yaHabia;
                const faltas = [];

                if (sinResponder) faltas.push('faltan ' + sinResponder + ' punto(s) por responder');
                if (no) faltas.push(no + ' punto(s) siguen mal');
                if (fotos < 1) faltas.push('falta al menos una foto');

                const puede = faltas.length === 0;

                bloqueo.style.display = puede ? 'none' : '';
                listo.style.display = puede ? '' : 'none';
                botonCerrar.disabled = !puede;

                if (!puede) {
                    bloqueoTxt.innerHTML = '<b>Todavía no se puede cerrar:</b> ' + faltas.join(', ') + '.';
                }
            }

            puntos.forEach(p => p.addEventListener('change', revisar));
            revisar();
        });
        </script>
    @endpush
@endsection
