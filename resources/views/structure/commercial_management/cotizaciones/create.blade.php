@extends('layouts.dashboard')
@section('title', 'Nueva Cotización')
@section('page-title', 'Nueva Cotización')
@section('page-sub', 'Selecciona un cliente, un producto o paquete, y define el plan de pagos')

@section('content')
    <form method="POST" action="{{ route('commercial.cotizaciones.store') }}" id="form-cotizacion" style="max-width:840px;">
        @csrf

        {{-- Cliente --}}
        <x-ui.card style="margin-bottom:18px;">
            <x-ui.section-title style="margin:0 0 16px;">Cliente</x-ui.section-title>

            <input type="hidden" name="cliente_id" id="cliente_id" value="{{ $clienteSeleccionado?->id }}">

            <div id="cliente-buscador" style="{{ $clienteSeleccionado ? 'display:none;' : '' }}">
                <div style="position:relative;">
                    <input type="text" id="cliente-search" placeholder="Buscar cliente por nombre o teléfono..." autocomplete="off"
                           style="width:100%; padding:11px 12px; border:1px solid var(--border); border-radius:9px; font-size:15px; background:var(--surface); color:var(--text);">
                    <div id="cliente-resultados" style="position:absolute; top:calc(100% + 4px); left:0; right:0; background:var(--surface); border:1px solid var(--border); border-radius:9px; box-shadow:0 8px 24px rgba(0,0,0,.12); z-index:20; display:none; max-height:220px; overflow-y:auto;"></div>
                </div>
                <button type="button" id="btn-nuevo-cliente" class="btn btn--ghost" style="margin-top:10px;">
                    + Agregar cliente nuevo
                </button>
            </div>

            <div id="cliente-seleccionado" style="{{ $clienteSeleccionado ? '' : 'display:none;' }} display:flex; align-items:center; justify-content:space-between; padding:12px 14px; border:1px solid var(--border); border-radius:9px;">
                <span id="cliente-seleccionado-nombre">
                    @if($clienteSeleccionado)
                        {{ $clienteSeleccionado->nombre }} {{ $clienteSeleccionado->apellido }} — {{ $clienteSeleccionado->telefono }}
                    @endif
                </span>
                <button type="button" id="btn-cambiar-cliente" class="link" style="background:none; border:none; cursor:pointer;">Cambiar</button>
            </div>
        </x-ui.card>

        {{-- Producto / Paquete --}}
        <x-ui.card style="margin-bottom:18px;">
            <x-ui.section-title style="margin:0 0 16px;">Producto o Paquete</x-ui.section-title>
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
                <x-ui.form-group for="producto_id" label="Producto">
                    <select id="producto_id" name="producto_id" style="width:100%; padding:11px 12px; border:1px solid var(--border); border-radius:9px; font-size:15px; background:var(--surface); color:var(--text);">
                        <option value="">— Ninguno —</option>
                        @foreach($productos as $producto)
                            <option value="{{ $producto->id }}" data-precio="{{ $producto->precio }}" {{ $producto->stock < 1 ? 'disabled' : '' }}>
                                {{ $producto->tipo_equipo }} {{ $producto->marca }} {{ $producto->modelo }} — ${{ number_format($producto->precio, 2) }} (Stock: {{ $producto->stock }})
                            </option>
                        @endforeach
                    </select>
                </x-ui.form-group>
                <x-ui.form-group for="paquete_id" label="Paquete">
                    <select id="paquete_id" name="paquete_id" style="width:100%; padding:11px 12px; border:1px solid var(--border); border-radius:9px; font-size:15px; background:var(--surface); color:var(--text);">
                        <option value="">— Ninguno —</option>
                        @foreach($paquetes as $paquete)
                            @php
                                $disponible = true;
                                $stockInfo = [];
                                foreach($paquete->productos as $prod) {
                                    if($prod->pivot->cantidad > $prod->stock) {
                                        $disponible = false;
                                        $stockInfo[] = "{$prod->tipo_equipo} (req: {$prod->pivot->cantidad}, stock: {$prod->stock})";
                                    }
                                }
                            @endphp
                            <option value="{{ $paquete->id }}" data-precio="{{ $paquete->productos->sum(function($p) { return $p->precio * $p->pivot->cantidad; }) }}" {{ !$disponible ? 'disabled' : '' }}>
                                {{ $paquete->nombre }} — ${{ number_format($paquete->productos->sum(function($p) { return $p->precio * $p->pivot->cantidad; }), 2) }}
                                @if(!$disponible)
                                    (Sin stock: {{ implode(', ', $stockInfo) }})
                                @else
                                    (Disponible)
                                @endif
                            </option>
                        @endforeach
                    </select>
                </x-ui.form-group>
            </div>
        </x-ui.card>

        {{-- Montos --}}
        <x-ui.card style="margin-bottom:18px;">
            <x-ui.section-title style="margin:0 0 16px;">Montos</x-ui.section-title>
            <div style="display:grid; grid-template-columns:1fr 1fr 1fr 1fr; gap:16px;">
                <x-ui.form-group label="Subtotal *" name="subtotal" id="subtotal" type="number" step="0.01" min="0" :required="true" />
                <x-ui.form-group label="Descuentos" name="descuentos" type="number" step="0.01" min="0" value="0" />
                <x-ui.form-group label="IVA" name="iva" type="number" step="0.01" min="0" value="0" />
                <x-ui.form-group label="Costo de envío" name="costo_envio" type="number" step="0.01" min="0" value="0" />
            </div>
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-top:4px;">
                <x-ui.form-group label="Lugar" name="lugar" placeholder="Lugar de entrega" />
                <x-ui.form-group for="regalo" label="¿Es regalo?">
                    <input type="hidden" name="regalo" value="0">
                    <label class="ui-switch">
                        <input type="checkbox" id="regalo" name="regalo" value="1">
                        <span class="slider"></span>
                    </label>
                </x-ui.form-group>
            </div>
        </x-ui.card>

        {{-- Plan de pagos --}}
        <x-ui.card style="margin-bottom:18px;">
            <x-ui.section-title style="margin:0 0 16px;">Plan de Pagos</x-ui.section-title>
            <div style="display:grid; grid-template-columns:1fr 1fr 1fr 1fr; gap:16px;">
                <x-ui.form-group label="No. de pagos *" name="numero_pagos" type="number" min="1" max="36" value="1" :required="true" />
                <x-ui.form-group for="metodo_pago" label="Método de pago *">
                    <select id="metodo_pago" name="metodo_pago" required style="width:100%; padding:11px 12px; border:1px solid var(--border); border-radius:9px; font-size:15px; background:var(--surface); color:var(--text);">
                        <option value="Efectivo">Efectivo</option>
                        <option value="Transferencia">Transferencia</option>
                        <option value="Tarjeta">Tarjeta</option>
                        <option value="Cheque">Cheque</option>
                    </select>
                </x-ui.form-group>
                <x-ui.form-group label="Fecha del primer pago *" name="fecha_inicio" type="date" value="{{ now()->toDateString() }}" :required="true" />
                <x-ui.form-group label="Días entre pagos *" name="dias_entre_pagos" type="number" min="1" value="30" :required="true" />
            </div>
        </x-ui.card>

        <div style="display:flex; gap:10px;">
            <x-ui.button>Guardar Cotización</x-ui.button>
            <a href="{{ route('commercial.cotizaciones.index') }}" class="btn btn--ghost" style="text-decoration:none;">Cancelar</a>
        </div>
    </form>

    {{-- Modal: nuevo cliente --}}
    <div id="modal-cliente" class="modal-overlay" style="display:none;">
        <div class="modal-card" style="max-width:520px;">
            <h3 style="margin:0 0 14px; font-size:18px;">Nuevo cliente</h3>
            <form id="form-nuevo-cliente">
                @csrf
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:14px;">
                    <x-ui.form-group label="Nombre *" name="nombre" :required="true" />
                    <x-ui.form-group label="Apellido" name="apellido" />
                    <x-ui.form-group label="Teléfono" name="telefono" />
                    <x-ui.form-group label="Correo (Gmail)" name="gmail" type="email" />
                </div>
                <div class="modal-actions">
                    <button type="button" id="btn-cancelar-cliente" class="btn btn--ghost">Cancelar</button>
                    <x-ui.button type="submit" style="width:auto;">Guardar cliente</x-ui.button>
                </div>
            </form>
        </div>
    </div>

    <style>
        .modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.45); display: flex; align-items: center; justify-content: center; z-index: 1000; }
        .modal-card { background: var(--surface); border: 1px solid var(--border); border-radius: 12px; padding: 22px; width: 100%; box-shadow: 0 12px 32px rgba(0,0,0,0.2); }
        .modal-actions { display: flex; justify-content: flex-end; gap: 10px; margin-top: 16px; }
        #cliente-resultados .resultado-item { padding:10px 12px; cursor:pointer; font-size:14px; }
        #cliente-resultados .resultado-item:hover { background: var(--surface-2); }
        .ui-switch { position: relative; display: inline-block; width: 50px; height: 26px; }
        .ui-switch input { opacity: 0; width: 0; height: 0; }
        .ui-switch .slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #ccc; border-radius: 26px; transition: .4s; }
        .ui-switch .slider:before { position: absolute; content: ""; height: 22px; width: 22px; left: 2px; bottom: 2px; background-color: white; border-radius: 50%; transition: .4s; box-shadow: 0 1px 3px rgba(0,0,0,0.3); }
        .ui-switch input:checked + .slider { background-color: var(--green, #22c55e); }
        .ui-switch input:checked + .slider:before { transform: translateX(24px); }
    </style>

    <script>
        // Búsqueda de cliente
        const clienteSearch = document.getElementById('cliente-search');
        const clienteResultados = document.getElementById('cliente-resultados');
        const clienteIdInput = document.getElementById('cliente_id');
        const clienteBuscador = document.getElementById('cliente-buscador');
        const clienteSeleccionadoBox = document.getElementById('cliente-seleccionado');
        const clienteSeleccionadoNombre = document.getElementById('cliente-seleccionado-nombre');

        function seleccionarCliente(cliente) {
            clienteIdInput.value = cliente.id;
            clienteSeleccionadoNombre.textContent = `${cliente.nombre} ${cliente.apellido ?? ''} — ${cliente.telefono ?? 'sin teléfono'}`;
            clienteBuscador.style.display = 'none';
            clienteSeleccionadoBox.style.display = 'flex';
            clienteResultados.style.display = 'none';
            clienteSearch.value = '';
        }

        let debounceTimer;
        clienteSearch?.addEventListener('input', function () {
            clearTimeout(debounceTimer);
            const q = this.value.trim();
            if (q.length < 2) { clienteResultados.style.display = 'none'; return; }
            debounceTimer = setTimeout(() => {
                fetch('{{ route('commercial.cotizaciones.buscarCliente') }}?q=' + encodeURIComponent(q))
                    .then(r => r.json())
                    .then(data => {
                        clienteResultados.innerHTML = '';
                        if (data.length === 0) {
                            clienteResultados.innerHTML = '<div class="resultado-item" style="color:var(--muted);">Sin resultados</div>';
                        } else {
                            data.forEach(cliente => {
                                const item = document.createElement('div');
                                item.className = 'resultado-item';
                                item.textContent = `${cliente.nombre} ${cliente.apellido ?? ''} — ${cliente.telefono ?? ''}`;
                                item.addEventListener('click', () => seleccionarCliente(cliente));
                                clienteResultados.appendChild(item);
                            });
                        }
                        clienteResultados.style.display = 'block';
                    });
            }, 250);
        });

        document.getElementById('btn-cambiar-cliente')?.addEventListener('click', function () {
            clienteIdInput.value = '';
            clienteBuscador.style.display = 'block';
            clienteSeleccionadoBox.style.display = 'none';
        });

        // Modal nuevo cliente
        const modalCliente = document.getElementById('modal-cliente');
        const formNuevoCliente = document.getElementById('form-nuevo-cliente');

        document.getElementById('btn-nuevo-cliente')?.addEventListener('click', () => modalCliente.style.display = 'flex');
        document.getElementById('btn-cancelar-cliente')?.addEventListener('click', () => {
            modalCliente.style.display = 'none';
            formNuevoCliente.reset();
        });
        modalCliente.addEventListener('click', (e) => { if (e.target === modalCliente) modalCliente.style.display = 'none'; });

        formNuevoCliente.addEventListener('submit', function (e) {
            e.preventDefault();
            const formData = new FormData(this);
            fetch('{{ route('commercial.clientes.store') }}', {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                body: formData,
            })
            .then(r => r.json())
            .then(data => {
                seleccionarCliente(data);
                modalCliente.style.display = 'none';
                formNuevoCliente.reset();
            })
            .catch(() => alert('No se pudo guardar el cliente.'));
        });

        // Autocompletar subtotal según producto/paquete elegido
        const subtotalInput = document.getElementById('subtotal');
        document.getElementById('producto_id')?.addEventListener('change', function () {
            const precio = this.options[this.selectedIndex]?.dataset.precio;
            if (precio) { subtotalInput.value = precio; document.getElementById('paquete_id').value = ''; }
        });
        document.getElementById('paquete_id')?.addEventListener('change', function () {
            const precio = this.options[this.selectedIndex]?.dataset.precio;
            if (precio) { subtotalInput.value = precio; document.getElementById('producto_id').value = ''; }
        });
    </script>
@endsection
