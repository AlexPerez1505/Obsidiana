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
        <a href="{{ route('inventory.paquetes.index') }}" class="btn btn--ghost" style="text-decoration:none; display:inline-flex; align-items:center; gap:7px;">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16V8z"/><path d="M3.27 6.96 12 12l8.73-5.04M12 22.08V12"/></svg>
            Paquetes
        </a>
        <button type="button" id="btn-agrupar-paquete" class="btn" disabled
                style="display:inline-flex; align-items:center; gap:7px; opacity:.5; cursor:not-allowed;"
                onclick="agruparEnPaquete()">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 5v14M5 12h14"/></svg>
            Agrupar en paquete (<span id="contador-seleccionados">0</span>)
        </button>
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
                        <th style="width:36px;"></th>
                        <th>Imagen</th>
                        <th>Equipo</th>
                        <th>Marca / Modelo</th>
                        {{-- El precio es dato de administración: la columna
                             no se dibuja para quien no puede verlo. --}}
                        @if (\App\Support\PrecioVisible::para())
                            <th>Precio de venta</th>
                        @endif
                        <th>Stock</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($productos as $producto)
                        <tr class="producto-row" title="Ver unidades disponibles"
                            onclick="if (!event.target.closest('.row-menu') && event.target.tagName !== 'INPUT') { document.getElementById('unidades-modal-{{ $producto->id }}').style.display='flex'; }">
                            <td onclick="event.stopPropagation();">
                                <input type="checkbox" class="producto-checkbox" value="{{ $producto->id }}" onchange="actualizarSeleccionPaquete()" style="width:16px; height:16px; cursor:pointer;">
                            </td>
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
                            @if (\App\Support\PrecioVisible::para())
                                <td>{{ \App\Support\PrecioVisible::texto($producto) }}</td>
                            @endif
                            <td>
                                <span class="badge {{ $producto->stock > 0 ? 'badge--ok' : 'badge--danger' }}">
                                    {{ $producto->stock }} u.
                                </span>
                            </td>
                            <td>
                                {{-- Menú compartido: se posiciona en fijo, así que
                                     no lo recorta el overflow de la tarjeta. El
                                     de antes se cortaba cuando había una sola
                                     fila y no se alcanzaba a ver. --}}
                                <div class="row-menu" data-row-menu>
                                    <button type="button" class="row-menu-btn" data-row-menu-toggle
                                            aria-haspopup="true" aria-expanded="false"
                                            aria-label="Acciones de {{ $producto->tipo_equipo }}">
                                        <svg viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="5" r="1.7"/><circle cx="12" cy="12" r="1.7"/><circle cx="12" cy="19" r="1.7"/></svg>
                                    </button>

                                    <div class="row-menu-pop" data-row-menu-pop role="menu" hidden>
                                        <a href="{{ route('inventory.productos.show', $producto) }}" role="menuitem">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                                            Ver ficha
                                        </a>
                                        <a href="{{ route('inventory.productos.edit', $producto) }}" role="menuitem">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                                            Editar
                                        </a>
                                        <form method="POST" action="{{ route('inventory.productos.destroy', $producto) }}"
                                              onsubmit="return confirm('¿Eliminar este producto?');" style="margin:0;">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="es-danger" role="menuitem">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 6h18"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/><path d="M10 11v6M14 11v6"/></svg>
                                                Eliminar
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" style="text-align:center; padding:32px; color:var(--muted);">
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

    <script>
        function actualizarSeleccionPaquete() {
            const marcados = document.querySelectorAll('.producto-checkbox:checked');
            const boton = document.getElementById('btn-agrupar-paquete');

            document.getElementById('contador-seleccionados').textContent = marcados.length;

            if (marcados.length > 0) {
                boton.disabled = false;
                boton.style.opacity = '1';
                boton.style.cursor = 'pointer';
            } else {
                boton.disabled = true;
                boton.style.opacity = '.5';
                boton.style.cursor = 'not-allowed';
            }
        }

        function agruparEnPaquete() {
            const marcados = Array.from(document.querySelectorAll('.producto-checkbox:checked')).map(c => c.value);

            if (marcados.length === 0) return;

            const params = marcados.map(id => 'productos[]=' + encodeURIComponent(id)).join('&');
            window.location.href = @json(route('inventory.paquetes.create')) + '?' + params;
        }
    </script>

    @include('partials.row-menu')
@endsection
