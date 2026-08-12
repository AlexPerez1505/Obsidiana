@extends('layouts.dashboard')

@section('title', 'Nuevo Movimiento')
@section('page-title', 'Nuevo Movimiento')
@section('page-sub', 'Gestión de Inventario > Entrada / Salida > Nuevo Movimiento')

@section('content')
    <p class="muted" style="margin:0 0 18px; font-size:14px;">Registra un nuevo movimiento de entrada o salida de inventario.</p>

    <form method="POST" action="{{ route('inventory.movimientos.store') }}" style="display:grid; gap:18px;">
        @csrf

        <x-ui.card>
            <x-ui.section-title style="margin:0 0 18px; color:var(--primary, #1689ff);">Datos del movimiento</x-ui.section-title>

            <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(260px, 1fr)); gap:18px;">
                <x-ui.form-group for="movement_type" label="Tipo de movimiento *">
                    <select id="movement_type" name="movement_type" required style="width:100%; padding:11px 12px; border:1px solid var(--border); border-radius:9px; font-size:15px; background:var(--surface); color:var(--text);">
                        <option value="" disabled @selected(!old('movement_type', $defaultType))>Selecciona una opción</option>
                        <option value="entrada" @selected(old('movement_type', $defaultType) === 'entrada')>Entrada</option>
                        <option value="salida" @selected(old('movement_type', $defaultType) === 'salida')>Salida</option>
                        <option value="transferencia" @selected(old('movement_type', $defaultType) === 'transferencia')>Transferencia</option>
                    </select>
                </x-ui.form-group>

                <x-ui.form-group for="movement_date" label="Fecha del movimiento *">
                    <input id="movement_date" name="movement_date" type="date" value="{{ old('movement_date', now()->format('Y-m-d')) }}" required style="width:100%; padding:11px 12px; border:1px solid var(--border); border-radius:9px; font-size:15px; background:var(--surface); color:var(--text);">
                </x-ui.form-group>

                <x-ui.form-group for="warehouse" label="Almacén *">
                    <select id="warehouse" name="warehouse" required style="width:100%; padding:11px 12px; border:1px solid var(--border); border-radius:9px; font-size:15px; background:var(--surface); color:var(--text);">
                        <option value="" disabled selected>Selecciona un almacén</option>
                        <option value="Almacén Central" @selected(old('warehouse') == 'Almacén Central')>Almacén Central</option>
                        <option value="Almacén Norte" @selected(old('warehouse') == 'Almacén Norte')>Almacén Norte</option>
                        <option value="Almacén Sur" @selected(old('warehouse') == 'Almacén Sur')>Almacén Sur</option>
                    </select>
                </x-ui.form-group>

                <x-ui.form-group for="folio" label="Folio (automático)">
                    <input id="folio" type="text" value="Se generará al guardar el movimiento" readonly style="width:100%; padding:11px 12px; border:1px solid var(--border); border-radius:9px; font-size:15px; background:var(--surface-2); color:var(--muted);" />
                </x-ui.form-group>

                <div style="grid-column:1 / -1; min-width:0;">
                    <label for="item_id" style="display:block; margin:0 0 7px; color:var(--text); font-size:13px; font-weight:700;">Producto *</label>
                    <div style="display:flex; align-items:center; gap:12px; min-width:0;">
                        <select id="item_id" name="item_id" required style="flex:1; min-width:0; padding:11px 12px; border:1px solid var(--border); border-radius:9px; font-size:15px; background:var(--surface); color:var(--text);">
                            <option value="" disabled selected>Selecciona un producto ({{ $productos->count() }} disponibles)</option>
                            @foreach ($productos as $producto)
                                <option value="{{ $producto->id }}" data-name="{{ $producto->tipo_equipo }}" data-code="{{ $producto->no_serie ?? $producto->id }}" @selected(old('item_id') == $producto->id)>{{ $producto->tipo_equipo }} — {{ $producto->marca }} {{ $producto->modelo }}</option>
                            @endforeach
                        </select>
                        <a href="{{ route('inventory.productos.create') }}" class="btn" target="_blank" style="white-space:nowrap; flex-shrink:0;">+ Nuevo producto</a>
                    </div>
                    @error('item_id')
                        <p class="err" style="color:var(--danger); font-size:13px; margin:6px 0 0;">{{ $message }}</p>
                    @enderror
                </div>

                <x-ui.form-group for="quantity" label="Cantidad *">
                    <input id="quantity" name="quantity" type="number" min="1" value="{{ old('quantity', 1) }}" placeholder="Ej. 1" required style="width:100%; padding:11px 12px; border:1px solid var(--border); border-radius:9px; font-size:15px; background:var(--surface); color:var(--text);">
                </x-ui.form-group>

                <x-ui.form-group for="unit" label="Unidad de medida *">
                    <select id="unit" name="unit" required style="width:100%; padding:11px 12px; border:1px solid var(--border); border-radius:9px; font-size:15px; background:var(--surface); color:var(--text);">
                        <option value="" disabled selected>Selecciona una unidad</option>
                        <option value="Pza" @selected(old('unit') == 'Pza')>Pza</option>
                        <option value="Caja" @selected(old('unit') == 'Caja')>Caja</option>
                        <option value="Kit" @selected(old('unit') == 'Kit')>Kit</option>
                        <option value="Lote" @selected(old('unit') == 'Lote')>Lote</option>
                    </select>
                </x-ui.form-group>

                <x-ui.form-group for="unit_cost" label="Costo unitario">
                    <div style="position:relative;">
                        <span style="position:absolute; left:12px; top:50%; transform:translateY(-50%); color:var(--muted); font-size:15px;">$</span>
                        <input id="unit_cost" name="unit_cost" type="number" step="0.01" min="0" value="{{ old('unit_cost', '0.00') }}" placeholder="0.00" style="width:100%; padding:11px 12px 11px 30px; border:1px solid var(--border); border-radius:9px; font-size:15px; background:var(--surface); color:var(--text);">
                    </div>
                </x-ui.form-group>

                <x-ui.form-group for="payment_method" label="Método de pago">
                    <select id="payment_method" name="payment_method" style="width:100%; padding:11px 12px; border:1px solid var(--border); border-radius:9px; font-size:15px; background:var(--surface); color:var(--text);">
                        <option value="" selected>Selecciona un método (opcional)</option>
                        <option value="Efectivo" @selected(old('payment_method') == 'Efectivo')>Efectivo</option>
                        <option value="Transferencia" @selected(old('payment_method') == 'Transferencia')>Transferencia</option>
                        <option value="Tarjeta" @selected(old('payment_method') == 'Tarjeta')>Tarjeta</option>
                        <option value="Crédito" @selected(old('payment_method') == 'Crédito')>Crédito</option>
                    </select>
                </x-ui.form-group>

                <x-ui.form-group for="reference" label="Referencia / Proveedor">
                    <input id="reference" name="reference" type="text" value="{{ old('reference') }}" placeholder="Ej. Olimpus México SA de CV" style="width:100%; padding:11px 12px; border:1px solid var(--border); border-radius:9px; font-size:15px; background:var(--surface); color:var(--text);">
                </x-ui.form-group>
            </div>

            <div style="margin-top:18px; display:grid; gap:18px;">
                <x-ui.form-group for="description" label="Descripción / Concepto">
                    <textarea id="description" name="description" rows="3" placeholder="Ej. Entrada de producto por compra a proveedor" style="width:100%; padding:11px 12px; border:1px solid var(--border); border-radius:9px; font-size:15px; background:var(--surface); color:var(--text); resize:vertical;">{{ old('description') }}</textarea>
                </x-ui.form-group>

                <x-ui.form-group for="notes" label="Observaciones (opcional)">
                    <textarea id="notes" name="notes" rows="3" placeholder="Agrega observaciones adicionales sobre el movimiento" style="width:100%; padding:11px 12px; border:1px solid var(--border); border-radius:9px; font-size:15px; background:var(--surface); color:var(--text); resize:vertical;">{{ old('notes') }}</textarea>
                </x-ui.form-group>
            </div>
        </x-ui.card>

        <div style="display:flex; align-items:center; justify-content:flex-end; gap:12px;">
            <a href="{{ route('inventory.movimientos.index') }}" class="btn btn--ghost" style="text-decoration:none;">Cancelar</a>
            <x-ui.button>
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                Guardar movimiento
            </x-ui.button>
        </div>

        <p class="muted" style="margin:0; font-size:13px;">* Campos obligatorios</p>
    </form>
@endsection
