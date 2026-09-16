@extends('layouts.dashboard')

@section('title', 'Entrada / Salida')
@section('page-title', 'Entrada / Salida')
@section('page-sub', 'Lo que llega con su evidencia; las salidas las genera Ventas')

@section('content')
    @php
        // Se recorre una sola vez: los mismos datos alimentan la tabla y las tarjetas.
        $filas = collect($movements->items())->map(function ($m) {
            $tipo = $m->movement_type;

            return [
                'modelo' => $m,
                'tipo' => $tipo,
                'tipoLabel' => ucfirst($tipo),
                'condicion' => $m->condicion ?: 'nuevo',
                'almacen' => $m->warehouse ?: 'Sin almacén',
                'quien' => $m->creator?->name ?: 'Sin registrar',
                'fecha' => $m->movement_date?->format('Y-m-d') ?? '',
                'fechaVista' => $m->movement_date?->format('d/m/Y') ?? '—',
                'nombre' => $m->item_name ?: 'Sin nombre',
            ];
        });

        $almacenes = $filas->pluck('almacen')->filter()->unique()->sort()->values();
        $quienes = $filas->pluck('quien')->filter()->unique()->sort()->values();

        // Cómo se pinta cada tipo de movimiento.
        $tono = ['entrada' => 'badge--ok', 'salida' => 'badge--danger', 'transferencia' => 'badge--info'];

        $datos = function (array $fila) {
            return [
                'data-buscar' => mb_strtolower($fila['modelo']->folio.' '.$fila['nombre'].' '.$fila['almacen'].' '.$fila['quien']),
                'data-tipo' => $fila['tipo'],
                'data-almacen' => $fila['almacen'],
                'data-condicion' => $fila['condicion'],
                'data-quien' => $fila['quien'],
                'data-fecha' => $fila['fecha'],
            ];
        };
    @endphp

    <div class="content-actions">
        <a href="{{ route('inventory.movimientos.create') }}" class="btn">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="15" height="15"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Nueva entrada
        </a>
    </div>

    {{-- ===================== Métricas ===================== --}}
    <div class="mv-stats">
        <div class="card card--accent stat">
            <span class="stat-ico blue">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><path d="m3.3 7 8.7 5 8.7-5M12 22V12"/></svg>
            </span>
            <div>
                <div class="stat-num">{{ $resumen['movimientos'] }}</div>
                <div class="stat-lbl">Movimientos registrados</div>
            </div>
        </div>

        <div class="card card--accent is-green stat">
            <span class="stat-ico green">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 5v14"/><path d="m19 12-7 7-7-7"/></svg>
            </span>
            <div>
                <div class="stat-num">{{ $resumen['entradas_mes'] }}</div>
                <div class="stat-lbl">Entradas este mes</div>
            </div>
        </div>

        <div class="card card--accent stat">
            <span class="stat-ico blue">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/></svg>
            </span>
            <div>
                <div class="stat-num">{{ $resumen['piezas'] }}</div>
                <div class="stat-lbl">Piezas en inventario</div>
            </div>
        </div>

        <div class="card card--accent is-amber stat">
            <span class="stat-ico orange">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            </span>
            <div>
                <div class="stat-num">{{ $resumen['en_proceso'] }}</div>
                <div class="stat-lbl">
                    En proceso, sin poder venderse
                    @if ($resumen['en_proceso'] > 0)
                        · <a href="{{ route('inventory.procesos.index') }}" class="link">ver</a>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- ===================== Búsqueda y filtros ===================== --}}
    <div class="f-toolbar">
        <div class="f-search">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            <input type="text" id="fBuscar" placeholder="Buscar por folio, equipo, almacén o quien registró" autocomplete="off">
        </div>

        <div class="flt" data-flt>
            <button type="button" class="flt-btn" data-flt-toggle aria-expanded="false">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>
                Filtros
                <span class="flt-count" data-flt-count hidden>0</span>
            </button>

            <div class="flt-panel" data-flt-panel hidden>
                <div class="flt-group">
                    <h4>Tipo de movimiento</h4>
                    @foreach (['entrada' => 'Entradas', 'salida' => 'Salidas', 'transferencia' => 'Transferencias'] as $valor => $texto)
                        <label class="flt-opt">
                            <span class="flt-opt-txt">{{ $texto }}</span>
                            <input type="checkbox" data-f="tipo" value="{{ $valor }}">
                        </label>
                    @endforeach
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

                @if ($almacenes->count() > 1)
                    <div class="flt-group">
                        <h4>Almacén</h4>
                        @foreach ($almacenes as $almacen)
                            <label class="flt-opt">
                                <span class="flt-opt-txt">{{ $almacen }}</span>
                                <input type="checkbox" data-f="almacen" value="{{ $almacen }}">
                            </label>
                        @endforeach
                    </div>
                @endif

                @if ($quienes->count() > 1)
                    <div class="flt-group">
                        <h4>Registró</h4>
                        @foreach ($quienes as $quien)
                            <label class="flt-opt">
                                <span class="flt-opt-txt">{{ $quien }}</span>
                                <input type="checkbox" data-f="quien" value="{{ $quien }}">
                            </label>
                        @endforeach
                    </div>
                @endif

                <div class="flt-group">
                    <h4>Fecha del movimiento</h4>
                    <div class="flt-fechas">
                        <input type="date" data-f="desde" aria-label="Movimiento desde">
                        <input type="date" data-f="hasta" aria-label="Movimiento hasta">
                    </div>
                </div>
            </div>
        </div>

        {{-- Accesos rápidos: lo que entra / lo que sale --}}
        <div class="flt-toggles" role="group" aria-label="Tipo de movimiento">
            <button type="button" class="flt-tgl" data-f="estado" data-valor="entrada" title="Ver solo entradas" aria-pressed="false">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 5v14"/><path d="m19 12-7 7-7-7"/></svg>
            </button>
            <button type="button" class="flt-tgl" data-f="estado" data-valor="salida" title="Ver solo salidas" aria-pressed="false">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 19V5"/><path d="m5 12 7-7 7 7"/></svg>
            </button>
        </div>

        <button type="button" class="flt-btn flt-btn--icon" id="fLimpiar" title="Limpiar todos los filtros" aria-label="Limpiar filtros">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M22 3H2l8 9.46V19l4 2v-8.54"/><line x1="16" y1="5" x2="22" y2="11"/><line x1="22" y1="5" x2="16" y2="11"/></svg>
        </button>

        <x-ui.view-switch key="movimientos" />
    </div>

    <div class="flt-chips" id="fChips" hidden></div>

    {{-- ===================== Vista lista ===================== --}}
    <div class="card" data-view-list style="overflow-x:auto; padding:0;">
        <table class="mv-table">
            <thead>
                <tr>
                    <th>Movimiento</th>
                    <th>Equipo</th>
                    <th>Cantidad</th>
                    <th>Almacén</th>
                    <th>Registró</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($filas as $fila)
                    @php $m = $fila['modelo']; @endphp

                    <tr class="f-row" @foreach ($datos($fila) as $attr => $valor) {{ $attr }}="{{ $valor }}" @endforeach>
                        <td>
                            <div class="cell-id">
                                <span class="mv-ico {{ $fila['tipo'] }}">
                                    @if ($fila['tipo'] === 'entrada')
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14"/><path d="m19 12-7 7-7-7"/></svg>
                                    @elseif ($fila['tipo'] === 'salida')
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 19V5"/><path d="m5 12 7-7 7 7"/></svg>
                                    @else
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 3h5v5"/><path d="M8 21H3v-5"/><path d="M21 3 3 21"/></svg>
                                    @endif
                                </span>
                                <div style="min-width:0;">
                                    <div class="t">{{ $m->folio }}</div>
                                    <div class="s">{{ $fila['fechaVista'] }}</div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div class="t">{{ $fila['nombre'] }}</div>
                            <div class="s">{{ ucfirst($m->item_type) }} · {{ ucfirst($fila['condicion']) }}</div>
                        </td>
                        <td style="white-space:nowrap;">{{ $m->quantity }} {{ $m->unit }}</td>
                        <td>{{ $fila['almacen'] }}</td>
                        <td>{{ $fila['quien'] }}</td>
                        <td style="text-align:right; white-space:nowrap;">
                            @include('structure.gestion_Inventario.entrada_salida._acciones', ['movimiento' => $m])
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6">
                            <div class="empty-state">
                                <span class="ico">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><path d="m3.3 7 8.7 5 8.7-5M12 22V12"/></svg>
                                </span>
                                <h3>Todavía no hay movimientos</h3>
                                <p>Registra la primera entrada y aparecerá aquí con su evidencia.</p>
                                <a href="{{ route('inventory.movimientos.create') }}" class="btn">Nueva entrada</a>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- ===================== Vista tarjetas ===================== --}}
    <div class="data-cards" data-view-cards style="display:none;">
        @forelse ($filas as $fila)
            @php $m = $fila['modelo']; @endphp

            <article class="data-card f-row" @foreach ($datos($fila) as $attr => $valor) {{ $attr }}="{{ $valor }}" @endforeach>
                <div class="data-card-top">
                    <span class="mv-ico {{ $fila['tipo'] }}">
                        @if ($fila['tipo'] === 'entrada')
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14"/><path d="m19 12-7 7-7-7"/></svg>
                        @elseif ($fila['tipo'] === 'salida')
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 19V5"/><path d="m5 12 7-7 7 7"/></svg>
                        @else
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 3h5v5"/><path d="M8 21H3v-5"/><path d="M21 3 3 21"/></svg>
                        @endif
                    </span>
                    <div style="min-width:0; flex:1;">
                        <div class="t">{{ $m->folio }}</div>
                        <div class="s">{{ $fila['fechaVista'] }}</div>
                    </div>
                    <span class="badge {{ $tono[$fila['tipo']] ?? '' }}">{{ $fila['tipoLabel'] }}</span>
                </div>

                <dl>
                    <div><dt>Equipo</dt><dd>{{ $fila['nombre'] }}</dd></div>
                    <div><dt>Cantidad</dt><dd>{{ $m->quantity }} {{ $m->unit }}</dd></div>
                    <div><dt>Condición</dt><dd>{{ ucfirst($fila['condicion']) }}</dd></div>
                    <div><dt>Almacén</dt><dd>{{ $fila['almacen'] }}</dd></div>
                    <div><dt>Registró</dt><dd>{{ $fila['quien'] }}</dd></div>
                </dl>

                <div class="data-card-foot">
                    @include('structure.gestion_Inventario.entrada_salida._acciones', ['movimiento' => $m])
                </div>
            </article>
        @empty
            <div class="card">
                <div class="empty-state">
                    <span class="ico">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><path d="m3.3 7 8.7 5 8.7-5M12 22V12"/></svg>
                    </span>
                    <h3>Todavía no hay movimientos</h3>
                    <p>Registra la primera entrada y aparecerá aquí con su evidencia.</p>
                    <a href="{{ route('inventory.movimientos.create') }}" class="btn">Nueva entrada</a>
                </div>
            </div>
        @endforelse
    </div>

    {{-- Aviso cuando los filtros no dejan nada visible --}}
    <div class="card" id="fVacio" hidden>
        <div class="empty-state">
            <span class="ico">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            </span>
            <h3>Ningún movimiento coincide</h3>
            <p>Prueba a quitar algún filtro o a cambiar la búsqueda.</p>
            <button type="button" class="btn" data-limpiar-filtros>Limpiar filtros</button>
        </div>
    </div>

    <p class="f-conteo" id="fConteo"></p>

    @include('partials._paginacion', ['paginator' => $movements])

    {{-- ===================== Eliminar ===================== --}}
    <dialog id="modalEliminar" class="mv-modal">
        <form method="POST" action="" id="formEliminar">
            @csrf
            @method('DELETE')

            <div class="mv-modal-ico">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
            </div>

            <h3>Eliminar movimiento</h3>
            <p class="campo-nota">
                Se va a eliminar <b data-folio-eliminar></b> y las piezas que dio de alta.
                Escribe tu PIN para confirmarlo.
            </p>

            <x-ui.form-group label="PIN" for="pinEliminar">
                <input id="pinEliminar" name="password" type="password" inputmode="numeric" required
                       placeholder="Tu PIN de aprobación" autocomplete="off">
            </x-ui.form-group>

            @error('password')<p class="err">{{ $message }}</p>@enderror

            <div class="mv-modal-pie">
                <button type="button" class="btn btn--ghost" data-cerrar-modal>Cancelar</button>
                <button type="submit" class="btn btn--danger">Eliminar</button>
            </div>
        </form>
    </dialog>

    <style>
        /* Solo lo propio de esta pantalla: lo demás vive en los partials. */
        .mv-stats { display:grid; grid-template-columns:repeat(auto-fit,minmax(210px,1fr)); gap:12px; margin-bottom:16px; }
        .mv-table { width:100%; border-collapse:collapse; }

        .mv-ico { display:flex; align-items:center; justify-content:center; width:36px; height:36px;
                  border-radius:10px; flex:0 0 36px; background:var(--surface-2); color:var(--muted); }
        .mv-ico svg { width:17px; height:17px; }
        .mv-ico.entrada { background:var(--green-soft); color:var(--green); }
        .mv-ico.salida { background:var(--danger-soft); color:var(--danger); }
        .mv-ico.transferencia { background:var(--primary-soft); color:var(--primary); }

        .mv-modal { width:min(420px, calc(100vw - 32px)); padding:24px; border:1px solid var(--border);
                    border-radius:16px; background:var(--surface); color:var(--text); }
        .mv-modal::backdrop { background:rgba(15,23,42,.45); }
        .mv-modal h3 { margin:0 0 6px; font-size:17px; text-align:center; }
        .mv-modal .campo-nota { margin:0 0 4px; text-align:center; }
        .mv-modal-ico { width:52px; height:52px; margin:0 auto 12px; border-radius:50%;
                        display:flex; align-items:center; justify-content:center;
                        background:var(--danger-soft); color:var(--danger); }
        .mv-modal-ico svg { width:24px; height:24px; }
        .mv-modal-pie { display:flex; justify-content:flex-end; gap:10px; margin-top:18px; }
        @media (max-width:520px) { .mv-modal-pie .btn { flex:1; justify-content:center; } }
    </style>

    @include('partials.tabla-filtrable.estilos')

    @include('partials.tabla-filtrable.script', [
        'singular' => 'movimiento',
        'plural' => 'movimientos',
        'estadoCampo' => 'tipo',
        // Entradas y salidas son alternativas: limpiar debe mostrar las dos.
        'toggleInicial' => null,
        'etiquetas' => [
            'tipo' => 'Tipo',
            'condicion' => 'Condición',
            'almacen' => 'Almacén',
            'quien' => 'Registró',
            'estado:entrada' => 'Solo entradas',
            'estado:salida' => 'Solo salidas',
        ],
    ])

    @push('scripts')
        <script>
        (function () {
            /*
            | Se escucha en document y en fase de CAPTURA, por dos razones:
            |
            |  - Engancharse de inmediato, sin esperar DOMContentLoaded: para
            |    cuando este script corre, ese evento puede haber pasado ya.
            |  - El menú de tres puntos corta la propagación de los clics que
            |    ocurren dentro de él (para no cerrarse solo), así que en
            |    fase de burbuja el clic en "Eliminar" nunca llegaba aquí.
            */
            document.addEventListener('click', function (e) {
                const modal = document.getElementById('modalEliminar');
                if (!modal) return;

                const boton = e.target.closest('[data-eliminar-movimiento]');

                if (boton) {
                    const form = document.getElementById('formEliminar');
                    form.action = boton.dataset.url;
                    modal.querySelector('[data-folio-eliminar]').textContent = boton.dataset.folio;
                    modal.querySelector('#pinEliminar').value = '';
                    modal.showModal();

                    return;
                }

                // Clic en el fondo: el objetivo es el propio <dialog>.
                if (e.target === modal || e.target.closest('[data-cerrar-modal]')) {
                    modal.close();
                }
            }, true);

            @if ($errors->has('password'))
                // El PIN salió mal: se vuelve a abrir con el error a la vista.
                document.getElementById('modalEliminar')?.showModal();
            @endif
        })();
        </script>
    @endpush
@endsection
