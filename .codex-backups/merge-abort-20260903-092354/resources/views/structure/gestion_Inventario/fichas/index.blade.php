@extends('layouts.dashboard')

@section('title', 'Fichas técnicas')
@section('page-title', 'Fichas técnicas')
@section('page-sub', 'Documentos en PDF que se pueden adjuntar a cotizaciones y ventas')

@section('content')
    @php
        // Solo se ofrece en los filtros lo que realmente existe en los datos.
        $equipos = $fichas->map(fn ($f) => $f->nombreRelacionado())
            ->filter()->unique()->sort()->values();

        $sinPdf = $fichas->whereNull('archivo')->count();
        $inactivas = $fichas->where('activo', false)->count();
    @endphp

    <div class="content-actions">
        <a href="{{ route('inventory.fichas.create') }}" class="btn">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="15" height="15"><path d="M12 5v14M5 12h14"/></svg>
            Nueva ficha técnica
        </a>
    </div>

    {{-- Metricas --}}
    <div class="ft-stats">
        <div class="card card--accent stat">
            <span class="stat-ico blue">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/></svg>
            </span>
            <div>
                <div class="stat-num">{{ $total }}</div>
                <div class="stat-lbl">Fichas registradas</div>
            </div>
        </div>

        <div class="card card--accent is-green stat">
            <span class="stat-ico green">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="m7 10 5 5 5-5"/><path d="M12 15V3"/></svg>
            </span>
            <div>
                <div class="stat-num">{{ $conPdf }}</div>
                <div class="stat-lbl">Con PDF cargado</div>
            </div>
        </div>

        <div class="card card--accent is-amber stat">
            <span class="stat-ico orange">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            </span>
            <div>
                <div class="stat-num">{{ $sinPdf }}</div>
                <div class="stat-lbl">Sin archivo · {{ $inactivas }} inactivas</div>
            </div>
        </div>
    </div>

    {{-- ===================== Barra de busqueda y filtros ===================== --}}
    <div class="f-toolbar">
        <div class="f-search">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            <input type="text" id="fBuscar" placeholder="Buscar por nombre, equipo o notas" autocomplete="off">
        </div>

        <div class="flt" data-flt>
            <button type="button" class="flt-btn" data-flt-toggle aria-expanded="false">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>
                Filtros
                <span class="flt-count" data-flt-count hidden>0</span>
            </button>

            <div class="flt-panel" data-flt-panel hidden>
                <div class="flt-group">
                    <h4>Archivo</h4>
                    <label class="flt-opt">
                        <span class="flt-dot c3"></span>
                        <span class="flt-opt-txt">Solo con PDF</span>
                        <input type="checkbox" data-f="pref" value="pdf">
                    </label>
                    <label class="flt-opt">
                        <span class="flt-dot c2"></span>
                        <span class="flt-opt-txt">Solo con notas</span>
                        <input type="checkbox" data-f="pref" value="notas">
                    </label>
                </div>

                @if ($equipos->isNotEmpty())
                    <div class="flt-group">
                        <h4>Equipo</h4>
                        @foreach ($equipos as $nombreEquipo)
                            <label class="flt-opt">
                                <span class="flt-opt-txt">{{ $nombreEquipo }}</span>
                                <input type="checkbox" data-f="equipo" value="{{ $nombreEquipo }}">
                            </label>
                        @endforeach
                    </div>
                @endif

                <div class="flt-group">
                    <h4>Rango de fechas</h4>
                    <div class="flt-fechas">
                        <input type="date" data-f="desde" aria-label="Alta desde">
                        <input type="date" data-f="hasta" aria-label="Alta hasta">
                    </div>
                </div>
            </div>
        </div>

        {{-- Accesos rapidos: activas / inactivas --}}
        <div class="flt-toggles" role="group" aria-label="Estado de la ficha">
            <button type="button" class="flt-tgl is-on" data-valor="1" title="Ver fichas activas" aria-pressed="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>
            </button>
            <button type="button" class="flt-tgl" data-valor="0" title="Ver fichas inactivas" aria-pressed="false">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><polyline points="21 8 21 21 3 21 3 8"/><rect x="1" y="3" width="22" height="5"/><line x1="10" y1="12" x2="14" y2="12"/></svg>
            </button>
        </div>

        <button type="button" class="flt-btn flt-btn--icon" id="fLimpiar" title="Limpiar todos los filtros" aria-label="Limpiar filtros">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M22 3H2l8 9.46V19l4 2v-8.54"/><line x1="16" y1="5" x2="22" y2="11"/><line x1="22" y1="5" x2="16" y2="11"/></svg>
        </button>

        <x-ui.view-switch key="fichas" />
    </div>

    <div class="flt-chips" id="fChips" hidden></div>

    @php
        // Los mismos atributos alimentan la tabla y las tarjetas.
        $datos = function ($ficha) {
            $equipo = $ficha->nombreRelacionado() ?? '';

            return [
                'data-buscar' => mb_strtolower($ficha->titulo . ' ' . $equipo . ' ' . ($ficha->contenido ?? '')),
                'data-equipo' => $equipo,
                'data-pdf' => $ficha->archivo ? '1' : '0',
                'data-notas' => $ficha->contenido ? '1' : '0',
                'data-activo' => $ficha->activo ? '1' : '0',
                'data-fecha' => $ficha->created_at?->format('Y-m-d') ?? '',
            ];
        };

        $menu = function ($ficha) {
            return [
                'ver' => $ficha->archivo ? asset('storage/' . $ficha->archivo) : null,
                'descargar' => $ficha->archivo ? route('inventory.fichas.download', $ficha) : null,
                'editar' => route('inventory.fichas.edit', $ficha),
                'borrar' => route('inventory.fichas.destroy', $ficha),
            ];
        };
    @endphp

    {{-- ===================== Vista lista ===================== --}}
    <div class="card" data-view-list style="overflow-x:auto; padding:0;">
        <table class="ft-table">
            <thead>
                <tr>
                    <th>Ficha técnica</th>
                    <th>Producto/Paquete</th>
                    <th>Archivo</th>
                    <th>Alta</th>
                    <th>Estado</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($fichas as $ficha)
                    <tr class="f-row" @foreach ($datos($ficha) as $attr => $valor) {{ $attr }}="{{ $valor }}" @endforeach>
                        <td>
                            <div class="cell-id">
                                <span class="ft-pdf {{ $ficha->archivo ? '' : 'es-vacio' }}">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/></svg>
                                </span>
                                <div style="min-width:0;">
                                    <div class="t">{{ $ficha->titulo }}</div>
                                    <div class="s">{{ $ficha->contenido ? \Illuminate\Support\Str::limit($ficha->contenido, 60) : 'Sin notas' }}</div>
                                </div>
                            </div>
                        </td>
                        <td>{{ $ficha->nombreRelacionado() ?? '—' }}</td>
                        <td>
                            <span class="badge {{ $ficha->archivo ? 'badge--info' : '' }}">
                                {{ $ficha->archivo ? 'PDF' : 'Sin archivo' }}
                            </span>
                        </td>
                        <td>{{ $ficha->created_at?->format('d/m/Y') ?? '—' }}</td>
                        <td>
                            <span class="badge {{ $ficha->activo ? 'badge--ok' : '' }}">
                                {{ $ficha->activo ? 'Activa' : 'Inactiva' }}
                            </span>
                        </td>
                        <td style="text-align:right; white-space:nowrap;">
                            @include('structure.gestion_Inventario.fichas._menu', ['rutas' => $menu($ficha), 'ficha' => $ficha])
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6">
                            <div class="empty-state">
                                <span class="ico">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/></svg>
                                </span>
                                <h3>Aún no hay fichas técnicas</h3>
                                <p>Sube el primer PDF y aparecerá en esta lista.</p>
                                <a href="{{ route('inventory.fichas.create') }}" class="btn">Nueva ficha técnica</a>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- ===================== Vista tarjetas ===================== --}}
    <div class="data-cards" data-view-cards style="display:none;">
        @forelse ($fichas as $ficha)
            <article class="data-card f-row" @foreach ($datos($ficha) as $attr => $valor) {{ $attr }}="{{ $valor }}" @endforeach>
                <div class="data-card-top">
                    <span class="ft-pdf {{ $ficha->archivo ? '' : 'es-vacio' }}">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/></svg>
                    </span>
                    <div style="min-width:0; flex:1;">
                        <div class="t">{{ $ficha->titulo }}</div>
                        <div class="s">{{ $ficha->nombreRelacionado() ?? 'Sin relacionar' }}</div>
                    </div>
                    @if ($ficha->activo)
                        <span class="badge badge--ok">Activa</span>
                    @endif
                </div>

                <dl>
                    <div><dt>Archivo</dt><dd>{{ $ficha->archivo ? 'PDF cargado' : 'Sin archivo' }}</dd></div>
                    <div><dt>Alta</dt><dd>{{ $ficha->created_at?->format('d/m/Y') ?? '—' }}</dd></div>
                    <div><dt>Notas</dt><dd>{{ $ficha->contenido ? \Illuminate\Support\Str::limit($ficha->contenido, 40) : '—' }}</dd></div>
                </dl>

                <div class="data-card-foot">
                    @include('structure.gestion_Inventario.fichas._menu', ['rutas' => $menu($ficha), 'ficha' => $ficha])
                </div>
            </article>
        @empty
            <div class="card">
                <div class="empty-state">
                    <span class="ico">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/></svg>
                    </span>
                    <h3>Aún no hay fichas técnicas</h3>
                    <p>Sube el primer PDF y aparecerá aquí.</p>
                    <a href="{{ route('inventory.fichas.create') }}" class="btn">Nueva ficha técnica</a>
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
            <h3>Ninguna ficha coincide</h3>
            <p>Prueba a quitar algún filtro o a cambiar la búsqueda.</p>
            <button type="button" class="btn" data-limpiar-filtros>Limpiar filtros</button>
        </div>
    </div>

    <p class="f-conteo" id="fConteo"></p>

    {{-- Confirmación de borrado --}}
    <dialog class="ft-modal" id="ftEliminar">
        <form method="POST" class="ft-modal-box" data-form>
            @csrf
            @method('DELETE')

            <div class="ft-modal-head">
                <h3>Eliminar ficha técnica</h3>
            </div>

            <div class="ft-modal-body">
                <div class="danger-box" style="margin:0;">
                    <div class="ft-del">
                        <span class="ft-del-ico">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="M3 6h18"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/></svg>
                        </span>
                        <div>
                            <div class="ft-del-name" data-nombre>—</div>
                            <div class="ft-del-note">También se borra su PDF. Esta acción no se puede deshacer.</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="ft-modal-foot">
                <button type="button" class="btn btn--ghost" data-cerrar>Cancelar</button>
                <button type="submit" class="btn btn--danger">Eliminar</button>
            </div>
        </form>
    </dialog>

    <style>
        /* Solo lo propio de esta pantalla: lo demás vive en los partials. */
        .ft-stats { display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); gap:12px; margin-bottom:16px; }
        .ft-table { width:100%; border-collapse:collapse; }

        .ft-pdf { display:inline-flex; align-items:center; justify-content:center; width:36px; height:36px;
                  flex:0 0 36px; border-radius:9px; background:var(--danger-soft); color:var(--danger); }
        .ft-pdf svg { width:17px; height:17px; }
        .ft-pdf.es-vacio { background:var(--surface-2); color:var(--muted); }

        .ft-modal { padding:0; border:0; background:transparent; max-width:none; }
        .ft-modal::backdrop { background:rgba(2,6,23,.5); backdrop-filter:blur(2px); -webkit-backdrop-filter:blur(2px); }
        .ft-modal-box { width:min(440px, calc(100vw - 28px)); background:var(--surface);
                        border:1px solid var(--border); border-radius:14px; overflow:hidden;
                        box-shadow:0 24px 60px rgba(17,24,39,.22); }
        .ft-modal-head { padding:17px 20px; border-bottom:1px solid var(--border); }
        .ft-modal-head h3 { margin:0; font-size:17px; font-weight:600; letter-spacing:-.01em; }
        .ft-modal-body { padding:20px; }
        .ft-modal-foot { display:flex; justify-content:flex-end; gap:10px; padding:15px 20px;
                         border-top:1px solid var(--border); background:var(--surface-2); }
        .ft-del { display:flex; align-items:center; gap:14px; }
        .ft-del-ico { flex:0 0 auto; display:flex; align-items:center; justify-content:center;
                      width:40px; height:40px; border-radius:11px; background:var(--surface); color:var(--danger); }
        .ft-del-ico svg { width:18px; height:18px; }
        .ft-del-name { font-size:15px; font-weight:600; overflow-wrap:anywhere; }
        .ft-del-note { margin-top:2px; color:var(--muted); font-size:13px; }

        @media (max-width:600px) {
            .ft-modal-foot { flex-direction:column-reverse; }
            .ft-modal-foot .btn { width:100%; text-align:center; }
        }
    </style>

    @include('partials.tabla-filtrable.estilos')

    @include('partials.tabla-filtrable.script', [
        'singular' => 'ficha',
        'plural' => 'fichas',
        'estadoCampo' => 'activo',
        'etiquetas' => [
            'equipo' => 'Equipo',
            'pdf' => 'Con PDF',
            'notas' => 'Con notas',
            'estado:1' => 'Solo activas',
            'estado:0' => 'Solo inactivas',
        ],
    ])

    <script>
    (function () {
        var modal = document.getElementById('ftEliminar');
        var form = modal.querySelector('[data-form]');

        document.querySelectorAll('[data-borrar-ficha]').forEach(function (b) {
            b.addEventListener('click', function () {
                form.action = b.dataset.url;
                modal.querySelector('[data-nombre]').textContent = b.dataset.nombre;
                if (typeof modal.showModal === 'function') { modal.showModal(); } else { modal.setAttribute('open', ''); }
            });
        });

        modal.querySelectorAll('[data-cerrar]').forEach(function (b) {
            b.addEventListener('click', function () { modal.close(); });
        });

        modal.addEventListener('click', function (e) { if (e.target === modal) modal.close(); });
    })();
    </script>
@endsection
