@extends('layouts.dashboard')
@section('title', 'Editar Producto')
@section('page-title', 'Editar Producto')
@section('page-sub', $producto->tipo_equipo)

@push('head')
    <style>
        .rgrid-2 { display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 12px 18px; }
        @media (max-width: 520px) { .rgrid-2 { grid-template-columns: 1fr; } }
    </style>
@endpush

@section('content')
    <form method="POST" action="{{ route('inventory.productos.update', $producto) }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        <x-ui.card style="margin-bottom:18px;">
            <x-ui.section-title style="margin:0 0 16px;">Datos del Producto</x-ui.section-title>
            <div class="rgrid-2">
                @include('structure.gestion_Inventario.productos._selects_catalogo')

                {{-- El campo solo se dibuja para quien tiene precios.editar. --}}
                @if (\App\Support\PrecioVisible::editable())
                    <x-ui.form-group label="Precio de venta" name="precio" type="number" step="0.01" min="0" :value="$producto->precio" />
                @endif

                <x-ui.form-group label="Stock" for="stock_display">
                    <input id="stock_display" type="number" value="{{ $producto->stock }}" disabled
                           style="width:100%; padding:11px 12px; border:1px solid var(--border); border-radius:9px; font-size:15px; background:var(--surface-2); color:var(--muted);">
                    <input type="hidden" name="stock" value="{{ $producto->stock }}">
                    <small style="color:var(--muted);">El stock se calcula solo, según las unidades de abajo. Usa "Agregar unidades" para sumarle.</small>
                </x-ui.form-group>

            </div>
            <x-ui.form-group label="Descripción" for="descripcion">
                <textarea id="descripcion" name="descripcion" rows="3" style="width:100%; padding:11px 12px; border:1px solid var(--border); border-radius:9px; font-size:15px; background:var(--surface); color:var(--text); resize:vertical;">{{ old('descripcion', $producto->descripcion) }}</textarea>
            </x-ui.form-group>
            <x-ui.form-group label="Imagen del Producto" for="imagen">
                @if($producto->imagen_path)
                    <div style="margin-bottom:10px;">
                        <img src="{{ asset('storage/' . $producto->imagen_path) }}" alt="Imagen actual" style="max-height:150px; border-radius:8px; border:1px solid var(--border);">
                        <small style="color:var(--muted); display:block; margin-top:4px;">Imagen actual</small>
                    </div>
                @endif
                <input type="file" id="imagen" name="imagen" accept="image/*" style="width:100%; padding:8px; border:1px solid var(--border); border-radius:9px; font-size:14px; background:var(--surface); color:var(--text);">
                <small style="color:var(--muted);">Formatos: JPG, PNG, GIF. Máximo 5MB. Deja vacío para mantener la imagen actual.</small>
            </x-ui.form-group>
        </x-ui.card>

        <div style="display:flex; gap:10px;">
            <x-ui.button>Actualizar Producto</x-ui.button>
            <a href="{{ route('inventory.productos.index') }}" class="btn btn--ghost" style="text-decoration:none;">Cancelar</a>
        </div>
    </form>

    <x-ui.card style="margin-top:18px;">
        <x-ui.section-title style="margin:0 0 16px;">Unidades en inventario</x-ui.section-title>

        <div style="overflow-x:auto; margin-bottom:18px;">
            <table>
                <thead>
                    <tr>
                        <th>Foto</th>
                        <th>No. Serie</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($seriales as $serial)
                        <tr>
                            <td>
                                @if ($serial->fotoUrl())
                                    <img src="{{ $serial->fotoUrl() }}" alt="Foto de la unidad" style="width:44px; height:44px; object-fit:cover; border-radius:6px; border:1px solid var(--border); cursor:pointer;" onclick="window.open('{{ $serial->fotoUrl() }}', '_blank')">
                                @else
                                    <span class="muted" style="font-size:12px;">—</span>
                                @endif
                            </td>
                            <td>{{ $serial->no_serie ?: '— (sin serial capturado)' }}</td>
                            <td>
                                <span class="badge {{ $serial->vendido ? 'badge--danger' : 'badge--ok' }}">
                                    {{ $serial->vendido ? 'Vendida' : 'Disponible' }}
                                </span>
                            </td>
                            <td style="display:flex; gap:6px;">
                                <button type="button" class="btn btn--ghost" style="padding:5px 10px; font-size:12.5px;"
                                        onclick="document.getElementById('editar-serial-{{ $serial->id }}').style.display='flex'">Editar</button>
                                @unless ($serial->vendido)
                                    <form method="POST" action="{{ route('inventory.productos.seriales.destroy', $serial) }}" onsubmit="return confirm('¿Quitar esta unidad del inventario?');" style="margin:0;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn--ghost" style="padding:5px 10px; font-size:12.5px; color:var(--danger);">Eliminar</button>
                                    </form>
                                @endunless
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" style="text-align:center; padding:20px; color:var(--muted);">No hay unidades registradas.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Modales de edición de unidad (serial + foto), piden PIN/contraseña --}}
        @foreach ($seriales as $serial)
            <div id="editar-serial-{{ $serial->id }}" class="modal-overlay" style="display:none;">
                <div style="background:var(--surface); border-radius:12px; max-width:420px; width:100%; padding:20px; margin:auto;">
                    <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:14px;">
                        <strong>Editar unidad</strong>
                        <button type="button" class="btn btn--ghost" style="padding:4px 8px;" onclick="document.getElementById('editar-serial-{{ $serial->id }}').style.display='none'">✕</button>
                    </div>
                    <form method="POST" action="{{ route('inventory.productos.seriales.update', $serial) }}" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')
                        <x-ui.form-group label="Número de serie" name="no_serie" :value="$serial->no_serie" />
                        <x-ui.form-group label="Foto (déjalo vacío para no cambiarla)" for="foto_{{ $serial->id }}">
                            <input type="file" id="foto_{{ $serial->id }}" name="foto" accept="image/*" style="width:100%; padding:8px; border:1px solid var(--border); border-radius:9px; font-size:14px; background:var(--surface); color:var(--text);">
                        </x-ui.form-group>
                        <x-ui.form-group label="PIN o contraseña, para confirmar el cambio *" for="password_{{ $serial->id }}">
                            <input type="password" id="password_{{ $serial->id }}" name="password" required
                                   style="width:100%; padding:11px 12px; border:1px solid var(--border); border-radius:9px; font-size:15px; background:var(--surface); color:var(--text);">
                        </x-ui.form-group>
                        <x-ui.button type="submit" style="width:100%;">Guardar cambios</x-ui.button>
                    </form>
                </div>
            </div>
        @endforeach

        <x-ui.section-title style="margin:0 0 12px;">Agregar unidades (ajuste rápido, sin evidencia)</x-ui.section-title>
        <p style="margin:0 0 12px; color:var(--muted); font-size:13.5px;">
            Úsalo solo para correcciones puntuales. Si está llegando mercancía nueva de verdad,
            regístrala como <a href="{{ route('inventory.movimientos.create') }}">Entrada</a> para dejar la evidencia fotográfica del envío.
        </p>
        <form method="POST" action="{{ route('inventory.productos.seriales.store', $producto) }}">
            @csrf
            <div class="rgrid-2">
                <x-ui.form-group label="Cantidad a agregar *" name="cantidad" type="number" min="1" placeholder="1" :required="true" />
            </div>
            <x-ui.form-group label="Números de serie (uno por línea, opcional)" for="series_texto">
                <textarea id="series_texto" name="series_texto" rows="3" placeholder="Déjalo vacío si estas unidades no tienen serial individual"
                          style="width:100%; padding:11px 12px; border:1px solid var(--border); border-radius:9px; font-size:15px; background:var(--surface); color:var(--text); resize:vertical;">{{ old('series_texto') }}</textarea>
                <small style="color:var(--muted);">Si capturas todas las series, deben ser exactamente tantas líneas como la cantidad de arriba. Si solo pones una y la cantidad es mayor a 1, el resto de la secuencia se genera solo (ej. 23A12345 → 23A12346, 23A12347...).</small>
            </x-ui.form-group>
            <x-ui.button type="submit">Agregar al inventario</x-ui.button>
        </form>
    </x-ui.card>
@endsection
