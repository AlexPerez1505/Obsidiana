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

    {{-- ===================== Evidencia =====================
         Fotos y videos del equipo preparado, emplayado o entregado. Sin tope
         de cuántas: cada foto se sube en su propia petición y se suma a las
         que ya había. Se sigue pudiendo subir después de entregada (la foto
         de la entrega a veces llega luego), pero no a una orden cancelada. --}}
    @php
        $ordenCancelada = $orden->estado === \App\Models\OrdenSalida::CANCELADA;
        $puedeSubirEvidencia = $puedePreparar && ! $ordenCancelada;
        $evFotos = $orden->evidencias->where('tipo', \App\Models\OrdenSalidaEvidencia::FOTO);
        $evVideos = $orden->evidencias->where('tipo', \App\Models\OrdenSalidaEvidencia::VIDEO);
    @endphp
    <x-ui.card style="margin-bottom:18px;" id="evidencia"
               data-ev-foto-url="{{ $puedeSubirEvidencia ? route('inventory.salidas.evidencias.foto', $orden) : '' }}"
               data-ev-video-url="{{ $puedeSubirEvidencia ? route('inventory.salidas.evidencias.videoChunk', $orden) : '' }}">
        <div class="ev-cab">
            <div class="ev-cab-txt">
                <x-ui.section-title style="margin:0 0 2px;">Evidencia</x-ui.section-title>
                <span class="ev-cuenta" data-ev-cuenta>{{ $orden->resumenEvidencias() }}</span>
            </div>

            @if ($puedeSubirEvidencia)
                <div class="ev-acciones">
                    {{-- Sin "capture": el teléfono ofrece cámara o galería y deja elegir varias. --}}
                    <label class="btn ev-boton">
                        <x-ui.icon name="plus"/> Agregar fotos
                        <input type="file" accept="image/*" multiple class="ev-input" data-ev-fotos>
                    </label>
                    {{-- Directo a la cámara trasera; en computadora no se enseña. --}}
                    <label class="btn btn--ghost ev-boton ev-solo-tactil">
                        <x-ui.icon name="camera"/> Tomar foto
                        <input type="file" accept="image/*" capture="environment" class="ev-input" data-ev-fotos>
                    </label>
                    <label class="btn btn--ghost ev-boton">
                        <x-ui.icon name="play"/> Subir video
                        <input type="file" accept="video/mp4,video/quicktime,video/webm,video/x-m4v,video/*" multiple class="ev-input" data-ev-videos>
                    </label>
                </div>
            @endif
        </div>

        @if ($puedeSubirEvidencia)
            <p class="muted ev-nota">
                Sube todas las que hagan falta: cada foto se suma a las anteriores (hasta 10MB cada una).
                Videos MP4, MOV o WEBM de hasta 150MB.
            </p>
        @elseif ($ordenCancelada)
            <p class="muted ev-nota">La orden está cancelada: su evidencia se queda como estaba.</p>
        @endif

        {{-- Lo que se está subiendo, uno por uno, con su avance. --}}
        <div class="ev-cola" data-ev-cola hidden></div>

        <p class="muted ev-vacio" data-ev-vacio @if ($orden->evidencias->isNotEmpty()) hidden @endif>
            Todavía no hay evidencia.
            @if ($puedeSubirEvidencia) Toma fotos del equipo preparado, emplayado o de la entrega. @endif
        </p>

        <div class="ev-fotos" data-ev-galeria="foto">
            @foreach ($evFotos as $ev)
                @include('structure.gestion_Inventario.ordenes_salida._evidencia', [
                    'evidencia' => $ev,
                    'borrable' => ! $ordenCancelada && $ev->puedeBorrarla(auth()->user()),
                ])
            @endforeach
        </div>

        <div class="ev-videos" data-ev-galeria="video">
            @foreach ($evVideos as $ev)
                @include('structure.gestion_Inventario.ordenes_salida._evidencia', [
                    'evidencia' => $ev,
                    'borrable' => ! $ordenCancelada && $ev->puedeBorrarla(auth()->user()),
                ])
            @endforeach
        </div>
    </x-ui.card>

    {{-- Visor de fotos en grande. El script lo pasa al <body> para que
         ningún contenedor recorte el position:fixed. --}}
    <div class="ev-visor" id="evVisor" hidden role="dialog" aria-modal="true" aria-label="Foto de evidencia">
        <button type="button" class="ev-visor-btn ev-visor-cerrar" data-visor="cerrar" aria-label="Cerrar"><x-ui.icon name="x" size="20"/></button>
        <button type="button" class="ev-visor-btn ev-visor-ant" data-visor="ant" aria-label="Foto anterior"><x-ui.icon name="chevron-left" size="22"/></button>
        <figure class="ev-visor-fig">
            <img id="evVisorImg" alt="Foto de evidencia en grande">
            <figcaption id="evVisorPie"></figcaption>
        </figure>
        <button type="button" class="ev-visor-btn ev-visor-sig" data-visor="sig" aria-label="Foto siguiente"><x-ui.icon name="chevron-right" size="22"/></button>
    </div>

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

                {{--
                    Checklist terminado pero sin cerrar: quien prepara deja la
                    orden lista y se va; la firma la hace quien entregue, que
                    puede ser otra persona y otro día.
                --}}
                @elseif ($orden->puedeConfirmarPreparacion())
                    @if ($puedePreparar)
                        <div class="os-lista-aviso">
                            <div>
                                <b>El checklist está completo.</b>
                                <span>Déjala lista para salir y se queda esperando que alguien firme la salida.</span>
                            </div>
                            <form method="POST" action="{{ route('inventory.salidas.preparada', $orden) }}">
                                @csrf
                                <button type="submit" class="btn">Dejar lista para salir</button>
                            </form>
                        </div>
                    @else
                        <p class="muted" style="margin:0; font-size:13px;">
                            El checklist está completo. Falta que almacén la deje lista para salir.
                        </p>
                    @endif

                @elseif (! $puedePreparar)
                    <p class="muted" style="margin:0; font-size:13px;">
                        Lista para salir desde el {{ $orden->preparada_en?->format('d/m/Y H:i') }}. La firma la hace almacén.
                    </p>
                @else
                    <p class="muted" style="margin:0 0 12px; font-size:13px;">
                        Lista para salir: la preparó {{ $orden->preparadaPor?->name ?? '—' }}
                        el {{ $orden->preparada_en?->format('d/m/Y H:i') }}.
                        Ahora firma quien entrega y quien se lleva el equipo.
                    </p>

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

        /* Checklist completo, esperando que almacén cierre la preparación. */
        .os-lista-aviso { display:flex; align-items:center; gap:14px; flex-wrap:wrap; margin-top:10px;
                          padding:14px 16px; border:1px solid var(--primary); border-radius:11px;
                          background:var(--primary-soft); font-size:13.5px; }
        .os-lista-aviso > div { flex:1; min-width:190px; }
        .os-lista-aviso b { display:block; }
        .os-lista-aviso span { color:var(--muted); font-size:12.5px; }
        .os-lista-aviso form { flex:0 0 auto; }
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

        /* ===== Evidencia ===== */
        .ev-cab { display:flex; align-items:flex-start; justify-content:space-between; gap:12px; flex-wrap:wrap; }
        .ev-cab-txt { min-width:0; }
        .ev-cuenta { color:var(--muted); font-size:13px; }
        .ev-acciones { display:flex; gap:8px; flex-wrap:wrap; }
        /* El label global es de bloque con margen: aquí es un botón. */
        .ev-boton { position:relative; display:inline-flex; align-items:center; gap:7px; margin:0; font-size:13.5px; font-weight:500; }
        /* El input se oculta sin display:none: algunos Safari de iPhone no abren el selector si el input no se pinta. */
        .ev-input { position:absolute; width:1px; height:1px; padding:0; margin:-1px; border:0; overflow:hidden; clip:rect(0 0 0 0); opacity:0; }
        .ev-boton:focus-within { box-shadow:0 0 0 3px var(--primary-soft); }
        @media (hover:hover) and (pointer:fine) { .ev-solo-tactil { display:none; } }
        .ev-nota { margin:10px 0 0; font-size:12.5px; }
        .ev-vacio { margin:14px 0 0; font-size:13px; }

        .ev-cola { display:flex; flex-direction:column; gap:6px; margin-top:12px; }
        .ev-fila { display:grid; grid-template-columns:minmax(0,1fr) auto; gap:4px 10px; align-items:center; padding:8px 10px;
                   border:1px solid var(--border); border-radius:9px; background:var(--surface-2); font-size:12.5px; }
        .ev-fila-nombre { overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
        .ev-fila-estado { color:var(--muted); white-space:nowrap; }
        .ev-fila-riel { grid-column:1 / -1; height:4px; border-radius:2px; background:var(--border); overflow:hidden; }
        .ev-fila-riel span { display:block; height:100%; width:0; background:var(--primary); transition:width .15s; }
        .ev-fila.is-ok .ev-fila-riel span { background:var(--green); }
        .ev-fila.is-error { border-color:var(--danger); background:var(--danger-soft); }
        /* El mensaje baja a su propio renglón para no comerse el nombre del archivo. */
        .ev-fila.is-error .ev-fila-estado { grid-column:1 / -1; color:var(--danger); white-space:normal; }
        .ev-fila.is-error .ev-fila-riel { display:none; }
        .ev-reintentar { background:none; border:none; padding:0; margin-left:6px; color:var(--primary); font:inherit; cursor:pointer; text-decoration:underline; }

        .ev-fotos, .ev-videos { display:grid; gap:10px; }
        .ev-fotos { grid-template-columns:repeat(auto-fill, minmax(104px, 1fr)); }
        .ev-videos { grid-template-columns:repeat(auto-fill, minmax(min(100%, 260px), 1fr)); }
        /* Margen solo si la galería tiene algo (con espacios en blanco, :empty no sirve). */
        .ev-fotos:has(.ev-item), .ev-videos:has(.ev-item) { margin-top:14px; }
        .ev-item { position:relative; margin:0; min-width:0; }
        .ev-mini { display:block; width:100%; aspect-ratio:1 / 1; padding:0; border:1px solid var(--border); border-radius:10px;
                   overflow:hidden; background:var(--surface-2); cursor:zoom-in; }
        .ev-mini img { display:block; width:100%; height:100%; object-fit:cover; }
        .ev-mini:focus-visible { outline:2px solid var(--primary); outline-offset:2px; }
        .ev-item--video video { display:block; width:100%; aspect-ratio:16 / 9; border-radius:10px; background:#000; }
        .ev-pie { display:flex; flex-direction:column; margin-top:4px; font-size:11.5px; line-height:1.35; min-width:0; }
        .ev-quien { font-weight:600; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
        .ev-cuando { color:var(--muted); }
        .ev-quitar { position:absolute; top:5px; right:5px; width:28px; height:28px; display:inline-flex; align-items:center; justify-content:center;
                     border:none; border-radius:8px; background:rgba(17,24,39,.62); color:#fff; cursor:pointer; }
        .ev-quitar:hover { background:var(--danger); }
        .ev-item.is-borrando { opacity:.45; pointer-events:none; }

        .ev-visor { position:fixed; inset:0; z-index:9000; display:flex; align-items:center; justify-content:center;
                    background:rgba(0,0,0,.92); padding:48px 12px; }
        .ev-visor[hidden] { display:none; }
        .ev-visor-fig { margin:0; max-width:100%; max-height:100%; display:flex; flex-direction:column; align-items:center; gap:8px; min-width:0; }
        .ev-visor-fig img { max-width:100%; max-height:calc(100vh - 130px); object-fit:contain; border-radius:6px; }
        .ev-visor-fig figcaption { color:#e5e7eb; font-size:13px; text-align:center; }
        .ev-visor-btn { position:absolute; width:42px; height:42px; display:inline-flex; align-items:center; justify-content:center;
                        border:none; border-radius:50%; background:rgba(255,255,255,.14); color:#fff; cursor:pointer; }
        .ev-visor-btn:hover { background:rgba(255,255,255,.26); }
        .ev-visor-cerrar { top:10px; right:10px; }
        .ev-visor-ant { left:10px; top:50%; transform:translateY(-50%); }
        .ev-visor-sig { right:10px; top:50%; transform:translateY(-50%); }
        .ev-visor-btn[hidden] { display:none; }
        @media (max-width:600px) {
            .ev-acciones { width:100%; }
            .ev-boton { flex:1 1 auto; justify-content:center; }
            .ev-fotos { grid-template-columns:repeat(3, minmax(0, 1fr)); gap:8px; }
            .ev-visor-ant, .ev-visor-sig { top:auto; bottom:10px; transform:none; }
        }
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
                    window.showToast('Faltan firmas: ' + (faltan.length === 2 ? 'la de quien entrega y la de quien recibe.' : (faltan[0].dataset.pad === 'firma_entrega' ? 'la de quien entrega.' : 'la de quien recibe.')), 'warn');
                    faltan[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            });
        });
    </script>

    <script>
        /*
        | Evidencia de la orden: fotos sin tope y videos por pedazos.
        |
        | Cada foto viaja en su propia petición, una tras otra, para que
        | ninguna rebase el tamaño máximo que acepta el servidor por más fotos
        | que se elijan. Lo que se sube se suma a la galería; nunca reemplaza
        | lo que ya había. El video va en pedazos de 4MB, igual que en las
        | entradas de inventario.
        */
        document.addEventListener('DOMContentLoaded', function () {
            const tarjeta = document.getElementById('evidencia');
            if (!tarjeta) return;

            const csrf = document.querySelector('meta[name="csrf-token"]')?.content || @json(csrf_token());
            const urlFoto = tarjeta.dataset.evFotoUrl;
            const urlVideo = tarjeta.dataset.evVideoUrl;
            const cola = tarjeta.querySelector('[data-ev-cola]');
            const cuenta = tarjeta.querySelector('[data-ev-cuenta]');
            const vacio = tarjeta.querySelector('[data-ev-vacio]');
            const galeria = {
                foto: tarjeta.querySelector('[data-ev-galeria="foto"]'),
                video: tarjeta.querySelector('[data-ev-galeria="video"]'),
            };

            const FOTO_MAX = 10 * 1024 * 1024;
            const VIDEO_MAX = 150 * 1024 * 1024;
            const CHUNK = 4 * 1024 * 1024;
            const EXT_VIDEO = ['mp4', 'mov', 'm4v', 'webm'];
            const EXT_POR_TIPO = { 'video/mp4': 'mp4', 'video/quicktime': 'mov', 'video/x-m4v': 'm4v', 'video/webm': 'webm' };

            const pendientes = [];
            let trabajando = false;
            let subidas = 0;
            let fallidas = 0;

            function pintarConteo(c) {
                if (!c) return;
                cuenta.textContent = c.fotos + (c.fotos === 1 ? ' foto' : ' fotos') + ', ' + c.videos + (c.videos === 1 ? ' video' : ' videos');
                vacio.hidden = (c.fotos + c.videos) > 0;
            }

            function mensajeDeError(status, json) {
                if (status === 413) return 'El archivo pasa del límite del servidor.';
                if (status === 419) return 'La sesión expiró. Recarga la página.';
                if (json && json.errors) {
                    const primero = Object.values(json.errors)[0];
                    if (primero && primero[0]) return primero[0];
                }
                return (json && json.message) || 'No se pudo subir. Inténtalo otra vez.';
            }

            // POST con avance de subida (fetch no lo da).
            function enviar(url, datos, alAvanzar) {
                return new Promise(function (resolver, rechazar) {
                    const xhr = new XMLHttpRequest();
                    xhr.open('POST', url);
                    xhr.setRequestHeader('X-CSRF-TOKEN', csrf);
                    xhr.setRequestHeader('Accept', 'application/json');
                    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
                    if (alAvanzar) {
                        xhr.upload.addEventListener('progress', e => { if (e.lengthComputable) alAvanzar(e.loaded / e.total); });
                    }
                    xhr.onload = function () {
                        let json = {};
                        try { json = JSON.parse(xhr.responseText || '{}'); } catch (e) { /* respuesta no JSON */ }
                        if (xhr.status >= 200 && xhr.status < 300) return resolver(json);
                        const err = new Error(mensajeDeError(xhr.status, json));
                        err.status = xhr.status;
                        rechazar(err);
                    };
                    xhr.onerror = function () {
                        const err = new Error('Sin conexión. Revisa la señal e inténtalo otra vez.');
                        err.status = 0;
                        rechazar(err);
                    };
                    xhr.send(datos);
                });
            }

            // Un pedazo de video que falla por la red (o por el servidor) se
            // reintenta un par de veces antes de rendirse; uno rechazado no.
            async function conReintentos(fn) {
                for (let intento = 1; ; intento++) {
                    try {
                        return await fn();
                    } catch (err) {
                        if (intento >= 3 || (err.status >= 400 && err.status < 500)) throw err;
                        await new Promise(r => setTimeout(r, 1500 * intento));
                    }
                }
            }

            /* ---------- Renglones de la cola ---------- */
            function filaNueva(archivo, tipo) {
                const fila = document.createElement('div');
                fila.className = 'ev-fila';
                fila.innerHTML = '<span class="ev-fila-nombre"></span><span class="ev-fila-estado">En espera</span><div class="ev-fila-riel"><span></span></div>';
                fila.querySelector('.ev-fila-nombre').textContent = (tipo === 'video' ? 'Video · ' : '') + (archivo.name || 'foto');
                cola.appendChild(fila);
                cola.hidden = false;
                return fila;
            }

            function avance(fila, fraccion, texto) {
                fila.querySelector('.ev-fila-riel span').style.width = Math.round(Math.min(1, fraccion) * 100) + '%';
                fila.querySelector('.ev-fila-estado').textContent = texto;
            }

            function quitarFila(fila) {
                fila.remove();
                if (!cola.children.length) cola.hidden = true;
            }

            function marcarOk(fila) {
                fila.classList.add('is-ok');
                avance(fila, 1, 'Listo');
                setTimeout(() => quitarFila(fila), 1400);
            }

            function marcarError(tarea, mensaje) {
                const fila = tarea.fila;
                fila.classList.add('is-error');
                const estado = fila.querySelector('.ev-fila-estado');
                estado.textContent = mensaje;

                const reintentar = document.createElement('button');
                reintentar.type = 'button';
                reintentar.className = 'ev-reintentar';
                reintentar.textContent = 'Reintentar';
                reintentar.addEventListener('click', function () {
                    fila.classList.remove('is-error');
                    avance(fila, 0, 'En espera');
                    pendientes.push(tarea);
                    procesar();
                });

                const descartar = document.createElement('button');
                descartar.type = 'button';
                descartar.className = 'ev-reintentar';
                descartar.textContent = 'Descartar';
                descartar.addEventListener('click', () => quitarFila(fila));

                estado.append(reintentar, descartar);
            }

            /* ---------- Subidas ---------- */
            function encolar(archivos, tipo) {
                Array.from(archivos).forEach(function (archivo) {
                    pendientes.push({ archivo, tipo, fila: filaNueva(archivo, tipo) });
                });
                procesar();
            }

            async function procesar() {
                if (trabajando) return;
                trabajando = true;

                while (pendientes.length) {
                    const tarea = pendientes.shift();
                    try {
                        const json = tarea.tipo === 'video' ? await subirVideo(tarea) : await subirFoto(tarea);
                        agregarALaGaleria(json);
                        marcarOk(tarea.fila);
                        subidas++;
                    } catch (err) {
                        marcarError(tarea, err.message || 'No se pudo subir.');
                        fallidas++;
                    }
                }

                trabajando = false;

                if (fallidas) {
                    window.showToast((subidas ? 'Se subieron ' + subidas + '; ' : '') + (fallidas === 1 ? '1 archivo no se pudo subir.' : fallidas + ' archivos no se pudieron subir.') + ' Puedes reintentarlo.', 'warn');
                } else if (subidas) {
                    window.showToast(subidas === 1 ? 'Evidencia agregada.' : subidas + ' archivos agregados a la evidencia.', 'ok');
                }
                subidas = 0;
                fallidas = 0;
            }

            /*
            | Una foto de más de 10MB (cámaras de muchos megapíxeles) se achica
            | en el teléfono antes de mandarla, en vez de rechazarla. Las que
            | caben se mandan tal cual, sin perder calidad.
            */
            async function reducir(archivo) {
                try {
                    const imagen = await createImageBitmap(archivo);
                    const escala = Math.min(1, 3200 / Math.max(imagen.width, imagen.height));
                    const lienzo = document.createElement('canvas');
                    lienzo.width = Math.round(imagen.width * escala);
                    lienzo.height = Math.round(imagen.height * escala);
                    lienzo.getContext('2d').drawImage(imagen, 0, 0, lienzo.width, lienzo.height);
                    if (imagen.close) imagen.close();

                    const blob = await new Promise(r => lienzo.toBlob(r, 'image/jpeg', 0.85));
                    if (!blob) return archivo;

                    return new File([blob], (archivo.name || 'foto').replace(/\.[^.]+$/, '') + '.jpg', { type: 'image/jpeg' });
                } catch (e) {
                    return archivo;
                }
            }

            async function subirFoto(tarea) {
                if (tarea.archivo.type && !tarea.archivo.type.startsWith('image/')) {
                    throw new Error('No es una imagen.');
                }

                if (tarea.archivo.size > FOTO_MAX) {
                    avance(tarea.fila, 0, 'Reduciendo...');
                    tarea.archivo = await reducir(tarea.archivo);
                    if (tarea.archivo.size > FOTO_MAX) throw new Error('Pesa más de 10MB.');
                }

                const datos = new FormData();
                datos.append('foto', tarea.archivo, tarea.archivo.name || 'foto.jpg');

                return enviar(urlFoto, datos, f => avance(tarea.fila, f, 'Subiendo... ' + Math.round(f * 100) + '%'));
            }

            async function subirVideo(tarea) {
                const archivo = tarea.archivo;
                const porNombre = (archivo.name.includes('.') ? archivo.name.split('.').pop() : '').toLowerCase();
                const extension = EXT_VIDEO.includes(porNombre) ? porNombre : (EXT_POR_TIPO[archivo.type] || porNombre);

                if (!EXT_VIDEO.includes(extension)) throw new Error('Formato de video no permitido. Usa MP4, MOV o WEBM.');
                if (archivo.size > VIDEO_MAX) throw new Error('El video pasa de 150MB.');

                const uploadId = (crypto.randomUUID
                    ? crypto.randomUUID()
                    : (Date.now() + '-' + Math.random().toString(36).slice(2))).replace(/[^a-zA-Z0-9-]/g, '');
                const total = Math.max(1, Math.ceil(archivo.size / CHUNK));
                let json = null;

                for (let i = 0; i < total; i++) {
                    const datos = new FormData();
                    datos.append('chunk', archivo.slice(i * CHUNK, (i + 1) * CHUNK), 'chunk');
                    datos.append('upload_id', uploadId);
                    datos.append('index', i);
                    datos.append('total', total);
                    datos.append('extension', extension);
                    datos.append('nombre', archivo.name || ('video.' + extension));

                    json = await conReintentos(() => enviar(urlVideo, datos, function (f) {
                        const hecho = (i + f) / total;
                        avance(tarea.fila, hecho, 'Subiendo video... ' + Math.round(hecho * 100) + '%');
                    }));
                }

                return json;
            }

            function agregarALaGaleria(json) {
                if (!json || !json.html) return;
                const tipo = json.evidencia && json.evidencia.tipo === 'video' ? 'video' : 'foto';
                galeria[tipo].insertAdjacentHTML('beforeend', json.html);
                pintarConteo(json.conteo);
            }

            if (urlFoto) {
                tarjeta.querySelectorAll('[data-ev-fotos]').forEach(input => input.addEventListener('change', function () {
                    if (input.files.length) encolar(input.files, 'foto');
                    input.value = ''; // para poder volver a elegir las mismas
                }));
                tarjeta.querySelectorAll('[data-ev-videos]').forEach(input => input.addEventListener('change', function () {
                    if (input.files.length) encolar(input.files, 'video');
                    input.value = '';
                }));

                // En computadora también se pueden soltar archivos sobre la tarjeta.
                tarjeta.addEventListener('dragover', e => { if (e.dataTransfer?.types?.includes('Files')) e.preventDefault(); });
                tarjeta.addEventListener('drop', function (e) {
                    const archivos = Array.from(e.dataTransfer?.files || []);
                    if (!archivos.length) return;
                    e.preventDefault();
                    const videos = archivos.filter(a => a.type.startsWith('video/'));
                    const fotos = archivos.filter(a => !a.type.startsWith('video/'));
                    if (fotos.length) encolar(fotos, 'foto');
                    if (videos.length) encolar(videos, 'video');
                });

                // Salir a media subida dejaría fotos sin subir.
                window.addEventListener('beforeunload', function (e) {
                    if (!trabajando) return;
                    e.preventDefault();
                    e.returnValue = '';
                });
            }

            /* ---------- Quitar ---------- */
            tarjeta.addEventListener('click', async function (e) {
                const boton = e.target.closest('[data-borrar]');
                if (!boton) return;
                e.preventDefault();

                const item = boton.closest('.ev-item');
                const esVideo = item.dataset.tipo === 'video';
                const ok = await window.confirmModal({
                    title: esVideo ? '¿Quitar este video?' : '¿Quitar esta foto?',
                    message: 'Se borra de la evidencia de la orden, con todo y el archivo. No se puede deshacer.',
                    confirmText: 'Sí, quitar',
                    danger: true,
                });
                if (!ok) return;

                item.classList.add('is-borrando');
                try {
                    const r = await fetch(boton.dataset.borrar, {
                        method: 'DELETE',
                        headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    });
                    const json = await r.json().catch(() => ({}));
                    if (!r.ok) throw new Error(json.message || 'No se pudo quitar. Inténtalo otra vez.');

                    item.remove();
                    pintarConteo(json.conteo);
                    window.showToast(json.message || 'Evidencia eliminada.', 'ok');
                } catch (err) {
                    item.classList.remove('is-borrando');
                    window.showToast(err.message, 'warn');
                }
            });

            /* ---------- Visor de fotos en grande ---------- */
            const visor = document.getElementById('evVisor');
            if (!visor) return;
            document.body.appendChild(visor);

            const visorImg = document.getElementById('evVisorImg');
            const visorPie = document.getElementById('evVisorPie');
            let fotos = [];
            let actual = 0;
            let focoPrevio = null;

            function mostrar(i) {
                actual = (i + fotos.length) % fotos.length;
                const boton = fotos[actual];
                visorImg.src = boton.dataset.ver;
                visorPie.textContent = boton.dataset.pie + (fotos.length > 1 ? ' · ' + (actual + 1) + ' de ' + fotos.length : '');
                visor.querySelector('[data-visor="ant"]').hidden = fotos.length < 2;
                visor.querySelector('[data-visor="sig"]').hidden = fotos.length < 2;
            }

            function abrir(boton) {
                // Se toman al abrir: así entran también las que se acaban de subir.
                fotos = Array.from(galeria.foto.querySelectorAll('[data-ver]'));
                focoPrevio = boton;
                mostrar(Math.max(0, fotos.indexOf(boton)));
                visor.hidden = false;
                document.body.style.overflow = 'hidden';
                visor.querySelector('[data-visor="cerrar"]').focus();
            }

            function cerrar() {
                visor.hidden = true;
                visorImg.removeAttribute('src');
                document.body.style.overflow = '';
                if (focoPrevio && document.body.contains(focoPrevio)) focoPrevio.focus();
            }

            galeria.foto.addEventListener('click', function (e) {
                const boton = e.target.closest('[data-ver]');
                if (boton) abrir(boton);
            });

            visor.addEventListener('click', function (e) {
                const accion = e.target.closest('[data-visor]')?.dataset.visor;
                if (accion === 'cerrar' || e.target === visor) cerrar();
                else if (accion === 'ant') mostrar(actual - 1);
                else if (accion === 'sig') mostrar(actual + 1);
            });

            document.addEventListener('keydown', function (e) {
                if (visor.hidden) return;
                if (e.key === 'Escape') cerrar();
                else if (e.key === 'ArrowLeft') mostrar(actual - 1);
                else if (e.key === 'ArrowRight') mostrar(actual + 1);
            });

            // En el teléfono se pasa de foto deslizando el dedo.
            let inicioX = null;
            visor.addEventListener('touchstart', e => { inicioX = e.touches[0].clientX; }, { passive: true });
            visor.addEventListener('touchend', function (e) {
                if (inicioX === null) return;
                const dx = e.changedTouches[0].clientX - inicioX;
                inicioX = null;
                if (Math.abs(dx) > 50 && fotos.length > 1) mostrar(actual + (dx < 0 ? 1 : -1));
            });
        });
    </script>
@endsection
