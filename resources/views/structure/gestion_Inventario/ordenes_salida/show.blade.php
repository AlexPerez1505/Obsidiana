@extends('layouts.dashboard')

@section('title', 'Orden de salida ' . $orden->folio)
@section('page-title', 'Orden de salida ' . $orden->folio)
@section('page-sub', 'Gestión de Inventario > Órdenes de salida > ' . $orden->estadoLabel())

@section('content')
    @php
        $venta = $orden->venta;
        $cliente = trim(($venta?->customer?->nombre ?? '').' '.($venta?->customer?->apellido ?? '')) ?: 'Sin cliente';
        $avance = $orden->avance();
        $editable = $puedePreparar && ! $orden->cerrada();
    @endphp

    @if (session('status'))
        <x-ui.alert type="success" style="margin-bottom:14px;">{{ session('status') }}</x-ui.alert>
    @endif

    @if ($errors->any())
        <x-ui.alert type="danger" style="margin-bottom:14px;">
            <ul style="margin:0; padding-left:18px;">
                @foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach
            </ul>
        </x-ui.alert>
    @endif

    <div class="content-actions" style="justify-content:space-between; flex-wrap:wrap; gap:10px;">
        <a href="{{ route('inventory.salidas.index') }}" class="btn btn--ghost">← Órdenes de salida</a>
        <div style="display:flex; gap:8px; flex-wrap:wrap;">
            @if ($venta)
                @can('ventas.ver')
                    <a href="{{ route('commercial.ventas.show', $venta) }}" class="btn btn--ghost">Ver venta {{ $venta->folio }}</a>
                @endcan
            @endif
            <a href="{{ route('inventory.salidas.pdf', $orden) }}" target="_blank" class="btn {{ $orden->estado === \App\Models\OrdenSalida::ENTREGADA ? '' : 'btn--ghost' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="15" height="15"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                {{ $orden->estado === \App\Models\OrdenSalida::ENTREGADA ? 'PDF de la salida' : 'Imprimir hoja de salida' }}
            </a>
        </div>
    </div>

    {{-- ===================== Resumen ===================== --}}
    <div class="os-cab">
        <x-ui.card>
            <div class="os-cab-grid">
                <div><span class="k">Estado</span><span class="badge {{ $orden->estadoTono() }}">{{ $orden->estadoLabel() }}</span></div>
                <div><span class="k">Venta</span><b>{{ $venta?->folio ?? '—' }}</b></div>
                <div><span class="k">Cliente</span><b>{{ $cliente }}</b></div>
                <div><span class="k">Asesor</span><b>{{ $venta?->seller?->name ?? '—' }}</b></div>
                <div><span class="k">Generada</span><b>{{ $orden->created_at?->format('d/m/Y H:i') }}</b></div>
                @if ($venta?->customer?->direccion)
                    <div style="grid-column:1 / -1;"><span class="k">Dirección de entrega</span><b>{{ $venta->customer->direccion }}</b></div>
                @endif
            </div>

            <div class="os-avance">
                <div class="os-riel"><span style="width:{{ $avance['pct'] }}%"></span></div>
                <span class="os-riel-txt">{{ $avance['hechos'] }} de {{ $avance['total'] }} pasos hechos</span>
            </div>
        </x-ui.card>
    </div>

    {{-- ===================== Checklist ===================== --}}
    <x-ui.card style="margin-bottom:18px;">
        <x-ui.section-title style="margin:0 0 4px;">Qué hay que preparar</x-ui.section-title>
        <p class="muted" style="margin:0 0 14px; font-size:13px;">
            Marca cada paso conforme lo hagas. Lo que no se emplaya (accesorios, consumibles) viene marcado como "no se emplaya";
            si algo viene mal clasificado, puedes cambiarlo aquí mismo.
        </p>

        <div class="os-lista">
            @foreach ($orden->items as $i => $item)
                <div class="os-item {{ $item->completo() ? 'is-listo' : '' }}">
                    <div class="os-item-num">{{ $i + 1 }}</div>

                    <div class="os-item-info">
                        <div class="os-item-nombre">{{ $item->nombre }} <span class="os-item-cant">× {{ $item->cantidad }}</span></div>
                        @if ($item->descripcion)
                            <div class="os-item-sub">{{ $item->descripcion }}</div>
                        @endif
                        @if ($item->no_series)
                            <div class="os-item-sub">Series: {{ $item->no_series }}</div>
                        @endif

                        {{-- Se vendió en un congreso: el equipo ya está allá,
                             no hay que buscarlo en el anaquel. --}}
                        @if ($congreso = $item->congresoDeOrigen())
                            <div class="os-item-congreso">
                                Este equipo está en el {{ $congreso }}: no lo busques en el almacén.
                            </div>
                        @endif

                        {{-- Observaciones de la partida --}}
                        @if ($editable)
                            <form method="POST" action="{{ route('inventory.salidas.item', [$orden, $item]) }}" class="os-obs">
                                @csrf
                                <input type="text" name="observaciones" value="{{ $item->observaciones }}" maxlength="500"
                                       placeholder="Observación (ej. falta cable, caja dañada)" aria-label="Observaciones">
                                <button type="submit" class="btn btn--ghost" style="padding:6px 10px;">Guardar</button>
                            </form>
                        @elseif ($item->observaciones)
                            <div class="os-item-sub">Obs.: {{ $item->observaciones }}</div>
                        @endif
                    </div>

                    <div class="os-pasos">
                        {{-- Preparado --}}
                        <form method="POST" action="{{ route('inventory.salidas.item', [$orden, $item]) }}">
                            @csrf
                            <input type="hidden" name="preparado" value="{{ $item->preparado ? 0 : 1 }}">
                            <button type="submit" class="os-paso {{ $item->preparado ? 'is-on' : '' }}" @disabled(! $editable)
                                    title="{{ $item->preparado ? 'Preparado el '.$item->preparado_en?->format('d/m/Y H:i') : 'Marcar como preparado' }}">
                                <span class="os-check"></span>
                                Preparado
                            </button>
                        </form>

                        {{-- Emplayado (solo si aplica) --}}
                        @if ($item->requiere_emplayado)
                            <form method="POST" action="{{ route('inventory.salidas.item', [$orden, $item]) }}">
                                @csrf
                                <input type="hidden" name="emplayado" value="{{ $item->emplayado ? 0 : 1 }}">
                                <button type="submit" class="os-paso {{ $item->emplayado ? 'is-on' : '' }}" @disabled(! $editable)
                                        title="{{ $item->emplayado ? 'Emplayado el '.$item->emplayado_en?->format('d/m/Y H:i') : 'Marcar como emplayado' }}">
                                    <span class="os-check"></span>
                                    Emplayado
                                </button>
                            </form>
                        @else
                            <span class="os-paso is-na" title="Esta partida no se emplaya">No se emplaya</span>
                        @endif

                        {{-- Cambiar si se emplaya o no --}}
                        @if ($editable)
                            <form method="POST" action="{{ route('inventory.salidas.item', [$orden, $item]) }}">
                                @csrf
                                <input type="hidden" name="requiere_emplayado" value="{{ $item->requiere_emplayado ? 0 : 1 }}">
                                <button type="submit" class="os-link">
                                    {{ $item->requiere_emplayado ? 'No se emplaya' : 'Sí se emplaya' }}
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </x-ui.card>

    <div class="os-dos">
        {{-- ===================== Notas ===================== --}}
        <x-ui.card>
            <x-ui.section-title style="margin:0 0 10px;">Notas para almacén</x-ui.section-title>
            @if ($editable)
                <form method="POST" action="{{ route('inventory.salidas.notas', $orden) }}">
                    @csrf
                    <textarea name="notas" rows="4" maxlength="2000" placeholder="Ej. va por paquetería, entregar con manual, revisar cable de luz..."
                              style="width:100%; padding:10px 12px; border:1px solid var(--border); border-radius:9px; font-size:14px; background:var(--surface); color:var(--text); resize:vertical;">{{ old('notas', $orden->notas) }}</textarea>
                    <div style="margin-top:8px; text-align:right;"><button type="submit" class="btn btn--ghost">Guardar notas</button></div>
                </form>
            @else
                <p class="muted" style="margin:0; white-space:pre-line;">{{ $orden->notas ?: 'Sin notas.' }}</p>
            @endif
        </x-ui.card>

        {{-- ===================== Salida firmada ===================== --}}
        <x-ui.card>
            @if ($orden->estado === \App\Models\OrdenSalida::ENTREGADA)
                <x-ui.section-title style="margin:0 0 10px;">Salida firmada</x-ui.section-title>
                <p class="muted" style="margin:0 0 12px; font-size:13px;">
                    Salió el {{ $orden->entregada_en?->format('d/m/Y H:i') }}.
                    Entregó {{ $orden->entregadaPor?->name ?? 'almacén' }}; recibió <b>{{ $orden->recibe_nombre }}</b>.
                    @if ($orden->preparada_en)
                        Preparada por {{ $orden->preparadaPor?->name ?? '—' }} el {{ $orden->preparada_en->format('d/m/Y H:i') }}.
                    @endif
                </p>
                <div class="os-firmas">
                    <div>
                        <div class="k">Entrega (almacén)</div>
                        @if ($orden->firmaEntregaUrl())<img src="{{ $orden->firmaEntregaUrl() }}" alt="Firma de quien entrega">@endif
                    </div>
                    <div>
                        <div class="k">Recibe: {{ $orden->recibe_nombre }}</div>
                        @if ($orden->firmaRecibeUrl())<img src="{{ $orden->firmaRecibeUrl() }}" alt="Firma de quien recibe">@endif
                    </div>
                </div>
            @elseif ($orden->estado === \App\Models\OrdenSalida::CANCELADA)
                <x-ui.section-title style="margin:0 0 10px;">Orden cancelada</x-ui.section-title>
                <p class="muted" style="margin:0;">La venta se canceló; no hay nada que preparar.</p>
            @else
                <x-ui.section-title style="margin:0 0 4px;">Firmar salida</x-ui.section-title>
                @if (! $orden->todoListo())
                    <p class="muted" style="margin:0; font-size:13px;">
                        Se habilita cuando todas las partidas estén preparadas y, las que aplican, emplayadas.
                        Faltan {{ $avance['total'] - $avance['hechos'] }} paso(s).
                    </p>
                @elseif (! $puedePreparar)
                    <p class="muted" style="margin:0; font-size:13px;">Todo está listo. La salida la firma almacén.</p>
                @else
                    <p class="muted" style="margin:0 0 12px; font-size:13px;">Todo listo. Firma quien entrega y quien se lleva el equipo.</p>

                    <form method="POST" action="{{ route('inventory.salidas.entregar', $orden) }}" id="formEntrega">
                        @csrf
                        <x-ui.form-group label="Quién recibe / se lo lleva *" for="recibe_nombre">
                            <input type="text" id="recibe_nombre" name="recibe_nombre" required maxlength="255"
                                   value="{{ old('recibe_nombre') }}" placeholder="Nombre del chofer, cliente o paquetería">
                        </x-ui.form-group>

                        <div class="os-firmas">
                            <div>
                                <div class="k">Firma de quien entrega (almacén)</div>
                                {{-- La firma registrada del usuario se carga sola.
                                     La de quien recibe no: esa es del cliente o del
                                     chofer y se traza en el momento. --}}
                                <canvas class="os-pad" data-pad="firma_entrega" width="400" height="150"
                                        @if (auth()->user()->tieneFirma())
                                            data-firma-registrada="{{ auth()->user()->firmaDataUri() }}"
                                        @endif></canvas>
                                <input type="hidden" name="firma_entrega" id="firma_entrega">
                                <a href="#" class="os-link" data-limpiar="firma_entrega">Limpiar</a>
                                @if (! auth()->user()->tieneFirma())
                                    <a href="{{ route('profile.edit') }}" class="os-link">Registrar mi firma</a>
                                @endif
                            </div>
                            <div>
                                <div class="k">Firma de quien recibe</div>
                                <canvas class="os-pad" data-pad="firma_recibe" width="400" height="150"></canvas>
                                <input type="hidden" name="firma_recibe" id="firma_recibe">
                                <a href="#" class="os-link" data-limpiar="firma_recibe">Limpiar</a>
                            </div>
                        </div>

                        <div style="margin-top:14px; text-align:right;">
                            <button type="submit" class="btn">Firmar y dar salida</button>
                        </div>
                    </form>
                @endif
            @endif
        </x-ui.card>
    </div>

    <style>
        .os-cab { margin-bottom:18px; }
        .os-cab-grid { display:grid; grid-template-columns:repeat(auto-fit, minmax(160px, 1fr)); gap:12px 18px; }
        .os-cab-grid .k { display:block; color:var(--muted); font-size:11.5px; text-transform:uppercase; letter-spacing:.04em; margin-bottom:3px; }
        .os-cab-grid b { font-size:14px; }
        .os-avance { display:flex; align-items:center; gap:12px; margin-top:16px; }
        .os-riel { flex:1; height:8px; border-radius:4px; background:var(--surface-2); overflow:hidden; }
        .os-riel span { display:block; height:100%; background:var(--green); transition:width .2s; }
        .os-riel-txt { color:var(--muted); font-size:12.5px; white-space:nowrap; }

        .os-lista { display:flex; flex-direction:column; gap:10px; }
        .os-item { display:grid; grid-template-columns:34px minmax(0,1fr) auto; gap:12px; align-items:start; padding:14px; border:1px solid var(--border); border-radius:12px; background:var(--surface); }
        .os-item.is-listo { border-color:var(--green); background:var(--green-soft, rgba(34,197,94,.06)); }
        .os-item-num { width:30px; height:30px; border-radius:9px; background:var(--surface-2); color:var(--muted); font-weight:800; font-size:13px; display:flex; align-items:center; justify-content:center; }
        .os-item.is-listo .os-item-num { background:var(--green); color:#fff; }
        .os-item-nombre { font-weight:700; font-size:14.5px; }
        .os-item-cant { color:var(--muted); font-weight:600; font-size:13px; margin-left:4px; }
        .os-item-sub { color:var(--muted); font-size:12.5px; margin-top:2px; overflow-wrap:anywhere; }
        /* El equipo se vendió en un congreso: nunca volvió al almacén. */
        .os-item-congreso { display:inline-block; margin-top:5px; padding:3px 9px; border-radius:999px;
                            background:var(--warn-soft, rgba(217,119,6,.12)); color:var(--warn, #b45309);
                            font-size:12px; font-weight:700; }
        .os-obs { display:flex; gap:8px; margin-top:8px; max-width:520px; }
        .os-obs input { flex:1; min-width:0; padding:7px 10px; border:1px solid var(--border); border-radius:8px; font-size:13px; background:var(--surface); color:var(--text); }

        .os-pasos { display:flex; flex-direction:column; gap:6px; align-items:stretch; min-width:150px; }
        .os-paso { display:inline-flex; align-items:center; gap:8px; padding:8px 12px; border:1px solid var(--border); border-radius:9px; background:var(--surface); color:var(--text); font-size:13px; font-weight:600; cursor:pointer; font-family:inherit; text-align:left; }
        .os-paso:hover:not(:disabled) { border-color:var(--primary); }
        .os-paso:disabled { cursor:default; opacity:.8; }
        .os-paso.is-on { background:var(--green); border-color:var(--green); color:#fff; }
        .os-paso.is-na { color:var(--muted); border-style:dashed; cursor:default; font-weight:500; }
        .os-check { width:16px; height:16px; border-radius:5px; border:2px solid currentColor; flex-shrink:0; position:relative; }
        .os-paso.is-on .os-check::after { content:""; position:absolute; left:3px; top:0; width:5px; height:9px; border:solid #fff; border-width:0 2px 2px 0; transform:rotate(45deg); }
        .os-link { background:none; border:none; padding:2px 0; color:var(--primary); font-size:12.5px; cursor:pointer; font-family:inherit; text-decoration:none; text-align:left; }
        .os-link:hover { text-decoration:underline; }

        .os-dos { display:grid; grid-template-columns:minmax(0,1fr) minmax(0,1.4fr); gap:18px; align-items:start; }
        @media (max-width:900px) { .os-dos { grid-template-columns:1fr; } .os-item { grid-template-columns:34px minmax(0,1fr); } .os-pasos { grid-column:2; flex-direction:row; flex-wrap:wrap; } }
        .os-firmas { display:grid; grid-template-columns:repeat(auto-fit, minmax(220px, 1fr)); gap:14px; }
        .os-firmas .k { color:var(--muted); font-size:12px; margin-bottom:6px; }
        .os-firmas img { max-width:100%; border:1px solid var(--border); border-radius:10px; background:#fff; }
        .os-pad { width:100%; height:150px; border:1px solid var(--border); border-radius:10px; background:#fff; touch-action:none; display:block; }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const form = document.getElementById('formEntrega');
            if (!form) return;

            // Lienzo de firma: mismo comportamiento que en las entradas,
            // con dos firmas en la misma hoja.
            form.querySelectorAll('canvas[data-pad]').forEach(lienzo => {
                const input = document.getElementById(lienzo.dataset.pad);
                const ctx = lienzo.getContext('2d');
                let firmando = false;

                /*
                | La firma que el usuario registró en su perfil se carga sola
                | en la de "quien entrega". La de "quien recibe" no la trae
                | nadie: es del cliente o del chofer y se traza en el momento.
                */
                if (! input.value && lienzo.dataset.firmaRegistrada) {
                    input.value = lienzo.dataset.firmaRegistrada;
                }

                const ajustar = () => {
                    const previo = input.value;
                    const rect = lienzo.getBoundingClientRect();
                    lienzo.width = Math.max(200, Math.round(rect.width));
                    lienzo.height = 150;
                    ctx.lineWidth = 2.2; ctx.lineCap = 'round'; ctx.lineJoin = 'round'; ctx.strokeStyle = '#111827';
                    if (previo) { const img = new Image(); img.onload = () => ctx.drawImage(img, 0, 0, lienzo.width, lienzo.height); img.src = previo; }
                };
                ajustar();
                window.addEventListener('resize', ajustar);

                const punto = e => {
                    const r = lienzo.getBoundingClientRect();
                    const p = e.touches ? e.touches[0] : e;
                    return { x: (p.clientX - r.left) * (lienzo.width / r.width), y: (p.clientY - r.top) * (lienzo.height / r.height) };
                };
                const empezar = e => { e.preventDefault(); firmando = true; const p = punto(e); ctx.beginPath(); ctx.moveTo(p.x, p.y); };
                const mover = e => { if (!firmando) return; e.preventDefault(); const p = punto(e); ctx.lineTo(p.x, p.y); ctx.stroke(); };
                const terminar = () => { if (firmando) { firmando = false; input.value = lienzo.toDataURL('image/png'); } };

                lienzo.addEventListener('mousedown', empezar); lienzo.addEventListener('mousemove', mover);
                window.addEventListener('mouseup', terminar);
                lienzo.addEventListener('touchstart', empezar, { passive: false }); lienzo.addEventListener('touchmove', mover, { passive: false });
                lienzo.addEventListener('touchend', terminar);

                form.querySelector('[data-limpiar="' + lienzo.dataset.pad + '"]').addEventListener('click', e => {
                    e.preventDefault(); ctx.clearRect(0, 0, lienzo.width, lienzo.height); input.value = '';
                });
            });

            form.addEventListener('submit', e => {
                const faltan = [...form.querySelectorAll('canvas[data-pad]')].filter(c => !document.getElementById(c.dataset.pad).value);
                if (faltan.length) {
                    e.preventDefault();
                    alert('Faltan firmas: ' + (faltan.length === 2 ? 'la de quien entrega y la de quien recibe.' : (faltan[0].dataset.pad === 'firma_entrega' ? 'la de quien entrega.' : 'la de quien recibe.')));
                    faltan[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            });
        });
    </script>
@endsection
