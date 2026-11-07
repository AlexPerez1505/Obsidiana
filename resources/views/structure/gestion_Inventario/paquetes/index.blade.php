@extends('layouts.dashboard')
@section('title', 'Paquetes')
@section('page-title', 'Paquetes')
@section('page-sub', 'Paquetes armados a partir de productos del inventario')

@section('content')
    {{-- El botón va en la cabecera de la tarjeta: una fila entera solo para
         él dejaba espacio muerto arriba de la tabla. --}}
    <x-ui.card>
        <div class="pq-cab">
            <span class="muted">{{ $paquetes->count() }} {{ $paquetes->count() === 1 ? 'paquete' : 'paquetes' }}</span>
            @can('inventario.registrar')
            <a href="{{ route('inventory.paquetes.create') }}" class="btn" style="text-decoration:none; display:inline-flex; align-items:center; gap:7px;">
                <x-gravityui-plus width="16" height="16" />
                Agregar paquete
            </a>
            @endcan
        </div>

        <div style="overflow-x:auto;">
            <table>
                <thead>
                    <tr>
                        <th>Paquete</th>
                        <th>Productos</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($paquetes->groupBy(fn ($p) => $p->tipoPrincipal())->sortKeys() as $tipoPaq => $grupo)
                        <tr class="pq-grupo"><td colspan="3">{{ $tipoPaq }} <span>{{ $grupo->count() }} {{ $grupo->count() === 1 ? 'paquete' : 'paquetes' }}</span></td></tr>
                        @foreach ($grupo as $paquete)
                            @php $contenido = $paquete->contenido(); @endphp
                            <tr>
                                <td style="font-weight:700;">{{ $paquete->nombre }}
                                    @if (! $paquete->activo)<span class="badge" style="font-size:11px; margin-left:6px;">Inactivo</span>@endif
                                </td>
                                <td>
                                    @if (! empty($contenido))
                                        {{-- Plegado: un paquete con muchos productos ya no hace una fila altísima. --}}
                                        <details class="pq-det">
                                            <summary>{{ count($contenido) }} {{ count($contenido) === 1 ? 'artículo' : 'artículos' }} · ver contenido</summary>
                                            <ul class="pq-prods">
                                                @foreach ($contenido as $l)
                                                    <li><b>{{ $l['cantidad'] }}×</b> {{ $l['nombre'] }}{{ $l['marca'] ? ' · '.$l['marca'] : '' }}{{ $l['modelo'] ? ' · '.$l['modelo'] : '' }}</li>
                                                @endforeach
                                            </ul>
                                        </details>
                                    @else
                                        <span style="color:var(--muted);">Sin productos</span>
                                    @endif
                                </td>
                                <td style="white-space:nowrap;">
                                    <div style="display:flex; gap:8px;">
                                        <a href="{{ route('inventory.paquetes.edit', $paquete) }}" class="btn btn--ghost" style="padding:6px 12px; font-size:13px; text-decoration:none;">Editar</a>
                                        <form method="POST" action="{{ route('inventory.paquetes.destroy', $paquete) }}" data-confirm="¿Eliminar este paquete?">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn--danger" style="padding:6px 12px; font-size:13px;">Eliminar</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    @empty
                        <tr>
                            <td colspan="3" style="text-align:center; padding:32px; color:var(--muted);">
                                No hay paquetes registrados.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-ui.card>

    <style>
        .pq-cab { display:flex; align-items:center; justify-content:space-between; gap:10px; flex-wrap:wrap;
                  margin-bottom:12px; }
        .pq-prods { display:flex; flex-wrap:wrap; gap:6px; margin:0; padding:0; list-style:none; }
        .pq-prods li { padding:3px 9px; border:1px solid var(--border); border-radius:999px;
                       background:var(--surface-2); font-size:12.5px; line-height:1.4; }
        .pq-prods b { font-weight:700; }
        .pq-grupo td { background:var(--surface-2); font-size:12.5px; font-weight:700; letter-spacing:.04em; text-transform:uppercase; padding:9px 12px !important; }
        .pq-grupo td span { font-weight:500; color:var(--muted); letter-spacing:0; text-transform:none; margin-left:6px; }
        .pq-det summary { cursor:pointer; font-size:13px; color:var(--primary); font-weight:600; margin-bottom:6px; }
    </style>
@endsection
