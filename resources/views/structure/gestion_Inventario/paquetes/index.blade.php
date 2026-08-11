@extends('layouts.dashboard')
@section('title', 'Paquetes')
@section('page-title', 'Paquetes')
@section('page-sub', 'Paquetes armados a partir de productos del inventario')

@section('content')
    <div style="display:flex; justify-content:flex-end; margin-bottom:18px;">
        <a href="{{ route('inventory.paquetes.create') }}" class="btn" style="text-decoration:none; display:inline-flex; align-items:center; gap:7px;">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 5v14M5 12h14"/></svg>
            Agregar paquete
        </a>
    </div>

    <x-ui.card>
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
                                    <ul style="margin:0; padding-left:18px; font-size:13.5px;">
                                        @foreach($paquete->productos as $producto)
                                            <li>{{ $producto->tipo_equipo }} {{ $producto->marca }} {{ $producto->modelo }} (x{{ $producto->pivot->cantidad }})</li>
                                        @endforeach
                                    </ul>
                                @else
                                    <span style="color:var(--muted);">Sin productos</span>
                                @endif
                            </td>
                            <td>
                                <div style="display:flex; gap:8px;">
                                    <a href="{{ route('inventory.paquetes.edit', $paquete) }}" class="btn btn--ghost" style="padding:6px 12px; font-size:13px; text-decoration:none;">Editar</a>
                                    <form method="POST" action="{{ route('inventory.paquetes.destroy', $paquete) }}" onsubmit="return confirm('¿Eliminar este paquete?');">
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
@endsection
