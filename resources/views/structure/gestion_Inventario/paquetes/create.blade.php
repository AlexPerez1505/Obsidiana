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

        @php
            // Si viene precargado desde la selección múltiple en Productos,
            // arrancamos con un renglón por cada uno; si no, con uno vacío.
            $renglonesIniciales = $preseleccionados->isNotEmpty() ? $preseleccionados : collect([null]);
        @endphp

        <x-ui.card style="margin-bottom:18px;">
            <x-ui.section-title style="margin:0 0 16px;">Productos del Paquete</x-ui.section-title>
            @if($preseleccionados->isNotEmpty())
                <p style="margin:0 0 14px; color:var(--muted); font-size:13.5px;">
                    Se precargaron {{ $preseleccionados->count() }} producto(s) que seleccionaste. Ajusta la cantidad de cada uno si hace falta.
                </p>
            @endif
            <div id="productos-container" style="display:flex; flex-direction:column; gap:12px;">
                @foreach($renglonesIniciales as $i => $preseleccionado)
                    <div class="producto-row rgrid-producto-row">
                        <div>
                            <label style="display:block; font-size:13px; font-weight:600; margin-bottom:6px; color:var(--text);">Producto</label>
                            <select name="productos[{{ $i }}][id]" required class="producto-select" style="width:100%; padding:11px 12px; border:1px solid var(--border); border-radius:9px; font-size:15px; background:var(--surface); color:var(--text);">
                                <option value="" @if(! $preseleccionado) disabled selected @endif>Seleccione un producto</option>
                                @foreach($productos as $producto)
                                    <option value="{{ $producto->id }}" data-precio="{{ $producto->precio }}" @selected($preseleccionado && $preseleccionado->id === $producto->id)>
                                        {{ $producto->tipo_equipo }} {{ $producto->marca }} {{ $producto->modelo }} — ${{ number_format($producto->precio, 2) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label style="display:block; font-size:13px; font-weight:600; margin-bottom:6px; color:var(--text);">Cantidad</label>
                            <input type="number" name="productos[{{ $i }}][cantidad]" value="1" min="1" required style="width:100%; padding:11px 12px; border:1px solid var(--border); border-radius:9px; font-size:15px; background:var(--surface); color:var(--text);">
                        </div>
                        <div>
                            @if($i > 0 || $preseleccionados->count() > 1)
                                <button type="button" class="remove-producto-row" style="width:40px; height:40px; border:1px solid var(--border); border-radius:9px; background:var(--surface); color:var(--text); font-size:20px; cursor:pointer; display:flex; align-items:center; justify-content:center;">×</button>
                            @endif
                        </div>
                    </div>
                @endforeach
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
        let productoIndex = {{ $renglonesIniciales->count() }};

        document.querySelectorAll('.remove-producto-row').forEach(function (btn) {
            btn.addEventListener('click', function () {
                btn.closest('.producto-row').remove();
            });
        });

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
