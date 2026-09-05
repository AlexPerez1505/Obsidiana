@extends('layouts.dashboard')

@section('title', 'Nuevo equipo')
@section('page-title', 'Nuevo equipo')
@section('page-sub', 'El equipo que se puede cotizar y vender')

@push('head')
    <style>
        .eq-campos { display:grid; grid-template-columns:repeat(auto-fit, minmax(220px, 1fr)); gap:0 16px; }
        @media (max-width:560px) { .eq-campos { grid-template-columns:1fr; } }
    </style>
@endpush

@section('content')
    <form method="POST" action="{{ route('inventory.equipos.store') }}" enctype="multipart/form-data" style="max-width:760px;">
        @csrf

        <x-ui.card>
            <div class="eq-campos">
                {{-- Tipo, subtipo, marca y modelo salen del catálogo, igual
                     que en Productos: escribirlos a mano dejaba el mismo
                     equipo con dos nombres distintos en cada módulo. --}}
                @include('structure.gestion_Inventario.productos._selects_catalogo')

                {{-- El campo solo se dibuja para quien tiene precios.editar. --}}
                @if (\App\Support\PrecioVisible::editable())
                    <x-ui.form-group label="Precio de venta" name="precio" type="text" inputmode="decimal" placeholder="0.00" />
                @endif

                <x-ui.form-group label="SKU / Clave" name="sku" placeholder="Opcional" />

                <x-ui.form-group for="imagen" label="Imagen">
                    <input id="imagen" type="file" name="imagen" accept="image/*">
                    <small class="campo-nota">JPG, PNG o GIF. Máximo 4 MB.</small>
                </x-ui.form-group>
            </div>
            <x-ui.form-group for="descripcion" label="Descripción">
                <textarea id="descripcion" name="descripcion" rows="3" placeholder="Descripción del equipo">{{ old('descripcion') }}</textarea>
            </x-ui.form-group>
            <label class="ui-switch-row" style="display:flex; align-items:center; gap:10px; margin-top:14px;">
                <input type="hidden" name="activo" value="0">
                <input type="checkbox" name="activo" value="1" checked>
                <span>Activo (disponible para cotizar)</span>
            </label>
        </x-ui.card>

        <div class="page-foot">
            <a href="{{ route('inventory.equipos.index') }}" class="btn btn--ghost">Cancelar</a>
            <x-ui.button>Guardar equipo</x-ui.button>
        </div>
    </form>
@endsection
