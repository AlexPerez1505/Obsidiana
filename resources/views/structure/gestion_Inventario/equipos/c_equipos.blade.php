@extends('layouts.dashboard')

@php
    $mode = $mode ?? 'create';
    $isEdit = $mode === 'edit';
    $pageTitle = $isEdit ? 'Editar Equipo' : 'Agregar Equipo';
    $pageSub = $isEdit ? 'Gestion de Inventario > Equipos > Editar' : 'Gestion de Inventario > Equipos > Nuevo';
    $equipo = $equipo ?? null;
@endphp

@section('title', $pageTitle)
@section('page-title', $pageTitle)
@section('page-sub', $pageSub)

@push('head')
<style>
    .reg-form { display: grid; gap: 18px; max-width: 1100px; margin: 0 auto; }
    .reg-bar {
        display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap;
    }
    .reg-bar h2 { margin: 0; font-size: 1.35rem; font-weight: 800; color: var(--text); }
    .reg-bar p { margin: 4px 0 0; color: var(--muted); font-size: 0.95rem; font-weight: 600; }
    .reg-actions { display: flex; gap: 10px; align-items: center; }
    .reg-actions a, .reg-actions button { min-height: 38px; }
    .reg-grid {
        display: grid;
        grid-template-columns: 1.3fr 0.7fr;
        gap: 18px;
        align-items: start;
    }
    @media (max-width: 980px) { .reg-grid { grid-template-columns: 1fr; } }
    .reg-card {
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: 18px;
        box-shadow: var(--shadow);
        padding: 22px;
    }
    .reg-card h3 { margin: 0 0 18px; font-size: 1rem; font-weight: 800; color: var(--text); }
    .reg-fields {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 14px 18px;
    }
    .reg-field { display: grid; gap: 6px; min-width: 0; }
    .reg-field.wide { grid-column: 1 / -1; }
    .reg-field label { color: var(--muted); font-size: 0.78rem; font-weight: 700; }
    .reg-field .reg-input,
    .reg-field .reg-select,
    .reg-field .reg-textarea {
        width: 100%;
        border: 1px solid var(--border);
        background: var(--surface-2);
        color: var(--text);
        border-radius: 8px;
        min-height: 40px;
        padding: 8px 12px;
        font: inherit;
        font-size: 14px;
        outline: none;
    }
    .reg-field .reg-textarea { min-height: 86px; resize: vertical; }
    .reg-field .reg-input:focus,
    .reg-field .reg-select:focus,
    .reg-field .reg-textarea:focus {
        border-color: rgba(37, 99, 235, 0.9);
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.16);
    }
    .reg-image-card { display: grid; gap: 14px; }
    .reg-image-preview {
        border: 1px dashed rgba(37, 99, 235, 0.55);
        background: rgba(37, 99, 235, 0.04);
        border-radius: 12px;
        min-height: 180px;
        display: grid;
        place-items: center;
        overflow: hidden;
    }
    .reg-image-preview img { max-width: 100%; max-height: 240px; object-fit: contain; display: block; padding: 12px; }
    .reg-image-preview svg { width: min(140px, 80%); height: auto; color: #6b7280; }
    .reg-image-label {
        color: #1e90ff;
        cursor: pointer;
        text-align: center;
        font-weight: 800;
        font-size: 0.86rem;
    }
    .reg-image-input { position: absolute; width: 1px; height: 1px; opacity: 0; pointer-events: none; }
    .reg-flow { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 8px; }
    .reg-flow label {
        display: inline-flex; align-items: center; gap: 6px;
        padding: 8px 12px; border-radius: 999px; border: 1px solid var(--border);
        background: var(--surface-2); color: var(--text); font-size: 0.82rem; font-weight: 700;
        cursor: pointer; user-select: none;
    }
    .reg-flow input { position: absolute; opacity: 0; width: 0; height: 0; }
    .reg-flow input:checked + label {
        background: rgba(20, 184, 166, 0.12); border-color: #14b8a6; color: #0f766e;
    }
    .reg-help { font-size: 12px; color: #14b8a6; font-weight: 700; margin: 0; }
</style>
@endpush

@section('content')
    <form class="reg-form" method="POST" action="{{ $isEdit ? route('inventory.equipos.update', $equipo) : route('inventory.equipos.store') }}" enctype="multipart/form-data">
        @csrf
        @if($isEdit) @method('PUT') @endif

        <div class="reg-bar">
            <div>
                <h2>{{ $pageTitle }}</h2>
                <p>Completa los datos, adjunta evidencias y guarda el equipo.</p>
            </div>
            <div class="reg-actions">
                <a href="{{ route('inventory.equipos.index') }}" class="btn btn--ghost" style="text-decoration:none;">Volver</a>
                <button type="submit" class="btn">{{ $isEdit ? 'Actualizar equipo' : 'Guardar equipo' }}</button>
            </div>
        </div>

        <div class="reg-grid">
            <section class="reg-card">
                <h3>Flujo de procesos</h3>
                <p style="margin:0 0 12px; color:var(--muted); font-size:0.88rem;">Elige qué etapa quieres seguir para el registro.</p>
                <div class="reg-flow">
                    <input type="radio" name="flujo" id="flujo-completo" value="completo" checked>
                    <label for="flujo-completo">Completo</label>
                    <input type="radio" name="flujo" id="flujo-mantenimiento" value="mantenimiento">
                    <label for="flujo-mantenimiento">Saltar Mantenimiento</label>
                    <input type="radio" name="flujo" id="flujo-hojalateria" value="hojalateria">
                    <label for="flujo-hojalateria">Saltar Hojalateria</label>
                    <input type="radio" name="flujo" id="flujo-stock" value="stock">
                    <label for="flujo-stock">Directo a stock</label>
                </div>
            </section>

            <section class="reg-card reg-image-card" style="grid-row: span 2;">
                <h3>Imagen del equipo</h3>
                <div class="reg-image-preview" id="imagePreview">
                    @if($equipo?->imagen_path)
                        <img src="{{ asset('storage/' . $equipo->imagen_path) }}" alt="Vista previa" id="previewImg">
                    @else
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.3"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="M21 15l-5-5L5 21"/></svg>
                    @endif
                </div>
                <label class="reg-image-label" for="imagen">Cargar / cambiar imagen</label>
                <input type="file" id="imagen" name="imagen" accept="image/*" class="reg-image-input" onchange="previewImage(this)">
                <small style="color:var(--muted); text-align:center;">JPG, PNG. Máximo 5 MB.</small>
            </section>

            <section class="reg-card" style="grid-column: 1;">
                <h3>Datos del equipo</h3>
                <div class="reg-fields">
                    <div class="reg-field">
                        <label for="tipo_equipo">Tipo de equipo *</label>
                        <select id="tipo_equipo" name="tipo_equipo" class="reg-select" required onchange="updateSubtipos(); fetchBaseSerialPreview();">
                            <option value="">Selecciona un tipo</option>
                            @foreach($tipos as $tipo)
                                <option value="{{ $tipo->name }}" data-subtipos="{{ $tipo->subtypes->pluck('name')->toJson() }}" @selected(old('tipo_equipo', $equipo?->tipo_equipo) === $tipo->name)>{{ $tipo->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="reg-field">
                        <label for="subtipo">Subtipo *</label>
                        <select id="subtipo" name="subtipo" class="reg-select" required onchange="fetchBaseSerialPreview();">
                            <option value="">Selecciona un subtipo</option>
                        </select>
                    </div>
                    <div class="reg-field">
                        <label for="marca">Marca *</label>
                        <select id="marca" name="marca" class="reg-select" required onchange="updateModelos(); fetchBaseSerialPreview();">
                            <option value="">Selecciona una marca</option>
                            @foreach($marcas as $marca)
                                <option value="{{ $marca->name }}" data-modelos="{{ $marca->equipmentModels->pluck('name')->toJson() }}" @selected(old('marca', $equipo?->marca) === $marca->name)>{{ $marca->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="reg-field">
                        <label for="modelo">Modelo *</label>
                        <select id="modelo" name="modelo" class="reg-select" required onchange="fetchBaseSerialPreview();">
                            <option value="">Selecciona un modelo</option>
                        </select>
                    </div>
                    <div class="reg-field">
                        <label for="no_serie">Número de serie *</label>
                        <input type="text" id="no_serie" name="no_serie" class="reg-input" value="{{ old('no_serie', $equipo?->no_serie) }}" required placeholder="Ej. S/N-123456">
                    </div>
                    <div class="reg-field">
                        <label for="no_serie_base_preview">Número de serie base (interno)</label>
                        <input type="text" id="no_serie_base_preview" class="reg-input" readonly
                               value="{{ $equipo?->no_serie_base ?? '' }}"
                               placeholder="Se generará automáticamente al guardar">
                        <small class="reg-help">Se genera a partir del tipo, subtipo, marca y modelo.</small>
                    </div>
                    <div class="reg-field">
                        <label for="estado">Estado</label>
                        <select id="estado" name="estado" class="reg-select">
                            <option value="Activo" @selected(old('estado', $equipo?->estado) === 'Activo')>Activo</option>
                            <option value="Mantenimiento" @selected(old('estado', $equipo?->estado) === 'Mantenimiento')>Mantenimiento</option>
                            <option value="Inactivo" @selected(old('estado', $equipo?->estado) === 'Inactivo')>Inactivo</option>
                        </select>
                    </div>
                    <div class="reg-field">
                        <label for="fecha_adquisicion">Fecha de adquisición</label>
                        <input type="date" id="fecha_adquisicion" name="fecha_adquisicion" class="reg-input" value="{{ old('fecha_adquisicion', optional($equipo?->fecha_adquisicion)->format('Y-m-d')) }}">
                    </div>
                    <div class="reg-field wide">
                        <label for="descripcion">Descripción / notas</label>
                        <textarea id="descripcion" name="descripcion" class="reg-textarea" placeholder="Observaciones adicionales del equipo...">{{ old('descripcion', $equipo?->descripcion) }}</textarea>
                    </div>
                    <div class="reg-field wide">
                        <label for="firmaCanvas">Firma del responsable *</label>
                        <div style="border: 1px solid var(--border); border-radius: 8px; background: #fff; touch-action: none; display: inline-block; width: 100%; max-width: 400px;">
                            <canvas id="firmaCanvas" width="400" height="150" style="width:100%; max-width:400px; display:block; border-radius: 8px;"></canvas>
                        </div>
                        <div style="margin-top: 8px;">
                            <button type="button" class="btn btn--ghost" onclick="clearFirma()" style="font-size:0.82rem;">Borrar firma</button>
                        </div>
                        <input type="hidden" id="firmaInput" name="firma">
                        <small class="reg-help">Dibuja tu firma para poder guardar el equipo.</small>
                    </div>
                </div>
            </section>
        </div>
    </form>

    <script>
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        function fillSelect(select, options, selectedValue, placeholder) {
            select.innerHTML = '<option value="">' + placeholder + '</option>';
            options.forEach(function(value) {
                const option = document.createElement('option');
                option.value = value;
                option.textContent = value;
                if (value === selectedValue) option.selected = true;
                select.appendChild(option);
            });
            if (!select.value && selectedValue) {
                const opt = document.createElement('option');
                opt.value = selectedValue;
                opt.textContent = selectedValue;
                opt.selected = true;
                select.appendChild(opt);
            }
        }

        function updateSubtipos() {
            const tipoSelect = document.getElementById('tipo_equipo');
            const subtipoSelect = document.getElementById('subtipo');
            const selectedOption = tipoSelect.options[tipoSelect.selectedIndex];
            const subtipos = selectedOption.dataset.subtipos ? JSON.parse(selectedOption.dataset.subtipos) : [];
            const selectedSubtipo = '{{ old('subtipo', $equipo?->subtipo) }}';
            fillSelect(subtipoSelect, subtipos, selectedSubtipo, 'Selecciona un subtipo');
            fetchBaseSerialPreview();
        }

        function updateModelos() {
            const marcaSelect = document.getElementById('marca');
            const modeloSelect = document.getElementById('modelo');
            const selectedOption = marcaSelect.options[marcaSelect.selectedIndex];
            const modelos = selectedOption.dataset.modelos ? JSON.parse(selectedOption.dataset.modelos) : [];
            const selectedModelo = '{{ old('modelo', $equipo?->modelo) }}';
            fillSelect(modeloSelect, modelos, selectedModelo, 'Selecciona un modelo');
            fetchBaseSerialPreview();
        }

        function previewImage(input) {
            const preview = document.getElementById('imagePreview');
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.innerHTML = '<img src="' + e.target.result + '" alt="Vista previa" id="previewImg">';
                };
                reader.readAsDataURL(input.files[0]);
            }
        }

        function abbreviateBase(text) {
            if (!text) return 'XX';
            const normalized = text.toUpperCase()
                .normalize('NFD')
                .replace(/[\u0300-\u036f]/g, '')
                .replace(/[^A-Z0-9 ]/g, ' ')
                .trim();
            const words = normalized.split(/\s+/).filter(Boolean);
            if (words.length >= 2) {
                return words.map(w => w[0]).join('').slice(0, 2);
            }
            return normalized.replace(/\s/g, '').slice(0, 2) || 'XX';
        }

        let baseSerialTimeout;

        function fetchBaseSerialPreview() {
            const previewInput = document.getElementById('no_serie_base_preview');
            if (!previewInput) return;

            const tipo = document.getElementById('tipo_equipo').value;
            const subtipo = document.getElementById('subtipo').value;
            const marca = document.getElementById('marca').value;
            const modelo = document.getElementById('modelo').value;

            if (!tipo || !subtipo || !marca || !modelo) {
                previewInput.value = '';
                return;
            }

            clearTimeout(baseSerialTimeout);
            baseSerialTimeout = setTimeout(() => {
                const existing = previewInput.getAttribute('data-saved');
                if (existing) {
                    const existingBase = existing.replace(/-\d{3}$/, '');
                    const currentBase = [tipo, subtipo, marca, modelo].map(abbreviateBase).join('-');
                    if (existingBase === currentBase) {
                        previewInput.value = existing;
                        return;
                    }
                }

                const params = new URLSearchParams({
                    tipo_equipo: tipo,
                    subtipo: subtipo,
                    marca: marca,
                    modelo: modelo,
                });
                @if($isEdit && $equipo?->id)
                    params.append('exclude_id', @json($equipo->id));
                @endif

                fetch('{{ route('inventory.equipos.next-base-serial') }}?' + params.toString(), {
                    credentials: 'same-origin',
                })
                    .then(r => r.json())
                    .then(data => {
                        if (data.no_serie_base) {
                            previewInput.value = data.no_serie_base;
                        }
                    })
                    .catch(() => {
                        previewInput.value = [tipo, subtipo, marca, modelo].map(abbreviateBase).join('-') + '-001';
                    });
            }, 200);
        }

        const canvas = document.getElementById('firmaCanvas');
        const ctx = canvas ? canvas.getContext('2d') : null;
        let drawing = false;
        let hasSignature = false;

        function getPointerPos(e) {
            const rect = canvas.getBoundingClientRect();
            const scaleX = canvas.width / rect.width;
            const scaleY = canvas.height / rect.height;
            const clientX = e.touches ? e.touches[0].clientX : e.clientX;
            const clientY = e.touches ? e.touches[0].clientY : e.clientY;
            return {
                x: (clientX - rect.left) * scaleX,
                y: (clientY - rect.top) * scaleY,
            };
        }

        function startDraw(e) {
            e.preventDefault();
            drawing = true;
            hasSignature = true;
            const pos = getPointerPos(e);
            ctx.beginPath();
            ctx.moveTo(pos.x, pos.y);
        }

        function draw(e) {
            if (!drawing) return;
            e.preventDefault();
            const pos = getPointerPos(e);
            ctx.lineTo(pos.x, pos.y);
            ctx.stroke();
        }

        function stopDraw() {
            drawing = false;
            ctx.beginPath();
        }

        function clearFirma() {
            if (!ctx) return;
            ctx.fillStyle = '#ffffff';
            ctx.fillRect(0, 0, canvas.width, canvas.height);
            hasSignature = false;
            document.getElementById('firmaInput').value = '';
        }

        function isCanvasEmpty() {
            return !hasSignature;
        }

        if (canvas && ctx) {
            ctx.lineWidth = 2;
            ctx.lineCap = 'round';
            ctx.strokeStyle = '#000000';
            clearFirma();

            canvas.addEventListener('mousedown', startDraw);
            canvas.addEventListener('mousemove', draw);
            canvas.addEventListener('mouseup', stopDraw);
            canvas.addEventListener('mouseleave', stopDraw);

            canvas.addEventListener('touchstart', startDraw, { passive: false });
            canvas.addEventListener('touchmove', draw, { passive: false });
            canvas.addEventListener('touchend', stopDraw);
            canvas.addEventListener('touchcancel', stopDraw);

            const form = document.querySelector('.reg-form');
            form.addEventListener('submit', function(e) {
                if (isCanvasEmpty()) {
                    e.preventDefault();
                    alert('Debes firmar antes de guardar el equipo.');
                    return false;
                }
                document.getElementById('firmaInput').value = canvas.toDataURL('image/png');
            });
        }

        document.addEventListener('DOMContentLoaded', function() {
            updateSubtipos();
            updateModelos();

            const savedBase = @json($equipo?->no_serie_base ?? '');
            const previewInput = document.getElementById('no_serie_base_preview');
            if (previewInput && savedBase) {
                previewInput.setAttribute('data-saved', savedBase);
            }
        });
    </script>
@endsection
