@extends('layouts.dashboard')
@section('title', 'Editar Paquete')
@section('page-title', 'Editar Paquete')
@section('page-sub', $paquete->nombre)

@push('head')
    <style>
        .paquete-grid { display:grid; grid-template-columns: 340px 1fr; gap:18px; align-items:start; }
        @media (max-width:900px) { .paquete-grid { grid-template-columns: 1fr; } }
    </style>
@endpush

@section('content')
    <form method="POST" action="{{ route('inventory.paquetes.update', $paquete) }}" id="paquete-form" class="paquete-grid">
        @csrf
        @method('PUT')
        <div style="display:flex; flex-direction:column; gap:18px;">
            <x-ui.card>
                <x-ui.section-title style="margin:0 0 16px;">Datos del Paquete</x-ui.section-title>
                <x-ui.form-group label="Nombre del Paquete *" name="nombre" :value="$paquete->nombre" :required="true" />
            </x-ui.card>

            <div style="display:flex; gap:10px;">
                <x-ui.button>Actualizar Paquete</x-ui.button>
                <a href="{{ route('inventory.paquetes.index') }}" class="btn btn--ghost" style="text-decoration:none;">Cancelar</a>
            </div>
        </div>

        <div>
            @include('structure.gestion_Inventario.paquetes._selector_productos', [
                'productos' => $productos,
                'seleccionados' => $seleccionados,
            ])
        </div>
    </form>
@endsection
