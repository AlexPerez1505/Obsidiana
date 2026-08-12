@extends('layouts.dashboard')

@section('title', 'Editar Movimiento')
@section('page-title', 'Editar Movimiento')
@section('page-sub', 'Gestión de Inventario > Entrada / Salida > Editar')

@section('content')
    <p class="muted" style="margin:0 0 18px; font-size:14px;">Edita los datos del movimiento {{ $movement->folio }}.</p>

    <form method="POST" action="{{ route('inventory.movimientos.update', $movement) }}" style="display:grid; gap:18px;">
        @csrf
        @method('PUT')

        <x-ui.card>
            <x-ui.section-title style="margin:0 0 18px; color:var(--primary, #1689ff);">Datos del movimiento</x-ui.section-title>

            <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(260px, 1fr)); gap:18px;">
                <x-ui.form-group for="movement_type" label="Tipo de movimiento *">
                    <select id="movement_type" name="movement_type" required style="width:100%; padding:11px 12px; border:1px solid var(--border); border-radius:9px; font-size:15px; background:var(--surface); color:var(--text);">
                        <option value="" disabled @selected(!old('movement_type', $movement->movement_type))>Selecciona una opción</option>
                        <option value="entrada" @selected(old('movement_type', $movement->movement_type) === 'entrada')>Entrada</option>
                        <option value="salida" @selected(old('movement_type', $movement->movement_type) === 'salida')>Salida</option>
                        <option value="transferencia" @selected(old('movement_type', $movement->movement_type) === 'transferencia')>Transferencia</option>
                    </select>
                </x-ui.form-group>

                <x-ui.form-group for="movement_date" label="Fecha del movimiento *">
                    <input id="movement_date" name="movement_date" type="date" value="{{ old('movement_date', $movement->movement_date->format('Y-m-d')) }}" required style="width:100%; padding:11px 12px; border:1px solid var(--border); border-radius:9px; font-size:15px; background:var(--surface); color:var(--text);">
                </x-ui.form-group>

                <x-ui.form-group for="warehouse" label="Almacén *">
                    <select id="warehouse" name="warehouse" required style="width:100%; padding:11px 12px; border:1px solid var(--border); border-radius:9px; font-size:15px; background:var(--surface); color:var(--text);">
                        <option value="" disabled>Selecciona un almacén</option>
                        <option value="Almacén Central" @selected(old('warehouse', $movement->warehouse) == 'Almacén Central')>Almacén Central</option>
                        <option value="Almacén Norte" @selected(old('warehouse', $movement->warehouse) == 'Almacén Norte')>Almacén Norte</option>
                        <option value="Almacén Sur" @selected(old('warehouse', $movement->warehouse) == 'Almacén Sur')>Almacén Sur</option>
                    </select>
                </x-ui.form-group>

                <x-ui.form-group for="folio" label="Folio">
                    <input id="folio" type="text" value="{{ $movement->folio }}" readonly style="width:100%; padding:11px 12px; border:1px solid var(--border); border-radius:9px; font-size:15px; background:var(--surface-2); color:var(--muted);" />
                </x-ui.form-group>

                <x-ui.form-group for="product" label="Producto">
                    <input id="product" type="text" value="{{ $movement->item_name }}" readonly style="width:100%; padding:11px 12px; border:1px solid var(--border); border-radius:9px; font-size:15px; background:var(--surface-2); color:var(--muted);" />
                </x-ui.form-group>

                <x-ui.form-group for="quantity" label="Cantidad *">
                    <input id="quantity" name="quantity" type="number" min="1" value="{{ old('quantity', $movement->quantity) }}" required style="width:100%; padding:11px 12px; border:1px solid var(--border); border-radius:9px; font-size:15px; background:var(--surface); color:var(--text);">
                </x-ui.form-group>

                <x-ui.form-group for="unit" label="Unidad de medida *">
                    <select id="unit" name="unit" required style="width:100%; padding:11px 12px; border:1px solid var(--border); border-radius:9px; font-size:15px; background:var(--surface); color:var(--text);">
                        <option value="" disabled>Selecciona una unidad</option>
                        <option value="Pza" @selected(old('unit', $movement->unit) == 'Pza')>Pza</option>
                        <option value="Caja" @selected(old('unit', $movement->unit) == 'Caja')>Caja</option>
                        <option value="Kit" @selected(old('unit', $movement->unit) == 'Kit')>Kit</option>
                        <option value="Lote" @selected(old('unit', $movement->unit) == 'Lote')>Lote</option>
                    </select>
                </x-ui.form-group>

                <x-ui.form-group for="unit_cost" label="Costo unitario">
                    <div style="position:relative;">
                        <span style="position:absolute; left:12px; top:50%; transform:translateY(-50%); color:var(--muted); font-size:15px;">$</span>
                        <input id="unit_cost" name="unit_cost" type="number" step="0.01" min="0" value="{{ old('unit_cost', $movement->metadata['unit_cost'] ?? '0.00') }}" placeholder="0.00" style="width:100%; padding:11px 12px 11px 30px; border:1px solid var(--border); border-radius:9px; font-size:15px; background:var(--surface); color:var(--text);">
                    </div>
                </x-ui.form-group>

                <x-ui.form-group for="payment_method" label="Método de pago">
                    <select id="payment_method" name="payment_method" style="width:100%; padding:11px 12px; border:1px solid var(--border); border-radius:9px; font-size:15px; background:var(--surface); color:var(--text);">
                        <option value="" @selected(!old('payment_method', $movement->metadata['payment_method'] ?? ''))>Selecciona un método (opcional)</option>
                        <option value="Efectivo" @selected(old('payment_method', $movement->metadata['payment_method'] ?? '') == 'Efectivo')>Efectivo</option>
                        <option value="Transferencia" @selected(old('payment_method', $movement->metadata['payment_method'] ?? '') == 'Transferencia')>Transferencia</option>
                        <option value="Tarjeta" @selected(old('payment_method', $movement->metadata['payment_method'] ?? '') == 'Tarjeta')>Tarjeta</option>
                        <option value="Crédito" @selected(old('payment_method', $movement->metadata['payment_method'] ?? '') == 'Crédito')>Crédito</option>
                    </select>
                </x-ui.form-group>

                <x-ui.form-group for="reference" label="Referencia / Proveedor">
                    <input id="reference" name="reference" type="text" value="{{ old('reference', $movement->reference) }}" placeholder="Ej. Olimpus México SA de CV" style="width:100%; padding:11px 12px; border:1px solid var(--border); border-radius:9px; font-size:15px; background:var(--surface); color:var(--text);">
                </x-ui.form-group>

                <x-ui.form-group for="notes" label="Observaciones">
                    <textarea id="notes" name="notes" rows="3" placeholder="Agrega observaciones adicionales" style="width:100%; padding:11px 12px; border:1px solid var(--border); border-radius:9px; font-size:15px; background:var(--surface); color:var(--text); resize:vertical;">{{ old('notes', $movement->notes) }}</textarea>
                </x-ui.form-group>
            </div>
        </x-ui.card>

        <div style="display:flex; align-items:center; justify-content:flex-end; gap:12px;">
            <a href="{{ route('inventory.movimientos.index') }}" class="btn btn--ghost" style="text-decoration:none;">Cancelar</a>
            <x-ui.button>
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                Actualizar movimiento
            </x-ui.button>
        </div>
    </form>
@endsection
