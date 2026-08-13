@extends('layouts.dashboard')
@section('title', 'Editar Producto')
@section('page-title', 'Editar Producto')
@section('page-sub', $producto->tipo_equipo)

@push('head')
<style>
    .product-edit-form { max-width: 720px; margin: 0 auto; }
    .product-edit-card { padding: 18px; border-radius: 14px; background: var(--surface); border: 1px solid rgba(0,168,255,0.55); box-shadow: 0 0 14px rgba(0,168,255,0.35), inset 0 1px 0 rgba(255,255,255,0.04); margin-bottom: 18px; }
    .product-edit-title { margin: 0 0 16px; color: var(--text); font-size: 1.05rem; font-weight: 800; }
    .product-edit-card label { color: var(--muted); font-size: 13px; font-weight: 700; }
    .product-edit-card input[type="text"], .product-edit-card input[type="number"], .product-edit-card input[type="file"], .product-edit-card select, .product-edit-card textarea { width: 100%; padding: 11px 14px; border: 1px solid rgba(0,168,255,0.55); border-radius: 10px; background: var(--surface); color: var(--text); font: inherit; font-size: 15px; box-sizing: border-box; }
    .product-edit-card input:focus, .product-edit-card select:focus, .product-edit-card textarea:focus { outline: none; border-color: #00A8FF; box-shadow: 0 0 0 3px rgba(0,168,255,0.18), 0 0 18px rgba(0,168,255,0.45); }
    .product-edit-card small { color: var(--muted); font-size: 12px; }
    .product-edit-actions { display: flex; gap: 10px; }
    .product-edit-btn { display: inline-flex; align-items: center; justify-content: center; flex: 1; padding: 11px 22px; border: 1px solid rgba(255,255,255,0.15); border-radius: 12px; background: linear-gradient(135deg, #00A8FF, #7C3AED); color: #fff; font-size: 14px; font-weight: 600; cursor: pointer; box-shadow: 0 0 12px rgba(59,130,246,0.35), 0 0 30px rgba(124,58,237,0.2); transition: all 0.2s ease; text-decoration: none; }
    .product-edit-btn:hover { filter: brightness(1.1); }
    .product-edit-btn--ghost { background: rgba(0,168,255,0.12); color: #00A8FF; border: 1px solid rgba(0,168,255,0.55); box-shadow: 0 0 10px rgba(0,168,255,0.15); }
    .product-edit-btn--ghost:hover { background: rgba(0,168,255,0.22); border-color: #00A8FF; }
    :root[data-theme="light"] .product-edit-card { background: #ffffff; border-color: rgba(0,168,255,0.55); box-shadow: 0 0 14px rgba(0,168,255,0.2), inset 0 1px 0 rgba(255,255,255,0.6); }
    :root[data-theme="light"] .product-edit-title { color: #00A8FF; }
    :root[data-theme="light"] .product-edit-card label { color: #3730a3; }
    :root[data-theme="light"] .product-edit-card input[type="text"], :root[data-theme="light"] .product-edit-card input[type="number"], :root[data-theme="light"] .product-edit-card input[type="file"], :root[data-theme="light"] .product-edit-card select, :root[data-theme="light"] .product-edit-card textarea { background: #ffffff; color: #1e1b4b; border-color: rgba(0,168,255,0.55); }
    :root[data-theme="light"] .product-edit-card input:focus, :root[data-theme="light"] .product-edit-card select:focus, :root[data-theme="light"] .product-edit-card textarea:focus { border-color: #00A8FF; box-shadow: 0 0 0 3px rgba(0,168,255,0.18), 0 0 18px rgba(0,168,255,0.35); }
    :root[data-theme="light"] .product-edit-btn--ghost { background: rgba(0,168,255,0.08); color: #00A8FF; border-color: rgba(0,168,255,0.55); }
    :root[data-theme="light"] .product-edit-btn--ghost:hover { background: rgba(0,168,255,0.14); border-color: #00A8FF; }
</style>
@endpush

@section('content')
    <form method="POST" class="product-edit-form" action="{{ route('inventory.productos.update', $producto) }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        <x-ui.card class="product-edit-card" style="margin-bottom:18px;">
            <x-ui.section-title class="product-edit-title" style="margin:0 0 16px;">Equipo del inventario</x-ui.section-title>
            <x-ui.form-group label="Seleccionar equipo (opcional)" for="equipment_id">
                <select id="equipment_id" name="equipment_id" class="product-edit-input">
                    <option value="">Producto sin equipo ligado (llenar manualmente)</option>
                    @foreach ($equipmentOptions ?? [] as $option)
                        <option value="{{ $option['id'] }}" @selected(old('equipment_id', $producto->equipment_id) == $option['id'])>
                            {{ $option['code'] }} - {{ $option['name'] }}{{ $option['marca'] ? ' (' . $option['marca'] . ($option['modelo'] ? ' ' . $option['modelo'] : '') . ')' : '' }}
                        </option>
                    @endforeach
                </select>
                <small>Al elegir un equipo, sus datos se llenan automaticamente y quedan ligados al inventario.</small>
            </x-ui.form-group>
        </x-ui.card>

        <x-ui.card class="product-edit-card" style="margin-bottom:18px;">
            <x-ui.section-title class="product-edit-title" style="margin:0 0 16px;">Datos del Producto</x-ui.section-title>
            <div class="rgrid-2">
                <x-ui.form-group label="Tipo de Equipo *" name="tipo_equipo" :value="$producto->tipo_equipo" :required="true" />
                <x-ui.form-group label="Subtipo" name="subtipo" :value="$producto->subtipo" />
                <x-ui.form-group label="Marca" name="marca" :value="$producto->marca" />
                <x-ui.form-group label="Modelo" name="modelo" :value="$producto->modelo" />
                <x-ui.form-group label="Precio *" name="precio" type="number" step="0.01" min="0" :value="$producto->precio" :required="true" />
                <x-ui.form-group label="Stock *" name="stock" type="number" min="0" :value="$producto->stock" :required="true" />
                <x-ui.form-group label="Proveedor" name="proveedor" :value="$producto->proveedor" />
                <x-ui.form-group label="No. Serie" name="no_serie" :value="$producto->no_serie" />
            </div>
            <x-ui.form-group label="Descripción" for="descripcion">
                <textarea id="descripcion" name="descripcion" rows="3" class="product-edit-input" style="resize:vertical;">{{ old('descripcion', $producto->descripcion) }}</textarea>
            </x-ui.form-group>
            <x-ui.form-group label="Imagen del Producto" for="imagen">
                @if($producto->imagen_path)
                    <div style="margin-bottom:10px;">
                        <img src="{{ asset('storage/' . $producto->imagen_path) }}" alt="Imagen actual" style="max-height:150px; border-radius:8px; border:1px solid var(--border);">
                        <small style="color:var(--muted); display:block; margin-top:4px;">Imagen actual</small>
                    </div>
                @endif
                <input type="file" id="imagen" name="imagen" accept="image/*" class="product-edit-input">
                <small>Formatos: JPG, PNG, GIF. Máximo 5MB. Deja vacío para mantener la imagen actual.</small>
            </x-ui.form-group>
        </x-ui.card>

        <div class="product-edit-actions">
            <button type="submit" class="product-edit-btn">Actualizar Producto</button>
            <a href="{{ route('inventory.productos.index') }}" class="product-edit-btn product-edit-btn--ghost">Cancelar</a>
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
