@extends('layouts.dashboard')
@section('title', 'Remisiones')
@section('page-title', 'Remisiones')
@section('page-sub', 'Ventas definitivas y seguimiento de sus pagos')

@section('content')
    <div style="display:flex; align-items:center; gap:12px; margin-bottom:18px; flex-wrap:wrap;">
        <form method="GET" style="flex:1; min-width:220px; max-width:380px;">
            <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Buscar por cliente..."
                   style="width:100%; padding:11px 14px; border:1px solid var(--border); border-radius:9px; font-size:14.5px; background:var(--surface); color:var(--text);">
        </form>
        <div style="flex:1;"></div>
        <a href="{{ route('commercial.cotizaciones.create') }}" class="btn" style="text-decoration:none; display:inline-flex; align-items:center; gap:7px;">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 5v14M5 12h14"/></svg>
            Nueva cotización
        </a>
    </div>

    <x-ui.card>
        <div style="overflow-x:auto;">
            <table>
                <thead>
                    <tr>
                        <th>Cliente</th>
                        <th>Producto / Paquete</th>
                        <th>Total</th>
                        <th>Pagos</th>
                        <th>Pagado</th>
                        <th>Estado de pago</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($remisiones as $rem)
                        @php
                            $totalPagado = $rem->planPagos->flatMap(fn ($p) => $p->pagos->where('pagado', true))->sum('monto_pagado');
                            $totalPagos = $rem->planPagos->count();
                            $pagosCompletados = $rem->planPagos->filter(fn ($p) => $p->pagos->where('pagado', true)->isNotEmpty())->count();
                            $liquidada = $totalPagos > 0 && $pagosCompletados === $totalPagos;
                        @endphp
                        <tr>
                            <td>{{ $rem->cliente?->nombre }} {{ $rem->cliente?->apellido }}</td>
                            <td>
                                @if($rem->items->count() > 0)
                                    <span style="font-size:13.5px;">{{ $rem->items->pluck('nombre')->implode(', ') }}</span>
                                @else
                                    —
                                @endif
                            </td>
                            <td>${{ number_format($rem->total, 2) }}</td>
                            <td>{{ $pagosCompletados }} / {{ $totalPagos }}</td>
                            <td style="font-weight:700;">${{ number_format($totalPagado, 2) }}</td>
                            <td>
                                <span class="badge {{ $liquidada ? 'badge--ok' : 'badge--warn' }}">
                                    {{ $liquidada ? 'Liquidada' : 'En curso' }}
                                </span>
                            </td>
                            <td>
                                <a href="{{ route('commercial.cotizaciones.show', $rem) }}" class="btn btn--ghost" style="padding:6px 12px; font-size:13px; text-decoration:none;">Ver detalle</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" style="text-align:center; padding:32px; color:var(--muted);">
                                No hay remisiones registradas. Convierte una cotización en remisión desde su detalle.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-ui.card>
@endsection
