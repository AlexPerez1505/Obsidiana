@extends('layouts.dashboard')
@section('title', 'Editar producto')
@section('page-title', 'Editar producto')

@section('page-sub')
    Actualiza la información y existencias de <span style="color:var(--primary);font-weight:800;">{{ $producto->tipo_equipo }}</span>.
@endsection

@push('head')
<style>
    .product-edit-page { display: grid; gap: 24px; }
    .product-edit-grid { display: grid; grid-template-columns: 1.2fr 0.9fr; gap: 26px; align-items: start; }

    .product-card, .settings-card {
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: 18px;
        padding: 26px;
        box-shadow: var(--shadow);
        display: grid;
        gap: 22px;
    }

    .card-header { display: flex; align-items: center; gap: 10px; margin-bottom: 2px; }
    .card-header svg { width: 22px; height: 22px; color: var(--primary); }
    .card-header h4 { font-size: 1.05rem; font-weight: 800; color: var(--text); margin: 0; }

    .product-image-wrap { display: flex; flex-direction: column; align-items: center; gap: 12px; }
    .product-image {
        width: 220px;
        height: 220px;
        border-radius: 16px;
        background: #f5f8ff;
        border: 1px solid var(--border);
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
    }
    :root[data-theme="dark"] .product-image { background: #0d1528; }
    .product-image img { width: 100%; height: 100%; object-fit: contain; display: block; padding: 12px; }
    .product-image-empty svg { width: 64px; height: 64px; color: var(--muted); }
    .view-image-link {
        font-size: 0.9rem;
        font-weight: 700;
        color: var(--primary);
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }
    .view-image-link:hover { text-decoration: underline; }
    .view-image-link svg { width: 16px; height: 16px; }

    .product-title-row { text-align: center; }
    .product-title-row h2 { font-size: 1.6rem; font-weight: 900; margin: 0 0 10px; color: var(--text); }
    .product-status {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 4px 12px;
        border-radius: 999px;
        background: var(--green-soft);
        color: var(--green);
        font-size: 0.8rem;
        font-weight: 800;
    }
    .product-status::before {
        content: '';
        width: 7px;
        height: 7px;
        border-radius: 50%;
        background: currentColor;
    }
    .product-status--empty { background: var(--danger-soft); color: var(--danger); }

    .product-specs { display: grid; gap: 10px; }
    .product-spec {
        display: flex;
        align-items: center;
        gap: 14px;
        padding: 12px 14px;
        border-radius: 12px;
        background: var(--surface-2);
    }
    .product-spec svg { width: 20px; height: 20px; color: var(--muted); flex-shrink: 0; }
    .product-spec__content { flex: 1; min-width: 0; }
    .product-spec__label { display: block; font-size: 0.78rem; color: var(--muted); font-weight: 700; margin-bottom: 2px; }
    .product-spec__value { font-size: 0.95rem; color: var(--text); font-weight: 800; }

    .product-description { display: grid; gap: 10px; padding: 16px; border-radius: 12px; background: var(--surface-2); }
    .product-description__title { display: flex; align-items: center; gap: 8px; font-size: 0.9rem; font-weight: 800; color: var(--text); margin: 0; }
    .product-description__title svg { width: 18px; height: 18px; color: var(--muted); }
    .product-description__text { margin: 0; font-size: 0.9rem; color: var(--text); line-height: 1.5; }

    .product-meta-footer { display: flex; gap: 18px; flex-wrap: wrap; color: var(--muted); font-size: 0.78rem; font-weight: 700; }
    .product-meta-footer span { display: flex; align-items: center; gap: 6px; }
    .product-meta-footer svg { width: 14px; height: 14px; }

    .settings-sub { color: var(--muted); font-size: 0.92rem; margin: 0; }
    .settings-inputs { display: grid; grid-template-columns: 1fr 1fr; gap: 18px; }

    .input-group { display: grid; gap: 6px; }
    .input-group__label { font-size: 0.85rem; font-weight: 700; color: var(--text); }
    .input-wrap { position: relative; }
    .input-wrap__icon { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: var(--muted); pointer-events: none; }
    .input-wrap__icon svg { width: 20px; height: 20px; display: block; }
    .input-wrap input[type=number] {
        width: 100%;
        padding: 13px 14px 13px 44px;
        border: 1px solid var(--border);
        border-radius: 12px;
        font-size: 1rem;
        font-weight: 700;
        outline: none;
        background: var(--surface);
        color: var(--text);
        transition: border .15s, box-shadow .15s;
    }
    .input-wrap input[type=number]:focus { border-color: var(--primary); box-shadow: 0 0 0 4px rgba(0,122,255,.12); }
    .input-wrap input[type=number].is-empty { border-color: #f87171; background: #fff1f2; }
    :root[data-theme="dark"] .input-wrap input[type=number].is-empty { background: #3b1515; }
    .stock-badge {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        margin-top: 6px;
        padding: 5px 10px;
        border-radius: 8px;
        background: var(--danger-soft);
        color: var(--danger);
        font-size: 0.78rem;
        font-weight: 700;
    }
    .stock-badge svg { width: 14px; height: 14px; }

    .upload-zone {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 10px;
        padding: 30px 24px;
        border: 2px dashed #3b82f6;
        border-radius: 14px;
        background: #eff6ff;
        color: #3b82f6;
        text-align: center;
        cursor: pointer;
        transition: background .15s, border-color .15s;
    }
    :root[data-theme="dark"] .upload-zone { background: #0d1528; }
    .upload-zone:hover, .upload-zone.drag-over { background: #dbeafe; border-color: #2563eb; }
    :root[data-theme="dark"] .upload-zone:hover, :root[data-theme="dark"] .upload-zone.drag-over { background: #132142; }
    .upload-zone__icon svg { width: 42px; height: 42px; display: block; }
    .upload-zone__text { font-size: 0.95rem; font-weight: 700; margin: 0; color: var(--text); }
    .upload-zone__hint { font-size: 0.8rem; color: var(--muted); margin: 0; }
    .upload-zone__btn {
        display: inline-block;
        margin-top: 4px;
        padding: 8px 18px;
        border-radius: 8px;
        background: #3b82f6;
        color: #fff;
        font-size: 0.85rem;
        font-weight: 700;
        pointer-events: none;
    }
    .upload-zone input[type=file] { display: none; }

    .preview-box { display: none; align-items: center; gap: 12px; padding: 12px; border: 1px solid var(--border); border-radius: 12px; background: var(--surface-2); }
    .preview-box.is-visible { display: flex; }
    .preview-box__thumb { width: 48px; height: 48px; border-radius: 8px; object-fit: cover; border: 1px solid var(--border); background: #fff; }
    .preview-box__info { flex: 1; min-width: 0; }
    .preview-box__label { font-size: 0.78rem; color: var(--muted); font-weight: 700; margin: 0 0 2px; }
    .preview-box__name { font-size: 0.85rem; color: var(--text); font-weight: 700; margin: 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .preview-box__remove { background: none; border: none; color: var(--danger); cursor: pointer; padding: 6px; display: flex; align-items: center; justify-content: center; border-radius: 6px; }
    .preview-box__remove:hover { background: var(--danger-soft); }
    .preview-box__remove svg { width: 18px; height: 18px; display: block; }

    .form-actions { display: flex; gap: 12px; justify-content: flex-end; margin-top: 8px; }
    .form-actions .btn { display: inline-flex; align-items: center; gap: 8px; }
    .form-actions .btn svg { width: 18px; height: 18px; }

    @media (max-width: 980px) {
        .product-edit-grid { grid-template-columns: 1fr; }
    }
    @media (max-width: 560px) {
        .settings-inputs { grid-template-columns: 1fr; }
        .form-actions { flex-direction: column; }
        .form-actions .btn { width: 100%; justify-content: center; }
    }
</style>
@endpush

@push('scripts')
<script>
(function(){
    const input = document.getElementById('imagen');
    const zone = document.getElementById('upload-zone');
    const previewBox = document.getElementById('preview-box');
    const previewImg = document.getElementById('preview-img');
    const previewName = document.getElementById('preview-name');
    const remove = document.getElementById('remove-preview');

    function showPreview(file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            previewImg.src = e.target.result;
            previewName.textContent = file.name;
            previewBox.classList.add('is-visible');
        };
        reader.readAsDataURL(file);
    }

    input.addEventListener('change', function() {
        if (input.files && input.files[0]) {
            showPreview(input.files[0]);
        }
    });

    remove.addEventListener('click', function(e) {
        e.preventDefault();
        input.value = '';
        previewImg.src = '';
        previewName.textContent = '-';
        previewBox.classList.remove('is-visible');
    });

    ['dragenter','dragover','dragleave','drop'].forEach(function(evt){
        zone.addEventListener(evt, function(e){ e.preventDefault(); e.stopPropagation(); });
    });
    ['dragenter','dragover'].forEach(function(evt){
        zone.addEventListener(evt, function(){ zone.classList.add('drag-over'); });
    });
    ['dragleave','drop'].forEach(function(evt){
        zone.addEventListener(evt, function(){ zone.classList.remove('drag-over'); });
    });
    zone.addEventListener('drop', function(e) {
        const files = e.dataTransfer.files;
        if (files.length) {
            const dt = new DataTransfer();
            dt.items.add(files[0]);
            input.files = dt.files;
            input.dispatchEvent(new Event('change'));
        }
    });
})();
</script>
@endpush

@section('content')
<section class="product-edit-page">
    <form method="POST" action="{{ route('inventory.productos.update', $producto) }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div class="product-edit-grid">
            <div class="product-card">
                <div class="card-header">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
                    <h4>Información del producto</h4>
                </div>

                <div class="product-image-wrap">
                    <div class="product-image">
                        @if($producto->imagen_path)
                            <img src="{{ asset('storage/' . $producto->imagen_path) }}" alt="{{ $producto->tipo_equipo }}">
                        @else
                            <div class="product-image-empty">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="M21 15l-5-5L5 21"/></svg>
                            </div>
                        @endif
                    </div>
                    @if($producto->imagen_path)
                        <a href="{{ asset('storage/' . $producto->imagen_path) }}" target="_blank" class="view-image-link">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                            Ver imagen completa
                        </a>
                    @endif
                </div>

                <div class="product-title-row">
                    <h2>{{ $producto->tipo_equipo }}</h2>
                    <span class="product-status {{ $producto->stock == 0 ? 'product-status--empty' : '' }}">
                        {{ $producto->stock == 0 ? 'Sin existencias' : ($producto->estado ?: 'Activo') }}
                    </span>
                </div>

                <div class="product-specs">
                    <div class="product-spec">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>
                        <div class="product-spec__content">
                            <span class="product-spec__label">Subtipo</span>
                            <span class="product-spec__value">{{ $producto->subtipo ?: '—' }}</span>
                        </div>
                    </div>
                    <div class="product-spec">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M16.2 7.8l-2 6.3l-6.4 2.2l2-6.3z"/></svg>
                        <div class="product-spec__content">
                            <span class="product-spec__label">Marca</span>
                            <span class="product-spec__value">{{ $producto->marca ?: '—' }}</span>
                        </div>
                    </div>
                    <div class="product-spec">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="7" width="20" height="10" rx="2"/><path d="M16 7V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2"/></svg>
                        <div class="product-spec__content">
                            <span class="product-spec__label">Modelo</span>
                            <span class="product-spec__value">{{ $producto->modelo ?: '—' }}</span>
                        </div>
                    </div>
                    <div class="product-spec">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 7V4a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v3"/><path d="M4 11h16"/><path d="M4 15h16"/><path d="M4 19h16"/></svg>
                        <div class="product-spec__content">
                            <span class="product-spec__label">No. Serie</span>
                            <span class="product-spec__value">{{ $producto->no_serie ?: '—' }}</span>
                        </div>
                    </div>
                    <div class="product-spec">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg>
                        <div class="product-spec__content">
                            <span class="product-spec__label">Proveedor</span>
                            <span class="product-spec__value">{{ $producto->proveedor ?: '—' }}</span>
                        </div>
                    </div>
                </div>

                <div class="product-description">
                    <p class="product-description__title">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                        Descripción
                    </p>
                    <p class="product-description__text">{{ $producto->descripcion ?: 'Sin descripción' }}</p>
                </div>

                <div class="product-meta-footer">
                    <span>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                        Creado: {{ $producto->created_at->format('d/m/Y') }}
                    </span>
                    <span>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                        Última actualización: {{ $producto->updated_at->format('d/m/Y') }}
                    </span>
                </div>
            </div>

            <div class="settings-card">
                <div class="card-header">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
                    <h4>Ajustes del producto</h4>
                </div>

                <p class="settings-sub">Modifica el precio, el stock o cambia la imagen del producto.</p>

                <div class="settings-inputs">
                    <div class="input-group">
                        <label class="input-group__label" for="precio">Precio (MXN)</label>
                        <div class="input-wrap">
                            <span class="input-wrap__icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                            </span>
                            <input type="number" id="precio" name="precio" step="0.01" min="0" value="{{ old('precio', $producto->precio) }}" required>
                        </div>
                        @error('precio')<p class="err">{{ $message }}</p>@enderror
                    </div>

                    <div class="input-group">
                        <label class="input-group__label" for="stock">Stock</label>
                        <div class="input-wrap">
                            <span class="input-wrap__icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg>
                            </span>
                            <input type="number" id="stock" name="stock" min="0" value="{{ old('stock', $producto->stock) }}" class="{{ $producto->stock == 0 ? 'is-empty' : '' }}" required>
                        </div>
                        @if($producto->stock == 0)
                            <span class="stock-badge">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                                Sin existencias
                            </span>
                        @endif
                        @error('stock')<p class="err">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div class="input-group" style="gap:10px;">
                    <span class="input-group__label">Cambiar imagen del producto</span>
                    <label class="upload-zone" for="imagen" id="upload-zone">
                        <input type="file" id="imagen" name="imagen" accept="image/*">
                        <span class="upload-zone__icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M18 10h-1.26A8 8 0 1 0 9 20h9a5 5 0 0 0 0-10z"/><polyline points="16 12 12 8 8 12"/><line x1="12" y1="16" x2="12" y2="8"/></svg>
                        </span>
                        <p class="upload-zone__text">Arrastra una imagen aquí</p>
                        <p class="upload-zone__hint">JPG, PNG o GIF • Máx. 5 MB</p>
                        <span class="upload-zone__btn">Seleccionar imagen</span>
                    </label>
                    @error('imagen')<p class="err">{{ $message }}</p>@enderror
                </div>

                <div class="preview-box" id="preview-box">
                    <img class="preview-box__thumb" id="preview-img" src="" alt="Vista previa">
                    <div class="preview-box__info">
                        <p class="preview-box__label">Vista previa</p>
                        <p class="preview-box__name" id="preview-name">-</p>
                    </div>
                    <button type="button" class="preview-box__remove" id="remove-preview">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/></svg>
                    </button>
                </div>

                <div class="form-actions">
                    <a href="{{ route('inventory.productos.index') }}" class="btn btn--ghost" style="text-decoration:none;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                        Cancelar
                    </a>
                    <x-ui.button>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                        Guardar cambios
                    </x-ui.button>
                </div>
            </div>
        </div>
    </form>
</section>
@endsection
