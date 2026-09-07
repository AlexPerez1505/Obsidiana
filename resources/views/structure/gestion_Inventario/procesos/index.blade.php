@extends('layouts.dashboard')

@section('title', 'Procesos')
@section('page-title', 'Procesos')
@section('page-sub', 'Lo que está en hojalatería, mantenimiento y limpieza antes de poder venderse')

@section('content')
    @php
        $filas = $pasos->map(function ($paso) {
            $pieza = $paso->pieza;
            $prod = $pieza->producto;

            return [
                'paso' => $paso,
                'pieza' => $pieza,
                'equipo' => trim(collect([$prod?->tipo_equipo, $prod?->marca, $prod?->modelo])->filter()->implode(' ')) ?: 'Equipo',
                'responsable' => $paso->responsable?->name ?: 'Sin asignar',
                'dias' => $paso->diasDetenida(),
                'fecha' => ($paso->iniciado_en ?? $paso->created_at)?->format('Y-m-d') ?? '',
            ];
        });

        $responsables = $filas->pluck('responsable')->unique()->sort()->values();

        $datos = fn (array $f) => [
            'data-buscar' => mb_strtolower($f['pieza']->codigo.' '.$f['equipo'].' '.$f['responsable'].' '.($f['pieza']->no_serie ?? '')),
            'data-proceso' => $f['paso']->proceso,
            'data-avance' => $f['paso']->estado,
            'data-responsable' => $f['responsable'],
            'data-condicion' => $f['pieza']->condicion,
            'data-atorada' => $f['dias'] >= 7 ? '1' : '0',
            'data-fecha' => $f['fecha'],
        ];
    @endphp

    @if (session('status'))
        <x-ui.alert type="ok">{{ session('status') }}</x-ui.alert>
    @endif

    {{-- ===================== Métricas ===================== --}}
    <div class="pr-stats">
        <div class="card card--accent stat">
            <span class="stat-ico blue">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            </span>
            <div>
                <div class="stat-num">{{ $resumen['en_cola'] }}</div>
                <div class="stat-lbl">Esperando turno</div>
            </div>
        </div>

        <div class="card card--accent is-amber stat">
            <span class="stat-ico orange">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>
            </span>
            <div>
                <div class="stat-num">{{ $resumen['en_curso'] }}</div>
                <div class="stat-lbl">En curso ahorita</div>
            </div>
        </div>

        <div class="card card--accent stat" style="--acento:var(--danger);">
            <span class="stat-ico orange">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
            </span>
            <div>
                <div class="stat-num">{{ $resumen['atoradas'] }}</div>
                <div class="stat-lbl">Más de una semana detenidas</div>
            </div>
        </div>

        <div class="card card--accent is-green stat">
            <span class="stat-ico green">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M20 6 9 17l-5-5"/></svg>
            </span>
            <div>
                <div class="stat-num">{{ $resumen['listas'] }}</div>
                <div class="stat-lbl">Salieron y ya son stock</div>
            </div>
        </div>
    </div>

    {{-- ===================== Búsqueda y filtros ===================== --}}
    <div class="f-toolbar">
        <div class="f-search">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            <input type="text" id="fBuscar" placeholder="Buscar por etiqueta, equipo, serie o responsable" autocomplete="off">
        </div>

        <div class="flt" data-flt>
            <button type="button" class="flt-btn" data-flt-toggle aria-expanded="false">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>
                Filtros
                <span class="flt-count" data-flt-count hidden>0</span>
            </button>

            <div class="flt-panel" data-flt-panel hidden>
                <div class="flt-group">
                    <h4>Proceso</h4>
                    @foreach (\App\Models\PiezaProceso::PROCESOS as $clave => $nombre)
                        <label class="flt-opt">
                            <span class="flt-opt-txt">{{ $nombre }}</span>
                            <input type="checkbox" data-f="proceso" value="{{ $clave }}">
                        </label>
                    @endforeach
                </div>

                <div class="flt-group">
                    <h4>Cómo va</h4>
                    <label class="flt-opt">
                        <span class="flt-opt-txt">Esperando turno</span>
                        <input type="checkbox" data-f="avance" value="pendiente">
                    </label>
                    <label class="flt-opt">
                        <span class="flt-opt-txt">En curso</span>
                        <input type="checkbox" data-f="avance" value="en_curso">
                    </label>
                    <label class="flt-opt">
                        <span class="flt-opt-txt">Detenidas más de una semana</span>
                        <input type="checkbox" data-f="pref" value="atorada">
                    </label>
                </div>

                <div class="flt-group">
                    <h4>Condición</h4>
                    <label class="flt-opt">
                        <span class="flt-opt-txt">Equipo nuevo</span>
                        <input type="checkbox" data-f="condicion" value="nuevo">
                    </label>
                    <label class="flt-opt">
                        <span class="flt-opt-txt">Equipo usado</span>
                        <input type="checkbox" data-f="condicion" value="usado">
                    </label>
                </div>

                @if ($responsables->count() > 1)
                    <div class="flt-group">
                        <h4>Responsable</h4>
                        @foreach ($responsables as $quien)
                            <label class="flt-opt">
                                <span class="flt-opt-txt">{{ $quien }}</span>
                                <input type="checkbox" data-f="responsable" value="{{ $quien }}">
                            </label>
                        @endforeach
                    </div>
                @endif

                <div class="flt-group">
                    <h4>Entró al proceso</h4>
                    <div class="flt-fechas">
                        <input type="date" data-f="desde" aria-label="Desde">
                        <input type="date" data-f="hasta" aria-label="Hasta">
                    </div>
                </div>
            </div>
        </div>

        {{-- Accesos rápidos por proceso --}}
        <div class="flt-toggles" role="group" aria-label="Proceso">
            <button type="button" class="flt-tgl" data-f="estado" data-valor="hojalateria" title="Solo hojalatería" aria-pressed="false">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>
            </button>
            <button type="button" class="flt-tgl" data-f="estado" data-valor="mantenimiento" title="Solo mantenimiento" aria-pressed="false">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.6 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.6a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
            </button>
        </div>

        <button type="button" class="flt-btn flt-btn--icon" id="fLimpiar" title="Limpiar todos los filtros" aria-label="Limpiar filtros">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M22 3H2l8 9.46V19l4 2v-8.54"/><line x1="16" y1="5" x2="22" y2="11"/><line x1="22" y1="5" x2="16" y2="11"/></svg>
        </button>
    </div>

    <div class="flt-chips" id="fChips" hidden></div>

    {{-- ===================== Las colas, una por proceso ===================== --}}
    <div class="pr-colas">
        @foreach (\App\Models\PiezaProceso::PROCESOS as $clave => $nombre)
            @php
                $delProceso = $filas->where('paso.proceso', $clave);
                $colorCola = \App\Models\PiezaProceso::COLORES[$clave] ?? 'azul';
            @endphp

            <section class="pr-cola" data-cola="{{ $clave }}">
                <header class="pr-cola-cab">
                    <span class="pr-ico {{ $colorCola }}">
                        @if ($clave === 'hojalateria')
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>
                        @elseif ($clave === 'mantenimiento')
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.6 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.6a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
                        @else
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18l-1.5 13a2 2 0 0 1-2 1.8H6.5a2 2 0 0 1-2-1.8z"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                        @endif
                    </span>
                    <h3>{{ $nombre }}</h3>
                    {{-- El contador lo recalcula el filtro, para que diga lo
                         que de verdad se está viendo. --}}
                    <span class="pr-cola-n" data-cola-n>{{ $delProceso->count() }}</span>
                </header>

                @forelse ($delProceso as $fila)
                    @php
                        $paso = $fila['paso'];
                        $pieza = $fila['pieza'];
                    @endphp

                    <article class="data-card f-row" @foreach ($datos($fila) as $attr => $valor) {{ $attr }}="{{ $valor }}" @endforeach>
                        <div class="data-card-top">
                            <div style="min-width:0; flex:1;">
                                <div class="t" style="font-family:ui-monospace,Consolas,monospace; font-size:13px;">{{ $pieza->codigo }}</div>
                                <div class="s">{{ $fila['equipo'] }}</div>
                            </div>

                            <span class="badge {{ $paso->estado === 'en_curso' ? 'badge--info' : '' }}">
                                {{ $paso->estado === 'en_curso' ? 'En curso' : 'En cola' }}
                            </span>
                        </div>

                        @if ($paso->motivo)
                            <p class="pr-motivo">{{ $paso->motivo }}</p>
                        @endif

                        <dl>
                            <div>
                                <dt>Detenida</dt>
                                <dd class="{{ $fila['dias'] >= 7 ? 'pr-alerta' : '' }}">
                                    {{ $fila['dias'] === 0 ? 'Hoy' : $fila['dias'].' día(s)' }}
                                </dd>
                            </div>
                            <div><dt>Responsable</dt><dd>{{ $fila['responsable'] }}</dd></div>
                            <div>
                                <dt>Al terminar</dt>
                                <dd>
                                    @php $sig = $pieza->procesos->where('estado','pendiente')->where('id','!=',$paso->id); @endphp
                                    {{ $sig->isNotEmpty() ? 'Sigue en '.$sig->map->nombre()->implode(', ') : 'Pasa a stock' }}
                                </dd>
                            </div>
                        </dl>

                        <div class="data-card-foot">
                            <a href="{{ route('inventory.procesos.show', $paso) }}" class="btn">
                                {{ $paso->estado === 'en_curso' ? 'Continuar' : 'Trabajar esta pieza' }}
                            </a>
                        </div>
                    </article>
                @empty
                    <p class="pr-cola-vacia">Nada esperando en {{ mb_strtolower($nombre) }}.</p>
                @endforelse

                {{-- Sale cuando los filtros dejan esta cola sin nada. --}}
                <p class="pr-cola-vacia" data-cola-filtrada hidden>Ninguna coincide con el filtro.</p>
            </section>
        @endforeach
    </div>

    <div class="card" id="fVacio" hidden>
        <div class="empty-state">
            <span class="ico">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            </span>
            <h3>Ninguna pieza coincide</h3>
            <p>Prueba a quitar algún filtro o a cambiar la búsqueda.</p>
            <button type="button" class="btn" data-limpiar-filtros>Limpiar filtros</button>
        </div>
    </div>

    <p class="f-conteo" id="fConteo"></p>

    {{-- ===================== Ya salieron ===================== --}}
    @if ($listas->isNotEmpty())
        <x-ui.card style="margin-top:18px;">
            <x-ui.section-title style="margin:0 0 4px;">Salieron de procesos</x-ui.section-title>
            <p class="campo-nota" style="margin:0 0 12px;">
                Pasaron su verificación y ya cuentan como stock disponible.
            </p>

            @foreach ($listas as $pieza)
                <div class="pr-listo">
                    <span style="font-family:ui-monospace,Consolas,monospace; font-weight:700; font-size:13px;">{{ $pieza->codigo }}</span>
                    <span style="flex:1; min-width:0; color:var(--muted); font-size:13.5px;">
                        {{ trim(collect([$pieza->producto?->tipo_equipo, $pieza->producto?->marca, $pieza->producto?->modelo])->filter()->implode(' ')) ?: 'Equipo' }}
                    </span>
                    <span class="badge badge--ok">Disponible</span>
                </div>
            @endforeach
        </x-ui.card>
    @endif

    <style>
        .pr-stats { display:grid; grid-template-columns:repeat(auto-fit,minmax(210px,1fr)); gap:12px; margin-bottom:16px; }

        /* Una cola por proceso, lado a lado en escritorio y apiladas en el
           teléfono. Se ve de un vistazo dónde está el cuello de botella. */
        .pr-colas { display:grid; grid-template-columns:repeat(auto-fit,minmax(290px,1fr)); gap:16px; align-items:start; }
        .pr-cola { background:var(--surface); border:1px solid var(--border); border-radius:12px; padding:16px; }
        .pr-cola-cab { display:flex; align-items:center; gap:10px; margin-bottom:14px; }
        .pr-cola-cab h3 { flex:1; margin:0; font-size:15px; font-weight:600; }
        .pr-cola-n { padding:2px 9px; border-radius:999px; background:var(--surface-2);
                     border:1px solid var(--border); font-size:12.5px; font-weight:700;
                     font-variant-numeric:tabular-nums; }
        .pr-cola .data-card + .data-card { margin-top:12px; }
        .pr-cola-vacia { margin:0; color:var(--muted); font-size:13.5px; }

        .pr-ico { display:flex; align-items:center; justify-content:center; width:36px; height:36px;
                  border-radius:10px; flex:0 0 36px; background:var(--surface-2); color:var(--muted); }
        .pr-ico svg { width:17px; height:17px; }
        .pr-ico.ambar { background:var(--ambar-soft, #fdf3e3); color:var(--ambar, #b45309); }
        .pr-ico.azul { background:var(--primary-soft); color:var(--primary); }
        .pr-ico.verde { background:var(--green-soft); color:var(--green); }

        .pr-motivo { margin:12px 0 0; padding:9px 11px; border-radius:8px; background:var(--surface-2);
                     color:var(--muted); font-size:12.5px; line-height:1.45; }
        .pr-alerta { color:var(--danger); font-weight:700; }

        .pr-listo { display:flex; align-items:center; gap:10px; padding:10px 0;
                    border-bottom:1px solid var(--border); }
        .pr-listo:last-child { border-bottom:none; }
    </style>

    @include('partials.tabla-filtrable.estilos')

    @include('partials.tabla-filtrable.script', [
        'singular' => 'pieza',
        'plural' => 'piezas',
        'estadoCampo' => 'proceso',
        // Los accesos rápidos son alternativas: limpiar debe mostrar todo.
        'toggleInicial' => null,
        'etiquetas' => [
            'proceso' => 'Proceso',
            'avance' => 'Cómo va',
            'responsable' => 'Responsable',
            'condicion' => 'Condición',
            'atorada' => 'Detenidas +1 semana',
            'estado:hojalateria' => 'Solo hojalatería',
            'estado:mantenimiento' => 'Solo mantenimiento',
        ],
    ])

    @push('scripts')
        <script>
        (function () {
            /*
            | El filtro compartido esconde las tarjetas una por una, pero no
            | sabe que están agrupadas por proceso. Aquí se ajusta lo que le
            | toca a cada cola: su contador, y esconderla entera cuando el
            | filtro no le deja nada.
            |
            | Se engancha a los mismos controles y corre después, en el
            | siguiente tick, para leer el resultado ya aplicado.
            */
            const colas = Array.from(document.querySelectorAll('[data-cola]'));
            if (!colas.length) return;

            function ajustar() {
                colas.forEach(function (cola) {
                    const tarjetas = Array.from(cola.querySelectorAll('.f-row'));
                    const visibles = tarjetas.filter(t => t.style.display !== 'none');

                    const contador = cola.querySelector('[data-cola-n]');
                    if (contador) contador.textContent = visibles.length;

                    // El aviso de "ninguna coincide" solo cuando hay tarjetas
                    // pero el filtro las escondió todas.
                    const aviso = cola.querySelector('[data-cola-filtrada]');
                    if (aviso) aviso.hidden = ! (tarjetas.length > 0 && visibles.length === 0);
                });
            }

            const barra = document.querySelector('.f-toolbar');

            ['input', 'change', 'click'].forEach(function (evento) {
                barra?.addEventListener(evento, () => setTimeout(ajustar, 0));
            });

            document.addEventListener('click', function (e) {
                if (e.target.closest('[data-limpiar-filtros]')) setTimeout(ajustar, 0);
            });

            ajustar();
        })();
        </script>
    @endpush
@endsection
