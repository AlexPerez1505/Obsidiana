@extends('layouts.dashboard')

@section('title', 'Movimiento ' . $movimiento->folio)
@section('page-title', 'Movimiento ' . $movimiento->folio)
@section('page-sub', 'Gestion de Inventario > Entrada / Salida > Detalle')

@section('content')
    <div class="dashboard-card" style="margin-bottom:18px;">
        <div style="display:flex; align-items:center; justify-content:space-between; gap:18px; flex-wrap:wrap;">
            <div>
                <span class="badge {{ $movimiento->movement_type === 'entrada' ? 'badge--ok' : 'badge--danger' }}">
                    {{ ucfirst($movimiento->movement_type) }}
                </span>
                <span style="margin-left:8px; color:var(--muted);">{{ $movimiento->movement_date->format('d/m/Y') }}</span>
            </div>
            <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
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

    @if ($movimiento->movement_type === 'entrada')
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
                <p class="muted">Este movimiento no tiene evidencia fotográfica.</p>
            @endif

            @if ($movimiento->videoUrl())
                <div style="margin-top:16px;">
                    <div class="muted" style="font-size:12.5px; margin-bottom:6px;">Video de verificación</div>
                    <video src="{{ $movimiento->videoUrl() }}" controls style="max-width:320px; border-radius:10px; border:1px solid var(--border);"></video>
                </div>
            @endif

            @if ($movimiento->signatureUrl())
                <div style="margin-top:16px;">
                    <div class="muted" style="font-size:12.5px; margin-bottom:6px;">Firma de {{ $movimiento->creator?->name ?: 'quien registró' }}</div>
                    <img src="{{ $movimiento->signatureUrl() }}" alt="Firma digital" style="max-width:260px; border-radius:10px; border:1px solid var(--border); background:#fff;">
                </div>
            @endif
        </x-ui.card>
    @endif

    <x-ui.card>
        <x-ui.section-title style="margin:0 0 16px;">Unidades de este movimiento</x-ui.section-title>
        <div style="overflow-x:auto;">
            <table>
                <thead>
                    <tr>
                        <th>No. Serie</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($movimiento->unidadesRelacionadas() as $serial)
                        <tr>
                            <td>{{ $serial->no_serie ?: '— (sin serial capturado)' }}</td>
                            <td>
                                <span class="badge {{ $serial->vendido ? 'badge--danger' : 'badge--ok' }}">
                                    {{ $serial->vendido ? 'Vendida' : 'Disponible' }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="2" style="text-align:center; padding:20px; color:var(--muted);">Sin unidades registradas.</td></tr>
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
@endsection
