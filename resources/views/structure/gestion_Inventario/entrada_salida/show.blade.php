@extends('layouts.dashboard')

@section('title', 'Movimiento ' . $movimiento->folio)
@section('page-title', 'Movimiento ' . $movimiento->folio)
@section('page-sub', 'Gestion de Inventario > Entrada / Salida > Detalle')

@section('content')
    <div class="dashboard-card" style="margin-bottom:18px;">
        <div style="display:flex; align-items:center; justify-content:space-between; gap:18px; flex-wrap:wrap;">
            <div>
                @php
                    $tonoTipo = match ($movimiento->tipoVista()) {
                        'entrada' => 'badge--ok',
                        'vendido' => 'badge--warn',
                        'salida' => 'badge--danger',
                        default => 'badge--info',
                    };
                @endphp
                <span class="badge {{ $tonoTipo }}">{{ $movimiento->tipoVistaLabel() }}</span>
                <span style="margin-left:8px; color:var(--muted);">{{ $movimiento->movement_date->format('d/m/Y') }}</span>

                @if ($movimiento->ventaPendienteDeEntrega())
                    <div class="muted" style="margin-top:6px; font-size:13px;">
                        Ya se descontó del stock, pero el equipo sigue en el almacén:
                        sale cuando se firme su orden de salida.
                    </div>
                @elseif ($movimiento->entregada())
                    <div class="muted" style="margin-top:6px; font-size:13px;">
                        Salió del almacén el {{ $movimiento->entregado_en->format('d/m/Y \a \l\a\s H:i') }}.
                    </div>
                @endif
            </div>
            <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
                {{-- La orden de salida es la que controla la entrega física:
                     su checklist y sus firmas. --}}
                @if (! empty($ordenSalida))
                    <a href="{{ route('inventory.salidas.show', $ordenSalida) }}" class="btn btn--ghost">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
                        {{ $movimiento->entregada() ? 'Ver orden firmada' : 'Ir a la orden de salida' }}
                    </a>
                @endif

                {{-- Las etiquetas con QR se imprimen y se pegan en cada pieza. --}}
                @if ($movimiento->seriales()->exists())
                    <a href="{{ route('inventory.movimientos.etiquetas', $movimiento) }}" target="_blank" class="btn">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><path d="M14 14h3v3h-3zM19 19h2v2h-2z"/></svg>
                        Etiquetas QR
                    </a>
                @endif

                <a href="{{ route('inventory.movimientos.index') }}" class="btn-icono" title="Regresar" aria-label="Regresar">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5"/><path d="M12 19l-7-7 7-7"/></svg>
                </a>
            </div>
        </div>
    </div>

    <x-ui.card style="margin-bottom:18px;">
        <x-ui.section-title style="margin:0 0 16px;">Datos del movimiento</x-ui.section-title>
        <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(200px, 1fr)); gap:14px;">
            <div>
                <div class="muted" style="font-size:12.5px;">Producto</div>
                <div style="font-weight:700;">
                    @if ($producto)
                        <a href="{{ route('inventory.productos.show', $producto) }}" style="color:inherit;">{{ $movimiento->item_name }}</a>
                    @else
                        {{ $movimiento->item_name }}
                    @endif
                </div>
            </div>
            <div><div class="muted" style="font-size:12.5px;">Cantidad</div><div style="font-weight:700;">{{ $movimiento->quantity }} {{ $movimiento->unit }}</div></div>
            <div><div class="muted" style="font-size:12.5px;">Stock antes / después</div><div style="font-weight:700;">{{ $movimiento->stock_before }} → {{ $movimiento->stock_after }}</div></div>
            <div><div class="muted" style="font-size:12.5px;">Almacén</div><div style="font-weight:700;">{{ $movimiento->warehouse }}</div></div>
            @if ($movimiento->reference)
                <div><div class="muted" style="font-size:12.5px;">Referencia</div><div style="font-weight:700;">{{ $movimiento->reference }}</div></div>
            @endif
            <div><div class="muted" style="font-size:12.5px;">Registrado por</div><div style="font-weight:700;">{{ $movimiento->creator?->name ?: '—' }}</div></div>
        </div>

        @if ($movimiento->notes)
            <p style="margin:16px 0 0; color:var(--muted);"><b>Notas:</b> {{ $movimiento->notes }}</p>
        @endif
    </x-ui.card>

    {{-- La evidencia del lote solo existe en entradas viejas: hoy cada pieza
         trae la suya. Una salida no tiene evidencia propia. --}}
    @if ($movimiento->movement_type === 'entrada' && (count($movimiento->evidenceUrls()) || $movimiento->videoUrl()))
        <x-ui.card style="margin-bottom:18px;">
            <x-ui.section-title style="margin:0 0 16px;">Evidencia de la entrada</x-ui.section-title>
            @if (count($movimiento->evidenceUrls()))
                <div style="display:flex; flex-wrap:wrap; gap:12px;">
                    @foreach ($movimiento->evidenceUrls() as $url)
                        <a href="{{ $url }}" target="_blank">
                            <img src="{{ $url }}" alt="Evidencia de entrada" style="width:140px; height:140px; object-fit:cover; border-radius:10px; border:1px solid var(--border);">
                        </a>
                    @endforeach
                </div>
            @else
                <p class="muted">Este movimiento no tiene evidencia fotográfica del lote.</p>
            @endif

            @if ($movimiento->videoUrl())
                <div style="margin-top:16px;">
                    <div class="muted" style="font-size:12.5px; margin-bottom:6px;">Video de verificación</div>
                    <video src="{{ $movimiento->videoUrl() }}" controls style="max-width:320px; border-radius:10px; border:1px solid var(--border);"></video>
                </div>
            @endif
        </x-ui.card>
    @endif

    {{--
        Evidencia pieza por pieza. También en las salidas: la evidencia vive
        en la pieza, así que al abrir una salida se puede ver cómo había
        llegado cada equipo que salió. Antes esto solo se mostraba en las
        entradas y una salida se veía vacía.
    --}}
    <x-ui.card style="margin-bottom:18px;">
        <x-ui.section-title style="margin:0 0 6px;">
            {{ $movimiento->movement_type === 'entrada' ? 'Evidencia por unidad' : 'Piezas que salieron y su evidencia' }}
        </x-ui.section-title>
        <p class="muted" style="margin:0 0 16px; font-size:13.5px;">
            @if ($movimiento->movement_type === 'entrada')
                Cada unidad que llegó tiene su propia evidencia, para revisar cómo llegó cada una por separado.
            @else
                La evidencia es de cada pieza, así que aquí se ve cómo había llegado cada equipo que salió.
            @endif
        </p>

            @forelse ($movimiento->unidadesRelacionadas() as $serial)
                <div style="border:1px solid var(--border); border-radius:12px; padding:14px; margin-bottom:12px;">
                    <div style="display:flex; align-items:center; justify-content:between; gap:10px; margin-bottom:10px;">
                        <strong>{{ $serial->no_serie ?: 'Unidad sin serial capturado' }}</strong>
                        <span class="badge {{ $serial->vendido ? 'badge--danger' : 'badge--ok' }}" style="margin-left:8px;">
                            {{ $serial->vendido ? 'Vendida' : 'Disponible' }}
                        </span>
                    </div>

                    @if (count($serial->evidenceUrls()))
                        <div style="display:flex; flex-wrap:wrap; gap:10px;">
                            @foreach ($serial->evidenceUrls() as $url)
                                <a href="{{ $url }}" target="_blank">
                                    <img src="{{ $url }}" alt="Evidencia de la unidad" style="width:110px; height:110px; object-fit:cover; border-radius:9px; border:1px solid var(--border);">
                                </a>
                            @endforeach
                        </div>
                    @else
                        <p class="muted" style="font-size:13px; margin:0;">Esta unidad no tiene fotos de evidencia.</p>
                    @endif

                    @if ($serial->videoUrl())
                        <div style="margin-top:10px;">
                            <video src="{{ $serial->videoUrl() }}" controls style="max-width:220px; border-radius:9px; border:1px solid var(--border);"></video>
                        </div>
                    @endif
                </div>
            @empty
                <p class="muted">Este movimiento no tiene unidades registradas.</p>
            @endforelse

        @if ($movimiento->signatureUrl())
            <div style="margin-top:6px;">
                <div class="muted" style="font-size:12.5px; margin-bottom:6px;">Firma de {{ $movimiento->creator?->name ?: 'quien registró' }}</div>
                <img src="{{ $movimiento->signatureUrl() }}" alt="Firma digital" style="max-width:260px; border-radius:10px; border:1px solid var(--border); background:#fff;">
            </div>
        @endif
    </x-ui.card>

    @php
        $esEntrada = $movimiento->movement_type === \App\Models\InventoryMovement::TYPE_ENTRY;
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
    @endphp

    <x-ui.card>
        <x-ui.section-title style="margin:0 0 4px;">Unidades de este movimiento</x-ui.section-title>
        @if ($esEntrada)
            <p class="muted" style="font-size:12.5px; margin:0 0 16px;">
                ¿Se asignaron mal los procesos al dar la entrada? Puedes corregir la ruta
                de cada pieza aquí mismo. Lo que ya se hizo se conserva.
            </p>
        @endif

        <div style="overflow-x:auto;">
            <table>
                <thead>
                    <tr>
                        <th>No. Serie</th>
                        <th>Estado</th>
                        <th>Procesos</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($movimiento->unidadesRelacionadas() as $serial)
                        <tr>
                            <td>{{ $serial->no_serie ?: '— (sin serial capturado)' }}</td>
                            <td>
                                <span class="badge {{ $serial->vendido ? 'badge--danger' : 'badge--ok' }}">
                                    {{ $serial->vendido ? 'Vendida' : $serial->estadoLabel() }}
                                </span>
                            </td>
                            <td>
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
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="3" style="text-align:center; padding:20px; color:var(--muted);">Sin unidades registradas.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($producto)
            <p style="margin:16px 0 0;">
                <a href="{{ route('inventory.productos.show', $producto) }}">Ver la ficha del producto y su historial →</a>
            </p>
        @endif
    </x-ui.card>

    <style>
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
    </style>
@endsection
