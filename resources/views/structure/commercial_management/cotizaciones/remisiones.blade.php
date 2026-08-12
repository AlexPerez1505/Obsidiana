@extends('layouts.dashboard')
@section('title', 'Remisiones')
@section('page-title', 'Remisiones')
@section('page-sub', 'Ventas definitivas y seguimiento de sus pagos')

@section('content')
    <div style="display:flex; align-items:center; gap:12px; margin-bottom:18px; flex-wrap:wrap;">
        <form id="remisiones-search-form" method="GET" action="{{ route('commercial.remisiones.index') }}" style="flex:1; min-width:260px; max-width:620px; display:flex; gap:8px; flex-wrap:wrap;">
            <input id="remisiones-search" type="search" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Buscar por cliente, teléfono, producto, folio o total..."
                   style="flex:1; min-width:220px; padding:11px 14px; border:1px solid var(--border); border-radius:9px; font-size:14.5px; background:var(--surface); color:var(--text);">
            <button type="submit" class="btn btn--ghost" style="padding:10px 14px; display:inline-flex; align-items:center; gap:6px;">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
                Buscar
            </button>
            @if(! empty($filters['search']))
                <a href="{{ route('commercial.remisiones.index') }}" class="btn btn--ghost" style="padding:10px 14px; text-decoration:none;">Limpiar</a>
            @endif
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
                        <tr class="remision-row" data-search="{{ strtolower(trim(($rem->id ?? '').' '.($rem->cliente?->nombre ?? '').' '.($rem->cliente?->apellido ?? '').' '.($rem->cliente?->telefono ?? '').' '.($rem->cliente?->gmail ?? '').' '.$rem->items->pluck('nombre')->implode(' ').' '.$rem->total)) }}">
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
                                <div style="display:flex; gap:8px; flex-wrap:wrap;">
                                    <a href="{{ route('commercial.cotizaciones.show', $rem) }}" class="btn btn--ghost" style="padding:6px 12px; font-size:13px; text-decoration:none; display:inline-flex; align-items:center; gap:6px;">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12Z"/><circle cx="12" cy="12" r="3"/></svg>
                                        Ver detalle
                                    </a>
                                    <a href="{{ route('commercial.remisiones.pdf', $rem) }}" class="btn" style="padding:6px 12px; font-size:13px; text-decoration:none; display:inline-flex; align-items:center; gap:6px;">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="M7 10l5 5 5-5"/><path d="M12 15V3"/></svg>
                                        Descargar PDF
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" style="text-align:center; padding:32px; color:var(--muted);">
                                No hay remisiones registradas. Convierte una cotización en remisión desde su detalle.
                            </td>
                        </tr>
                    @endforelse
                    <tr id="remisiones-no-results" style="display:none;">
                        <td colspan="7" style="text-align:center; padding:32px; color:var(--muted);">
                            No se encontraron remisiones con esa búsqueda.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </x-ui.card>

    <script>
        const remisionesSearch = document.getElementById('remisiones-search');
        const remisionesRows = Array.from(document.querySelectorAll('.remision-row'));
        const remisionesNoResults = document.getElementById('remisiones-no-results');

        function filtrarRemisiones() {
            const query = remisionesSearch.value.trim().toLowerCase();
            let visibles = 0;

            remisionesRows.forEach(row => {
                const show = !query || row.dataset.search.includes(query);
                row.style.display = show ? '' : 'none';
                if (show) visibles++;
            });

            remisionesNoResults.style.display = remisionesRows.length > 0 && visibles === 0 ? '' : 'none';
        }

        remisionesSearch.addEventListener('input', filtrarRemisiones);
        filtrarRemisiones();
    </script>
@endsection
