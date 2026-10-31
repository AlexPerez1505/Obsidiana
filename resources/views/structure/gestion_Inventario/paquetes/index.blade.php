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
                    @forelse($paquetes as $paquete)
                        <tr>
                            <td style="font-weight:700;">{{ $paquete->nombre }}</td>
                            <td>
                                @if($paquete->productos->count() > 0)
                                    {{-- Fichas en línea: con una viñeta por renglón, un paquete
                                         con muchos productos hacía una fila altísima. --}}
                                    <ul class="pq-prods">
                                        @foreach($paquete->productos as $producto)
                                            <li>{{ $producto->tipo_equipo }} {{ $producto->marca }} {{ $producto->modelo }} <b>(x{{ $producto->pivot->cantidad }})</b></li>
                                        @endforeach
                                    </ul>
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
    </style>
@endsection
