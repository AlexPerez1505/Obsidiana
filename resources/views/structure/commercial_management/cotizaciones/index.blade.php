@extends('layouts.dashboard')
@section('title', 'Cotizaciones')
@section('page-title', 'Cotizaciones')
@section('page-sub', 'Cotizaciones y planes de pago de clientes')

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
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($cotizaciones as $cot)
                        <tr>
                            <td>{{ $cot->cliente?->nombre }} {{ $cot->cliente?->apellido }}</td>
                            <td>{{ $cot->paquete?->nombre ?? $cot->producto?->tipo_equipo ?? '—' }}</td>
                            <td>${{ number_format($cot->total, 2) }}</td>
                            <td>{{ $cot->planPagos->count() }} pago(s)</td>
                            <td>
                                <span class="badge {{ $cot->estado ? 'badge--ok' : 'badge--danger' }}">
                                    {{ $cot->estado ? 'Vigente' : 'Cerrada' }}
                                </span>
                            </td>
                            <td>
                                <a href="{{ route('commercial.cotizaciones.show', $cot) }}" class="btn btn--ghost" style="padding:6px 12px; font-size:13px; text-decoration:none;">Ver detalle</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" style="text-align:center; padding:32px; color:var(--muted);">
                                No hay cotizaciones registradas.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-ui.card>
@endsection
