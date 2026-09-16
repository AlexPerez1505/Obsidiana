@extends('layouts.dashboard')
@section('title', 'Agregar Vehículo')
@section('page-title', 'Agregar Vehículo')
@section('page-sub', 'Registra un nuevo vehículo en la flota')

@push('head')
<style>
    .rgrid-3 { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 12px 18px; }
    @media (max-width: 640px) { .rgrid-3 { grid-template-columns: 1fr; } }

    /* Zona para agregar fotos del vehículo */
    .vh-drop-zone {
        border: 1.5px dashed var(--border); border-radius: 12px;
        padding: 26px 20px; text-align: center; cursor: pointer;
        transition: border-color .15s, background .15s;
        background: var(--surface-2);
    }
    .vh-drop-zone:hover { border-color: var(--primary); background: var(--primary-soft); }
    .vh-drop-zone svg { width: 30px; height: 30px; color: var(--muted); margin-bottom: 8px; }
    .vh-drop-zone p { margin: 0; font-size: 14px; font-weight: 600; color: var(--text); }
    .vh-drop-zone span { font-size: 12.5px; color: var(--muted); }
    .vh-photo-previews {
        display: grid; grid-template-columns: repeat(auto-fill, minmax(110px, 1fr)); gap: 10px; margin-top: 12px;
    }
    .vh-photo-previews:empty { display: none; }
    .vh-photo-thumb {
        aspect-ratio: 4/3; border-radius: 10px; overflow: hidden;
        border: 1px solid var(--border); position: relative;
        background: var(--surface-2);
    }
    .vh-photo-thumb img { width: 100%; height: 100%; object-fit: cover; }
    .vh-photo-badge {
        position: absolute; top: 6px; left: 6px;
        background: var(--primary); color: #fff;
        font-size: 9px; font-weight: 800; padding: 2px 7px;
        border-radius: 6px; text-transform: uppercase;
    }

    /* Filas de documentos */
    .vh-doc-row {
        display: flex; align-items: center; gap: 12px;
        padding: 12px 14px; border: 1px solid var(--border);
        border-radius: 10px; background: var(--surface);
        margin-bottom: 8px;
    }
    .vh-doc-icon {
        width: 36px; height: 36px; border-radius: 9px;
        background: var(--primary-soft); color: var(--primary);
        display: flex; align-items: center; justify-content: center; flex: 0 0 auto;
    }
    .vh-doc-icon svg { width: 17px; height: 17px; }
    .vh-doc-info { flex: 1; min-width: 0; }
    .vh-doc-name { font-size: 13.5px; font-weight: 700; margin: 0; }
    .vh-doc-status { font-size: 12px; color: var(--muted); margin: 1px 0 0; }
</style>
@endpush

