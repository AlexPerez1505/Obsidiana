@extends('layouts.dashboard')
@section('title', 'Planes de Pago')
@section('page-title', 'Planes de Pago')
@section('page-sub', 'Plantillas reutilizables de número de pagos y frecuencia para las cotizaciones')

@section('content')
    <div style="display:flex; justify-content:flex-end; margin-bottom:18px;">
        <a href="{{ route('commercial.planesPago.create') }}" class="btn" style="text-decoration:none; display:inline-flex; align-items:center; gap:7px;">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 5v14M5 12h14"/></svg>
            Agregar plan de pago
        </a>
    </div>

    <x-ui.card>
        <div style="overflow-x:auto;">
            <table>
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>No. de pagos</th>
                        <th>Frecuencia</th>
                        <th>Método de pago</th>
                        <th>Descripción</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($planes as $plan)
                        <tr>
                            <td style="font-weight:700;">{{ $plan->nombre }}</td>
                            <td>{{ $plan->numero_pagos }}</td>
                            <td>Cada {{ $plan->dias_entre_pagos }} días</td>
                            <td>{{ $plan->metodo_pago }}</td>
                            <td class="muted">{{ $plan->descripcion ?: '—' }}</td>
                            <td>
                                <div style="display:flex; gap:8px;">
                                    <a href="{{ route('commercial.planesPago.edit', $plan) }}" class="btn btn--ghost" style="padding:6px 12px; font-size:13px; text-decoration:none;">Editar</a>
                                    <form method="POST" action="{{ route('commercial.planesPago.destroy', $plan) }}" onsubmit="return confirm('¿Eliminar este plan de pago?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn--danger" style="padding:6px 12px; font-size:13px;">Eliminar</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" style="text-align:center; padding:32px; color:var(--muted);">
                                No hay planes de pago registrados. Agrega uno para poder seleccionarlo en las cotizaciones.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-ui.card>
@endsection
