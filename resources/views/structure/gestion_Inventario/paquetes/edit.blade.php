@extends('layouts.dashboard')
@section('title', 'Editar Paquete')
@section('page-title', 'Editar Paquete')
@section('page-sub', $paquete->nombre)

@push('head')
<style>
    .package-form { display: grid; gap: 20px; max-width: 760px; }
    .package-form__header { margin-bottom: 2px; }
    .package-form__header h2 { margin: 0 0 4px; font-size: 1.3rem; font-weight: 900; color: var(--text); }
    .package-form__header p { margin: 0; color: var(--muted); font-size: 0.9rem; }

    .form-card {
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: 16px;
        padding: 22px;
        box-shadow: var(--shadow);
    }
    .form-card__title {
        display: flex;
        align-items: center;
        gap: 10px;
        margin: 0 0 18px;
        font-size: 1rem;
        font-weight: 900;
        color: var(--text);
    }
    .form-card__title svg { width: 20px; height: 20px; color: var(--primary); }

    .form-field { display: grid; gap: 6px; }
    .form-field label { font-size: 0.85rem; font-weight: 800; color: var(--text); }
    .form-field input, .form-field select {
        width: 100%;
        padding: 12px 14px;
        border: 1px solid var(--border);
        border-radius: 10px;
        font-size: 0.95rem;
        background: var(--surface);
        color: var(--text);
        outline: none;
    }
    .form-field input:focus, .form-field select:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(21,139,232,.12); }

    .products-table { width: 100%; border-collapse: collapse; }
    .products-table th {
        text-align: left;
        padding: 10px 12px;
        font-size: 0.72rem;
        font-weight: 900;
        color: var(--muted);
        text-transform: uppercase;
        letter-spacing: 0.03em;
        border-bottom: 1px solid var(--border);
    }
    .products-table td { padding: 10px 12px; border-bottom: 1px solid var(--border); }
    .products-table td:last-child { width: 50px; }
    .product-select, .product-qty {
        width: 100%;
        padding: 10px 12px;
        border: 1px solid var(--border);
        border-radius: 8px;
        font-size: 0.9rem;
        background: var(--surface);
        color: var(--text);
    }
    .product-qty { max-width: 90px; }
    .remove-row {
        width: 36px;
        height: 36px;
        border: 1px solid var(--border);
        border-radius: 8px;
        background: var(--surface);
        color: var(--text);
        font-size: 1.2rem;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .remove-row:hover { background: #fee2e2; color: #991b1b; border-color: #fecaca; }

    .add-product {
        margin-top: 14px;
        padding: 9px 14px;
        border: 1px dashed var(--border);
        border-radius: 9px;
        background: var(--surface);
        color: var(--text);
        font-size: 0.88rem;
        font-weight: 800;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }
    .add-product:hover { background: var(--surface-2); }
    .add-product svg { width: 16px; height: 16px; }

    .summary-bar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        padding: 14px 18px;
        border-radius: 12px;
        background: rgba(21, 139, 232, .08);
        border: 1px solid rgba(21, 139, 232, .2);
    }
    .summary-bar p { margin: 0; font-size: 0.9rem; color: var(--text); }
    .summary-bar strong { font-size: 1.1rem; color: #158be8; }

    .form-actions { display: flex; gap: 12px; margin-top: 6px; }
    .form-actions button, .form-actions a {
        padding: 11px 18px;
        border-radius: 10px;
        font-size: 0.9rem;
        font-weight: 800;
        text-decoration: none;
        border: 0;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 7px;
    }
    .form-actions .btn-primary { background: var(--primary); color: #fff; }
    .form-actions .btn-primary:hover { filter: brightness(.95); }
    .form-actions .btn-ghost { background: var(--surface); color: var(--text); border: 1px solid var(--border); }
    .form-actions .btn-ghost:hover { background: var(--surface-2); }
</style>
@endpush

@section('content')
    <div class="package-form">
        <div class="package-form__header">
            <h2>Editar paquete</h2>
            <p>Modifica los productos, cantidades o el nombre del paquete.</p>
        </div>

        <form method="POST" action="{{ route('inventory.paquetes.update', $paquete) }}">
            @csrf
            @method('PUT')

            <div class="form-card">
                <h3 class="form-card__title">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><line x1="8" y1="12" x2="16" y2="12"></line></svg>
                    Datos del paquete
                </h3>
                <div class="form-field">
                    <label for="nombre">Nombre del paquete *</label>
                    <input id="nombre" type="text" name="nombre" value="{{ old('nombre', $paquete->nombre) }}" placeholder="Ej. Paquete Endoscopía Básico" required>
                </div>
            </div>

            <div class="form-card">
                <h3 class="form-card__title">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path><line x1="3" y1="6" x2="21" y2="6"></line><path d="M16 10a4 4 0 0 1-8 0"></path></svg>
                    Productos del paquete
                </h3>

                <table class="products-table">
                    <thead>
                        <tr>
                            <th>Producto</th>
                            <th style="width: 110px;">Cantidad</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody id="productos-body">
                        @php $index = 0; @endphp
                        @foreach($paquete->productos as $producto)
                            <tr class="producto-row">
                                <td>
                                    <select name="productos[{{ $index }}][id]" class="product-select" required>
                                        <option value="" disabled>Seleccione un producto</option>
                                        @foreach($productos as $prod)
                                            <option value="{{ $prod->id }}" data-precio="{{ $prod->precio }}" @selected($prod->id == $producto->id)>
                                                {{ $prod->tipo_equipo }} {{ $prod->marca }} {{ $prod->modelo }} — ${{ number_format($prod->precio, 2) }}
                                            </option>
                                        @endforeach
                                    </select>
                                </td>
                                <td>
                                    <input type="number" name="productos[{{ $index }}][cantidad]" class="product-qty" value="{{ $producto->pivot->cantidad }}" min="1" required>
                                </td>
                                <td>
                                    <button type="button" class="remove-row">×</button>
                                </td>
                            </tr>
                            @php $index++; @endphp
                        @endforeach
                    </tbody>
                </table>

                <button type="button" class="add-product" id="add-producto">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 5v14M5 12h14"/></svg>
                    Agregar otro producto
                </button>

                <div class="summary-bar" style="margin-top: 18px;">
                    <p>Productos: <strong id="total-productos">0</strong></p>
                    <p>Total estimado: <strong id="total-precio">$0.00</strong></p>
                </div>
            </div>

            <div class="form-actions">
                <a href="{{ route('inventory.paquetes.index') }}" class="btn-ghost">Cancelar</a>
                <button type="submit" class="btn-primary">Actualizar paquete</button>
            </div>
        </form>
    </div>

    <script>
        let productoIndex = {{ $index }};
        const productosBody = document.getElementById('productos-body');
        const productOptions = document.querySelector('.product-select').innerHTML;

        function updateSummary() {
            const rows = document.querySelectorAll('.producto-row');
            let totalProductos = 0;
            let totalPrecio = 0;
            rows.forEach(row => {
                const select = row.querySelector('.product-select');
                const qtyInput = row.querySelector('.product-qty');
                const option = select.options[select.selectedIndex];
                if (option && option.value) {
                    const cantidad = parseInt(qtyInput.value) || 0;
                    const precio = parseFloat(option.dataset.precio) || 0;
                    totalProductos += cantidad;
                    totalPrecio += cantidad * precio;
                }
            });
            document.getElementById('total-productos').textContent = totalProductos;
            document.getElementById('total-precio').textContent = '$' + totalPrecio.toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }

        function bindRow(row) {
            row.querySelector('.product-select')?.addEventListener('change', updateSummary);
            row.querySelector('.product-qty')?.addEventListener('input', updateSummary);
            const removeBtn = row.querySelector('.remove-row');
            if (removeBtn) {
                removeBtn.addEventListener('click', function() {
                    row.remove();
                    updateSummary();
                });
            }
        }

        document.querySelectorAll('.producto-row').forEach(bindRow);

        document.getElementById('add-producto').addEventListener('click', function() {
            const row = document.createElement('tr');
            row.className = 'producto-row';
            row.innerHTML = `
                <td><select name="productos[${productoIndex}][id]" class="product-select" required>${productOptions}</select></td>
                <td><input type="number" name="productos[${productoIndex}][cantidad]" class="product-qty" value="1" min="1" required></td>
                <td><button type="button" class="remove-row">×</button></td>
            `;
            productosBody.appendChild(row);
            bindRow(row);
            productoIndex++;
            updateSummary();
        });

        updateSummary();
    </script>
@endsection
