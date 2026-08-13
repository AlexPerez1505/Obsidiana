@extends('layouts.dashboard')
@section('title', 'Agregar Paquete')
@section('page-title', 'Agregar Paquete')
@section('page-sub', 'Crea un paquete con múltiples productos del inventario')

@section('content')
    <form method="POST" action="{{ route('inventory.paquetes.store') }}">
        @csrf
        <x-ui.card style="margin-bottom:18px;">
            <x-ui.section-title style="margin:0 0 16px;">Datos del Paquete</x-ui.section-title>
            <x-ui.form-group label="Nombre del Paquete *" name="nombre" placeholder="Ej. Paquete Endoscopía Básico" :required="true" />
        </x-ui.card>

        <x-ui.card style="margin-bottom:18px;">
            <div class="pchk-head">
                <div>
                    <x-ui.section-title style="margin:0;">Productos del Paquete</x-ui.section-title>
                    <div class="muted" style="font-size:13px; margin-top:4px;">Marca los productos que quieras incluir y define la cantidad de cada uno.</div>
                </div>
                <button type="button" id="pchk-ver-seleccionados" class="pchk-toggle-btn">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                    Ver seleccionados <span class="pchk-count" id="pchk-count">0</span>
                </button>
            </div>

            <div id="pchk-seleccionados-panel" class="pchk-seleccionados-panel" hidden>
                <div id="pchk-seleccionados-lista" class="pchk-chips"></div>
                <div id="pchk-seleccionados-vacio" class="muted" style="font-size:13px;">Aún no has seleccionado productos.</div>
            </div>

            <div class="pchk-search">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
                <input type="text" id="pchk-buscador" placeholder="Buscar producto por nombre, marca o modelo...">
            </div>

            <div id="pchk-lista" class="pchk-grid">
                @foreach($productos as $producto)
                    @php
                        $nombreProducto = trim("{$producto->tipo_equipo} {$producto->marca} {$producto->modelo}");
                    @endphp
                    <div class="pchk-item" data-search="{{ \Illuminate\Support\Str::lower($nombreProducto) }}">
                        <div class="pchk-item-row">
                            <input type="checkbox" id="pchk-{{ $producto->id }}" class="pchk-checkbox" data-id="{{ $producto->id }}" data-nombre="{{ $nombreProducto }}">
                            <label for="pchk-{{ $producto->id }}" class="pchk-item-label">
                                <span class="pchk-item-name">{{ $nombreProducto }}</span>
                                <span class="pchk-item-price muted">${{ number_format($producto->precio, 2) }} · Stock: {{ $producto->stock }}</span>
                            </label>
                        </div>
                        <div class="pchk-qty" data-qty-for="{{ $producto->id }}" hidden>
                            <span class="pchk-qty-label">Cantidad</span>
                            <div class="pchk-stepper">
                                <button type="button" class="pchk-qty-dec">−</button>
                                <input type="number" class="pchk-qty-input" name="productos[{{ $producto->id }}][cantidad]" value="1" min="1" disabled>
                                <button type="button" class="pchk-qty-inc">+</button>
                            </div>
                            <input type="hidden" class="pchk-id-input" name="productos[{{ $producto->id }}][id]" value="{{ $producto->id }}" disabled>
                        </div>
                    </div>
                @endforeach
            </div>
            <div id="pchk-sin-resultados" class="muted" style="text-align:center; padding:24px; font-size:14px;" hidden>
                No se encontraron productos con ese nombre.
            </div>
        </x-ui.card>

        <div style="display:flex; gap:10px;">
            <x-ui.button>Guardar Paquete</x-ui.button>
            <a href="{{ route('inventory.paquetes.index') }}" class="btn btn--ghost" style="text-decoration:none;">Cancelar</a>
        </div>
    </form>

    <script>
        const pchkLista = document.getElementById('pchk-lista');
        const pchkBuscador = document.getElementById('pchk-buscador');
        const pchkSinResultados = document.getElementById('pchk-sin-resultados');
        const pchkCount = document.getElementById('pchk-count');
        const pchkPanel = document.getElementById('pchk-seleccionados-panel');
        const pchkVerBtn = document.getElementById('pchk-ver-seleccionados');
        const pchkChips = document.getElementById('pchk-seleccionados-lista');
        const pchkVacio = document.getElementById('pchk-seleccionados-vacio');

        function actualizarSeleccionados() {
            const marcados = document.querySelectorAll('.pchk-checkbox:checked');
            pchkCount.textContent = marcados.length;

            pchkChips.innerHTML = '';
            if (marcados.length === 0) {
                pchkVacio.style.display = 'block';
            } else {
                pchkVacio.style.display = 'none';
                marcados.forEach(cb => {
                    const chip = document.createElement('div');
                    chip.className = 'pchk-chip';
                    chip.innerHTML = '<span>' + cb.dataset.nombre + '</span>';
                    const quitar = document.createElement('button');
                    quitar.type = 'button';
                    quitar.innerHTML = '×';
                    quitar.addEventListener('click', () => {
                        cb.checked = false;
                        cb.dispatchEvent(new Event('change'));
                    });
                    chip.appendChild(quitar);
                    pchkChips.appendChild(chip);
                });
            }
        }

        document.querySelectorAll('.pchk-checkbox').forEach(cb => {
            cb.addEventListener('change', function () {
                const id = this.dataset.id;
                const qtyBox = document.querySelector('.pchk-qty[data-qty-for="' + id + '"]');
                const qtyInput = qtyBox.querySelector('.pchk-qty-input');
                const idInput = qtyBox.querySelector('.pchk-id-input');
                const item = this.closest('.pchk-item');

                qtyBox.hidden = !this.checked;
                qtyInput.disabled = !this.checked;
                idInput.disabled = !this.checked;
                item.classList.toggle('pchk-item--activo', this.checked);

                actualizarSeleccionados();
            });
        });

        pchkLista.addEventListener('click', function (e) {
            const item = e.target.closest('.pchk-item');
            if (!item) return;
            const qtyInput = item.querySelector('.pchk-qty-input');

            if (e.target.classList.contains('pchk-qty-inc')) {
                qtyInput.value = (parseInt(qtyInput.value) || 1) + 1;
            }
            if (e.target.classList.contains('pchk-qty-dec')) {
                qtyInput.value = Math.max(1, (parseInt(qtyInput.value) || 1) - 1);
            }
        });

        pchkBuscador.addEventListener('input', function () {
            const q = this.value.trim().toLowerCase();
            let visibles = 0;
            document.querySelectorAll('.pchk-item').forEach(item => {
                const coincide = item.dataset.search.includes(q);
                item.style.display = coincide ? '' : 'none';
                if (coincide) visibles++;
            });
            pchkSinResultados.hidden = visibles !== 0;
        });

        pchkVerBtn.addEventListener('click', function () {
            pchkPanel.hidden = !pchkPanel.hidden;
            this.classList.toggle('pchk-toggle-btn--activo', !pchkPanel.hidden);
        });

        document.querySelector('form').addEventListener('submit', function (e) {
            if (document.querySelectorAll('.pchk-checkbox:checked').length === 0) {
                e.preventDefault();
                alert('Selecciona al menos un producto para el paquete.');
            }
        });

        actualizarSeleccionados();
    </script>

    <style>
        .pchk-head { display:flex; align-items:flex-start; justify-content:space-between; gap:12px; flex-wrap:wrap; margin-bottom:16px; }

        .pchk-toggle-btn { display:flex; align-items:center; gap:8px; padding:9px 14px; border:1px solid var(--border); border-radius:9px; background:var(--surface); color:var(--text); font-size:13px; font-weight:600; cursor:pointer; white-space:nowrap; transition:background .15s, border-color .15s; }
        .pchk-toggle-btn:hover { background:var(--surface-2); }
        .pchk-toggle-btn--activo { border-color:var(--primary); color:var(--primary); }
        .pchk-count { display:inline-flex; align-items:center; justify-content:center; min-width:20px; height:20px; padding:0 6px; border-radius:999px; background:var(--primary); color:#fff; font-size:11px; font-weight:700; }

        .pchk-seleccionados-panel { border:1px solid var(--border); border-radius:12px; padding:14px; margin-bottom:16px; background:var(--surface-2); }
        .pchk-chips { display:flex; flex-wrap:wrap; gap:8px; }
        .pchk-chip { display:flex; align-items:center; gap:8px; padding:6px 8px 6px 12px; border-radius:999px; background:var(--surface); border:1px solid var(--border); font-size:12.5px; color:var(--text); }
        .pchk-chip button { border:none; background:transparent; color:var(--muted); font-size:15px; line-height:1; cursor:pointer; padding:2px; }
        .pchk-chip button:hover { color:#dc2626; }

        .pchk-search { position:relative; margin-bottom:14px; }
        .pchk-search svg { position:absolute; left:12px; top:50%; transform:translateY(-50%); color:var(--muted); pointer-events:none; }
        .pchk-search input { width:100%; padding:11px 12px 11px 38px; border:1px solid var(--field-border, var(--border)); border-radius:9px; font-size:14px; background:var(--surface); color:var(--text); }
        .pchk-search input:focus { outline:none; border-color:var(--primary); box-shadow:0 0 0 3px rgba(0,122,255,.15); }

        .pchk-grid { display:grid; grid-template-columns:repeat(auto-fill, minmax(240px, 1fr)); gap:10px; max-height:460px; overflow-y:auto; padding-right:4px; }
        .pchk-item { border:1px solid var(--border); border-radius:10px; padding:10px 12px; background:var(--surface); transition:border-color .15s, background .15s; }
        .pchk-item--activo { border-color:var(--primary); background:var(--primary-soft); }

        .pchk-item-row { display:flex; align-items:flex-start; gap:10px; }
        .pchk-checkbox { width:18px; height:18px; margin-top:2px; flex:0 0 auto; cursor:pointer; accent-color:var(--primary); }
        .pchk-item-label { display:flex; flex-direction:column; gap:2px; cursor:pointer; min-width:0; }
        .pchk-item-name { font-size:13.5px; font-weight:600; color:var(--text); overflow-wrap:anywhere; }
        .pchk-item-price { font-size:12px; }

        .pchk-qty { display:flex; align-items:center; gap:10px; margin-top:10px; padding-top:10px; border-top:1px dashed var(--border); }
        .pchk-qty-label { font-size:12px; color:var(--muted); font-weight:600; }
        .pchk-stepper { display:flex; align-items:center; border:1px solid var(--border); border-radius:8px; overflow:hidden; margin-left:auto; }
        .pchk-stepper button { width:28px; height:30px; border:none; background:var(--surface-2); color:var(--text); cursor:pointer; font-size:15px; }
        .pchk-stepper button:hover { background:var(--border); }
        .pchk-qty-input { width:44px; text-align:center; border:none; background:transparent; color:var(--text); font-size:14px; padding:6px 2px; -moz-appearance:textfield; }
        .pchk-qty-input::-webkit-outer-spin-button, .pchk-qty-input::-webkit-inner-spin-button { -webkit-appearance:none; margin:0; }

        @media (max-width:640px) {
            .pchk-grid { grid-template-columns:1fr; max-height:420px; }
            .pchk-head { flex-direction:column; align-items:stretch; }
            .pchk-toggle-btn { justify-content:center; }
        }
    </style>
@endsection
