@extends('layouts.dashboard')
@section('title', 'Agregar Paquete')
@section('page-title', 'Agregar Paquete')
@section('page-sub', 'Crea un paquete con múltiples productos del inventario')

@section('content')
    <form method="POST" action="{{ route('inventory.paquetes.store') }}" style="max-width:720px;">
        @csrf
        <x-ui.card style="margin-bottom:18px;">
            <x-ui.section-title style="margin:0 0 16px;">Datos del Paquete</x-ui.section-title>
            <x-ui.form-group label="Nombre del Paquete *" name="nombre" placeholder="Ej. Paquete Endoscopía Básico" :required="true" />
        </x-ui.card>

        <x-ui.card style="margin-bottom:18px;">
            <x-ui.section-title style="margin:0 0 16px;">Productos del Paquete</x-ui.section-title>
            <div id="productos-container" style="display:flex; flex-direction:column; gap:12px;">
                <div class="producto-row" style="display:grid; grid-template-columns:1fr 100px 40px; gap:10px; align-items:end;">
                    <div>
                        <label style="display:block; font-size:13px; font-weight:600; margin-bottom:6px; color:var(--text);">Producto</label>
                        <select name="productos[0][id]" required class="producto-select" style="width:100%; padding:11px 12px; border:1px solid var(--border); border-radius:9px; font-size:15px; background:var(--surface); color:var(--text);">
                            <option value="" disabled selected>Seleccione un producto</option>
                            @foreach($productos as $producto)
                                <option value="{{ $producto->id }}" data-precio="{{ $producto->precio }}">
                                    {{ $producto->tipo_equipo }} {{ $producto->marca }} {{ $producto->modelo }} — ${{ number_format($producto->precio, 2) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label style="display:block; font-size:13px; font-weight:600; margin-bottom:6px; color:var(--text);">Cantidad</label>
                        <input type="number" name="productos[0][cantidad]" value="1" min="1" required style="width:100%; padding:11px 12px; border:1px solid var(--border); border-radius:9px; font-size:15px; background:var(--surface); color:var(--text);">
                    </div>
                    <div></div>
                </div>
            </div>
            <button type="button" id="add-producto" style="margin-top:16px; padding:8px 16px; border:1px dashed var(--border); border-radius:9px; background:var(--surface); color:var(--text); font-size:14px; cursor:pointer; display:flex; align-items:center; gap:6px;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 5v14M5 12h14"/></svg>
                Agregar otro producto
            </button>
        </x-ui.card>

        <div style="display:flex; gap:10px;">
            <x-ui.button>Guardar Paquete</x-ui.button>
            <a href="{{ route('inventory.paquetes.index') }}" class="btn btn--ghost" style="text-decoration:none;">Cancelar</a>
        </div>
    </form>

    <script>
        let productoIndex = 1;
        document.getElementById('add-producto').addEventListener('click', function() {
            const container = document.getElementById('productos-container');
            const template = document.querySelector('.producto-row').cloneNode(true);
            template.querySelector('select').name = 'productos[' + productoIndex + '][id]';
            template.querySelector('select').value = '';
            template.querySelector('input').name = 'productos[' + productoIndex + '][cantidad]';
            template.querySelector('input').value = '1';
            const removeBtn = document.createElement('button');
            removeBtn.type = 'button';
            removeBtn.innerHTML = '×';
            removeBtn.style.cssText = 'width:40px; height:40px; border:1px solid var(--border); border-radius:9px; background:var(--surface); color:var(--text); font-size:20px; cursor:pointer; display:flex; align-items:center; justify-content:center;';
            removeBtn.addEventListener('click', function() {
                template.remove();
            });
            template.querySelector('div:last-child').replaceWith(removeBtn);
            container.appendChild(template);
            productoIndex++;
        });
    </script>
@endsection
