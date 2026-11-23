@extends('layouts.dashboard')

@section('title', 'Fichas técnicas')
@section('page-title', 'Fichas técnicas')
@section('page-sub', 'Documentos en PDF que se pueden adjuntar a cotizaciones y ventas')

@push('head')
    <style>
        /* Estilos críticos: evitan que el listado aparezca apilado mientras carga su diseño completo. */
        .content-actions { display:flex; justify-content:flex-end; align-items:center; margin:0 0 16px; }
        .ft-stats { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:16px; margin-bottom:16px; }
        .ft-stats .stat { min-width:0; }
        .f-toolbar { display:flex; align-items:center; gap:10px; margin-bottom:10px; }
        .f-search { flex:1 1 auto; min-width:0; }
        .card[data-view-list] { overflow:hidden; }
        .ft-table { width:100%; min-width:860px; border-collapse:collapse; }
        @media (max-width:600px) {
            .ft-stats { grid-template-columns:1fr; }
            .f-toolbar { flex-wrap:wrap; }
            .f-search { flex-basis:100%; }
        }
    </style>
@endpush

@section('content')
    @php
        // Solo se ofrece en los filtros lo que realmente existe en los datos.
        $equipos = $fichas->map(fn ($f) => $f->nombreRelacionado())
            ->filter()->unique()->sort()->values();

        $sinPdf = $fichas->whereNull('archivo')->count();
        $inactivas = $fichas->where('activo', false)->count();
    @endphp

    <div class="content-actions">
        @can('inventario.registrar')
        <a href="{{ route('inventory.fichas.create') }}" class="btn">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="15" height="15"><path d="M12 5v14M5 12h14"/></svg>
            Nueva ficha técnica
        </a>
        @endcan
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
                    <th>Equipo</th>
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
                        <td>
                            @if ($ficha->catalogoTexto())
                                <div class="ft-eq">{{ $ficha->catalogoTexto() }}</div>
                                <div class="ft-eq-s {{ $ficha->producto ? 'ok' : '' }}">
                                    {{ $ficha->producto ? 'Ligada al producto del inventario' : 'Aún sin producto en el inventario' }}@if ($ficha->paquete) · Paquete {{ $ficha->paquete->nombre }}@endif
                                </div>
                            @else
                                {{ $ficha->nombreRelacionado() ?? '—' }}
                                <div class="ft-eq-s falta">Falta elegir su tipo, subtipo, marca y modelo</div>
                            @endif
                            @php $obsPend = $ficha->observaciones->whereNull('atendida_en')->count(); @endphp
                            @if ($obsPend)
                                <button type="button" class="ft-obs-chip" data-obs-ficha="{{ $ficha->id }}" data-nombre="{{ $ficha->titulo }}">{{ $obsPend }} observación(es) por atender</button>
                            @endif
                        </td>
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
                        <div class="s">{{ $ficha->catalogoTexto() ?? $ficha->nombreRelacionado() ?? 'Sin relacionar' }}</div>
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

    {{-- Notas y observaciones de una ficha, dirigidas a un usuario. --}}
    <dialog class="ft-modal" id="ftObs">
        <div class="ft-modal-box ft-obs-box">
            <div class="ft-modal-head ft-obs-head">
                <h3>Notas y observaciones</h3>
                <span class="ft-obs-ficha" data-obs-nombre></span>
                <button type="button" class="ft-obs-x" data-cerrar aria-label="Cerrar">×</button>
            </div>

            <div class="ft-modal-body">
                <div class="ft-obs-lista" data-obs-lista></div>

                <form method="POST" class="ft-obs-form" data-obs-form>
                    @csrf
                    <label class="ft-obs-lbl" for="obsPara">¿A quién le llega?</label>
                    <select id="obsPara" name="para_id" required>
                        <option value="">Elige un usuario</option>
                        @foreach ($usuarios as $u)
                            <option value="{{ $u->id }}">{{ $u->name }}</option>
                        @endforeach
                    </select>

                    <label class="ft-obs-lbl" for="obsTexto">Observación</label>
                    <textarea id="obsTexto" name="texto" rows="3" maxlength="2000" required
                              placeholder="Ej. El modelo de la ficha no coincide: es GIF-H190, no GIF-H180."></textarea>

                    <div class="ft-obs-pie">
                        <span>Le llega a su campana de avisos.</span>
                        <button type="submit" class="btn">Enviar observación</button>
                    </div>
                </form>
            </div>
        </div>
    </dialog>

    <script type="application/json" id="ftObsDatos">@json($observaciones)</script>

    <style>
        /* Solo lo propio de esta pantalla: lo demás vive en los partials. */
        .ft-eq { font-size:13.5px; overflow-wrap:anywhere; }
        .ft-eq-s { font-size:12px; color:var(--muted); margin-top:2px; }
        .ft-eq-s.ok { color:var(--green, #15803d); }
        .ft-eq-s.falta { color:var(--amber, #b45309); }
        .ft-obs-chip { margin-top:6px; padding:2px 9px; border:0; border-radius:999px; cursor:pointer;
                       background:var(--danger-soft); color:var(--danger); font:inherit; font-size:12px; font-weight:600; }
        .ft-obs-n { margin-left:auto; min-width:18px; padding:0 6px; border-radius:999px; background:var(--danger);
                    color:#fff; font-size:11px; font-weight:700; line-height:18px; text-align:center; }

        .ft-obs-box { width:min(560px, calc(100vw - 28px)); }
        .ft-obs-head { display:flex; align-items:center; gap:10px; flex-wrap:wrap; }
        .ft-obs-head h3 { flex:0 0 auto; }
        .ft-obs-ficha { flex:1; min-width:0; color:var(--muted); font-size:13px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
        .ft-obs-x { width:30px; height:30px; border:0; border-radius:8px; background:transparent; color:var(--muted);
                    font-size:20px; cursor:pointer; }
        .ft-obs-x:hover { background:var(--surface-2); color:var(--text); }
        .ft-obs-lista { display:flex; flex-direction:column; gap:8px; max-height:280px; overflow:auto; margin-bottom:14px; }
        .ft-obs-vacio { margin:0; color:var(--muted); font-size:13px; }
        .ft-obs-item { padding:10px 12px; border:1px solid var(--border); border-radius:10px; }
        .ft-obs-item.atendida { opacity:.7; }
        .ft-obs-meta { display:flex; flex-wrap:wrap; gap:4px 10px; font-size:12px; color:var(--muted); }
        .ft-obs-meta b { color:var(--text); font-weight:600; }
        .ft-obs-texto { margin:6px 0 0; font-size:14px; color:var(--text); white-space:pre-wrap; overflow-wrap:anywhere; }
        .ft-obs-estado { display:flex; align-items:center; justify-content:space-between; gap:8px; margin-top:8px; font-size:12px; }
        .ft-obs-estado .pend { color:var(--danger); font-weight:600; }
        .ft-obs-estado .ok { color:var(--green, #15803d); }
        .ft-obs-form { display:flex; flex-direction:column; gap:6px; padding-top:12px; border-top:1px solid var(--border); }
        .ft-obs-lbl { font-size:13px; font-weight:600; }
        .ft-obs-form select, .ft-obs-form textarea { width:100%; padding:10px 12px; border:1px solid var(--border); border-radius:9px;
            background:var(--surface); color:var(--text); font:inherit; font-size:14px; }
        .ft-obs-form textarea { resize:vertical; }
        .ft-obs-pie { display:flex; align-items:center; justify-content:space-between; gap:10px; margin-top:6px;
                      font-size:12.5px; color:var(--muted); flex-wrap:wrap; }
        .ft-stats { display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); gap:12px; margin-bottom:16px; }
        .ft-table { width:100%; border-collapse:collapse; }

        .ft-pdf { display:inline-flex; align-items:center; justify-content:center; width:36px; height:36px;
                  flex:0 0 36px; border-radius:9px; background:var(--danger-soft); color:var(--danger); }
        .ft-pdf svg { width:17px; height:17px; }
        .ft-pdf.es-vacio { background:var(--surface-2); color:var(--muted); }

        .ft-modal { padding:0; border:0; background:transparent; max-width:none; }
        .ft-modal::backdrop { background:rgba(2,6,23,.5); backdrop-filter:blur(2px); -webkit-backdrop-filter:blur(2px); }
        .ft-modal-box { width:min(440px, calc(100vw - 28px)); background:var(--surface); color:var(--text);
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

        /* Rediseño visual de Fichas técnicas: conserva la estructura, filtros
           y menú de tres puntos existentes. */
        .content-actions { display:flex; justify-content:flex-end; align-items:center; margin:0 0 16px; }
        .content-actions .btn { min-height:44px; padding:0 20px; border-radius:10px; font-size:14px; font-weight:700; }
        .ft-stats { grid-template-columns:repeat(3,minmax(0,1fr)); gap:16px; }
        .ft-stats .stat { position:relative; min-height:102px; overflow:hidden; display:flex; align-items:center; gap:16px;
                          padding:18px 22px; border:1px solid #21558e; border-top:2px solid #0d8dff;
                          border-radius:14px; background:linear-gradient(135deg,#102b50 0%,#102c50 58%,#0b2241 100%); }
        .ft-stats .stat::after { content:''; position:absolute; right:-12px; bottom:-28px; width:128px; height:128px; opacity:.22;
                                 border:2px solid #1676cb; border-radius:24px; transform:rotate(28deg); pointer-events:none; }
        .ft-stats .stat.is-green { border-top-color:#22d67b; border-color:#176a61; background:linear-gradient(135deg,#0e393c 0%,#0e403c 58%,#0b2e35 100%); }
        .ft-stats .stat.is-green::after { border-color:#21b66c; transform:rotate(35deg); }
        .ft-stats .stat.is-amber { border-top-color:#f59e0b; border-color:#76501e; background:linear-gradient(135deg,#342d29 0%,#3f3022 58%,#2a2630 100%); }
        .ft-stats .stat.is-amber::after { border-color:#d58a19; border-radius:50%; transform:rotate(18deg); }
        .ft-stats .stat > * { position:relative; z-index:1; }
        .ft-stats .stat-ico { width:48px; height:48px; border-radius:12px; flex:0 0 48px; }
        .ft-stats .stat-num { font-size:30px; line-height:1; font-weight:800; color:#f5f8ff; }
        .ft-stats .stat-lbl { margin-top:5px; font-size:15px; color:#a9c2e3; }
        .f-toolbar { gap:10px; margin-bottom:10px; }
        .f-search { min-width:280px; }
        .f-toolbar .f-search input[type] { min-height:44px; padding-top:11px; padding-bottom:11px; border-radius:11px; background:var(--surface-2); }
        .f-toolbar .flt-btn { min-height:44px; border-radius:11px; background:var(--surface-2); }
        .flt-tgl { width:44px; height:44px; border-radius:11px; background:var(--surface-2); }
        .flt-btn--icon { width:44px; }
        .card[data-view-list] { border:1px solid #285a91; border-radius:14px; background:linear-gradient(180deg,#0f2748 0%,#0b1b34 100%); box-shadow:0 12px 26px rgba(0,0,0,.12); }
        .ft-table { min-width:860px; }
        .ft-table thead { background:#122f55; }
        .ft-table th { height:46px; padding:0 20px; border-bottom:1px solid #2c5b8e; color:#b8d0ee; font-size:13px; font-weight:700; text-align:left; white-space:nowrap; }
        .ft-table td { padding:12px 20px; border-bottom:1px solid rgba(65,105,150,.42); color:#e8f0fb; font-size:13px; }
        .ft-table tbody tr:last-child td { border-bottom:0; }
        .ft-table tbody tr:hover { background:rgba(37,87,143,.28); }
        .ft-table .cell-id { gap:12px; }
        .ft-table .cell-id .t, .ft-eq { color:#f3f7ff; font-weight:700; font-size:14px; }
        .ft-table .cell-id .s, .ft-eq-s { color:#a8c0df; }
        .ft-table .ft-pdf { width:40px; height:40px; border-radius:10px; background:rgba(157,36,72,.28); color:#ff6074; }
        .ft-table .ft-pdf.es-vacio { background:rgba(105,132,166,.2); color:#a6bddb; }
        .ft-conteo { margin-top:16px; font-size:14px; color:#a9c2e3; }

        [data-theme="light"] .ft-stats .stat { color:#fff; box-shadow:0 10px 22px rgba(27,55,90,.16); }
        [data-theme="light"] .card[data-view-list] { background:#ffffff; border-color:#b8cde2; box-shadow:0 12px 26px rgba(27,55,90,.12); }
        [data-theme="light"] .ft-table thead { background:#eaf3fc; }
        [data-theme="light"] .ft-table th { color:#315574; border-bottom-color:#c7d9eb; }
        [data-theme="light"] .ft-table td { color:#294966; border-bottom-color:#d9e5f0; }
        [data-theme="light"] .ft-table tbody tr:hover { background:#f2f8fd; }
        [data-theme="light"] .ft-table .cell-id .t, [data-theme="light"] .ft-eq { color:#173b5e; }
        [data-theme="light"] .ft-table .cell-id .s, [data-theme="light"] .ft-eq-s { color:#66819c; }
        [data-theme="light"] .ft-table .ft-pdf.es-vacio { background:#edf3f8; color:#7790a8; }
        [data-theme="light"] .ft-conteo { color:#66819c; }
        [data-theme="light"] .card[data-view-list] .empty-state h3 { color:#234966; }
        [data-theme="light"] .card[data-view-list] .empty-state p { color:#6d879f; }
        [data-theme="light"] .f-toolbar .f-search input[type], [data-theme="light"] .f-toolbar .flt-btn,
        [data-theme="light"] .flt-tgl { background:#ffffff; border-color:#c7d7e7; color:#315574; }

        @media (max-width:600px) {
            .ft-stats { grid-template-columns:1fr; }
            .content-actions { justify-content:stretch; }
            .content-actions .btn { width:100%; justify-content:center; }
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

    /* Notas y observaciones: lista de la ficha + enviar una nueva. */
    (function () {
        var modal = document.getElementById('ftObs');
        var datos = JSON.parse(document.getElementById('ftObsDatos').textContent || '{}');
        var lista = modal.querySelector('[data-obs-lista]');
        var form = modal.querySelector('[data-obs-form]');
        var urlObservar = @json(route('inventory.fichas.observar', ['ficha' => '__ID__']));
        var csrf = form.querySelector('input[name="_token"]').value;

        function esc(t) {
            return String(t ?? '').replace(/[&<>"']/g, function (c) { return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]; });
        }

        function abrir(id, nombre) {
            var obs = datos[id] || [];
            modal.querySelector('[data-obs-nombre]').textContent = nombre || '';
            form.action = urlObservar.replace('__ID__', id);
            lista.innerHTML = obs.length ? obs.map(function (o) {
                return '<div class="ft-obs-item ' + (o.atendida ? 'atendida' : '') + '">'
                    + '<div class="ft-obs-meta"><span>De <b>' + esc(o.autor) + '</b></span><span>para <b>' + esc(o.para) + '</b></span><span>' + esc(o.fecha) + '</span></div>'
                    + '<p class="ft-obs-texto">' + esc(o.texto) + '</p>'
                    + '<div class="ft-obs-estado">'
                    + (o.atendida
                        ? '<span class="ok">✓ Atendida ' + esc(o.atendida) + (o.atendio ? ' por ' + esc(o.atendio) : '') + '</span>'
                        : '<span class="pend">Por atender</span>')
                    + (o.puede_atender
                        ? '<form method="POST" action="' + esc(o.url_atender) + '"><input type="hidden" name="_token" value="' + esc(csrf) + '"><button type="submit" class="btn btn--ghost" style="padding:4px 10px; font-size:12.5px;">Marcar como atendida</button></form>'
                        : '')
                    + '</div></div>';
            }).join('') : '<p class="ft-obs-vacio">Esta ficha no tiene observaciones todavía.</p>';
            if (typeof modal.showModal === 'function') { modal.showModal(); } else { modal.setAttribute('open', ''); }
        }

        document.addEventListener('click', function (e) {
            var b = e.target.closest('[data-obs-ficha]');
            if (!b) return;
            abrir(b.dataset.obsFicha, b.dataset.nombre);
        }, true); // captura: el menú de tres puntos corta la propagación

        modal.querySelector('[data-cerrar]').addEventListener('click', function () { modal.close(); });
        modal.addEventListener('click', function (e) { if (e.target === modal) modal.close(); });

        // Desde el aviso de la campana: ?observaciones=ID abre esa ficha.
        var abrirId = @json($abrirObservaciones);
        if (abrirId) {
            var boton = document.querySelector('[data-obs-ficha="' + abrirId + '"]');
            abrir(abrirId, boton ? boton.dataset.nombre : '');
        }
    })();
    </script>
@endsection
