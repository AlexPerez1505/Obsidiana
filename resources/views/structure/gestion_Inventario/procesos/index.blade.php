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
                'equipo' => trim(collect([$prod?->tipo_equipo, $prod?->subtipo, $prod?->marca, $prod?->modelo])->filter()->implode(' ')) ?: 'Equipo',
                // Para la tarjeta: tipo-subtipo, marca y modelo por separado.
                'tipo' => collect([$prod?->tipo_equipo, $prod?->subtipo])->filter()->implode(' - ') ?: 'Sin tipo',
                'marca_modelo' => collect([$prod?->marca, $prod?->modelo])->filter()->implode(' · ') ?: 'Sin marca ni modelo',
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
            <span class="stat-art" aria-hidden="true"><img src="{{ asset('images/procesos/esperando-turno.png') }}" alt=""></span>
        </div>

        <div class="card card--accent is-amber stat">
            <span class="stat-ico orange">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>
            </span>
            <div>
                <div class="stat-num">{{ $resumen['en_curso'] }}</div>
                <div class="stat-lbl">En curso ahorita</div>
            </div>
            <span class="stat-art" aria-hidden="true"><img src="{{ asset('images/procesos/en-curso.png') }}" alt=""></span>
        </div>

        <div class="card card--accent is-warning stat">
            <span class="stat-ico yellow">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
            </span>
            <div>
                <div class="stat-num">{{ $resumen['atoradas'] }}</div>
                <div class="stat-lbl">Más de una semana detenidas</div>
            </div>
            <span class="stat-art" aria-hidden="true"><img src="{{ asset('images/procesos/detenidas.png') }}" alt=""></span>
        </div>

        <div class="card card--accent is-green stat">
            <span class="stat-ico green">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M20 6 9 17l-5-5"/></svg>
            </span>
            <div>
                <div class="stat-num">{{ $resumen['listas'] }}</div>
                <div class="stat-lbl">Salieron y ya son stock</div>
            </div>
            <span class="stat-art" aria-hidden="true"><img src="{{ asset('images/procesos/stock.png') }}" alt=""></span>
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

                <div class="pr-cola-list">
                @forelse ($delProceso as $fila)
                    @php
                        $paso = $fila['paso'];
                        $pieza = $fila['pieza'];
                    @endphp

                    <article class="data-card f-row" @foreach ($datos($fila) as $attr => $valor) {{ $attr }}="{{ $valor }}" @endforeach>
                        <div class="data-card-top">
                            <div style="min-width:0; flex:1;">
                                {{-- Lo importante es la serie del fabricante; luego tipo-subtipo,
                                     marca y modelo, y la etiqueta interna MB- en letra chica. --}}
                                <div class="t" style="font-family:ui-monospace,Consolas,monospace; font-size:14px;">{{ $pieza->no_serie ?: 'Sin serie' }}</div>
                                <div class="pr-eq-tipo">{{ $fila['tipo'] }}</div>
                                <div class="s">{{ $fila['marca_modelo'] }}</div>
                                <div class="s" style="font-family:ui-monospace,Consolas,monospace;">{{ $pieza->codigo }}</div>
                            </div>

                            <span class="badge {{ $paso->estado === 'en_curso' ? 'badge--info' : '' }}">
                                {{ $paso->estado === 'en_curso' ? 'En curso' : 'En cola' }}
                            </span>
                        </div>

                        @if ($paso->motivo)
                            <p class="pr-motivo"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 5a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V5Z"/><path d="M8 8h8M8 12h8M8 16h5"/></svg>{{ $paso->motivo }}</p>
                        @endif

                        <dl>
                            <div>
                                    <dt><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="4" width="18" height="17" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>Detenida</dt>
                                <dd class="{{ $fila['dias'] >= 7 ? 'pr-alerta' : '' }}">
                                    {{ $fila['dias'] === 0 ? 'Hoy' : $fila['dias'].' día(s)' }}
                                </dd>
                            </div>
                            <div><dt><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="8" r="3.5"/><path d="M4 21c.7-4 3.3-6 8-6s7.3 2 8 6"/></svg>Responsable</dt><dd>{{ $fila['responsable'] }}</dd></div>
                            <div>
                                    <dt><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="m4 7 8-4 8 4-8 4-8-4Z"/><path d="m4 12 8 4 8-4M4 17l8 4 8-4"/></svg>Al terminar</dt>
                                <dd>
                                    @php $sig = $pieza->procesos->where('estado','pendiente')->where('id','!=',$paso->id); @endphp
                                    {{ $sig->isNotEmpty() ? 'Sigue en '.$sig->map->nombre()->implode(', ') : 'Pasa a stock' }}
                                </dd>
                            </div>
                        </dl>

                        <div class="data-card-foot" style="align-items:center;">
                            <span style="font-family:ui-monospace,Consolas,monospace; font-size:11px; color:var(--muted);">{{ $pieza->codigo }}</span>
                            <a href="{{ route('inventory.procesos.show', $paso) }}" class="btn">
                                {{ $paso->estado === 'en_curso' ? 'Continuar' : 'Trabajar esta pieza' }}
                            </a>
                        </div>
                    </article>
                @empty
                    <p class="pr-cola-vacia">
                        <span class="pr-vacia-icon" aria-hidden="true">
                            @if ($clave === 'mantenimiento')
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 1 .33 1.82l-.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 1-1.82-.33h-.09a1.65 1.65 0 0 1-1.51 1H13a2 2 0 1 1-4 0v-.09a1.65 1.65 0 0 1-1-1.51 1.65 1.65 0 0 1-1.82-.33l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 1 .33-1.82v-.09A1.65 1.65 0 0 1 8 11H7a2 2 0 1 1 0-4h.09a1.65 1.65 0 0 1 1.51-1 1.65 1.65 0 0 1 .33-1.82l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06A1.65 1.65 0 0 1 13.58 7H13a2 2 0 1 1 4 0v.09a1.65 1.65 0 0 1 1.51 1 1.65 1.65 0 0 1 1.82.33l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06A1.65 1.65 0 0 1 17.23 13H17a2 2 0 1 1-4 0"/></svg>
                            @elseif ($clave === 'limpieza')
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M9 3h6M10 3v4l-3 4v8a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2v-8l-3-4V3"/><path d="M8 14h8M12 11v6"/></svg>
                            @else
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>
                            @endif
                        </span>
                        <span>Nada esperando en {{ mb_strtolower($nombre) }}.</span>
                    </p>
                @endforelse

                {{-- Sale cuando los filtros dejan esta cola sin nada. --}}
                <p class="pr-cola-vacia" data-cola-filtrada hidden>Ninguna coincide con el filtro.</p>
                </div>
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
        <x-ui.card class="pr-salieron-card" style="margin-top:18px;">
            <x-ui.section-title style="margin:0 0 4px;"><span class="pr-salieron-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="m4 7 8-4 8 4-8 4-8-4Z"/><path d="m4 12 8 4 8-4M4 17l8 4 8-4"/></svg></span>Salieron de procesos</x-ui.section-title>
            <p class="campo-nota" style="margin:0 0 12px;">
                Pasaron su verificación y ya cuentan como stock disponible.
            </p>

            {{-- En rejilla: una lista de una sola columna dejaba media
                 pantalla vacía y alargaba la página. --}}
            <div class="pr-listos">
            @foreach ($listas as $pieza)
                <div class="pr-listo">
                    <span style="font-family:ui-monospace,Consolas,monospace; font-weight:700; font-size:13px;">{{ $pieza->no_serie ?: $pieza->codigo }}
                        <span style="font-weight:400; font-size:11px; color:var(--muted);">{{ $pieza->codigo }}</span></span>
                    <span style="flex:1; min-width:0; color:var(--muted); font-size:13.5px;">
                        {{ trim(collect([$pieza->producto?->tipo_equipo, $pieza->producto?->marca, $pieza->producto?->modelo])->filter()->implode(' ')) ?: 'Equipo' }}
                    </span>
                    <span class="badge badge--ok">Disponible</span>
                </div>
            @endforeach
            </div>
        </x-ui.card>
    @endif

    <style>
        /* Métricas en tiras bajas: dicen un número, no necesitan altura. */
        .pr-stats { display:grid; grid-template-columns:repeat(auto-fit,minmax(200px,1fr)); gap:12px; margin-bottom:16px; }
        .pr-stats .stat { padding:13px 15px; gap:12px; }
        .pr-stats .stat-ico { width:38px; height:38px; }
        .pr-stats .stat-num { font-size:23px; }

        /* Una cola por proceso, lado a lado en escritorio y apiladas en el
           teléfono. Se ve de un vistazo dónde está el cuello de botella. */
        .pr-colas { display:grid; grid-template-columns:repeat(auto-fit,minmax(290px,1fr)); gap:16px; align-items:start; }
        .pr-cola { background:var(--surface); border:1px solid var(--border); border-radius:12px; padding:14px; min-width:0; }
        .pr-cola-cab { display:flex; align-items:center; gap:10px; margin-bottom:12px; }
        .pr-cola-cab h3 { flex:1; margin:0; font-size:15px; font-weight:600; }
        .pr-cola-n { padding:2px 9px; border-radius:999px; background:var(--surface-2);
                     border:1px solid var(--border); font-size:12.5px; font-weight:700;
                     font-variant-numeric:tabular-nums; }
        .pr-cola .data-card + .data-card { margin-top:10px; }
        .pr-eq-tipo { font-size:13.5px; font-weight:600; margin-top:3px; overflow-wrap:anywhere; }
        .pr-eq-serie { font-family:ui-monospace,Consolas,monospace; font-weight:600; color:var(--text); overflow-wrap:anywhere; }

        /* Tarjeta de pieza más compacta dentro de la cola: los datos en dos
           columnas (detenida | responsable) y "al terminar" abajo a lo
           ancho, en lugar de tres renglones con etiqueta a un lado. */
        .pr-cola .data-card { padding:13px 14px; }
        .pr-cola .data-card-top { padding-bottom:10px; }
        .pr-cola .data-card dl { margin-top:10px; grid-template-columns:repeat(2, minmax(0, 1fr)); gap:8px 14px; }
        .pr-cola .data-card dl > div { display:block; min-width:0; }
        .pr-cola .data-card dl > div:last-child { grid-column:1 / -1; }
        .pr-cola .data-card dt { font-size:12px; }
        .pr-cola .data-card dd { margin-top:1px; text-align:left; font-weight:600; }
        .pr-cola .data-card-foot { margin-top:10px; padding-top:10px; }
        .pr-cola-vacia { margin:0; color:var(--muted); font-size:13.5px; }

        .pr-ico { display:flex; align-items:center; justify-content:center; width:36px; height:36px;
                  border-radius:10px; flex:0 0 36px; background:var(--surface-2); color:var(--muted); }
        .pr-ico svg { width:17px; height:17px; }
        .pr-ico.ambar { background:var(--ambar-soft, #fdf3e3); color:var(--ambar, #b45309); }
        .pr-ico.azul { background:var(--primary-soft); color:var(--primary); }
        .pr-ico.verde { background:var(--green-soft); color:var(--green); }

        .pr-motivo { margin:10px 0 0; padding:8px 11px; border-radius:8px; background:var(--surface-2);
                     color:var(--muted); font-size:12.5px; line-height:1.45; }
        .pr-alerta { color:var(--danger); font-weight:700; }

        .pr-listos { display:grid; grid-template-columns:repeat(auto-fill, minmax(min(100%, 300px), 1fr)); gap:8px; }
        .pr-listo { display:flex; align-items:center; gap:10px; padding:8px 11px; min-width:0;
                    border:1px solid var(--border); border-radius:9px; }
    </style>

    <style>
        /* Rediseño visual de Procesos: conserva datos, filtros y acciones. */
        .pr-stats { grid-template-columns:repeat(4,minmax(0,1fr)); gap:10px; margin-bottom:10px; }
        .pr-stats .stat { position:relative; min-height:72px; overflow:hidden; display:flex; align-items:center; gap:11px; padding:11px 13px; border:1px solid #24558c; border-top:2px solid #1683f7; border-radius:9px; background:linear-gradient(135deg,#102d56 0%,#102c50 58%,#0a2140 100%); box-shadow:0 8px 18px rgba(0,0,0,.16); }
        .pr-stats .stat::after { content:''; position:absolute; right:-35px; bottom:-48px; width:150px; height:100px; border:1px solid rgba(34,139,245,.35); border-radius:50%; transform:rotate(-22deg); pointer-events:none; }
        .pr-stats .stat.is-amber { border-top-color:#f59e0b; border-color:#76501e; background:linear-gradient(135deg,#342d29 0%,#3f3022 58%,#282430 100%); }
        .pr-stats .stat.is-amber::after { border-color:rgba(245,158,11,.4); }
        .pr-stats .stat.is-green { border-top-color:#22d67b; border-color:#176a61; background:linear-gradient(135deg,#0e393c 0%,#0e403c 58%,#0b2e35 100%); }
        .pr-stats .stat.is-green::after { border-color:rgba(34,214,123,.36); }
        .pr-stats .stat > * { position:relative; z-index:1; }
        .pr-stats .stat-ico { width:36px; height:36px; flex:0 0 36px; border-radius:9px; }
        .pr-stats .stat-ico svg { width:19px; height:19px; }
        .pr-stats .stat-num { font-size:21px; line-height:1; font-weight:800; color:#f5f8ff; }
        .pr-stats .stat-lbl { margin-top:4px; color:#a9c2e3; font-size:10.5px; line-height:1.15; }
        .f-toolbar { gap:8px; margin-bottom:9px; }
        .f-toolbar .f-search input { min-height:36px; border-radius:8px; background:#0e2745; border-color:#28588d; }
        .f-toolbar .flt-btn, .f-toolbar .flt-tgl { min-height:36px; height:36px; border-radius:8px; background:#0e2745; border-color:#28588d; }
        .f-toolbar .flt-btn--icon { width:36px; }
        .pr-colas { grid-template-columns:repeat(3,minmax(0,1fr)); gap:10px; align-items:stretch; }
        .pr-cola { min-width:0; min-height:315px; padding:8px; border:1px solid #24558c; border-radius:9px; background:linear-gradient(180deg,#0d2547 0%,#091a32 100%); box-shadow:0 8px 18px rgba(0,0,0,.13); }
        .pr-cola-cab { margin:-8px -8px 8px; padding:7px 9px; border-bottom:1px solid rgba(58,103,157,.45); background:linear-gradient(180deg,#123b6d,#102b50); }
        .pr-cola-cab h3 { font-size:13px; font-weight:800; color:#eef6ff; }
        .pr-cola-n { padding:1px 7px; border-color:#315779; background:#0b203a; color:#c3d7ec; font-size:10px; }
        .pr-ico { width:28px; height:28px; flex-basis:28px; border-radius:8px; }
        .pr-ico svg { width:15px; height:15px; }
        .pr-cola .data-card { padding:8px 9px; border:1px solid #245787; border-radius:8px; background:linear-gradient(145deg,#102f59,#0b2343); box-shadow:inset 0 1px rgba(255,255,255,.03); }
        .pr-cola .data-card + .data-card { margin-top:6px; }
        .pr-cola .data-card:hover { border-color:#1683f7; background:linear-gradient(145deg,#123d70,#0d2b50); }
        .pr-cola .data-card-top { padding-bottom:6px; }
        .pr-cola .data-card .t { font-size:10px !important; line-height:1.1; }
        .pr-cola .data-card .s, .pr-eq-tipo { font-size:9px; line-height:1.1; }
        .pr-cola .data-card .badge { padding:2px 6px; font-size:9px; }
        .pr-cola .data-card dl { margin-top:7px; gap:5px 8px; }
        .pr-cola .data-card dt { font-size:8px; }
        .pr-cola .data-card dd { font-size:9px; }
        .pr-motivo { margin:6px 0 0; padding:5px 7px; font-size:9px; line-height:1.25; }
        .pr-cola .data-card-foot { margin-top:6px; padding-top:6px; }
        .pr-cola .data-card-foot span { font-size:8px !important; }
        .pr-cola .data-card-foot .btn { min-height:25px; padding:0 9px; border-radius:6px; font-size:9px; }
        .pr-cola-vacia { display:flex; min-height:250px; align-items:center; justify-content:center; padding:25px 12px; color:#a9c2e3; font-size:11px; text-align:center; background:radial-gradient(circle at 50% 78%,rgba(0,126,255,.15),transparent 43%); }
        .f-conteo { margin-top:10px; color:#a9c2e3; font-size:12px; }
        [data-theme="light"] .pr-stats .stat { color:#fff; box-shadow:0 8px 18px rgba(27,55,90,.12); }
        [data-theme="light"] .f-toolbar .f-search input,
        [data-theme="light"] .f-toolbar .flt-btn,
        [data-theme="light"] .f-toolbar .flt-tgl { background:#fff; border-color:#b8cde2; color:#315574; }
        [data-theme="light"] .pr-cola { border-color:#b8cde2; background:linear-gradient(180deg,#f1f7fc,#e8f1f9); }
        [data-theme="light"] .pr-cola-cab { border-bottom-color:#c7d9eb; background:linear-gradient(180deg,#e5f1fc,#d8e9f8); }
        [data-theme="light"] .pr-cola-cab h3 { color:#244765; }
        [data-theme="light"] .pr-cola-n { border-color:#b8cde2; background:#f8fbff; color:#55738e; }
        [data-theme="light"] .pr-cola .data-card { border-color:#b8cde2; background:#fff; box-shadow:0 3px 9px rgba(33,72,111,.08); }
        [data-theme="light"] .pr-cola .data-card:hover { border-color:#70b4e8; background:#edf7ff; }
        [data-theme="light"] .pr-cola-vacia { color:#66819c; background:radial-gradient(circle at 50% 78%,rgba(22,131,247,.09),transparent 43%); }
        [data-theme="light"] .f-conteo { color:#66819c; }
        @media (max-width:1050px) { .pr-stats { grid-template-columns:repeat(2,minmax(0,1fr)); } .pr-colas { grid-template-columns:repeat(2,minmax(0,1fr)); } }
        @media (max-width:700px) { .pr-stats, .pr-colas { grid-template-columns:1fr; } .pr-cola { min-height:0; } .pr-cola-vacia { min-height:130px; } }
    </style>

    <style>
        /* Bloque compacto de piezas que ya salieron de procesos. */
        .pr-salieron-card { position:relative; overflow:hidden; padding:8px; border:1px solid #24558c; border-radius:9px; background:linear-gradient(180deg,#0d2547 0%,#091a32 100%); box-shadow:0 8px 18px rgba(0,0,0,.16); }
        .pr-salieron-card::before { content:''; position:absolute; inset:0 0 auto; height:30px; background:linear-gradient(100deg,rgba(22,131,247,.2),transparent 70%); pointer-events:none; }
        .pr-salieron-card > * { position:relative; z-index:1; }
        .pr-salieron-card .section-title { display:flex; align-items:center; gap:7px; color:#f1f6ff; font-size:12px; font-weight:800; }
        .pr-salieron-icon { display:inline-flex; align-items:center; justify-content:center; width:24px; height:24px; border:1px solid #1683f7; border-radius:7px; background:#123d70; color:#72bfff; }
        .pr-salieron-icon svg { width:14px; height:14px; }
        .pr-salieron-card > .campo-nota { margin:0 0 7px 31px !important; color:#9db9d8; font-size:9px; }
        .pr-salieron-card .pr-listos { grid-template-columns:repeat(5,minmax(0,1fr)); gap:5px; }
        .pr-salieron-card .pr-listo { position:relative; display:block; min-width:0; min-height:45px; padding:7px 8px; overflow:hidden; border:1px solid #214568; border-radius:6px; background:linear-gradient(145deg,#102b4d,#0b203b); }
        .pr-salieron-card .pr-listo > span:first-child { display:block; padding-right:58px; color:#eef6ff; font-family:ui-monospace,Consolas,monospace; font-size:9px !important; line-height:1.1; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
        .pr-salieron-card .pr-listo > span:first-child > span { display:block; margin-top:2px; color:#7f9bb9 !important; font-size:7px !important; line-height:1.1; }
        .pr-salieron-card .pr-listo > span:nth-child(2) { display:block; margin-top:4px; padding-right:2px; color:#9db9d8 !important; font-size:7.5px !important; line-height:1.15; white-space:normal; overflow:hidden; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; }
        .pr-salieron-card .pr-listo > .badge { position:absolute; top:7px; right:7px; padding:2px 5px; border-radius:999px; font-size:7px; line-height:1; }
        [data-theme="light"] .pr-salieron-card { border-color:#b8cde2; background:linear-gradient(180deg,#f1f7fc,#e8f1f9); box-shadow:0 8px 18px rgba(27,55,90,.1); }
        [data-theme="light"] .pr-salieron-card::before { background:linear-gradient(100deg,rgba(22,131,247,.1),transparent 70%); }
        [data-theme="light"] .pr-salieron-card .section-title { color:#244765; }
        [data-theme="light"] .pr-salieron-icon { border-color:#70b4e8; background:#e0f0fc; color:#0879df; }
        [data-theme="light"] .pr-salieron-card > .campo-nota { color:#66819c; }
        [data-theme="light"] .pr-salieron-card .pr-listo { border-color:#b8cde2; background:#fff; }
        [data-theme="light"] .pr-salieron-card .pr-listo > span:first-child { color:#244765; }
        [data-theme="light"] .pr-salieron-card .pr-listo > span:first-child > span,
        [data-theme="light"] .pr-salieron-card .pr-listo > span:nth-child(2) { color:#66819c !important; }
        @media (max-width:1050px) { .pr-salieron-card .pr-listos { grid-template-columns:repeat(4,minmax(0,1fr)); } }
        @media (max-width:760px) { .pr-salieron-card .pr-listos { grid-template-columns:repeat(2,minmax(0,1fr)); } }
        @media (max-width:430px) { .pr-salieron-card .pr-listos { grid-template-columns:1fr; } }
    </style>

    <style>
        /* Ajuste de lectura e iconografia para las tarjetas de proceso. */
        .pr-cola { display:flex; flex-direction:column; min-height:0; }
        .pr-cola-list { height:600px; min-height:0; overflow-y:auto; overflow-x:hidden; padding:1px 5px 4px 1px; scrollbar-width:thin; scrollbar-color:#3978b7 rgba(6,24,48,.55); }
        .pr-cola-list::-webkit-scrollbar { width:8px; }
        .pr-cola-list::-webkit-scrollbar-track { border-radius:8px; background:rgba(6,24,48,.55); }
        .pr-cola-list::-webkit-scrollbar-thumb { border:2px solid transparent; border-radius:8px; background:#3978b7; background-clip:padding-box; }
        .pr-cola-list::-webkit-scrollbar-thumb:hover { background:#1683f7; background-clip:padding-box; }
        .pr-cola .data-card { padding:10px 11px; }
        .pr-cola .data-card-top { padding-bottom:8px; }
        .pr-cola .data-card .t { font-size:12px !important; line-height:1.15; }
        .pr-cola .data-card .s, .pr-eq-tipo { font-size:10px; line-height:1.2; }
        .pr-cola .data-card .badge { padding:3px 7px; font-size:10px; }
        .pr-cola .data-card dl { grid-template-columns:repeat(3,minmax(0,1fr)); gap:7px 10px; margin-top:8px; }
        .pr-cola .data-card dl > div:last-child { grid-column:auto; }
        .pr-cola .data-card dt { display:flex; align-items:center; gap:4px; font-size:9px; }
        .pr-cola .data-card dt svg { width:13px; height:13px; flex:0 0 13px; color:#8db4d9; }
        .pr-cola .data-card dd { font-size:10px; line-height:1.15; }
        .pr-motivo { display:flex; align-items:center; gap:6px; margin:7px 0 0; padding:6px 8px; font-size:10px; line-height:1.3; }
        .pr-motivo svg { width:13px; height:13px; flex:0 0 13px; color:#9fc2e5; }
        .pr-cola .data-card-foot { margin-top:8px; padding-top:7px; }
        .pr-cola .data-card-foot span { font-size:9px !important; }
        .pr-cola .data-card-foot .btn { min-height:29px; padding:0 10px; border-radius:7px; font-size:10px; }
        [data-theme="light"] .pr-cola-list { scrollbar-color:#8db4d9 #eaf2f9; }
        [data-theme="light"] .pr-cola-list::-webkit-scrollbar-track { background:#eaf2f9; }
        [data-theme="light"] .pr-cola-list::-webkit-scrollbar-thumb { background:#8db4d9; background-clip:padding-box; }
        @media (max-width:850px) { .pr-cola .data-card dl { grid-template-columns:repeat(2,minmax(0,1fr)); } .pr-cola .data-card dl > div:last-child { grid-column:1 / -1; } }
        @media (max-width:700px) { .pr-cola-list { height:auto; max-height:none; overflow:visible; padding-right:1px; } }
        @media (max-width:430px) { .pr-cola .data-card dl { grid-template-columns:1fr; } .pr-cola .data-card dl > div:last-child { grid-column:auto; } }
    </style>

    <style>
        /* Tarjetas de resumen: icono protagonista, jerarquia clara y arte decorativo. */
        .pr-stats .stat { min-height:92px; padding:14px 20px; gap:14px; border-top-width:3px; border-radius:12px; }
        .pr-stats .stat > div { position:relative; z-index:1; padding-left:14px; border-left:1px solid rgba(151,194,235,.42); }
        .pr-stats .stat-ico { width:58px; height:58px; flex-basis:58px; border-radius:13px; }
        .pr-stats .stat-ico svg { width:31px; height:31px; }
        .pr-stats .stat-num { font-size:30px; line-height:1; letter-spacing:.01em; }
        .pr-stats .stat-lbl { margin-top:6px; font-size:14px; line-height:1.15; color:#c2d7ef; }
        .pr-stats .stat-art { position:absolute !important; right:7px; bottom:-5px; z-index:0 !important; width:122px; height:88px; color:#48a5ed; opacity:.5; pointer-events:none; }
        .pr-stats .stat-art img { display:block; width:100%; height:100%; object-fit:contain; mix-blend-mode:screen; opacity:.68; }
        .pr-stats .stat::after { display:none; }
        .pr-stats .stat-art svg { width:100%; height:100%; stroke:currentColor; stroke-width:1.8; stroke-linecap:round; stroke-linejoin:round; filter:drop-shadow(0 0 3px currentColor); }
        .pr-stats .stat.is-amber .stat-art { color:#f3a43b; }
        .pr-stats .stat-ico.yellow { background:rgba(250,204,21,.16); color:#facc15; }
        .pr-stats .stat.is-warning { border-color:#d1a914 !important; border-top-color:#facc15 !important; background:linear-gradient(135deg,#3d3c24 0%,#4a4020 58%,#302e27 100%); box-shadow:0 8px 18px rgba(118,91,13,.2); }
        .pr-stats .stat.is-warning::after { display:none; }
        .pr-stats .stat.is-warning .stat-art { color:#facc15; opacity:.5; }
        .pr-stats .stat.is-warning .stat-art { right:-16px; bottom:-9px; width:126px; height:92px; }
        .pr-stats .stat.is-warning .stat-lbl { color:#f5e7a4; }
        .pr-stats .stat.is-green .stat-art { color:#23d879; }
        .pr-stats .stat { border:1px solid #1683f7; border-top:5px solid #1683f7; }
        .pr-stats .stat { border-color:#20508a; background:linear-gradient(135deg,#111b2e 0%,#12345c 100%); box-shadow:0 8px 18px rgba(2,20,52,.22); }
        .pr-stats .stat.is-amber { border-color:#20508a; border-top-color:#f59e0b; background:linear-gradient(135deg,#111b2e 0%,#3b2b1f 100%); }
        .pr-stats .stat.is-warning { border-color:#20508a !important; border-top-color:#facc15 !important; background:linear-gradient(135deg,#111b2e 0%,#3b3820 100%); }
        .pr-stats .stat.is-green { border-color:#20508a; border-top-color:#30d158; background:linear-gradient(135deg,#111b2e 0%,#123d3d 100%); }
        .pr-cola[data-cola="hojalateria"] { border-top-color:#24558c; }
        .pr-cola[data-cola="hojalateria"] .pr-ico.ambar { background:rgba(250,204,21,.16); color:#facc15; }
        .pr-cola[data-cola="hojalateria"] .pr-cola-cab { border-bottom-color:rgba(250,204,21,.35); }
        [data-theme="light"] .pr-stats .stat > div { border-left-color:rgba(255,255,255,.5); }
        [data-theme="light"] .pr-stats .stat-lbl { color:#d5e7fa; }
        @media (max-width:1050px) { .pr-stats .stat { min-height:88px; } .pr-stats .stat-lbl { font-size:13px; } }
        [data-theme="light"] .pr-stats .stat-art { opacity:.4; }
        @media (max-width:700px) { .pr-stats .stat { min-height:86px; padding:13px 16px; } .pr-stats .stat-art { right:2px; width:105px; height:78px; } }
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
