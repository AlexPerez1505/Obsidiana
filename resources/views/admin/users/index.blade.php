@extends('layouts.dashboard')

@section('title', 'Usuarios')
@section('page-title', 'Usuarios')
@section('page-sub', 'Quién entra al sistema y con qué rol')

@section('content')
    @if (session('status'))
        <x-ui.alert type="ok">{{ session('status') }}</x-ui.alert>
    @endif

    @php
        $pendientes = $users->filter(fn ($u) => $u->isPending());
        $activos = $users->filter(fn ($u) => ! $u->isPending() && ! $u->isBanned());
        $conectados = $users->filter(fn ($u) => ($activeCounts[$u->id] ?? 0) > 0);

        /*
        | Cada fila se arma una sola vez: los mismos datos alimentan la tabla,
        | las tarjetas y los data-* que lee el filtro.
        */
        $filas = $users->map(function ($u) use ($activeCounts) {
            $partes = array_values(array_filter(explode(' ', trim($u->name))));
            $iniciales = count($partes) >= 2
                ? mb_strtoupper(mb_substr($partes[0], 0, 1).mb_substr($partes[1], 0, 1))
                : (count($partes) === 1 ? mb_strtoupper(mb_substr($partes[0], 0, 2)) : 'US');

            $estado = $u->isBanned() ? 'banned' : ($u->isPending() ? 'pending' : 'approved');
            $sesiones = $activeCounts[$u->id] ?? 0;

            return [
                'modelo' => $u,
                'iniciales' => $iniciales,
                'tinte' => 'a'.((crc32($iniciales) % 5) + 1),
                'estado' => $estado,
                'estadoTexto' => ['approved' => 'Activo', 'pending' => 'Pendiente', 'banned' => 'Baneado'][$estado],
                'puesto' => $u->position ?: ($u->is_admin ? 'Administrador' : 'Sin puesto'),
                'roles' => $u->roles->pluck('label'),
                'sesiones' => $sesiones,
                'contacto' => $u->phone ?: $u->email,
            ];
        });

        // Solo se ofrece filtrar por lo que de verdad existe en los datos.
        $puestos = $filas->pluck('puesto')->unique()->sort()->values();

        $datos = function (array $fila) use ($roles) {
            $attrs = [
                'data-buscar' => mb_strtolower(implode(' ', array_filter([
                    $fila['modelo']->name,
                    $fila['modelo']->email,
                    $fila['modelo']->payroll_number,
                    $fila['modelo']->phone,
                    $fila['puesto'],
                    $fila['roles']->implode(' '),
                ]))),
                // El valor legible, no el slug: es lo que acaba en los chips.
                'data-estado' => $fila['estadoTexto'],
                'data-puesto' => $fila['puesto'],
                'data-conectado' => $fila['sesiones'] > 0 ? '1' : '0',
                'data-telefono' => $fila['modelo']->phone ? '1' : '0',
                'data-nomina' => $fila['modelo']->payroll_number ? '1' : '0',
                'data-sinrol' => $fila['modelo']->roles->isEmpty() ? '1' : '0',
                'data-fecha' => $fila['modelo']->created_at?->format('Y-m-d') ?? '',
            ];

            /*
            | Un usuario puede tener varios roles, así que el rol no cabe en un
            | solo data-*: cada rol va como bandera propia y el filtro los
            | trata como preferencias (se pueden exigir varias a la vez).
            */
            foreach ($roles as $rol) {
                $attrs['data-rol'.$rol->id] = $fila['modelo']->roles->contains($rol->id) ? '1' : '0';
            }

            return $attrs;
        };

        // Acciones del menú de tres puntos, iguales en lista y en tarjetas.
        $acciones = fn ($fila) => view('admin.users.partials.acciones', ['fila' => $fila]);
    @endphp

    <div class="content-actions">
        <button type="button" class="btn" data-abrir-rh>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="15" height="15"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Completar datos
        </button>
    </div>

    {{-- ===================== Métricas ===================== --}}
    <div class="us-stats">
        <div class="card card--accent stat">
            <span class="stat-ico blue">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
            </span>
            <div>
                <div class="stat-num">{{ $users->count() }}</div>
                <div class="stat-lbl">Usuarios registrados</div>
            </div>
        </div>

        <div class="card card--accent is-amber stat">
            <span class="stat-ico orange">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 3"/></svg>
            </span>
            <div>
                <div class="stat-num">{{ $pendientes->count() }}</div>
                <div class="stat-lbl">Pendientes de aprobar</div>
            </div>
        </div>

        <div class="card card--accent is-green stat">
            <span class="stat-ico green">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M20 6L9 17l-5-5"/></svg>
            </span>
            <div>
                <div class="stat-num">{{ $activos->count() }}</div>
                <div class="stat-lbl">Con acceso</div>
            </div>
        </div>

        <div class="card card--accent stat">
            <span class="stat-ico blue">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8M12 17v4"/></svg>
            </span>
            <div>
                <div class="stat-num">{{ $conectados->count() }}</div>
                <div class="stat-lbl">Conectados ahora</div>
            </div>
        </div>
    </div>

    {{-- ===================== Búsqueda y filtros ===================== --}}
    <div class="f-toolbar">
        <div class="f-search">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            <input type="text" id="fBuscar" placeholder="Buscar por nombre, correo, nómina, teléfono o rol" autocomplete="off">
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
                        <span class="flt-opt-txt">Pendiente de aprobar</span>
                        <input type="checkbox" data-f="estado" value="Pendiente">
                    </label>
                    <label class="flt-opt">
                        <span class="flt-dot c1"></span>
                        <span class="flt-opt-txt">Baneado</span>
                        <input type="checkbox" data-f="estado" value="Baneado">
                    </label>
                </div>

                @if ($roles->isNotEmpty())
                    <div class="flt-group">
                        <h4>Rol</h4>
                        @foreach ($roles as $rol)
                            <label class="flt-opt">
                                <span class="flt-opt-txt">{{ $rol->label }}</span>
                                <input type="checkbox" data-f="pref" value="rol{{ $rol->id }}">
                            </label>
                        @endforeach
                        <label class="flt-opt">
                            <span class="flt-opt-txt">Sin rol asignado</span>
                            <input type="checkbox" data-f="pref" value="sinrol">
                        </label>
                    </div>
                @endif

                @if ($puestos->isNotEmpty())
                    <div class="flt-group">
                        <h4>Puesto</h4>
                        @foreach ($puestos as $puesto)
                            <label class="flt-opt">
                                <span class="flt-opt-txt">{{ $puesto }}</span>
                                <input type="checkbox" data-f="puesto" value="{{ $puesto }}">
                            </label>
                        @endforeach
                    </div>
                @endif

                <div class="flt-group">
                    <h4>Ficha</h4>
                    <label class="flt-opt">
                        <span class="flt-opt-txt">Con teléfono</span>
                        <input type="checkbox" data-f="pref" value="telefono">
                    </label>
                    <label class="flt-opt">
                        <span class="flt-opt-txt">Con número de nómina</span>
                        <input type="checkbox" data-f="pref" value="nomina">
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

        {{-- Accesos rápidos: quién está en el sistema en este momento. --}}
        <div class="flt-toggles" role="group" aria-label="Sesión">
            <button type="button" class="flt-tgl" data-f="conectado" data-valor="1" title="Solo conectados ahora" aria-pressed="false">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8M12 17v4"/></svg>
            </button>
            <button type="button" class="flt-tgl" data-f="conectado" data-valor="0" title="Solo sin sesión" aria-pressed="false">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M2 3h20v14H2z"/><line x1="3" y1="3" x2="21" y2="17"/><path d="M8 21h8"/></svg>
            </button>
        </div>

        <button type="button" class="flt-btn flt-btn--icon" id="fLimpiar" title="Limpiar todos los filtros" aria-label="Limpiar filtros">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M22 3H2l8 9.46V19l4 2v-8.54"/><line x1="16" y1="5" x2="22" y2="11"/><line x1="22" y1="5" x2="16" y2="11"/></svg>
        </button>

        <x-ui.view-switch key="usuarios" />
    </div>

    <div class="flt-chips" id="fChips" hidden></div>

    {{-- ===================== Vista lista ===================== --}}
    <div class="card" data-view-list style="overflow-x:auto; padding:0;">
        <table class="us-table">
            <thead>
                <tr>
                    <th>Usuario</th>
                    <th>Puesto</th>
                    <th>Rol</th>
                    <th>Contacto</th>
                    <th>Estado</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($filas as $fila)
                    <tr class="f-row" @foreach ($datos($fila) as $attr => $valor) {{ $attr }}="{{ $valor }}" @endforeach>
                        <td>
                            <div class="cell-id">
                                <span class="us-avatar">
                                    @if ($fila['modelo']->avatar)
                                        <img src="{{ $fila['modelo']->avatar }}" alt="">
                                    @else
                                        <span class="avatar {{ $fila['tinte'] }}">{{ $fila['iniciales'] }}</span>
                                    @endif
                                    @if ($fila['sesiones'] > 0)
                                        <span class="us-online" title="Conectado ahora"></span>
                                    @endif
                                </span>
                                <div style="min-width:0;">
                                    <div class="t">{{ $fila['modelo']->name }}</div>
                                    <div class="s">{{ $fila['modelo']->email }}</div>
                                </div>
                            </div>
                        </td>
                        <td>{{ $fila['puesto'] }}</td>
                        <td>
                            @forelse ($fila['roles'] as $etiqueta)
                                <span class="badge">{{ $etiqueta }}</span>
                            @empty
                                <span class="s" style="color:var(--muted);">Sin rol</span>
                            @endforelse
                        </td>
                        <td>{{ $fila['contacto'] }}</td>
                        <td>
                            <span class="badge {{ $fila['estado'] === 'approved' ? 'badge--ok' : ($fila['estado'] === 'banned' ? 'badge--danger' : '') }}">
                                {{ $fila['estadoTexto'] }}
                            </span>
                        </td>
                        <td style="text-align:right; white-space:nowrap;">{{ $acciones($fila) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6">
                            <div class="empty-state">
                                <span class="ico">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
                                </span>
                                <h3>Aún no hay usuarios</h3>
                                <p>Las cuentas aparecen aquí cuando alguien se registra.</p>
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
                    <span class="us-avatar">
                        @if ($fila['modelo']->avatar)
                            <img src="{{ $fila['modelo']->avatar }}" alt="">
                        @else
                            <span class="avatar {{ $fila['tinte'] }}">{{ $fila['iniciales'] }}</span>
                        @endif
                        @if ($fila['sesiones'] > 0)
                            <span class="us-online" title="Conectado ahora"></span>
                        @endif
                    </span>
                    <div style="min-width:0; flex:1;">
                        <div class="t">{{ $fila['modelo']->name }}</div>
                        <div class="s">{{ $fila['puesto'] }}</div>
                    </div>
                    <span class="badge {{ $fila['estado'] === 'approved' ? 'badge--ok' : ($fila['estado'] === 'banned' ? 'badge--danger' : '') }}">
                        {{ $fila['estadoTexto'] }}
                    </span>
                </div>

                <dl>
                    <div><dt>Correo</dt><dd>{{ $fila['modelo']->email }}</dd></div>
                    <div><dt>Teléfono</dt><dd>{{ $fila['modelo']->phone ?: '—' }}</dd></div>
                    <div><dt>Nómina</dt><dd>{{ $fila['modelo']->payroll_number ?: '—' }}</dd></div>
                    <div>
                        <dt>Rol</dt>
                        <dd>{{ $fila['roles']->isNotEmpty() ? $fila['roles']->implode(', ') : 'Sin rol' }}</dd>
                    </div>
                </dl>

                <div class="data-card-foot">{{ $acciones($fila) }}</div>
            </article>
        @empty
            <div class="card">
                <div class="empty-state">
                    <span class="ico">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
                    </span>
                    <h3>Aún no hay usuarios</h3>
                    <p>Las cuentas aparecen aquí cuando alguien se registra.</p>
                </div>
            </div>
        @endforelse
    </div>

    <div class="card" id="fVacio" hidden>
        <div class="empty-state">
            <span class="ico">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            </span>
            <h3>Ningún usuario coincide</h3>
            <p>Prueba a quitar algún filtro o a cambiar la búsqueda.</p>
            <button type="button" class="btn" data-limpiar-filtros>Limpiar filtros</button>
        </div>
    </div>

    <p class="f-conteo" id="fConteo"></p>

    @include('admin.users.partials.modal-rh')

    <style>
        /* Solo lo propio de esta pantalla: lo demás vive en los partials. */
        .us-stats { display:grid; grid-template-columns:repeat(auto-fit,minmax(210px,1fr)); gap:12px; margin-bottom:16px; }
        .us-table { width:100%; border-collapse:collapse; }
        .us-table td .badge + .badge { margin-left:4px; }

        /* El punto de "conectado" cuelga del avatar, sea foto o iniciales. */
        .us-avatar { position:relative; flex:0 0 auto; display:inline-flex; }
        .us-avatar img { width:34px; height:34px; border-radius:50%; object-fit:cover; display:block; }
        .us-online { position:absolute; right:-1px; bottom:-1px; width:10px; height:10px; border-radius:50%;
                     background:var(--green); border:2px solid var(--surface); }
    </style>

    @include('partials.tabla-filtrable.estilos')

    @include('partials.tabla-filtrable.script', [
        'singular' => 'usuario',
        'plural' => 'usuarios',
        'estadoCampo' => 'conectado',
        // Los accesos rápidos son alternativas entre sí: al limpiar, ninguno.
        'toggleInicial' => null,
        'etiquetas' => collect($roles)->mapWithKeys(fn ($r) => ['rol'.$r->id => 'Rol: '.$r->label])->all() + [
            'estado' => 'Estado',
            'puesto' => 'Puesto',
            'telefono' => 'Con teléfono',
            'nomina' => 'Con nómina',
            'sinrol' => 'Sin rol asignado',
            'estado:1' => 'Solo conectados',
            'estado:0' => 'Solo sin sesión',
        ],
    ])
@endsection
