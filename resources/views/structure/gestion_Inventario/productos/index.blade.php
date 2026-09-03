@extends('layouts.dashboard')
@section('title', 'Productos')
@section('page-title', 'Productos')
@section('page-sub', 'Inventario de equipos y stock disponible')

@push('head')
    <style>
        .unidades-modal-overlay { position:fixed; inset:0; background:rgba(0,0,0,.45); display:none; align-items:center; justify-content:center; z-index:1000; padding:20px; }
        .unidades-modal { background:var(--surface); border-radius:12px; max-width:560px; width:100%; max-height:80vh; overflow-y:auto; padding:20px; }
        .unidades-modal-head { display:flex; align-items:center; justify-content:space-between; margin-bottom:14px; }
        .unidad-item { display:flex; align-items:center; gap:12px; padding:10px 0; border-bottom:1px solid var(--border); }
        .unidad-item:last-child { border-bottom:none; }
        .unidad-item img { width:54px; height:54px; object-fit:cover; border-radius:8px; border:1px solid var(--border); cursor:pointer; }
        .unidad-item .unidad-sin-foto { width:54px; height:54px; border-radius:8px; border:1px solid var(--border); background:var(--surface-2); display:flex; align-items:center; justify-content:center; color:var(--muted); font-size:11px; text-align:center; }
        .producto-row { cursor:pointer; }
        .producto-row:hover { background:var(--surface-2); }
    </style>
@endpush

@section('content')
    <div style="display:flex; align-items:center; gap:12px; margin-bottom:18px; flex-wrap:wrap;">
        <form method="GET" style="flex:1; min-width:220px; max-width:380px;">
            <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Buscar por tipo, marca, modelo o serie..."
                   style="width:100%; padding:11px 14px; border:1px solid var(--border); border-radius:9px; font-size:14.5px; background:var(--surface); color:var(--text);">
        </form>
        <div style="flex:1;"></div>
        <a href="{{ route('inventory.movimientos.create') }}" class="btn" style="text-decoration:none; display:inline-flex; align-items:center; gap:7px;">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 5v14M5 12h14"/></svg>
            Registrar entrada
        </a>
    </div>

    <x-ui.card>
        <div style="overflow-x:auto;">
            <table>
                <thead>
                    <tr>
                        <th>Imagen</th>
                        <th>Equipo</th>
                        <th>Marca / Modelo</th>
                        <th>Precio</th>
                        <th>Stock</th>
                        <th>Proveedor</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($productos as $producto)
                        <tr class="producto-row" title="Ver unidades disponibles"
                            onclick="if (!event.target.closest('.actions-dropdown')) { document.getElementById('unidades-modal-{{ $producto->id }}').style.display='flex'; }">
                            <td>
                                @if($producto->imagen_path)
                                    <img src="{{ asset('storage/' . $producto->imagen_path) }}" alt="{{ $producto->tipo_equipo }}" style="width:50px; height:50px; object-fit:cover; border-radius:6px; border:1px solid var(--border);">
                                @else
                                    <div style="width:50px; height:50px; background:var(--surface); border:1px solid var(--border); border-radius:6px; display:flex; align-items:center; justify-content:center; color:var(--muted); font-size:20px;">—</div>
                                @endif
                            </td>
                            <td>
                                <div style="font-weight:700;">{{ $producto->tipo_equipo }}</div>
                                @if($producto->subtipo)
                                    <div class="muted" style="font-size:12.5px;">{{ $producto->subtipo }}</div>
                                @endif
                            </td>
                            <td>{{ $producto->marca ?: '—' }} {{ $producto->modelo }}</td>
                            <td>${{ number_format($producto->precio, 2) }}</td>
                            <td>
                                <span class="badge {{ $producto->stock > 0 ? 'badge--ok' : 'badge--danger' }}">
                                    {{ $producto->stock }} u.
                                </span>
                            </td>
                            <td>{{ $producto->proveedor ?: '—' }}</td>
                            <td>
                                <details class="actions-dropdown" style="position:relative;">
                                    <summary style="list-style:none; cursor:pointer; display:inline-flex; padding:6px 8px; border-radius:6px; color:var(--text);">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="6" r="2"/><circle cx="12" cy="12" r="2"/><circle cx="12" cy="18" r="2"/></svg>
                                    </summary>
                                    <div style="position:absolute; right:0; top:100%; margin-top:6px; background:var(--surface); border:1px solid var(--border); border-radius:8px; box-shadow:0 6px 20px rgba(0,0,0,.12); min-width:130px; z-index:20; overflow:hidden;">
                                        <a href="{{ route('inventory.productos.edit', $producto) }}" style="display:flex; align-items:center; gap:8px; padding:10px 14px; font-size:13px; color:var(--text); text-decoration:none; white-space:nowrap;">
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                            Editar
                                        </a>
                                        <form method="POST" action="{{ route('inventory.productos.destroy', $producto) }}" onsubmit="return confirm('¿Eliminar este producto?');" style="margin:0;">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" style="display:flex; align-items:center; gap:8px; width:100%; padding:10px 14px; font-size:13px; color:var(--danger); background:transparent; border:none; cursor:pointer; text-align:left; white-space:nowrap;">
                                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                                                Eliminar
                                            </button>
                                        </form>
                                    </div>
                                </details>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" style="text-align:center; padding:32px; color:var(--muted);">
                                No hay productos registrados.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @include('partials._paginacion', ['paginator' => $productos])
    </x-ui.card>

    {{-- Modales de unidades disponibles, fuera de la tabla para que el HTML sea válido --}}
    @foreach ($productos as $producto)
        <div id="unidades-modal-{{ $producto->id }}" class="unidades-modal-overlay">
            <div class="unidades-modal">
                <div class="unidades-modal-head">
                    <div>
                        <strong>{{ trim($producto->marca.' '.$producto->modelo) ?: $producto->tipo_equipo }}</strong>
                        <div class="muted" style="font-size:12.5px;">Unidades disponibles ({{ $producto->serialesDisponibles->count() }})</div>
                    </div>
                    <button type="button" class="btn btn--ghost" style="padding:5px 10px;" onclick="document.getElementById('unidades-modal-{{ $producto->id }}').style.display='none'">Cerrar</button>
                </div>

                @forelse ($producto->serialesDisponibles as $serial)
                    <div class="unidad-item">
                        @if ($serial->fotoUrl())
                            <img src="{{ $serial->fotoUrl() }}" alt="Foto de la unidad" onclick="window.open('{{ $serial->fotoUrl() }}', '_blank')">
                        @else
                            <div class="unidad-sin-foto">Sin foto</div>
                        @endif
                        <div style="flex:1;">
                            <div style="font-weight:600; font-size:13.5px;">{{ $serial->no_serie ?: '— (sin serial capturado)' }}</div>
                            <span class="badge badge--ok" style="font-size:11px;">Disponible</span>
                        </div>
                    </div>
                @empty
                    <p class="muted" style="padding:16px 0; text-align:center;">No hay unidades disponibles.</p>
                @endforelse
            </div>
        </div>
    @endforeach
@endsection
