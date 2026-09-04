@extends('layouts.dashboard')
@section('title', 'Agregar Paquete')
@section('page-title', 'Agregar Paquete')
@section('page-sub', 'Crea un paquete con múltiples productos del inventario')

@push('head')
    <style>
        .paquete-grid { display:grid; grid-template-columns: 340px 1fr; gap:18px; align-items:start; }
        @media (max-width:900px) { .paquete-grid { grid-template-columns: 1fr; } }
    </style>
@endpush

@section('content')
    <form method="POST" action="{{ route('inventory.paquetes.store') }}" id="paquete-form" class="paquete-grid">
        @csrf
        <div style="display:flex; flex-direction:column; gap:18px;">
            <x-ui.card>
                <x-ui.section-title style="margin:0 0 16px;">Datos del Paquete</x-ui.section-title>
                <x-ui.form-group label="Nombre del Paquete *" name="nombre" placeholder="Ej. Paquete Endoscopía Básico" :required="true" />
            </x-ui.card>

            <div style="display:flex; gap:10px;">
                <x-ui.button>Guardar Paquete</x-ui.button>
                <a href="{{ route('inventory.paquetes.index') }}" class="btn btn--ghost" style="text-decoration:none;">Cancelar</a>
            </div>
        </div>

        <div>
            @include('structure.gestion_Inventario.paquetes._selector_productos', [
                'productos' => $productos,
                'seleccionados' => $seleccionados,
                'mensajePrecarga' => $preseleccionados->isNotEmpty()
                    ? "Se precargaron {$preseleccionados->count()} producto(s) que seleccionaste. Ajusta la cantidad de cada uno si hace falta."
                    : null,
            ])
        </div>
    </form>
@endsection
