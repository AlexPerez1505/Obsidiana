@extends('layouts.dashboard')

@section('title', 'Movimiento ' . $movimiento->folio)
@section('page-title', 'Movimiento ' . $movimiento->folio)
@section('page-sub', 'Gestion de Inventario > Entrada / Salida > Detalle')

@php
    $tonoTipo = match ($movimiento->tipoVista()) {
        'entrada' => 'badge--ok',
        'vendido' => 'badge--warn',
        'salida' => 'badge--danger',
        default => 'badge--info',
    };
    $esEntrada = $movimiento->movement_type === \App\Models\InventoryMovement::TYPE_ENTRY;
    $unidades = $movimiento->unidadesRelacionadas();
    $resumenComp = \App\Models\PiezaComponente::resumenPara($unidades->pluck('id'));
    $tonoProceso = [
        'pendiente' => 'badge--warn',
        'en_curso' => 'badge--info',
        'terminado' => 'badge--ok',
        'omitido' => 'badge--muted',
    ];
    $etiquetaEstadoProceso = [
        'pendiente' => 'pendiente',
        'en_curso' => 'en curso',
        'terminado' => 'hecho',
        'omitido' => 'omitido',
    ];
    // La evidencia del lote solo existe en entradas viejas: hoy cada pieza
    // trae la suya. Una salida no tiene evidencia propia.
    $hayEvidenciaLote = $esEntrada && (count($movimiento->evidenceUrls()) || $movimiento->videoUrl());
    $heroImage = $producto?->imagen_path
        ? asset('storage/'.$producto->imagen_path)
        : $unidades->flatMap(fn ($unidad) => $unidad->evidenceUrls())->first();
@endphp

