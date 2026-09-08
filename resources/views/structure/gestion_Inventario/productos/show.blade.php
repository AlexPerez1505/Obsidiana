@extends('layouts.dashboard')

@php
    $nombre = trim(collect([$producto->tipo_equipo, $producto->subtipo])->filter()->implode(' · ')) ?: 'Producto';
    $marcaModelo = trim(collect([$producto->marca, $producto->modelo])->filter()->implode(' '));
@endphp

@section('title', $nombre)
@section('page-title', $nombre)
@section('page-sub', $marcaModelo ?: 'Ficha del producto')

@push('head')
    <style>
        .pf-cab { display:flex; align-items:flex-start; gap:18px; flex-wrap:wrap; }
        .pf-cab img, .pf-cab .sinfoto { width:96px; height:96px; border-radius:12px; flex:0 0 96px;
                                        object-fit:cover; border:1px solid var(--border); background:var(--surface-2);
                                        display:flex; align-items:center; justify-content:center; color:var(--muted); }
        .pf-cab .txt { flex:1; min-width:200px; }
        .pf-cab h2 { margin:0 0 3px; font-size:19px; font-weight:600; line-height:1.25; }
        .pf-cab .mm { margin:0; color:var(--muted); font-size:14px; }
        .pf-cab .chips { display:flex; flex-wrap:wrap; gap:7px; margin-top:12px; }

        /* El stock, desglosado: de dónde sale el número. */
        .pf-stock { display:grid; grid-template-columns:repeat(auto-fit,minmax(140px,1fr)); gap:1px;
                    background:var(--border); border:1px solid var(--border); border-radius:12px;
                    overflow:hidden; margin-top:18px; }
        .pf-stock > div { padding:14px 16px; background:var(--surface); }
        .pf-stock .e { display:block; color:var(--muted); font-size:11px; font-weight:700;
                       letter-spacing:.06em; text-transform:uppercase; }
        .pf-stock .v { display:block; margin-top:4px; font-size:24px; font-weight:700;
                       font-variant-numeric:tabular-nums; line-height:1.1; }
        .pf-stock .d { display:block; margin-top:2px; color:var(--muted); font-size:12px; }
        .pf-stock .v.ok { color:var(--green); }
        .pf-stock .v.proceso { color:var(--ambar, #b45309); }

        /* Kardex */
        .kx { width:100%; border-collapse:collapse; }
        .kx th { text-align:left; padding:10px 12px; font-size:11px; font-weight:700;
                 letter-spacing:.06em; text-transform:uppercase; color:var(--muted);
                 border-bottom:1px solid var(--border); white-space:nowrap; }
        .kx td { padding:12px; border-bottom:1px solid var(--border); font-size:13.5px; vertical-align:top; }
        .kx tr:last-child td { border-bottom:none; }
        .kx .folio { font-family:ui-monospace, Consolas, monospace; font-weight:700; font-size:12.5px; }
        .kx .num { text-align:right; white-space:nowrap; font-variant-numeric:tabular-nums; }
        .kx .mov { font-weight:700; font-variant-numeric:tabular-nums; white-space:nowrap; }
        .kx .mov.entra { color:var(--green); }
        .kx .mov.sale { color:var(--danger); }
        .kx .saldo { color:var(--muted); font-size:12.5px; white-space:nowrap; }
        .kx .nota { display:block; margin-top:3px; color:var(--muted); font-size:12.5px; }

        .pz-cod { font-family:ui-monospace, Consolas, monospace; font-weight:700; font-size:12.5px; }

        .tbl-scroll { overflow-x:auto; }
        @media (max-width:640px) {
            .pf-cab img, .pf-cab .sinfoto { width:64px; height:64px; flex:0 0 64px; }
        }
    </style>
@endpush

@section('content')
    <x-ui.page-header :title="$producto->id ? 'Ficha del producto' : 'Producto'"
                      :back="route('inventory.productos.index')">
        <a href="{{ route('inventory.movimientos.create') }}" class="btn btn--ghost">Registrar entrada</a>
        <a href="{{ route('inventory.productos.edit', $producto) }}" class="btn-icono" title="Editar producto" aria-label="Editar producto">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
        </a>
    </x-ui.page-header>

    {{-- ===================== Identidad y stock ===================== --}}
    <x-ui.card style="margin-bottom:18px;">
        <div class="pf-cab">
            @if ($producto->imagen_path)
                <img src="{{ asset('storage/'.$producto->imagen_path) }}" alt="{{ $nombre }}">
            @else
                <span class="sinfoto">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" width="26" height="26"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><path d="m3.3 7 8.7 5 8.7-5M12 22V12"/></svg>
                </span>
            @endif

            <div class="txt">
                <h2>{{ $nombre }}</h2>
                @if ($marcaModelo)<p class="mm">{{ $marcaModelo }}</p>@endif
                @if ($producto->descripcion)
                    <p class="mm" style="margin-top:6px;">{{ $producto->descripcion }}</p>
                @endif

                <div class="chips">
                    @if (\App\Support\PrecioVisible::para())
                        <span class="badge">{{ \App\Support\PrecioVisible::texto($producto) }}</span>
                    @endif
                    @if ($producto->es_serializado)
                        <span class="badge badge--info">Se maneja pieza por pieza</span>
                    @endif
                </div>
            </div>
        </div>

        {{-- El stock no es un número suelto: se cuenta desde las piezas. --}}
        <div class="pf-stock">
            <div>
                <span class="e">Disponible</span>
                <span class="v ok">{{ $conteos['disponibles'] }}</span>
                <span class="d">Se puede vender hoy</span>
            </div>
            <div>
                <span class="e">En proceso</span>
                <span class="v {{ $conteos['en_proceso'] ? 'proceso' : '' }}">{{ $conteos['en_proceso'] }}</span>
                <span class="d">
                    @if ($conteos['en_proceso'])
                        <a href="{{ route('inventory.procesos.index') }}" class="link">Hojalatería o mantenimiento</a>
                    @else
                        Nada detenido
                    @endif
                </span>
            </div>
            <div>
                <span class="e">Vendidas</span>
                <span class="v">{{ $conteos['vendidas'] }}</span>
                <span class="d">Ya salieron</span>
            </div>
            <div>
                <span class="e">Han entrado</span>
                <span class="v">{{ $conteos['total'] }}</span>
                <span class="d">Piezas desde el inicio</span>
            </div>
        </div>
    </x-ui.card>

    {{-- ===================== Kardex ===================== --}}
    <x-ui.card style="margin-bottom:18px; padding:0;">
        <div style="padding:20px 20px 6px;">
            <x-ui.section-title style="margin:0 0 4px;">Movimientos</x-ui.section-title>
            <p class="campo-nota" style="margin:0;">
                Todo lo que entró y salió de este producto, con quién lo registró y cómo quedó el stock.
            </p>
        </div>

        <div class="tbl-scroll">
            <table class="kx">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Folio</th>
                        <th>Movimiento</th>
                        <th class="num">Cantidad</th>
                        <th class="num">Stock</th>
                        <th>Registró</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($movimientos as $mov)
                        @php $entra = $mov->movement_type === 'entrada'; @endphp

                        <tr>
                            <td style="white-space:nowrap;">{{ $mov->movement_date?->format('d/m/Y') ?? '—' }}</td>
                            <td class="folio">{{ $mov->folio }}</td>
                            <td>
                                <span class="badge {{ $entra ? 'badge--ok' : 'badge--danger' }}">
                                    {{ ucfirst($mov->movement_type) }}
                                </span>
                                @if ($entra && $mov->condicion)
                                    <span class="nota">Equipo {{ $mov->condicion }}</span>
                                @endif
                                @if ($mov->reference)
                                    <span class="nota">Ref. {{ $mov->reference }}</span>
                                @endif
                                @if ($mov->notes)
                                    <span class="nota">{{ $mov->notes }}</span>
                                @endif
                            </td>
                            <td class="num">
                                <span class="mov {{ $entra ? 'entra' : 'sale' }}">
                                    {{ $entra ? '+' : '−' }}{{ $mov->quantity }}
                                </span>
                            </td>
                            <td class="num">
                                <span class="saldo">
                                    {{ $mov->stock_before ?? '—' }} &rarr; <b>{{ $mov->stock_after ?? '—' }}</b>
                                </span>
                            </td>
                            <td>{{ $mov->creator?->name ?: '—' }}</td>
                            <td style="text-align:right; white-space:nowrap;">
                                <a href="{{ route('inventory.movimientos.show', $mov) }}" class="tbl-link">Ver</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7">
                                <div class="empty-state">
                                    <h3>Sin movimientos</h3>
                                    <p>Este producto todavía no registra entradas ni salidas.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-ui.card>

    {{-- ===================== Piezas ===================== --}}
    <x-ui.card style="padding:0;">
        <div style="padding:20px 20px 6px;">
            <x-ui.section-title style="margin:0 0 4px;">Piezas ({{ $piezas->count() }})</x-ui.section-title>
            <p class="campo-nota" style="margin:0;">
                Cada pieza física con su etiqueta. Escanea su QR para abrir la ficha pública.
            </p>
        </div>

        <div class="tbl-scroll">
            <table class="kx">
                <thead>
                    <tr>
                        <th>Etiqueta</th>
                        <th>No. de serie</th>
                        <th>Estado</th>
                        <th>Condición</th>
                        <th>Entró</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($piezas as $pieza)
                        <tr>
                            <td class="pz-cod">{{ $pieza->codigo ?: '—' }}</td>
                            <td>{{ $pieza->no_serie ?: '—' }}</td>
                            <td>
                                <span class="badge {{ $pieza->vendible() ? 'badge--ok' : ($pieza->vendido ? '' : 'badge--info') }}">
                                    {{ $pieza->estadoLabel() }}
                                </span>
                                @if ($falta = $pieza->faltaTexto())
                                    <span class="nota">Falta: {{ $falta }}</span>
                                @endif
                                @if ($pieza->vendido && $pieza->ventaItem?->venta)
                                    <span class="nota">Venta {{ $pieza->ventaItem->venta->folio }}</span>
                                @endif
                            </td>
                            <td>{{ ucfirst($pieza->condicion) }}</td>
                            <td style="white-space:nowrap;">
                                {{ $pieza->entrada?->movement_date?->format('d/m/Y') ?? '—' }}
                                @if ($pieza->entrada)
                                    <span class="nota">{{ $pieza->entrada->folio }}</span>
                                @endif
                            </td>
                            <td style="text-align:right; white-space:nowrap;">
                                @if ($pieza->urlPublica())
                                    <a href="{{ $pieza->urlPublica() }}" target="_blank" class="tbl-link">Ficha</a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">
                                <div class="empty-state">
                                    <h3>Sin piezas</h3>
                                    <p>Registra una entrada para dar de alta las primeras.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-ui.card>
@endsection
