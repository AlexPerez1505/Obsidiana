@extends('layouts.dashboard')
@section('title', 'Agregar Producto')
@section('page-title', 'Agregar Producto')
@section('page-sub', 'Registra un nuevo equipo en el inventario')

@section('content')
    <form method="POST" action="{{ route('inventory.productos.store') }}" enctype="multipart/form-data" style="max-width:720px;">
        @csrf
        <x-ui.card style="margin-bottom:18px;">
            <x-ui.section-title style="margin:0 0 16px;">Equipo del inventario</x-ui.section-title>
            <x-ui.form-group label="Seleccionar equipo (opcional)" for="equipment_id">
                <select id="equipment_id" name="equipment_id" style="width:100%; padding:11px 12px; border:1px solid var(--border); border-radius:9px; font-size:15px; background:var(--surface); color:var(--text);">
                    <option value="">Producto nuevo (llenar manualmente)</option>
                    @foreach ($equipmentOptions ?? [] as $option)
                        <option value="{{ $option['id'] }}" @selected(old('equipment_id') == $option['id'])>
                            {{ $option['code'] }} - {{ $option['name'] }}{{ $option['marca'] ? ' (' . $option['marca'] . ($option['modelo'] ? ' ' . $option['modelo'] : '') . ')' : '' }}
                        </option>
                    @endforeach
                </select>
                <small style="color:var(--muted);">Al elegir un equipo, sus datos se llenan automaticamente y quedan ligados al inventario.</small>
            </x-ui.form-group>
        </x-ui.card>

        <x-ui.card style="margin-bottom:18px;">
            <x-ui.section-title style="margin:0 0 16px;">Datos del Producto</x-ui.section-title>
            <div class="rgrid-2">
                <x-ui.form-group label="Tipo de Equipo *" name="tipo_equipo" placeholder="Ej. Endoscopio" :required="true" />
                <x-ui.form-group label="Subtipo" name="subtipo" placeholder="Ej. Flexible" />
                <x-ui.form-group label="Marca" name="marca" placeholder="Ej. Olympus" />
                <x-ui.form-group label="Modelo" name="modelo" placeholder="Ej. GIF-H190" />
                <x-ui.form-group label="Precio *" name="precio" type="number" step="0.01" min="0" placeholder="0.00" :required="true" />
                <x-ui.form-group label="Stock *" name="stock" type="number" min="0" placeholder="0" :required="true" />
                <x-ui.form-group label="Proveedor" name="proveedor" placeholder="Nombre del proveedor" />
                <x-ui.form-group label="No. Serie" name="no_serie" placeholder="Número de serie" />
            </div>
            <x-ui.form-group label="Descripción" for="descripcion">
                <textarea id="descripcion" name="descripcion" rows="3" style="width:100%; padding:11px 12px; border:1px solid var(--border); border-radius:9px; font-size:15px; background:var(--surface); color:var(--text); resize:vertical;">{{ old('descripcion') }}</textarea>
            </x-ui.form-group>
            <x-ui.form-group label="Imagen del Producto" for="imagen">
                <input type="file" id="imagen" name="imagen" accept="image/*" style="width:100%; padding:8px; border:1px solid var(--border); border-radius:9px; font-size:14px; background:var(--surface); color:var(--text);">
                <small style="color:var(--muted);">Formatos: JPG, PNG, GIF. Máximo 5MB.</small>
            </x-ui.form-group>
        </x-ui.card>

        <div style="display:flex; gap:10px;">
            <x-ui.button>Guardar Producto</x-ui.button>
            <a href="{{ route('inventory.productos.index') }}" class="btn btn--ghost" style="text-decoration:none;">Cancelar</a>
        </div>
    </form>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const equipmentSelect = document.getElementById('equipment_id');
            const equipmentOptions = @json($equipmentOptions ?? []);
            const linkedFields = ['tipo_equipo', 'subtipo', 'marca', 'modelo', 'proveedor', 'no_serie', 'descripcion'];

            if (!equipmentSelect) {
                return;
            }

            function applyEquipment() {
                const selected = equipmentOptions.find(function (option) {
                    return String(option.id) === equipmentSelect.value;
                });

                linkedFields.forEach(function (name) {
                    const field = document.querySelector('[name="' + name + '"]');

                    if (!field) {
                        return;
                    }

                    if (selected) {
                        field.value = selected[name] || '';
                        field.readOnly = true;
                        field.style.opacity = '0.7';
                    } else {
                        field.readOnly = false;
                        field.style.opacity = '';
                    }
                });
            }

            equipmentSelect.addEventListener('change', applyEquipment);
            applyEquipment();
        });
    </script>
@endsection
