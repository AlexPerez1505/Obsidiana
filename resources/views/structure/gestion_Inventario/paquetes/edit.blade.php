@extends('layouts.dashboard')
@section('title', 'Editar Paquete')
@section('page-title', 'Editar Paquete')
@section('page-sub', $paquete->nombre)

@section('content')
    <form method="POST" action="{{ route('inventory.paquetes.update', $paquete) }}" style="max-width:720px;">
        @csrf
        @method('PUT')
        <x-ui.card style="margin-bottom:18px;">
            <x-ui.section-title style="margin:0 0 16px;">Datos del Paquete</x-ui.section-title>
            <x-ui.form-group label="Nombre del Paquete *" name="nombre" :value="$paquete->nombre" :required="true" />
        </x-ui.card>

        <x-ui.card style="margin-bottom:18px;">
            <x-ui.section-title style="margin:0 0 16px;">Productos del Paquete</x-ui.section-title>
            <div id="productos-container" style="display:flex; flex-direction:column; gap:12px;">
                @php $index = 0; @endphp
                @foreach($paquete->productos as $producto)
                    <div class="producto-row rgrid-producto-row">
                        <div>
                            <label style="display:block; font-size:13px; font-weight:600; margin-bottom:6px; color:var(--text);">Producto</label>
                            <select name="productos[{{ $index }}][id]" required class="producto-select" style="width:100%; padding:11px 12px; border:1px solid var(--border); border-radius:9px; font-size:15px; background:var(--surface); color:var(--text);">
                                <option value="" disabled>Seleccione un producto</option>
                                @foreach($productos as $prod)
                                    <option value="{{ $prod->id }}" data-precio="{{ $prod->precio }}" @selected($prod->id == $producto->id)>
                                        {{ $prod->tipo_equipo }} {{ $prod->marca }} {{ $prod->modelo }} — ${{ number_format($prod->precio, 2) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label style="display:block; font-size:13px; font-weight:600; margin-bottom:6px; color:var(--text);">Cantidad</label>
                            <input type="number" name="productos[{{ $index }}][cantidad]" value="{{ $producto->pivot->cantidad }}" min="1" required style="width:100%; padding:11px 12px; border:1px solid var(--border); border-radius:9px; font-size:15px; background:var(--surface); color:var(--text);">
                        </div>
                        <div>
                            <button type="button" class="remove-producto" style="width:40px; height:40px; border:1px solid var(--border); border-radius:9px; background:var(--surface); color:var(--text); font-size:20px; cursor:pointer; display:flex; align-items:center; justify-content:center;">×</button>
                        </div>
                    </div>
                    @php $index++; @endphp
                @endforeach
            </div>
            <button type="button" id="add-producto" style="margin-top:16px; padding:8px 16px; border:1px dashed var(--border); border-radius:9px; background:var(--surface); color:var(--text); font-size:14px; cursor:pointer; display:flex; align-items:center; gap:6px;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 5v14M5 12h14"/></svg>
                Agregar otro producto
            </button>
        </x-ui.card>

        <div style="display:flex; gap:10px;">
            <x-ui.button>Actualizar Paquete</x-ui.button>
            <a href="{{ route('inventory.paquetes.index') }}" class="btn btn--ghost" style="text-decoration:none;">Cancelar</a>
        </div>
    </form>

    <script>
        let productoIndex = {{ $index }};
        document.getElementById('add-producto').addEventListener('click', function() {
            const container = document.getElementById('productos-container');
            const template = document.querySelector('.producto-row').cloneNode(true);
            template.querySelector('select').name = 'productos[' + productoIndex + '][id]';
            template.querySelector('select').value = '';
            template.querySelector('input').name = 'productos[' + productoIndex + '][cantidad]';
            template.querySelector('input').value = '1';
            container.appendChild(template);
            productoIndex++;
        });
        document.querySelectorAll('.remove-producto').forEach(btn => {
            btn.addEventListener('click', function() {
                this.closest('.producto-row').remove();
            });
        });
    </script>
@endsection
