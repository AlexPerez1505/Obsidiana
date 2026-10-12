@extends('layouts.dashboard')
@section('title', 'Ver Cliente')
@section('page-title', 'Ver Cliente')

@section('content')
    @php
        $nombreCompleto = trim($customer->nombre . ' ' . $customer->apellido);
        $partes = array_values(array_filter(explode(' ', $nombreCompleto)));
        $iniciales = count($partes) >= 2
            ? mb_strtoupper(mb_substr($partes[0], 0, 1) . mb_substr($partes[1], 0, 1))
            : (count($partes) === 1 ? mb_strtoupper(mb_substr($partes[0], 0, 2)) : 'CL');

        // Mismo tinte que en el listado: iniciales iguales, color igual.
        $tinte = 't' . ((crc32($iniciales) % 5) + 1);

        $cotizaciones = $customer->cotizaciones;
        $totalCotizado = $cotizaciones->sum('total');
    @endphp

    {{-- ===================== Identidad ===================== --}}
    <x-ui.card class="card--accent" style="padding:0; overflow:hidden;">
        <div class="vc-hero">
            <span class="vc-avatar {{ $tinte }}">{{ $iniciales }}</span>

            <div class="vc-id">
                <h2 class="vc-name">{{ $nombreCompleto ?: 'Sin nombre' }}</h2>
                <p class="vc-meta">
                    {{ $customer->category?->nombre ?? 'Sin categoría' }}
                    <span class="vc-sep">·</span>
                    {{ $customer->asesor?->name ?? 'Sin asesor' }}
                </p>
                <div class="vc-badges">
                    @if ($customer->esProspecto())
                        <span class="badge badge--info">Prospecto</span>
                    @endif
                    <span class="badge {{ $customer->activo ? 'badge--ok' : '' }}">
                        {{ $customer->activo ? 'Activo' : 'Inactivo' }}
                    </span>
                    <span class="badge {{ $customer->recibe_promocion ? 'badge--info' : '' }}">
                        {{ $customer->recibe_promocion ? 'Recibe promoción' : 'Sin promoción' }}
                    </span>
                </div>
            </div>

            <div class="vc-actions">
                <a href="{{ route('commercial.clientes.index') }}" class="btn btn--ghost">Regresar</a>
                @if ($customer->esProspecto())
                    <form method="POST" action="{{ route('commercial.clientes.convertir', $customer) }}" data-confirm="¿Convertir a {{ $nombreCompleto }} en cliente?">
                        @csrf
                        <button type="submit" class="btn btn--ghost" title="Ya compró o ya se decidió: pasa a cliente">Convertir en cliente</button>
                    </form>
                @endif
                <a href="{{ route('commercial.clientes.edit', $customer) }}" class="btn">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="15" height="15"><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                    Editar
                </a>
            </div>
        </div>

        {{-- Datos de contacto, accionables --}}
        <div class="vc-contact">
            @if ($customer->telefono)
                <a href="tel:{{ $customer->telefono }}">
                    <span class="vc-ico">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1 1 .4 1.9.7 2.8a2 2 0 0 1-.5 2.1L8.1 9.9a16 16 0 0 0 6 6l1.3-1.2a2 2 0 0 1 2.1-.5c.9.3 1.8.6 2.8.7a2 2 0 0 1 1.7 2Z"/></svg>
                    </span>
                    <span class="vc-cell">
                        <span class="vc-k">Teléfono</span>
                        <span class="vc-v">{{ $customer->telefono }}</span>
                    </span>
                </a>
            @else
                <div>
                    <span class="vc-ico">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1 1 .4 1.9.7 2.8a2 2 0 0 1-.5 2.1L8.1 9.9a16 16 0 0 0 6 6l1.3-1.2a2 2 0 0 1 2.1-.5c.9.3 1.8.6 2.8.7a2 2 0 0 1 1.7 2Z"/></svg>
                    </span>
                    <span class="vc-cell">
                        <span class="vc-k">Teléfono</span>
                        <span class="vc-v vc-empty">No registrado</span>
                    </span>
                </div>
            @endif

            @if ($customer->gmail)
                <a href="mailto:{{ $customer->gmail }}">
                    <span class="vc-ico">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-10 6L2 7"/></svg>
                    </span>
                    <span class="vc-cell">
                        <span class="vc-k">Correo</span>
                        <span class="vc-v">{{ $customer->gmail }}</span>
                    </span>
                </a>
            @else
                <div>
                    <span class="vc-ico">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-10 6L2 7"/></svg>
                    </span>
                    <span class="vc-cell">
                        <span class="vc-k">Correo</span>
                        <span class="vc-v vc-empty">No registrado</span>
                    </span>
                </div>
            @endif

            <div>
                <span class="vc-ico">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="4" width="18" height="16" rx="2"/><path d="M3 10h18"/><path d="M8 15h5"/></svg>
                </span>
                <span class="vc-cell">
                    <span class="vc-k">RFC</span>
                    <span class="vc-v {{ $customer->rfc ? '' : 'vc-empty' }}">{{ $customer->rfc ?: 'No registrado' }}</span>
                </span>
            </div>
        </div>
    </x-ui.card>

    {{-- ===================== Detalle ===================== --}}
    <div class="vc-grid">
        <div class="vc-col">
            <x-ui.card>
                <div class="vc-head"><x-ui.section-title style="margin:0;">Información comercial</x-ui.section-title></div>
                <dl class="vc-rows">
                    <div>
                        <dt>Asesor asignado</dt>
                        <dd class="{{ $customer->asesor ? '' : 'vc-empty' }}">{{ $customer->asesor?->name ?? 'Sin asesor' }}</dd>
                    </div>
                    <div>
                        <dt>Categoría</dt>
                        <dd class="{{ $customer->category ? '' : 'vc-empty' }}">{{ $customer->category?->nombre ?? 'Sin categoría' }}</dd>
                    </div>
                    <div>
                        <dt>Congreso conocido</dt>
                        <dd class="{{ $customer->congress ? '' : 'vc-empty' }}">{{ $customer->congress?->nombre ?? 'Sin congreso' }}</dd>
                    </div>
                    @if (! $customer->congress && $customer->como_conocio)
                        <div>
                            <dt>¿Cómo lo conocimos?</dt>
                            <dd>{{ $customer->como_conocio }}</dd>
                        </div>
                    @endif
                    <div>
                        <dt>Fecha de alta</dt>
                        <dd>{{ $customer->created_at?->format('d/m/Y') ?? '—' }}</dd>
                    </div>
                </dl>
            </x-ui.card>

            <x-ui.card>
                <div class="vc-head"><x-ui.section-title style="margin:0;">Dirección y notas</x-ui.section-title></div>
                <div class="vc-block">
                    <span class="vc-k">Dirección</span>
                    <p class="vc-text {{ $customer->direccion ? '' : 'vc-empty' }}">{{ $customer->direccion ?: 'Sin dirección registrada' }}</p>
                </div>
                <div class="vc-block">
                    <span class="vc-k">Comentarios</span>
                    <p class="vc-text vc-pre {{ $customer->comentarios ? '' : 'vc-empty' }}">{{ $customer->comentarios ?: 'Sin comentarios' }}</p>
                </div>
            </x-ui.card>
        </div>

        <div class="vc-col">
            {{-- ===================== Seguimientos ===================== --}}
            @php
                $pendientes = $customer->seguimientos->filter(fn ($s) => ! $s->hecho());
                $hechos = $customer->seguimientos->filter(fn ($s) => $s->hecho())->sortByDesc('hecho_en');
            @endphp
            <x-ui.card id="seguimientos">
                <div class="vc-head">
                    <x-ui.section-title style="margin:0;">Seguimientos</x-ui.section-title>
                    @if ($pendientes->isNotEmpty())
                        <span class="vc-count">{{ $pendientes->count() }} pendiente(s)</span>
                    @endif
                </div>

                @if (session('status'))
                    <x-ui.alert type="success" style="margin:12px 0 0;">{{ session('status') }}</x-ui.alert>
                @endif
                @if ($errors->any())
                    <x-ui.alert type="danger" style="margin:12px 0 0;">
                        <ul style="margin:0; padding-left:18px;">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
                    </x-ui.alert>
                @endif

                @forelse ($pendientes as $s)
                    <div class="sg-item {{ $s->vencido() ? 'is-vencido' : ($s->esHoy() ? 'is-hoy' : '') }}">
                        <div class="sg-txt">
                            <div class="sg-t">{{ $s->tipoLabel() }} <span class="sg-cuando">{{ $s->cuando() }} · {{ $s->fecha?->format('d/m/Y') }}</span></div>
                            @if ($s->nota)<div class="sg-s">{{ $s->nota }}</div>@endif
                            <div class="sg-s">Responsable: {{ $s->responsable?->name ?? '—' }}{{ $s->notificado_en ? ' · avisado el '.$s->notificado_en->format('d/m H:i') : '' }}</div>
                        </div>
                        <div class="sg-acc">
                            <form method="POST" action="{{ route('commercial.clientes.seguimientos.hecho', [$customer, $s]) }}" class="sg-hecho">
                                @csrf
                                <input type="text" name="resultado" maxlength="500" placeholder="¿Cómo salió? (opcional)" aria-label="Resultado">
                                <button type="submit" class="btn" style="padding:6px 12px;">Hecho</button>
                            </form>
                            <form method="POST" action="{{ route('commercial.clientes.seguimientos.reprogramar', [$customer, $s]) }}" class="sg-mover">
                                @csrf
                                <input type="date" name="fecha" required min="{{ now()->toDateString() }}" value="{{ $s->fecha?->toDateString() }}" aria-label="Nueva fecha">
                                <button type="submit" class="btn btn--ghost" style="padding:6px 10px;">Mover</button>
                            </form>
                            <form method="POST" action="{{ route('commercial.clientes.seguimientos.destroy', [$customer, $s]) }}" data-confirm="¿Eliminar este seguimiento?">
                                @csrf @method('DELETE')
                                <button type="submit" class="sg-x" title="Eliminar">×</button>
                            </form>
                        </div>
                    </div>
                @empty
                    <p class="vc-text vc-empty" style="margin:12px 0 0;">Sin seguimientos pendientes.</p>
                @endforelse

                {{-- Programar uno nuevo --}}
                <form method="POST" action="{{ route('commercial.clientes.seguimientos.store', $customer) }}" class="sg-nuevo">
                    @csrf
                    <div class="sg-nuevo-grid">
                        <select name="tipo" required aria-label="Qué hacer">
                            @foreach ($tiposSeguimiento as $valor => $texto)
                                <option value="{{ $valor }}" @selected(old('tipo', 'llamada') === $valor)>{{ $texto }}</option>
                            @endforeach
                        </select>
                        <input type="date" name="fecha" required min="{{ now()->toDateString() }}" value="{{ old('fecha') }}" aria-label="Cuándo">
                        @if ($usuarios->isNotEmpty())
                            <select name="user_id" aria-label="Responsable">
                                @foreach ($usuarios as $usr)
                                    <option value="{{ $usr->id }}" @selected((int) old('user_id', auth()->id()) === $usr->id)>{{ $usr->name }}</option>
                                @endforeach
                            </select>
                        @endif
                        <input type="text" name="nota" maxlength="500" value="{{ old('nota') }}" placeholder="Nota (ej. quiere cotización en un mes)" aria-label="Nota" style="grid-column:1 / -1;">
                    </div>
                    <div style="display:flex; justify-content:space-between; align-items:center; gap:10px; margin-top:8px; flex-wrap:wrap;">
                        <small style="color:var(--muted);">Ese día llega el aviso en la campana y por correo al responsable.</small>
                        <button type="submit" class="btn btn--ghost">Programar seguimiento</button>
                    </div>
                </form>

                @if ($hechos->isNotEmpty())
                    <details class="sg-hist">
                        <summary>Historial ({{ $hechos->count() }})</summary>
                        @foreach ($hechos as $s)
                            <div class="sg-item is-hecho">
                                <div class="sg-txt">
                                    <div class="sg-t">{{ $s->tipoLabel() }} <span class="sg-cuando">{{ $s->fecha?->format('d/m/Y') }} · hecho el {{ $s->hecho_en->format('d/m/Y') }} por {{ $s->hechoPor?->name ?? '—' }}</span></div>
                                    @if ($s->nota)<div class="sg-s">{{ $s->nota }}</div>@endif
                                    @if ($s->resultado)<div class="sg-s"><b>Resultado:</b> {{ $s->resultado }}</div>@endif
                                </div>
                            </div>
                        @endforeach
                    </details>
                @endif
            </x-ui.card>

            <x-ui.card>
                <div class="vc-head">
                    <x-ui.section-title style="margin:0;">Cotizaciones</x-ui.section-title>
                    @if ($cotizaciones->isNotEmpty())
                        <span class="vc-count">{{ $cotizaciones->count() }}</span>
                    @endif
                </div>

                @if ($cotizaciones->isEmpty())
                    <p class="vc-text vc-empty" style="margin:0;">Este cliente aún no tiene cotizaciones.</p>
                @else
                    <div class="vc-quotes">
                        @foreach ($cotizaciones as $cot)
                            <a href="{{ route('commercial.cotizaciones.show', $cot) }}" class="vc-quote">
                                <span class="vc-quote-id">
                                    <span class="vc-quote-f">{{ $cot->folio ?: 'Cotización #' . $cot->id }}</span>
                                    <span class="vc-k">{{ $cot->created_at?->format('d/m/Y') ?? '' }}</span>
                                </span>
                                <span class="vc-quote-t">${{ number_format((float) $cot->total, 2) }}</span>
                            </a>
                        @endforeach
                    </div>

                    <div class="vc-total">
                        <span class="vc-k">Total cotizado</span>
                        <span class="vc-total-v">${{ number_format((float) $totalCotizado, 2) }}</span>
                    </div>
                @endif
            </x-ui.card>
        </div>
    </div>

    <style>
        /* ===== Bloque de identidad ===== */
        .vc-hero { display:flex; align-items:center; gap:20px; flex-wrap:wrap; padding:24px; }

        .vc-avatar { flex:0 0 auto; width:72px; height:72px; border-radius:20px;
                     display:flex; align-items:center; justify-content:center;
                     font-size:24px; font-weight:700; letter-spacing:.02em; }
        .vc-avatar.t1 { background:var(--primary-soft); color:var(--primary); }
        .vc-avatar.t2 { background:var(--green-soft); color:var(--green); }
        .vc-avatar.t3 { background:var(--accent-soft); color:var(--accent); }
        .vc-avatar.t4 { background:var(--danger-soft); color:var(--danger); }
        .vc-avatar.t5 { background:var(--surface-2); color:var(--muted); }

        .vc-id { flex:1 1 260px; min-width:0; }
        .vc-name { margin:0; font-size:25px; font-weight:700; line-height:1.15; letter-spacing:-.01em; }
        .vc-meta { margin:6px 0 0; color:var(--muted); font-size:14px; }
        .vc-sep { opacity:.5; margin:0 4px; }
        .vc-badges { display:flex; flex-wrap:wrap; gap:8px; margin-top:14px; }

        .vc-actions { margin-left:auto; display:flex; align-items:center; gap:10px; flex-wrap:wrap; }
        .vc-actions .btn { display:inline-flex; align-items:center; gap:6px; }

        /* ===== Contacto ===== */
        .vc-contact { display:grid; grid-template-columns:repeat(3,1fr); border-top:1px solid var(--border); }
        .vc-contact > * { display:flex; align-items:center; gap:13px; min-width:0;
                          padding:16px 24px; color:var(--text); text-decoration:none; }
        .vc-contact > * + * { border-left:1px solid var(--border); }
        .vc-contact a { transition:background .15s ease; }
        .vc-contact a:hover { background:var(--surface-2); }
        .vc-contact a:hover .vc-ico { background:var(--primary-soft); color:var(--primary); }

        .vc-ico { flex:0 0 auto; display:flex; align-items:center; justify-content:center;
                  width:38px; height:38px; border-radius:11px;
                  background:var(--surface-2); color:var(--muted);
                  transition:background .15s ease, color .15s ease; }
        .vc-ico svg { width:17px; height:17px; }

        .vc-cell { display:flex; flex-direction:column; gap:2px; min-width:0; }
        .vc-k { color:var(--muted); font-size:13px; }
        .vc-v { font-size:15px; font-weight:600; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
        .vc-empty { color:var(--muted); font-weight:500; }

        /* ===== Rejilla del detalle ===== */
        .vc-grid { display:grid; grid-template-columns:minmax(0,1.55fr) minmax(0,1fr);
                   gap:18px; align-items:start; margin-top:18px; }
        .vc-col { display:flex; flex-direction:column; gap:18px; min-width:0; }

        .vc-head { display:flex; align-items:center; gap:10px;
                   padding-bottom:14px; margin-bottom:4px; border-bottom:1px solid var(--border); }
        .vc-count { margin-left:auto; padding:1px 8px; border-radius:6px;
                    background:var(--surface-2); border:1px solid var(--border);
                    color:var(--muted); font-size:12px; font-weight:500; }

        /* Filas etiqueta / valor */
        .vc-rows { margin:0; display:flex; flex-direction:column; }
        .vc-rows > div { display:flex; align-items:baseline; justify-content:space-between;
                         gap:18px; padding:14px 0; }
        .vc-rows > div + div { border-top:1px solid var(--border); }
        .vc-rows dt { color:var(--muted); font-size:13.5px; flex:0 0 auto; }
        .vc-rows dd { margin:0; font-size:14.5px; font-weight:600; text-align:right;
                      min-width:0; overflow-wrap:anywhere; }

        .vc-block + .vc-block { margin-top:18px; padding-top:18px; border-top:1px solid var(--border); }
        .vc-text { margin:6px 0 0; font-size:14.5px; font-weight:500; line-height:1.65; overflow-wrap:anywhere; }
        .vc-pre { white-space:pre-wrap; }

        /* ===== Cotizaciones ===== */
        .vc-quotes { display:flex; flex-direction:column; }
        .vc-quote { display:flex; align-items:center; justify-content:space-between; gap:14px;
                    padding:13px 0; color:var(--text); text-decoration:none; }
        .vc-quote + .vc-quote { border-top:1px solid var(--border); }
        .vc-quote-id { display:flex; flex-direction:column; gap:2px; min-width:0; }
        .vc-quote-f { font-size:14.5px; font-weight:600; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
        .vc-quote:hover .vc-quote-f { color:var(--primary); }
        .vc-quote-t { font-size:14.5px; font-weight:600; white-space:nowrap; }

        .vc-total { display:flex; align-items:baseline; justify-content:space-between; gap:14px;
                    margin-top:14px; padding-top:14px; border-top:1px solid var(--border); }
        .vc-total-v { font-size:17px; font-weight:700; letter-spacing:-.01em; }

        /* ===== Seguimientos ===== */
        .sg-item { padding:12px 0; border-bottom:1px solid var(--border); }
        .sg-item.is-hoy .sg-cuando { color:var(--accent); font-weight:700; }
        .sg-item.is-vencido .sg-cuando { color:var(--danger); font-weight:700; }
        .sg-item.is-hecho { opacity:.75; }
        .sg-t { font-size:14.5px; font-weight:600; }
        .sg-cuando { font-weight:500; color:var(--muted); font-size:12.5px; margin-left:6px; }
        .sg-s { color:var(--muted); font-size:12.5px; margin-top:2px; overflow-wrap:anywhere; }
        .sg-acc { display:flex; gap:8px; flex-wrap:wrap; align-items:center; margin-top:8px; }
        .sg-hecho, .sg-mover { display:flex; gap:6px; align-items:center; }
        .sg-hecho input { width:180px; padding:6px 9px; border:1px solid var(--border); border-radius:8px; font-size:13px; background:var(--surface); color:var(--text); }
        .sg-mover input { padding:6px 9px; border:1px solid var(--border); border-radius:8px; font-size:13px; background:var(--surface); color:var(--text); }
        .sg-x { width:28px; height:28px; border:1px solid var(--border); border-radius:8px; background:var(--surface); color:var(--muted); cursor:pointer; font-size:16px; line-height:1; }
        .sg-x:hover { color:var(--danger); border-color:var(--danger); }
        .sg-nuevo { margin-top:14px; padding-top:14px; border-top:1px dashed var(--border); }
        .sg-nuevo-grid { display:grid; grid-template-columns:repeat(auto-fit, minmax(150px, 1fr)); gap:8px; }
        .sg-nuevo-grid select, .sg-nuevo-grid input { width:100%; padding:8px 10px; border:1px solid var(--border); border-radius:8px; font-size:13.5px; background:var(--surface); color:var(--text); font-family:inherit; }
        .sg-hist { margin-top:12px; }
        .sg-hist summary { cursor:pointer; color:var(--muted); font-size:13px; }

        /* ===== Adaptable ===== */
        @media (max-width:1000px) {
            .vc-grid { grid-template-columns:1fr; }
        }
        @media (max-width:760px) {
            .vc-contact { grid-template-columns:1fr; }
            .vc-contact > * + * { border-left:0; border-top:1px solid var(--border); }
            .vc-actions { margin-left:0; width:100%; }
            .vc-actions .btn { flex:1; justify-content:center; }
        }
        @media (prefers-reduced-motion:reduce) {
            .vc-contact a, .vc-ico { transition:none; }
        }
    </style>
@endsection
