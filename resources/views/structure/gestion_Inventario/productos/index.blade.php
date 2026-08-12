@extends('layouts.dashboard')

@section('title', 'Productos')
@section('page-title', 'Productos')
@section('page-sub', 'Inventario de equipos y stock disponible')

@php
    $total = $equipos->count();
@endphp

@push('head')
<style>
    .product-catalog { max-width: 1200px; margin: 0 auto; }
    .product-search { display: flex; gap: 12px; flex-wrap: wrap; margin-bottom: 18px; align-items: stretch; }
    .product-search__box { position: relative; flex: 1; min-width: 220px; }
    .product-search__box svg { position: absolute; left: 20px; top: 50%; transform: translateY(-50%); color: #00A8FF; width: 20px; height: 20px; pointer-events: none; }
    form.product-search .product-search__box input[type="text"] { width: 100%; padding: 11px 14px 11px 60px; border: 1px solid rgba(0,168,255,0.55); border-radius: 12px; background: rgba(4,10,24,0.72); color: #fff; font: inherit; font-size: 15px; box-sizing: border-box; }
    form.product-search .product-search__box input[type="text"]:focus { outline: none; border-color: #00A8FF; box-shadow: 0 0 0 3px rgba(0,168,255,0.18), 0 0 18px rgba(0,168,255,0.45); }
    .product-search select { min-width: 160px; padding: 11px 14px; border: 1px solid rgba(0,168,255,0.55); border-radius: 12px; background: rgba(4,10,24,0.72); color: #fff; font: inherit; font-size: 15px; }
    .product-search select:focus { outline: none; border-color: #00A8FF; box-shadow: 0 0 0 3px rgba(0,168,255,0.18), 0 0 18px rgba(0,168,255,0.45); }
    .product-search__btn { padding: 11px 22px; border-radius: 12px; background: rgba(8,18,40,0.45); color: #00A8FF; border: 1px solid rgba(0,168,255,0.55); font-size: 14px; font-weight: 600; cursor: pointer; box-shadow: 0 0 10px rgba(0,168,255,0.2); transition: all 0.2s ease; }
    .product-search__btn:hover { background: rgba(0,168,255,0.14); border-color: #00A8FF; }
    :root[data-theme="light"] .product-search__box input[type="text"] { background: #fff; color: var(--text); border-color: rgba(0,168,255,0.35); }
    :root[data-theme="light"] .product-search__box svg { color: #00A8FF; }
    :root[data-theme="light"] .product-search select { background: #fff; color: var(--text); border-color: rgba(0,168,255,0.35); }
    :root[data-theme="light"] .product-search__btn { background: rgba(0,168,255,0.08); border-color: rgba(0,168,255,0.55); color: #00A8FF; }
    :root[data-theme="light"] .product-search__btn:hover { background: rgba(0,168,255,0.14); border-color: #00A8FF; }
    .product-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 18px; }
    .product-card {
        display: flex; flex-direction: column; gap: 12px;
        padding: 18px; border-radius: 14px;
        background: var(--surface);
        border: 1px solid rgba(0,168,255,0.55);
        box-shadow: 0 0 14px rgba(0,168,255,0.35), inset 0 1px 0 rgba(255,255,255,0.04);
    }
    .product-card__thumb {
        width: 70px; height: 70px; display: grid; place-items: center;
        border: 1px dashed rgba(0,168,255,0.55); border-radius: 12px;
        background: var(--surface); overflow: hidden; padding: 8px;
    }
    .product-card__name { font-size: 1.05rem; font-weight: 800; color: var(--text); margin: 0; }
    .product-card__meta { color: var(--muted); font-size: 13px; margin: 0; }
    .product-card__label { font-size: 12px; font-weight: 700; color: var(--muted); margin-bottom: 4px; display: block; }
    .product-card__input {
        width: 100%; padding: 9px 12px; border: 1px solid rgba(0,168,255,0.55); border-radius: 10px;
        background: var(--surface); color: var(--text); font: inherit; font-size: 14px;
        box-sizing: border-box;
    }
    .product-card__btn { display: inline-flex; align-items: center; justify-content: center; width: 100%; margin-top: 4px; background: linear-gradient(135deg, #00A8FF, #7C3AED); color: #fff; border: 1px solid rgba(255,255,255,0.15); border-radius: 12px; padding: 10px 14px; font-size: 14px; font-weight: 600; cursor: pointer; box-shadow: 0 0 12px rgba(59,130,246,0.35), 0 0 30px rgba(124,58,237,0.2); transition: all 0.2s ease; }
    .product-card__btn:hover { filter: brightness(1.1); }
    .product-card__actions { display: flex; gap: 8px; margin-top: 4px; }
    .product-card__actions a, .product-card__actions form { flex: 1; display: flex; }
    .product-card__actions a { text-decoration: none; }
    .product-card__btn--ghost { background: rgba(0,168,255,0.12); color: #00A8FF; border: 1px solid rgba(0,168,255,0.55); box-shadow: 0 0 10px rgba(0,168,255,0.15); }
    .product-card__btn--ghost:hover { background: rgba(0,168,255,0.22); border-color: #00A8FF; }
    .product-card__btn--danger { background: rgba(239,68,68,0.12); color: #ef4444; border: 1px solid rgba(239,68,68,0.55); box-shadow: 0 0 10px rgba(239,68,68,0.15); }
    .product-card__btn--danger:hover { background: rgba(239,68,68,0.22); border-color: #ef4444; }
    :root[data-theme="light"] .product-card__btn--ghost { background: rgba(0,168,255,0.08); color: #00A8FF; }
    :root[data-theme="light"] .product-card__btn--danger { background: rgba(239,68,68,0.08); color: #ef4444; }
    .product-alert { padding: 12px 16px; border-radius: 10px; margin-bottom: 14px; font-size: 14px; font-weight: 600; }
    .product-alert--success { background: rgba(34,197,94,0.12); color: #22c55e; border: 1px solid rgba(34,197,94,0.55); }
    .product-alert--error { background: rgba(239,68,68,0.12); color: #ef4444; border: 1px solid rgba(239,68,68,0.55); }
    .product-empty { text-align: center; padding: 32px; color: var(--muted); }
    .product-foot { display: flex; align-items: center; justify-content: space-between; margin-top: 18px; color: var(--muted); font-size: 13px; font-weight: 600; }
    .nip-modal { position: fixed; inset: 0; z-index: 1000; display: none; }
    .nip-modal__overlay { position: absolute; inset: 0; background: rgba(0,0,0,0.6); display: grid; place-items: center; padding: 20px; }
    .nip-modal__card { width: 100%; max-width: 360px; padding: 24px; border-radius: 14px; background: var(--surface); border: 1px solid rgba(0,168,255,0.55); box-shadow: 0 0 20px rgba(0,168,255,0.35); }
    .nip-modal__title { margin: 0 0 12px; color: var(--text); font-size: 1.1rem; font-weight: 800; }
    .nip-modal__text { margin: 0 0 16px; color: var(--muted); font-size: 14px; }
    .nip-modal__actions { display: flex; gap: 12px; margin-top: 18px; }
    .nip-modal__actions .product-card__btn { margin: 0; }
</style>
@endpush

@section('content')
    <div class="product-catalog">
        @if(session('status'))
            <div class="product-alert product-alert--success">{{ session('status') }}</div>
        @endif
        @if(session('error'))
            <div class="product-alert product-alert--error">{{ session('error') }}</div>
        @endif
        <form method="GET" class="product-search">
            <div class="product-search__box">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="11" cy="11" r="7"></circle>
                    <path d="m20 20-3.5-3.5"></path>
                </svg>
                <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Buscar por tipo, marca, modelo o serie..." autocomplete="off">
            </div>
            <select name="tipo">
                <option value="">Todos los tipos</option>
                @foreach($tipos as $tipo)
                    <option value="{{ $tipo }}" {{ ($filters['tipo'] ?? '') == $tipo ? 'selected' : '' }}>{{ $tipo }}</option>
                @endforeach
            </select>
            <select name="marca">
                <option value="">Todas las marcas</option>
                @foreach($marcas as $marca)
                    <option value="{{ $marca }}" {{ ($filters['marca'] ?? '') == $marca ? 'selected' : '' }}>{{ $marca }}</option>
                @endforeach
            </select>
            <button type="submit" class="product-search__btn">Filtrar</button>
        </form>

        @if($total === 0)
            <p class="product-empty">No hay equipos registrados.</p>
        @else
            <div class="product-grid">
                @foreach($equipos as $equipo)
                    @php $producto = $productos->get($equipo->id); @endphp
                    <div class="product-card">
                        <div style="display:flex; align-items:center; gap:14px;">
                            <div class="product-card__thumb">
                                @if($producto?->imagen_path)
                                    <img src="{{ asset('storage/' . $producto->imagen_path) }}" alt="{{ $equipo->name }}" style="max-width:100%; max-height:100%; object-fit:contain;">
                                @else
                                    @include('structure.gestion_Inventario.equipos.partials.equipment-thumb', ['type' => $equipo->thumb])
                                @endif
                            </div>
                            <div>
                                <p class="product-card__name">{{ $equipo->name }}</p>
                                <p class="product-card__meta">{{ $equipo->brand?->name }} {{ $equipo->equipmentModel?->name }}</p>
                                <p class="product-card__meta" style="margin-top:2px;">Serie: {{ $equipo->serial_number ?: '—' }}</p>
                            </div>
                        </div>

                        <form method="POST" action="{{ route('inventory.productos.sync') }}" style="display:grid; gap:10px;">
                            @csrf
                            <input type="hidden" name="equipment_id" value="{{ $equipo->id }}">
                            <div>
                                <label class="product-card__label">Precio</label>
                                <input type="number" step="0.01" min="0" name="precio" class="product-card__input" value="{{ $producto?->precio ?? '' }}" placeholder="0.00" required>
                            </div>
                            <div>
                                <label class="product-card__label">Stock</label>
                                <input type="number" min="0" name="stock" class="product-card__input" value="{{ $producto?->stock ?? '' }}" placeholder="0" required>
                            </div>
                            <button type="submit" class="product-card__btn">Guardar</button>
                        </form>

                        @if($producto)
                            <div class="product-card__actions">
                                <button type="button" class="product-card__btn product-card__btn--ghost" data-edit-href="{{ route('inventory.productos.edit', $producto) }}">Editar</button>
                                <form method="POST" action="{{ route('inventory.productos.destroy', $producto) }}" data-delete-form>
                                    @csrf
                                    @method('DELETE')
                                    <input type="hidden" name="nip" value="">
                                    <button type="submit" class="product-card__btn product-card__btn--danger">Eliminar</button>
                                </form>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>

            <div class="product-foot">
                <span>Mostrando 1 a {{ $total }} de {{ $total }} resultados</span>
            </div>
        @endif
    </div>

    <div id="nipModal" class="nip-modal">
        <div class="nip-modal__overlay">
            <div class="nip-modal__card">
                <h3 class="nip-modal__title">Confirmar eliminación</h3>
                <p class="nip-modal__text">Ingrese el NIP de acceso para eliminar:</p>
                <input type="password" id="nipInput" class="product-card__input" placeholder="NIP" autocomplete="off">
                <div class="nip-modal__actions">
                    <button type="button" id="nipCancel" class="product-card__btn product-card__btn--ghost">Cancelar</button>
                    <button type="button" id="nipAccept" class="product-card__btn">Aceptar</button>
                </div>
            </div>
        </div>
    </div>

    <div id="editNipModal" class="nip-modal">
        <div class="nip-modal__overlay">
            <div class="nip-modal__card">
                <h3 class="nip-modal__title">Confirmar edición</h3>
                <p class="nip-modal__text">Ingrese el NIP de acceso para editar:</p>
                <input type="password" id="editNipInput" class="product-card__input" placeholder="NIP" autocomplete="off">
                <div class="nip-modal__actions">
                    <button type="button" id="editNipCancel" class="product-card__btn product-card__btn--ghost">Cancelar</button>
                    <button type="button" id="editNipAccept" class="product-card__btn">Aceptar</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        const nipModal = document.getElementById('nipModal');
        const nipInput = document.getElementById('nipInput');
        let currentDeleteForm = null;

        const editNipModal = document.getElementById('editNipModal');
        const editNipInput = document.getElementById('editNipInput');
        let currentEditHref = null;

        document.querySelectorAll('[data-delete-form]').forEach(form => {
            form.addEventListener('submit', (e) => {
                e.preventDefault();
                currentDeleteForm = form;
                nipInput.value = '';
                nipModal.style.display = 'block';
                nipInput.focus();
            });
        });

        document.querySelectorAll('[data-edit-href]').forEach(btn => {
            btn.addEventListener('click', () => {
                currentEditHref = btn.dataset.editHref;
                editNipInput.value = '';
                editNipModal.style.display = 'block';
                editNipInput.focus();
            });
        });

        document.getElementById('nipAccept').addEventListener('click', () => {
            if (!currentDeleteForm) return;
            const nip = nipInput.value.trim();
            if (!nip) return;
            currentDeleteForm.querySelector('input[name="nip"]').value = nip;
            currentDeleteForm.submit();
        });

        document.getElementById('editNipAccept').addEventListener('click', () => {
            if (!currentEditHref) return;
            const nip = editNipInput.value.trim();
            if (!nip) return;
            window.location.href = currentEditHref + (currentEditHref.includes('?') ? '&' : '?') + 'nip=' + encodeURIComponent(nip);
        });

        function closeNipModal() {
            nipModal.style.display = 'none';
            currentDeleteForm = null;
        }

        function closeEditNipModal() {
            editNipModal.style.display = 'none';
            currentEditHref = null;
        }

        document.getElementById('nipCancel').addEventListener('click', closeNipModal);
        document.getElementById('editNipCancel').addEventListener('click', closeEditNipModal);

        nipModal.querySelector('.nip-modal__overlay').addEventListener('click', (e) => {
            if (e.target === e.currentTarget) closeNipModal();
        });

        editNipModal.querySelector('.nip-modal__overlay').addEventListener('click', (e) => {
            if (e.target === e.currentTarget) closeEditNipModal();
        });
    </script>
@endsection