@section('content')
    {{-- ===================== Encabezado + datos =====================
         Lo que es el movimiento y lo que se puede hacer con él, en un
         solo bloque. --}}
    <x-ui.card class="mv-cab">
        <div class="mv-cab-top">
            <div class="mv-hero-media">
                @if ($heroImage)
                    <img src="{{ $heroImage }}" alt="{{ $movimiento->item_name }}" loading="eager">
                @else
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4" aria-hidden="true"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/><path d="m3.3 7 8.7 5 8.7-5M12 22V12"/></svg>
                @endif
            </div>
            <div class="mv-cab-id">
                <span class="badge {{ $tonoTipo }}">{{ $movimiento->tipoVistaLabel() }}</span>
                <span class="muted">{{ $movimiento->movement_date->format('d/m/Y') }}</span>
                <h1 class="mv-hero-title">{{ $movimiento->item_name }}</h1>
                <p class="mv-hero-sub">Movimiento {{ $movimiento->folio }} · {{ $movimiento->quantity }} {{ $movimiento->unit }}</p>

                @if ($movimiento->ventaPendienteDeEntrega())
                    <span class="mv-aviso">
                        Ya se descontó del stock, pero el equipo sigue en el almacén:
                        sale cuando se firme su orden de salida.
                    </span>
                @elseif ($movimiento->entregada())
                    <span class="mv-aviso">
                        Salió del almacén el {{ $movimiento->entregado_en->format('d/m/Y \a \l\a\s H:i') }}.
                    </span>
                @endif
            </div>

            <div class="mv-acciones">
                {{-- La orden de salida es la que controla la entrega física:
                     su checklist y sus firmas. --}}
                @if (! empty($ordenSalida))
                    <a href="{{ route('inventory.salidas.show', $ordenSalida) }}" class="btn btn--ghost">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
                        {{ $movimiento->entregada() ? 'Ver orden firmada' : 'Ir a la orden de salida' }}
                    </a>
                @endif

                @if (! $editando && \App\Support\EdicionPropia::puedeEditarMovimiento(auth()->user(), $movimiento))
                    <a href="{{ route('inventory.movimientos.show', [$movimiento, 'editar' => 1]).'#editar' }}" class="btn btn--ghost">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                        Editar
                    </a>
                @endif

                {{-- Las etiquetas con QR se imprimen y se pegan en cada pieza. --}}
                @if ($movimiento->seriales()->exists())
                    <a href="{{ route('inventory.movimientos.etiquetas', $movimiento) }}" target="_blank" class="btn">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><path d="M14 14h3v3h-3zM19 19h2v2h-2z"/></svg>
                        Etiquetas QR
                    </a>
                @endif

                <a href="{{ $volverUrl }}" class="btn btn--ghost mv-back-action">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5"/><path d="M12 19l-7-7 7-7"/></svg>
                    Regresar
                </a>
            </div>
        </div>

        <dl class="mv-datos">
            <div class="mv-dato-ancho">
                <dt><span class="mv-dato-ico" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/><path d="m3.3 7 8.7 5 8.7-5M12 22V12"/></svg></span>Producto</dt>
                <dd>
                    @if ($producto)
                        <a href="{{ route('inventory.productos.show', ['producto' => $producto, 'return_to' => route('inventory.movimientos.show', $movimiento, false)]) }}" style="color:inherit;">{{ $movimiento->item_name }}</a>
                    @else
                        {{ $movimiento->item_name }}
                    @endif
                </dd>
            </div>
            <div><dt><span class="mv-dato-ico" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 3v18M3 12h18"/></svg></span>Cantidad</dt><dd>{{ $movimiento->quantity }} {{ $movimiento->unit }}</dd></div>
            <div><dt>Stock antes / después</dt><dd>{{ $movimiento->stock_before }} → {{ $movimiento->stock_after }}</dd></div>
            <div><dt><span class="mv-dato-ico" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="m3 10 9-7 9 7v10a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1Z"/><path d="M8 21v-6h8v6"/></svg></span>Almacén</dt><dd>{{ $movimiento->warehouse }}</dd></div>
            @if ($movimiento->fecha_fabricacion)
                <div><dt>Fabricación</dt><dd>{{ $movimiento->fecha_fabricacion->format('d/m/Y') }}</dd></div>
            @endif
            @if ($movimiento->fecha_caducidad)
                <div><dt>Caducidad</dt><dd>{{ $movimiento->fecha_caducidad->format('d/m/Y') }}</dd></div>
            @endif
            @if ($movimiento->reference)
                <div><dt>Referencia</dt><dd>{{ $movimiento->reference }}</dd></div>
            @endif
            <div><dt><span class="mv-dato-ico" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="8" r="3.5"/><path d="M4 21a8 8 0 0 1 16 0"/></svg></span>Registrado por</dt><dd>{{ $movimiento->creator?->name ?: '—' }}</dd></div>
        </dl>

        @if ($movimiento->notes)
            <p class="mv-notas"><b>Notas:</b> {{ $movimiento->notes }}</p>
        @endif
    </x-ui.card>

    @if ($editando)
        @include('structure.gestion_Inventario.entrada_salida._estilos')
        @include('structure.gestion_Inventario.entrada_salida._form_edicion')
    @endif

    <div class="mv-cuerpo">
        {{-- ===================== Unidades =====================
             Una fila por pieza con todo lo suyo: su evidencia (fotos y
             video), su estado y su ruta de procesos. También en las salidas:
             la evidencia vive en la pieza, así que se ve cómo había llegado
             cada equipo que salió. --}}
        <x-ui.card class="mv-unidades">
            <div class="mv-sec-cab">
                <x-ui.section-title style="margin:0;">
                    {{ $esEntrada ? 'Unidades y su evidencia' : 'Piezas que salieron y su evidencia' }}
                </x-ui.section-title>
                <span class="muted mv-cuenta">{{ $unidades->count() }} {{ $unidades->count() === 1 ? 'unidad' : 'unidades' }}</span>
            </div>
            <p class="muted mv-sec-nota">
                @if ($esEntrada)
                    Cada unidad trae su propia evidencia de cómo llegó. ¿Se asignaron mal los procesos?
                    Corrige la ruta de cada pieza aquí mismo; lo que ya se hizo se conserva.
                @else
                    La evidencia es de cada pieza, así que aquí se ve cómo había llegado cada equipo que salió.
                @endif
            </p>

            @forelse ($unidades as $serial)
                @php
                    // Fotos y video de la pieza, en el orden del visor.
                    $fotos = $serial->evidenceUrls();
                    $medios = collect($fotos)->map(fn ($url) => ['tipo' => 'foto', 'url' => $url]);
                    if ($serial->videoUrl()) {
                        $medios->push(['tipo' => 'video', 'url' => $serial->videoUrl()]);
                    }
                @endphp
                <div class="mv-unidad" @if ($medios->isNotEmpty()) data-medios="{{ $medios->toJson() }}" @endif>
                    {{-- Una portada; todo lo demás se ve en grande en el visor. --}}
                    @if ($medios->isNotEmpty())
                        <button type="button" class="mv-u-portada" data-galeria data-indice="0"
                                aria-label="Ver la evidencia de {{ $serial->no_serie ?: 'esta unidad' }}">
                            @if (count($fotos))
                                @if (in_array(strtolower(pathinfo(parse_url($fotos[0], PHP_URL_PATH) ?? '', PATHINFO_EXTENSION)), ['heic', 'heif']))
                                    <img data-foto-heic="{{ $fotos[0] }}" alt="Evidencia de la pieza" style="display:none;">
                                    <span data-foto-estado style="display:block; padding:8px; font-size:11px;">Preparando foto…</span>
                                @else
                                    <img src="{{ $fotos[0] }}" alt="Evidencia de la pieza" loading="lazy">
                                @endif
                            @else
                                <span class="mv-u-solo-video" aria-hidden="true">
                                    <svg viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>
                                </span>
                            @endif
                            <span class="mv-u-cuenta">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="m21 15-5-5L5 21"/></svg>
                                {{ count($fotos) }}
                                @if ($serial->videoUrl())
                                    <svg viewBox="0 0 24 24" fill="currentColor" style="margin-left:4px;"><path d="M8 5v14l11-7z"/></svg>
                                @endif
                            </span>
                        </button>
                    @else
                        <span class="mv-u-portada mv-u-vacia">Sin evidencia</span>
                    @endif

                    <div class="mv-u-id">
                        <strong class="{{ $serial->no_serie ? '' : 'mv-u-sinserie' }}">{{ $serial->no_serie ?: 'Sin número de serie' }}</strong>
                        @if ($serial->codigo)
                            <span class="mv-u-codigo">{{ $serial->codigo }}</span>
                        @endif
                        <span class="badge {{ $serial->vendido ? 'badge--danger' : 'badge--ok' }}">
                            {{ $serial->vendido ? 'Vendida' : $serial->estadoLabel() }}
                        </span>
                        @if ($medios->isNotEmpty())
                            <button type="button" class="mv-u-ver" data-galeria data-indice="0">
                                Ver evidencia
                                <span class="muted">· {{ count($fotos) }} {{ count($fotos) === 1 ? 'foto' : 'fotos' }}{{ $serial->videoUrl() ? ' y video' : '' }}</span>
                            </button>
                        @endif
                        @include('structure.gestion_Inventario._componentes_boton', ['pieza' => $serial, 'resumen' => $resumenComp])
                    </div>

                    <div class="mv-u-procesos">
                        <span class="mv-u-etq">Procesos</span>
                        @if ($serial->procesos->isEmpty())
                            <span class="muted" style="font-size:12.5px;">Sin procesos (directo a stock)</span>
                        @else
                            <div class="proc-chips">
                                @foreach ($serial->procesos as $paso)
                                    <span class="badge {{ $tonoProceso[$paso->estado] ?? 'badge--muted' }}">
                                        {{ $paso->nombre() }}
                                        <small>· {{ $etiquetaEstadoProceso[$paso->estado] ?? $paso->estado }}</small>
                                    </span>
                                @endforeach
                            </div>
                        @endif

                        @if ($esEntrada && ! $serial->vendido && $serial->estado !== 'baja')
                            @can('inventario.registrar')
                                <details class="proc-edit">
                                    <summary>Editar procesos</summary>
                                    <form method="POST" action="{{ route('inventory.procesos.definir', $serial) }}" class="proc-form">
                                        @csrf
                                        @method('PUT')
                                        @foreach (\App\Models\PiezaProceso::PROCESOS as $clave => $nombre)
                                            @php
                                                $actual = $serial->procesos->firstWhere('proceso', $clave);
                                                $resuelto = $actual && in_array($actual->estado, ['terminado', 'omitido'], true);
                                            @endphp
                                            <label class="proc-op">
                                                <input type="checkbox" name="procesos[]" value="{{ $clave }}"
                                                       @checked($actual) @disabled($resuelto)>
                                                <span>{{ $nombre }}</span>
                                                @if ($resuelto)
                                                    <small>({{ $actual->estado === 'terminado' ? 'ya hecho' : 'omitido' }} — se conserva)</small>
                                                @endif
                                            </label>
                                        @endforeach
                                        <p class="muted" style="font-size:11.5px; margin:4px 0 10px;">
                                            Marca todos los procesos por los que debe pasar. Los ya terminados
                                            se conservan aunque los desmarques.
                                        </p>
                                        <button type="submit" class="btn" style="padding:7px 14px;">Guardar procesos</button>
                                    </form>
                                </details>
                            @endcan
                        @endif
                    </div>
                </div>
            @empty
                <p class="muted" style="margin:0;">Este movimiento no tiene unidades registradas.</p>
            @endforelse
        </x-ui.card>

        {{-- ===================== Lateral ===================== --}}
        <div class="mv-lateral">
            @if ($movimiento->signatureUrl())
                <x-ui.card>
                    <x-ui.section-title style="margin:0 0 10px;">Firma</x-ui.section-title>
                    <img src="{{ $movimiento->signatureUrl() }}" alt="Firma digital" class="mv-firma">
                    <p class="muted mv-firma-quien">{{ $movimiento->creator?->name ?: 'Quien registró' }}</p>
                </x-ui.card>
            @endif

            @if ($hayEvidenciaLote)
                <x-ui.card>
                    <x-ui.section-title style="margin:0 0 10px;">Evidencia de la entrada</x-ui.section-title>
                    @if (count($movimiento->evidenceUrls()))
                        <div class="mv-lote">
                            @foreach ($movimiento->evidenceUrls() as $url)
                                <a href="{{ $url }}" target="_blank"><img src="{{ $url }}" alt="Evidencia de entrada" loading="lazy"></a>
                            @endforeach
                        </div>
                    @else
                        <p class="muted" style="margin:0;">Este movimiento no tiene evidencia fotográfica del lote.</p>
                    @endif

                    @if ($movimiento->videoUrl())
                        <div class="muted" style="font-size:12.5px; margin:12px 0 6px;">Video de verificación</div>
                        <video src="{{ $movimiento->videoUrl() }}" controls class="mv-lote-video"></video>
                    @endif
                </x-ui.card>
            @endif

            @if ($producto)
                <a href="{{ route('inventory.productos.show', ['producto' => $producto, 'return_to' => route('inventory.movimientos.show', $movimiento, false)]) }}" class="mv-ficha">
                    <span>Ver la ficha del producto y su historial</span>
                    <span aria-hidden="true">→</span>
                </a>
            @endif
        </div>
    </div>

    @include('partials.galeria')
    @include('structure.gestion_Inventario._componentes')

    <style>
        /* Cabecera de detalle: imagen, identidad y acciones en un solo panel. */
        .mv-cab { padding:18px; border:1px solid #176fc1; border-radius:18px; background:radial-gradient(circle at 78% 12%, rgba(22,131,247,.18), transparent 32%), linear-gradient(135deg,#0d1b32 0%,#102d52 100%); box-shadow:0 12px 28px rgba(2,20,52,.2); }
        .mv-cab-top { display:grid; grid-template-columns:154px minmax(0,1fr) 210px; align-items:center; gap:18px; }
        .mv-hero-media { display:flex; align-items:center; justify-content:center; width:154px; height:154px; overflow:hidden; border:1px solid #2b75b8; border-radius:16px; background:linear-gradient(145deg,#122b4b,#09172b); color:#83a5c9; box-shadow:inset 0 0 0 5px rgba(10,26,48,.45); }
        .mv-hero-media img { width:100%; height:100%; object-fit:contain; display:block; }
        .mv-hero-media svg { width:58px; height:58px; }
        .mv-cab-id { display:flex; align-items:center; gap:8px 10px; flex-wrap:wrap; min-width:0; }
        .mv-hero-title { flex-basis:100%; margin:4px 0 0; color:#f7fbff; font-size:clamp(24px,3vw,36px); line-height:1.05; letter-spacing:-.02em; }
        .mv-hero-sub { flex-basis:100%; margin:0; color:#a9c1dd; font-size:14px; }
        .mv-acciones { display:flex; flex-direction:column; align-items:stretch; gap:8px; min-width:0; }
        .mv-acciones .btn, .mv-acciones .btn--ghost { justify-content:center; width:100%; }
        .mv-acciones .btn-icono { align-self:flex-end; }
        .mv-acciones .mv-back-action { border-color:#2a527c; color:#b6cbe4; }
        .mv-acciones .mv-back-action:hover { border-color:#1683f7; color:#fff; background:#12365f; }
        .mv-datos dt { display:flex; align-items:center; gap:7px; }
        .mv-dato-ico { display:inline-flex; align-items:center; justify-content:center; flex:0 0 25px; width:25px; height:25px; border-radius:7px; background:#12365f; color:#6db8ff; }
        .mv-dato-ico svg { width:14px; height:14px; }
        .mv-stock { color:#55e68b; }
        .mv-datos { grid-template-columns:repeat(4,minmax(0,1fr)); margin-top:18px; padding:16px 0 0; border-top-color:rgba(144,186,229,.25); }
        .mv-datos dd { color:#f1f5f9; }
        .mv-unidades, .mv-lateral > .card { border-color:#203f67; background:linear-gradient(145deg,#0e1d34,#101f38); }
        .mv-unidad { border-color:#294e7b; background:linear-gradient(145deg,#122846,#0d1b31); }
        .mv-ficha { border-color:#245789; background:linear-gradient(135deg,#102945,#123c6e); }
        @media (max-width:900px) {
            .mv-cab-top { grid-template-columns:120px minmax(0,1fr); }
            .mv-hero-media { width:120px; height:120px; }
            .mv-acciones { grid-column:1 / -1; flex-direction:row; flex-wrap:wrap; }
            .mv-acciones .btn, .mv-acciones .btn--ghost { width:auto; flex:1 1 auto; }
            .mv-acciones .btn-icono { margin-left:auto; }
            .mv-datos { grid-template-columns:repeat(2,minmax(0,1fr)); }
        }
        @media (max-width:560px) {
            .mv-cab-top { grid-template-columns:1fr; }
            .mv-hero-media { width:100%; height:150px; }
            .mv-cab-id, .mv-acciones { grid-column:auto; }
            .mv-datos { grid-template-columns:1fr 1fr; }
        }

        /* Encabezado: identidad y acciones arriba, datos abajo */
        .mv-cab { margin-bottom:18px; }
        .mv-cab-top { display:flex; align-items:center; justify-content:space-between; gap:12px 18px; flex-wrap:wrap; }
        .mv-cab-id { display:flex; align-items:center; gap:8px 10px; flex-wrap:wrap; min-width:0; }
        .mv-aviso { flex-basis:100%; color:var(--muted); font-size:13px; }
        .mv-acciones { display:flex; align-items:center; gap:8px; flex-wrap:wrap; }

        .mv-datos { display:grid; grid-template-columns:repeat(auto-fill, minmax(160px, 1fr)); gap:12px 20px;
                    margin:16px 0 0; padding-top:16px; border-top:1px solid var(--border); }
        .mv-datos > div { min-width:0; }
        .mv-dato-ancho { grid-column:span 2; }
        .mv-datos dt { color:var(--muted); font-size:12.5px; }
        .mv-datos dd { margin:2px 0 0; font-weight:700; overflow-wrap:anywhere; }
        .mv-notas { margin:14px 0 0; color:var(--muted); }

        /* Cuerpo: unidades a la izquierda, firma y evidencia del lote al lado */
        .mv-cuerpo { display:grid; grid-template-columns:minmax(0, 1fr); gap:18px; align-items:start; }
        @media (min-width: 1000px) {
            .mv-cuerpo { grid-template-columns:minmax(0, 1fr) 300px; }
        }
        .mv-lateral { display:flex; flex-direction:column; gap:18px; min-width:0; }

        .mv-sec-cab { display:flex; align-items:baseline; justify-content:space-between; gap:10px; }
        .mv-cuenta { font-size:13px; white-space:nowrap; }
        .mv-sec-nota { margin:4px 0 0; font-size:12.5px; }

        /* Una unidad: fotos | serie y estado | procesos */
        /* Una unidad: portada | serie y estado | procesos. En móvil, portada y
           serie lado a lado y los procesos abajo a todo lo ancho. */
        .mv-unidad { display:grid; gap:14px 18px; padding:16px; margin-top:12px; align-items:start;
                     border:1px solid var(--border); border-radius:12px; background:var(--surface);
                     grid-template-columns:96px minmax(0, 1fr); }
        .mv-unidad .mv-u-procesos { grid-column:1 / -1; padding-top:12px; border-top:1px dashed var(--border); }
        @media (min-width: 760px) {
            .mv-unidad { grid-template-columns:112px minmax(160px, 1fr) minmax(220px, 1.2fr); }
            .mv-unidad .mv-u-procesos { grid-column:auto; padding-top:0; border-top:0;
                                        padding-left:18px; border-left:1px solid var(--border); align-self:stretch; }
        }

        .mv-u-portada { position:relative; display:block; width:100%; aspect-ratio:1; padding:0; overflow:hidden;
                        border:1px solid var(--border); border-radius:10px; background:var(--surface-2); cursor:pointer; }
        .mv-u-portada img { width:100%; height:100%; object-fit:cover; display:block; transition:transform .25s ease; }
        .mv-u-portada:hover img { transform:scale(1.04); }
        .mv-u-portada:focus-visible { outline:2px solid var(--primary); outline-offset:2px; }
        .mv-u-solo-video { display:flex; align-items:center; justify-content:center; width:100%; height:100%;
                           background:#0f172a; color:#fff; }
        .mv-u-solo-video svg { width:30px; height:30px; }
        .mv-u-cuenta { position:absolute; left:6px; bottom:6px; display:inline-flex; align-items:center; gap:4px;
                       padding:3px 7px; border-radius:999px; background:rgba(15,23,42,.72); color:#fff;
                       font-size:11.5px; font-weight:700; }
        .mv-u-cuenta svg { width:12px; height:12px; }
        .mv-u-vacia { display:flex; align-items:center; justify-content:center; cursor:default; border-style:dashed;
                      color:var(--muted); font-size:12px; text-align:center; padding:6px; }

        .mv-u-codigo { font-family:ui-monospace, Consolas, monospace; font-size:12px; color:var(--muted); }
        .mv-u-sinserie { color:var(--muted); font-weight:600; }
        .mv-u-ver { padding:0; border:0; background:none; color:var(--primary); font-size:13px; font-weight:600;
                    cursor:pointer; text-align:left; }
        .mv-u-ver .muted { font-weight:500; }
        .mv-u-ver:hover { text-decoration:underline; }
        .mv-u-id { display:flex; flex-direction:column; align-items:flex-start; gap:7px; min-width:0; }
        .mv-u-id strong { overflow-wrap:anywhere; }
        .mv-u-procesos { min-width:0; }
        .mv-u-etq { display:block; margin-bottom:5px; color:var(--muted); font-size:11px; font-weight:700;
                    letter-spacing:.06em; text-transform:uppercase; }

        /* Lateral */
        .mv-firma { display:block; width:100%; max-width:260px; border-radius:10px; border:1px solid var(--border); background:#fff; }
        .mv-firma-quien { margin:8px 0 0; font-size:12.5px; }
        .mv-lote { display:grid; grid-template-columns:repeat(auto-fill, minmax(80px, 1fr)); gap:8px; }
        .mv-lote img { width:100%; aspect-ratio:1; object-fit:cover; display:block; border-radius:9px; border:1px solid var(--border); }
        .mv-lote-video { width:100%; max-height:170px; object-fit:cover; background:#000;
                         border-radius:10px; border:1px solid var(--border); }
        .mv-ficha { display:flex; align-items:center; justify-content:space-between; gap:10px; padding:13px 16px;
                    border:1px solid var(--border); border-radius:12px; background:var(--surface);
                    color:var(--primary); font-weight:600; font-size:14px; text-decoration:none; }
        .mv-ficha:hover { border-color:var(--primary); }

        .proc-chips { display:flex; flex-wrap:wrap; gap:6px; }
        .proc-chips .badge small { font-weight:400; opacity:.85; }

        .proc-edit { margin-top:10px; }
        .proc-edit > summary { display:inline-flex; align-items:center; gap:6px; cursor:pointer;
            font-size:12.5px; font-weight:600; color:var(--primary); list-style:none; user-select:none; }
        .proc-edit > summary::-webkit-details-marker { display:none; }
        .proc-edit > summary::before { content:'✎'; font-size:12px; }
        .proc-edit[open] > summary::before { content:'▾'; }
        .proc-form { margin-top:10px; padding:12px; border:1px solid var(--border);
            border-radius:10px; background:var(--surface-2); max-width:340px; }
        .proc-op { display:flex; align-items:center; gap:8px; padding:5px 0; font-size:13.5px; }
        .proc-op input { width:16px; height:16px; margin:0; accent-color:var(--primary); }
        .proc-op small { color:var(--muted); font-size:11.5px; }

        /* Ajustes finales del nuevo encabezado, después de los estilos base. */
        .mv-cab { padding:18px; border:1px solid #176fc1; border-radius:18px; background:radial-gradient(circle at 78% 12%, rgba(22,131,247,.18), transparent 32%), linear-gradient(135deg,#0d1b32 0%,#102d52 100%); box-shadow:0 12px 28px rgba(2,20,52,.2); }
        .mv-cab-top { display:grid; grid-template-columns:154px minmax(0,1fr) 210px; align-items:center; gap:18px; }
        .mv-hero-media { display:flex; align-items:center; justify-content:center; width:154px; height:154px; overflow:hidden; border:1px solid #2b75b8; border-radius:16px; background:linear-gradient(145deg,#122b4b,#09172b); color:#83a5c9; box-shadow:inset 0 0 0 5px rgba(10,26,48,.45); }
        .mv-hero-media img { width:100%; height:100%; object-fit:contain; display:block; }
        .mv-hero-media svg { width:58px; height:58px; }
        .mv-cab-id { display:flex; align-items:center; gap:8px 10px; flex-wrap:wrap; min-width:0; }
        .mv-hero-title { flex-basis:100%; margin:4px 0 0; color:#f7fbff; font-size:clamp(24px,3vw,36px); line-height:1.05; letter-spacing:-.02em; }
        .mv-hero-sub { flex-basis:100%; margin:0; color:#a9c1dd; font-size:14px; }
        .mv-acciones { display:flex; flex-direction:column; align-items:stretch; gap:8px; min-width:0; }
        .mv-acciones .btn, .mv-acciones .btn--ghost { justify-content:center; width:100%; }
        .mv-acciones .btn-icono { align-self:flex-end; }
        .mv-stock { color:#55e68b; }
        .mv-datos { grid-template-columns:repeat(4,minmax(0,1fr)); margin-top:18px; padding:16px 0 0; border-top-color:rgba(144,186,229,.25); }
        .mv-datos dd { color:#f1f5f9; }
        .mv-unidades, .mv-lateral > .card { border-color:#203f67; background:linear-gradient(145deg,#0e1d34,#101f38); }
        .mv-unidad { border-color:#294e7b; background:linear-gradient(145deg,#122846,#0d1b31); }
        .mv-ficha { border-color:#245789; background:linear-gradient(135deg,#102945,#123c6e); }
        @media (max-width:900px) {
            .mv-cab-top { grid-template-columns:120px minmax(0,1fr); }
            .mv-hero-media { width:120px; height:120px; }
            .mv-acciones { grid-column:1 / -1; flex-direction:row; flex-wrap:wrap; }
            .mv-acciones .btn, .mv-acciones .btn--ghost { width:auto; flex:1 1 auto; }
            .mv-acciones .btn-icono { margin-left:auto; }
            .mv-datos { grid-template-columns:repeat(2,minmax(0,1fr)); }
        }
        @media (max-width:560px) {
            .mv-cab-top { grid-template-columns:1fr; }
            .mv-hero-media { width:100%; height:150px; }
            .mv-cab-id, .mv-acciones { grid-column:auto; }
            .mv-datos { grid-template-columns:1fr 1fr; }
        }

        /* Adaptación del detalle al tema claro: el encabezado conserva su
           identidad azul y el contenido inferior usa superficies claras. */
        :root[data-theme="light"] .mv-unidades,
        :root[data-theme="light"] .mv-lateral > .card {
            border-color:#d6e1ee;
            background:#fff;
            color:#1e293b;
            box-shadow:0 8px 20px rgba(15,23,42,.06);
        }
        :root[data-theme="light"] .mv-unidad {
            border-color:#cbd8e8;
            background:#f8fbff;
        }
        :root[data-theme="light"] .mv-u-id strong { color:#1e293b; }
        :root[data-theme="light"] .mv-unidades .muted,
        :root[data-theme="light"] .mv-lateral .muted,
        :root[data-theme="light"] .mv-u-codigo,
        :root[data-theme="light"] .mv-u-etq,
        :root[data-theme="light"] .mv-u-sinserie,
        :root[data-theme="light"] .mv-sec-nota { color:#64748b; }
        :root[data-theme="light"] .mv-u-portada {
            background:#f1f5f9;
            border-color:#cbd5e1;
        }
        :root[data-theme="light"] .mv-u-procesos { border-color:#dbe5ef; }
        :root[data-theme="light"] .proc-form {
            background:#f8fafc;
            border-color:#dbe5ef;
        }
        :root[data-theme="light"] .mv-firma { border-color:#cbd5e1; }
        :root[data-theme="light"] .mv-ficha {
            border-color:#b9d5f2;
            background:#eff6ff;
            color:#0b74d1;
        }
        :root[data-theme="light"] .mv-acciones .mv-back-action {
            background:#fff;
            border-color:#e2e8f0;
            color:#94a3b8;
        }
        :root[data-theme="light"] .mv-acciones .mv-back-action:hover {
            background:#eff6ff;
            border-color:#60a5fa;
            color:#2563eb;
        }

        @media (max-width: 560px) {
            .mv-dato-ancho { grid-column:auto; }
            .mv-datos { grid-template-columns:repeat(2, minmax(0, 1fr)); }
        }
    </style>
@endsection
