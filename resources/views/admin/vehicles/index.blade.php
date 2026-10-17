@extends('layouts.dashboard')

@section('title', 'Vehículos')
@section('page-title', 'Vehículos')
@section('page-sub', 'La flota de la empresa, con sus fotos y documentos')

@section('content')
    @if (session('status'))
        <x-ui.alert type="ok">{{ session('status') }}</x-ui.alert>
    @endif

    @if ($errors->any())
        <x-ui.alert type="err">@foreach ($errors->all() as $e)<div>{{ $e }}</div>@endforeach</x-ui.alert>
    @endif

    @php
        $estados = [
            'active' => ['texto' => 'Activo', 'badge' => 'badge--ok'],
            'maintenance' => ['texto' => 'En mantenimiento', 'badge' => 'badge--warn'],
            'inactive' => ['texto' => 'Inactivo', 'badge' => 'badge--danger'],
        ];

        /*
        | Cada fila se arma una sola vez: los mismos datos alimentan la tabla,
        | las tarjetas y los data-* que lee el filtro.
        */
        $filas = $vehicles->map(function ($v) use ($estados) {
            $estado = $estados[$v->status] ?? $estados['active'];
            $fotos = $v->photos ?: [];
            $docs = collect([$v->circulation_card_doc, $v->verification_doc, $v->tenancy_doc, $v->insurance_doc])
                ->filter()->count();

            return [
                'modelo' => $v,
                'nombre' => trim($v->brand.' '.$v->model) ?: 'Sin marca',
                'anio' => $v->year,
                'foto' => count($fotos) ? asset('storage/'.$fotos[0]) : null,
                'fotos' => count($fotos),
                'docs' => $docs,
                'estadoTexto' => $estado['texto'],
                'estadoBadge' => $estado['badge'],
            ];
        });

        $datos = fn (array $fila) => [
            'data-buscar' => mb_strtolower(implode(' ', array_filter([
                $fila['modelo']->plate_number,
                $fila['modelo']->brand,
                $fila['modelo']->model,
                $fila['modelo']->year,
                $fila['modelo']->vin,
                $fila['modelo']->color,
                $fila['modelo']->insurance_policy_number,
            ]))),
            'data-estado' => $fila['estadoTexto'],
            'data-fotos' => $fila['fotos'] ? '1' : '0',
            'data-docs' => $fila['docs'] === 4 ? '1' : '0',
            'data-fecha' => $fila['modelo']->created_at?->format('Y-m-d') ?? '',
        ];
    @endphp

    <div class="content-actions">
        <button type="button" class="btn" data-abrir-vh>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="15" height="15"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Agregar vehículo
        </button>
    </div>

    {{-- ===================== Métricas ===================== --}}
    <div class="vh-stats">
        <div class="card card--accent stat">
            <span class="stat-ico blue"><x-gravityui-car /></span>
            <div>
                <div class="stat-num">{{ $vehicles->count() }}</div>
                <div class="stat-lbl">Vehículos registrados</div>
            </div>
        </div>
        <div class="card card--accent is-green stat">
            <span class="stat-ico green">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M20 6L9 17l-5-5"/></svg>
            </span>
            <div>
                <div class="stat-num">{{ $vehicles->where('status', 'active')->count() }}</div>
                <div class="stat-lbl">Activos</div>
            </div>
        </div>
        <div class="card card--accent is-amber stat">
            <span class="stat-ico orange">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>
            </span>
            <div>
                <div class="stat-num">{{ $vehicles->where('status', 'maintenance')->count() }}</div>
                <div class="stat-lbl">En mantenimiento</div>
            </div>
        </div>
    </div>

    {{-- ===================== Búsqueda y filtros ===================== --}}
    <div class="f-toolbar">
        <div class="f-search">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            <input type="text" id="fBuscar" placeholder="Buscar por placa, marca, modelo, VIN o póliza" autocomplete="off">
        </div>

        <div class="flt" data-flt>
            <button type="button" class="flt-btn" data-flt-toggle aria-expanded="false">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>
                Filtros
                <span class="flt-count" data-flt-count hidden>0</span>
            </button>

            <div class="flt-panel" data-flt-panel hidden>
                <div class="flt-group">
                    <h4>Estado</h4>
                    <label class="flt-opt">
                        <span class="flt-dot c3"></span>
                        <span class="flt-opt-txt">Activo</span>
                        <input type="checkbox" data-f="estado" value="Activo">
                    </label>
                    <label class="flt-opt">
                        <span class="flt-dot c2"></span>
                        <span class="flt-opt-txt">En mantenimiento</span>
                        <input type="checkbox" data-f="estado" value="En mantenimiento">
                    </label>
                    <label class="flt-opt">
                        <span class="flt-dot c1"></span>
                        <span class="flt-opt-txt">Inactivo</span>
                        <input type="checkbox" data-f="estado" value="Inactivo">
                    </label>
                </div>

                <div class="flt-group">
                    <h4>Expediente</h4>
                    <label class="flt-opt">
                        <span class="flt-opt-txt">Con fotos</span>
                        <input type="checkbox" data-f="pref" value="fotos">
                    </label>
                    <label class="flt-opt">
                        <span class="flt-opt-txt">Documentos completos</span>
                        <input type="checkbox" data-f="pref" value="docs">
                    </label>
                </div>

                <div class="flt-group">
                    <h4>Fecha de alta</h4>
                    <div class="flt-fechas">
                        <input type="date" data-f="desde" aria-label="Alta desde">
                        <input type="date" data-f="hasta" aria-label="Alta hasta">
                    </div>
                </div>
            </div>
        </div>

        <button type="button" class="flt-btn flt-btn--icon" id="fLimpiar" title="Limpiar todos los filtros" aria-label="Limpiar filtros">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M22 3H2l8 9.46V19l4 2v-8.54"/><line x1="16" y1="5" x2="22" y2="11"/><line x1="22" y1="5" x2="16" y2="11"/></svg>
        </button>

        <x-ui.view-switch key="vehiculos" />
    </div>

    <div class="flt-chips" id="fChips" hidden></div>

    {{-- ===================== Vista lista ===================== --}}
    <div class="card" data-view-list style="overflow-x:auto; padding:0;">
        <table class="vh-table">
            <thead>
                <tr>
                    <th>Vehículo</th>
                    <th>Año</th>
                    <th>Color</th>
                    <th>VIN</th>
                    <th>Expediente</th>
                    <th>Estado</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($filas as $fila)
                    <tr class="f-row" @foreach ($datos($fila) as $attr => $valor) {{ $attr }}="{{ $valor }}" @endforeach>
                        <td>
                            <div class="cell-id">
                                <span class="vh-foto">
                                    @if ($fila['foto'])<img src="{{ $fila['foto'] }}" alt="">@else<x-gravityui-car />@endif
                                </span>
                                <div style="min-width:0;">
                                    <div class="t">{{ $fila['modelo']->plate_number }}</div>
                                    <div class="s">{{ $fila['nombre'] }}</div>
                                </div>
                            </div>
                        </td>
                        <td>{{ $fila['anio'] ?: '—' }}</td>
                        <td>{{ $fila['modelo']->color ?: '—' }}</td>
                        <td class="vh-mono">{{ $fila['modelo']->vin ?: '—' }}</td>
                        <td class="vh-exp">{{ $fila['fotos'] }} {{ $fila['fotos'] === 1 ? 'foto' : 'fotos' }} · {{ $fila['docs'] }}/4 docs</td>
                        <td><span class="badge {{ $fila['estadoBadge'] }}">{{ $fila['estadoTexto'] }}</span></td>
                        <td style="text-align:right; white-space:nowrap;">
                            <a class="tbl-link" href="{{ route('admin.vehicles.show', $fila['modelo']) }}">Ver ficha</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7">
                            <div class="empty-state">
                                <span class="ico"><x-gravityui-car /></span>
                                <h3>Aún no hay vehículos</h3>
                                <p>Registra el primero con su placa, fotos y documentos.</p>
                                <button type="button" class="btn" data-abrir-vh>Agregar vehículo</button>
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
            <article class="data-card f-row" @foreach ($datos($fila) as $attr => $valor) {{ $attr }}="{{ $valor }}" @endforeach>
                <div class="data-card-top">
                    <span class="vh-foto">
                        @if ($fila['foto'])<img src="{{ $fila['foto'] }}" alt="">@else<x-gravityui-car />@endif
                    </span>
                    <div style="min-width:0; flex:1;">
                        <div class="t">{{ $fila['modelo']->plate_number }}</div>
                        <div class="s">{{ $fila['nombre'] }}{{ $fila['anio'] ? ' · '.$fila['anio'] : '' }}</div>
                    </div>
                    <span class="badge {{ $fila['estadoBadge'] }}">{{ $fila['estadoTexto'] }}</span>
                </div>

                <dl>
                    <div><dt>Color</dt><dd>{{ $fila['modelo']->color ?: '—' }}</dd></div>
                    <div><dt>VIN</dt><dd>{{ $fila['modelo']->vin ?: '—' }}</dd></div>
                    <div><dt>Póliza</dt><dd>{{ $fila['modelo']->insurance_policy_number ?: '—' }}</dd></div>
                    <div><dt>Expediente</dt><dd>{{ $fila['fotos'] }} {{ $fila['fotos'] === 1 ? 'foto' : 'fotos' }} · {{ $fila['docs'] }}/4 docs</dd></div>
                </dl>

                <div class="data-card-foot">
                    <a class="tbl-link" href="{{ route('admin.vehicles.show', $fila['modelo']) }}">Ver ficha</a>
                </div>
            </article>
        @empty
            <div class="card">
                <div class="empty-state">
                    <span class="ico"><x-gravityui-car /></span>
                    <h3>Aún no hay vehículos</h3>
                    <p>Registra el primero con su placa, fotos y documentos.</p>
                    <button type="button" class="btn" data-abrir-vh>Agregar vehículo</button>
                </div>
            </div>
        @endforelse
    </div>

    <div class="card" id="fVacio" hidden>
        <div class="empty-state">
            <span class="ico">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            </span>
            <h3>Ningún vehículo coincide</h3>
            <p>Prueba a quitar algún filtro o a cambiar la búsqueda.</p>
            <button type="button" class="btn" data-limpiar-filtros>Limpiar filtros</button>
        </div>
    </div>

    <p class="f-conteo" id="fConteo"></p>

    {{-- ===================== Alta de vehículo ===================== --}}
    <dialog id="modalVh" class="vh-modal">
        <form method="POST" action="{{ route('admin.vehicles.store') }}" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="status" value="active">

            <div class="vh-head">
                <div>
                    <x-ui.section-title style="margin:0 0 4px;">Agregar vehículo</x-ui.section-title>
                    <p class="campo-nota" style="margin:0;">Solo la placa es obligatoria; lo demás se puede completar después.</p>
                </div>
                <button type="button" class="btn-icono" data-cerrar-vh aria-label="Cerrar">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"/></svg>
                </button>
            </div>

            <div class="vh-body">
                <div class="vh-grid">
                    <x-ui.form-group label="Placa *" name="plate_number" required placeholder="ABC-123" maxlength="20" />
                    <x-ui.form-group label="Número de serie (VIN)" name="vin" placeholder="1HGCM82633A123456" maxlength="50" />
                    <x-ui.form-group label="Marca" name="brand" placeholder="Toyota, Nissan…" maxlength="50" />
                    <x-ui.form-group label="Modelo" name="model" placeholder="Hilux, NP300…" maxlength="50" />
                    <x-ui.form-group label="Año" for="year">
                        <input type="number" id="year" name="year" value="{{ old('year') }}" min="1900" max="{{ date('Y') + 1 }}" placeholder="{{ date('Y') }}">
                    </x-ui.form-group>
                    <x-ui.form-group label="Color" name="color" placeholder="Blanco, gris…" maxlength="50" />
                </div>

                <div class="vh-seccion">
                    <x-ui.section-title style="margin:0 0 4px;">Fotos</x-ui.section-title>
                    <p class="campo-nota" style="margin:0 0 10px;">Hasta 10 imágenes; la primera queda como principal.</p>
                    <label class="vh-drop" for="vhFotos">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                        <span>Elegir fotos <small>frente, lateral, trasera, interior</small></span>
                    </label>
                    <input type="file" id="vhFotos" name="photos[]" accept="image/*" multiple hidden>
                    <div class="vh-previas" id="vhPrevias"></div>
                </div>

                <div class="vh-seccion">
                    <x-ui.section-title style="margin:0 0 10px;">Documentos</x-ui.section-title>
                    @foreach ([
                        'circulation_card_doc' => 'Tarjeta de circulación',
                        'verification_doc' => 'Verificación vehicular',
                        'tenancy_doc' => 'Pago de tenencia',
                        'insurance_doc' => 'Póliza de seguro',
                    ] as $campo => $nombre)
                        <div class="vh-doc">
                            <div style="min-width:0; flex:1;">
                                <div class="t">{{ $nombre }}</div>
                                <div class="s" data-doc-estado>Sin archivo</div>
                            </div>
                            <input type="file" id="vhDoc_{{ $campo }}" name="{{ $campo }}" accept=".jpg,.jpeg,.png,.pdf" hidden data-doc-input>
                            <label for="vhDoc_{{ $campo }}" class="btn btn--ghost vh-doc-btn">Elegir</label>
                        </div>
                    @endforeach
                    <x-ui.form-group label="Número de póliza" name="insurance_policy_number" maxlength="100" />
                </div>
            </div>

            <div class="vh-pie">
                <button type="button" class="btn btn--ghost" data-cerrar-vh>Cancelar</button>
                <button type="submit" class="btn">Guardar vehículo</button>
            </div>
        </form>
    </dialog>

    <style>
        .vh-stats { display:grid; grid-template-columns:repeat(auto-fit,minmax(210px,1fr)); gap:12px; margin-bottom:16px; }
        .vh-stats .stat-ico svg { width:20px; height:20px; }
        .vh-table { width:100%; border-collapse:collapse; }
        .vh-mono { font-variant-numeric:tabular-nums; font-size:13px; color:var(--muted); }
        .vh-exp { color:var(--muted); font-size:13px; white-space:nowrap; }

        .vh-foto { width:40px; height:40px; flex:0 0 40px; border-radius:9px; overflow:hidden;
                   display:inline-flex; align-items:center; justify-content:center;
                   background:var(--primary-soft); color:var(--primary); }
        .vh-foto img { width:100%; height:100%; object-fit:cover; display:block; }
        .vh-foto svg { width:20px; height:20px; }

        /* Modal de alta: mismo lenguaje que la ficha de RH. */
        .vh-modal { width:min(680px, calc(100vw - 24px)); max-height:88vh; padding:0; overflow:hidden;
                    border:1px solid var(--border); border-radius:16px;
                    background:var(--surface); color:var(--text); }
        .vh-modal::backdrop { background:rgba(15,23,42,.45); }
        .vh-modal form { display:flex; flex-direction:column; max-height:88vh; }
        .vh-head { display:flex; align-items:flex-start; gap:12px; padding:20px 24px 16px;
                   border-bottom:1px solid var(--border); }
        .vh-head > div:first-child { flex:1; min-width:0; }
        .vh-body { flex:1; overflow-y:auto; padding:6px 24px 18px; }
        .vh-grid { display:grid; grid-template-columns:repeat(auto-fit, minmax(230px, 1fr)); gap:0 16px; }
        .vh-seccion { padding-top:16px; margin-top:16px; border-top:1px solid var(--border); }

        .vh-drop { display:flex; align-items:center; gap:12px; margin:0; padding:14px 16px; cursor:pointer;
                   border:1px dashed var(--border); border-radius:10px; background:var(--surface-2);
                   font-weight:600; transition:border-color .15s ease, background .15s ease; }
        .vh-drop:hover { border-color:var(--primary); background:var(--primary-soft); }
        .vh-drop svg { width:22px; height:22px; color:var(--muted); flex:0 0 auto; }
        .vh-drop small { display:block; font-weight:400; color:var(--muted); font-size:12.5px; }
        .vh-previas { display:grid; grid-template-columns:repeat(auto-fill, minmax(90px, 1fr)); gap:8px; margin-top:10px; }
        .vh-previas:empty { display:none; }
        .vh-previas figure { position:relative; margin:0; aspect-ratio:4/3; border-radius:8px; overflow:hidden;
                             border:1px solid var(--border); background:var(--surface-2); }
        .vh-previas img { width:100%; height:100%; object-fit:cover; display:block; }
        .vh-previas figcaption { position:absolute; top:5px; left:5px; padding:1px 6px; border-radius:5px;
                                 background:var(--primary); color:#fff; font-size:10px; font-weight:600; }

        .vh-doc { display:flex; align-items:center; gap:12px; padding:10px 0; border-bottom:1px solid var(--border); }
        .vh-doc .t { font-weight:600; font-size:14px; }
        .vh-doc .s { color:var(--muted); font-size:12.5px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
        .vh-doc .s.listo { color:var(--green); }
        .vh-doc-btn { margin:0; font-size:13px; cursor:pointer; flex:0 0 auto; }

        .vh-pie { display:flex; justify-content:flex-end; gap:10px; padding:16px 24px;
                  border-top:1px solid var(--border); background:var(--surface-2); }

        @media (max-width: 640px) {
            .vh-modal { width:100vw; max-width:100vw; max-height:100dvh; border-radius:0; }
            .vh-modal form { max-height:100dvh; }
            .vh-head, .vh-pie { padding-left:16px; padding-right:16px; }
            .vh-body { padding-left:16px; padding-right:16px; }
            .vh-pie { flex-direction:column-reverse; }
            .vh-pie .btn { width:100%; text-align:center; }
        }
    </style>

    @include('partials.tabla-filtrable.estilos')

    @include('partials.tabla-filtrable.script', [
        'singular' => 'vehículo',
        'plural' => 'vehículos',
        'toggleInicial' => null,
        'etiquetas' => [
            'estado' => 'Estado',
            'fotos' => 'Con fotos',
            'docs' => 'Documentos completos',
        ],
    ])

    @push('scripts')
    <script>
    (function () {
        var modal = document.getElementById('modalVh');
        if (!modal) return;

        document.querySelectorAll('[data-abrir-vh]').forEach(function (b) {
            b.addEventListener('click', function () { modal.showModal(); });
        });
        modal.querySelectorAll('[data-cerrar-vh]').forEach(function (b) {
            b.addEventListener('click', function () { modal.close(); });
        });

        // Si la validación falló, el formulario vuelve abierto con lo capturado.
        @if ($errors->any()) modal.showModal(); @endif

        // Vista previa de las fotos elegidas.
        var fotos = document.getElementById('vhFotos');
        var previas = document.getElementById('vhPrevias');
        fotos.addEventListener('change', function () {
            previas.innerHTML = '';
            Array.prototype.slice.call(fotos.files, 0, 10).forEach(function (archivo, i) {
                var fig = document.createElement('figure');
                var img = document.createElement('img');
                img.alt = '';
                img.src = URL.createObjectURL(archivo);
                fig.appendChild(img);
                if (i === 0) {
                    var cap = document.createElement('figcaption');
                    cap.textContent = 'Principal';
                    fig.appendChild(cap);
                }
                previas.appendChild(fig);
            });
        });

        // Nombre del archivo elegido en cada documento.
        modal.querySelectorAll('[data-doc-input]').forEach(function (input) {
            input.addEventListener('change', function () {
                var estado = input.closest('.vh-doc').querySelector('[data-doc-estado]');
                var hay = input.files.length > 0;
                estado.textContent = hay ? input.files[0].name : 'Sin archivo';
                estado.classList.toggle('listo', hay);
            });
        });
    })();
    </script>
    @endpush
@endsection