@section('content')

    <x-ui.page-header title="Agregar Vehículo" :back="route('admin.vehicles.index')" />

    <form method="POST" action="{{ route('admin.vehicles.store') }}" enctype="multipart/form-data" id="vhForm">
        @csrf

        <x-ui.card style="margin-bottom:18px;">
            <x-ui.section-title style="margin:0 0 16px;">Identificación y especificaciones</x-ui.section-title>
            <div class="rgrid-3">
                <x-ui.form-group label="Número de placa *" name="plate_number" placeholder="ABC-123" :required="true" :autofocus="true" />
                <x-ui.form-group label="Número de serie (VIN)" name="vin" placeholder="1HGCM82633A123456" />
                <x-ui.form-group label="Marca" name="brand" placeholder="Toyota, Ford, Nissan..." />
                <x-ui.form-group label="Modelo" name="model" placeholder="Hilux, Focus, Sentra..." />
                <x-ui.form-group label="Año" name="year" type="number" min="1900" placeholder="{{ date('Y') }}" />
                <x-ui.form-group label="Color" name="color" placeholder="Blanco, Negro, Rojo..." />
                <x-ui.form-group for="status" label="Estado">
                    <select id="status" name="status">
                        <option value="active" selected>Activo</option>
                        <option value="maintenance">En mantenimiento</option>
                        <option value="inactive">Inactivo</option>
                    </select>
                </x-ui.form-group>
            </div>
        </x-ui.card>

        <x-ui.card style="margin-bottom:18px;">
            <x-ui.section-title style="margin:0 0 16px;">Galería de fotos</x-ui.section-title>
            <div class="vh-drop-zone" onclick="document.getElementById('vh-photos-input').click()">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                <p>Arrastra y suelta las fotos aquí</p>
                <span>o haz clic para explorar (frente, lateral, trasera, interior)</span>
            </div>
            <input type="file" id="vh-photos-input" name="photos[]" accept="image/*" multiple hidden>
            <div class="vh-photo-previews" id="vh-photo-previews"></div>
        </x-ui.card>

        <x-ui.card style="margin-bottom:18px;">
            <x-ui.section-title style="margin:0 0 16px;">Documentación</x-ui.section-title>

            <div class="vh-doc-row">
                <div class="vh-doc-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="6" width="18" height="12" rx="2"/><path d="M7 12h10"/></svg>
                </div>
                <div class="vh-doc-info">
                    <p class="vh-doc-name">Tarjeta de circulación</p>
                    <p class="vh-doc-status" data-doc-status>Sin archivo adjunto</p>
                </div>
                <input type="file" id="vh-doc-circulation_card_doc" name="circulation_card_doc" accept=".jpg,.jpeg,.png,.pdf" hidden data-doc-input>
                <button type="button" class="btn btn--ghost" style="padding:7px 14px; font-size:13px;" onclick="document.getElementById('vh-doc-circulation_card_doc').click()">Explorar</button>
            </div>
            <div class="vh-doc-row">
                <div class="vh-doc-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
                </div>
                <div class="vh-doc-info">
                    <p class="vh-doc-name">Verificación vehicular</p>
                    <p class="vh-doc-status" data-doc-status>Sin archivo adjunto</p>
                </div>
                <input type="file" id="vh-doc-verification_doc" name="verification_doc" accept=".jpg,.jpeg,.png,.pdf" hidden data-doc-input>
                <button type="button" class="btn btn--ghost" style="padding:7px 14px; font-size:13px;" onclick="document.getElementById('vh-doc-verification_doc').click()">Explorar</button>
            </div>
            <div class="vh-doc-row">
                <div class="vh-doc-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                </div>
                <div class="vh-doc-info">
                    <p class="vh-doc-name">Pago de tenencia</p>
                    <p class="vh-doc-status" data-doc-status>Sin archivo adjunto</p>
                </div>
                <input type="file" id="vh-doc-tenancy_doc" name="tenancy_doc" accept=".jpg,.jpeg,.png,.pdf" hidden data-doc-input>
                <button type="button" class="btn btn--ghost" style="padding:7px 14px; font-size:13px;" onclick="document.getElementById('vh-doc-tenancy_doc').click()">Explorar</button>
            </div>
            <div class="vh-doc-row">
                <div class="vh-doc-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                </div>
                <div class="vh-doc-info">
                    <p class="vh-doc-name">Póliza de seguro</p>
                    <p class="vh-doc-status" data-doc-status>Sin archivo adjunto</p>
                </div>
                <input type="file" id="vh-doc-insurance_doc" name="insurance_doc" accept=".jpg,.jpeg,.png,.pdf" hidden data-doc-input>
                <button type="button" class="btn btn--ghost" style="padding:7px 14px; font-size:13px;" onclick="document.getElementById('vh-doc-insurance_doc').click()">Explorar</button>
            </div>

            <x-ui.form-group label="Número de póliza" name="insurance_policy_number" placeholder="Número de la póliza de seguro" style="margin-top:6px;" />
        </x-ui.card>

        <div class="page-foot">
            <a href="{{ route('admin.vehicles.index') }}" class="btn btn--ghost">Cancelar</a>
            <x-ui.button>
                <x-gravityui-floppy-disk width="16" height="16" />
                Guardar vehículo
            </x-ui.button>
        </div>
    </form>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Vista previa de las fotos seleccionadas
        var photosInput = document.getElementById('vh-photos-input');
        var previewsBox = document.getElementById('vh-photo-previews');

        if (photosInput && previewsBox) {
            photosInput.addEventListener('change', function () {
                previewsBox.innerHTML = '';

                Array.from(photosInput.files).slice(0, 10).forEach(function (file, index) {
                    var reader = new FileReader();
                    reader.onload = function (event) {
                        var thumb = document.createElement('div');
                        thumb.className = 'vh-photo-thumb';
                        thumb.innerHTML = '<img src="' + event.target.result + '" alt="Foto del vehículo">' +
                            (index === 0 ? '<span class="vh-photo-badge">Principal</span>' : '');
                        previewsBox.appendChild(thumb);
                    };
                    reader.readAsDataURL(file);
                });
            });
        }

        // Mostrar el nombre del archivo elegido en cada documento
        document.querySelectorAll('[data-doc-input]').forEach(function (input) {
            input.addEventListener('change', function () {
                var status = input.closest('.vh-doc-row').querySelector('[data-doc-status]');
                if (status) {
                    status.textContent = input.files.length ? input.files[0].name : 'Sin archivo adjunto';
                }
            });
        });
    });
</script>
@endsection
