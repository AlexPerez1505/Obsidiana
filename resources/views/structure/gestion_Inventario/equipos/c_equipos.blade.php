@extends('layouts.dashboard')

@php
    $mode = $mode ?? 'create';
    $isEdit = $mode === 'edit';
    $equipmentData = array_merge([
        'code' => '',
        'name' => '',
        'category' => '',
        'serial_number' => '',
        'brand' => '',
        'model' => '',
        'description' => '',
        'stock_current' => '',
        'stock_max' => '',
        'stock_min' => '',
        'warehouse' => '',
        'assigned_to' => '',
        'department' => '',
        'service_date' => '',
        'next_maintenance' => '',
        'notes' => '',
        'voltage' => '',
        'frequency' => '',
        'power' => '',
        'weight' => '',
        'dimensions' => '',
        'color' => '',
        'technical_specs' => '',
        'supplier' => '',
        'contact' => '',
        'phone' => '',
        'email' => '',
        'invoice_number' => '',
        'invoice_date' => '',
        'thumb' => 'tower',
    ], $equipment ?? []);
    $pageTitle = $isEdit ? 'Editar Equipo' : 'Nuevo Equipo';
    $pageSub = $isEdit ? 'Gestion de Inventario > Equipos > Editar Equipo' : 'Gestion de Inventario > Equipos > Nuevo Equipo';
    $introText = $isEdit ? 'Actualiza la informacion del equipo del inventario.' : 'Registra un nuevo equipo del inventario.';
    $toastMessage = $isEdit ? 'Cambios del equipo preparados para guardar.' : 'Equipo preparado para guardar.';
@endphp

@section('title', $pageTitle)
@section('page-title', $pageTitle)
@section('page-sub', $pageSub)

@push('head')
<style>
    .equipment-form {
        display: grid;
        gap: 18px;
    }

    .equipment-form__bar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        flex-wrap: wrap;
    }

    .equipment-form__intro {
        color: var(--muted);
        margin: 0;
        font-size: 0.94rem;
        font-weight: 600;
    }

    .equipment-form__actions {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: 12px;
        flex-wrap: wrap;
    }

    .equipment-form__grid {
        display: grid;
        grid-template-columns: minmax(170px, 0.65fr) minmax(410px, 1.85fr) minmax(240px, 0.85fr);
        gap: 18px;
        align-items: stretch;
    }

    .equipment-form__row {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 18px;
    }

    .equipment-panel {
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: 18px;
        box-shadow: var(--shadow);
        padding: 20px;
        min-width: 0;
    }

    .equipment-panel__header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        margin-bottom: 16px;
    }

    .equipment-panel__title {
        color: var(--text);
        font-size: 1rem;
        font-weight: 800;
        margin: 0;
    }

    .equipment-pill {
        border: 1px solid rgba(34, 197, 94, 0.65);
        color: #22c55e;
        background: rgba(34, 197, 94, 0.08);
        border-radius: 999px;
        font-size: 0.72rem;
        font-weight: 800;
        line-height: 1;
        padding: 5px 10px;
        white-space: nowrap;
    }

    .equipment-fields {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 12px 18px;
    }

    .equipment-fields--three {
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }

    .equipment-fields--single {
        grid-template-columns: 1fr;
    }

    .equipment-field {
        display: grid;
        gap: 6px;
        min-width: 0;
    }

    .equipment-field--wide {
        grid-column: 1 / -1;
    }

    .equipment-field label {
        color: var(--muted);
        font-size: 0.78rem;
        font-weight: 700;
        margin: 0;
    }

    .equipment-field .equipment-input,
    .equipment-field .equipment-select,
    .equipment-field .equipment-textarea {
        width: 100%;
        border: 1px solid var(--border);
        background: var(--surface-2);
        color: var(--text);
        border-radius: 8px;
        min-height: 38px;
        padding: 8px 12px;
        font: inherit;
        outline: none;
        transition: border-color 0.15s ease, box-shadow 0.15s ease, background 0.15s ease;
    }

    .equipment-field .equipment-textarea {
        min-height: 78px;
        resize: vertical;
    }

    .equipment-field .equipment-input:focus,
    .equipment-field .equipment-select:focus,
    .equipment-field .equipment-textarea:focus {
        border-color: rgba(37, 99, 235, 0.9);
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.16);
    }

    .equipment-image-card {
        display: grid;
        grid-template-rows: auto 1fr auto;
        gap: 14px;
    }

    .equipment-image-card__preview {
        border: 1px dashed rgba(37, 99, 235, 0.55);
        background: rgba(37, 99, 235, 0.04);
        border-radius: 8px;
        min-height: 154px;
        display: grid;
        place-items: center;
        overflow: hidden;
    }

    .equipment-image-card__preview svg {
        width: min(132px, 82%);
        height: auto;
    }

    .equipment-image-card__preview img {
        width: 100%;
        height: 100%;
        object-fit: contain;
        display: block;
        padding: 12px;
    }

    .equipment-image-card__button {
        color: #1e90ff;
        cursor: pointer;
        display: inline-flex;
        justify-content: center;
        text-align: center;
        font-weight: 800;
        font-size: 0.86rem;
        margin: 0;
    }

    .equipment-image-card__input {
        position: absolute;
        width: 1px;
        height: 1px;
        opacity: 0;
        pointer-events: none;
    }

    .equipment-form .sr-only {
        position: absolute;
        width: 1px;
        height: 1px;
        padding: 0;
        margin: -1px;
        overflow: hidden;
        clip: rect(0, 0, 0, 0);
        white-space: nowrap;
        border: 0;
    }

    @media (max-width: 1220px) {
        .equipment-form__grid,
        .equipment-form__row {
            grid-template-columns: 1fr;
        }

        .equipment-image-card {
            max-width: 340px;
        }
    }

    @media (max-width: 760px) {
        .equipment-form__bar,
        .equipment-form__actions {
            align-items: stretch;
            justify-content: stretch;
        }

        .equipment-form__actions,
        .equipment-form__actions .btn {
            width: 100%;
        }

        .equipment-fields,
        .equipment-fields--three {
            grid-template-columns: 1fr;
        }

        .equipment-panel {
            padding: 16px;
        }
    }
</style>
@endpush

@section('content')
    <form class="equipment-form" id="equipmentCreateForm" method="POST" action="#" enctype="multipart/form-data">
        @csrf

        <div class="equipment-form__bar">
            <p class="equipment-form__intro">{{ $introText }}</p>

            <div class="equipment-form__actions">
                <a href="{{ route('inventory.equipos.index') }}" class="btn btn--ghost" style="text-decoration:none;">
                    Volver
                </a>
                <button type="submit" class="btn">
                    Guardar equipo
                </button>
            </div>
        </div>

        <div class="equipment-form__grid">
            <section class="equipment-panel equipment-image-card" aria-labelledby="equipment-image-title">
                <h3 class="equipment-panel__title" id="equipment-image-title">Imagen del equipo</h3>

                <div class="equipment-image-card__preview" id="equipmentImagePreview">
                    @include('structure.gestion_Inventario.equipos.partials.equipment-thumb', ['type' => $equipmentData['thumb']])
                </div>

                <label class="equipment-image-card__button" for="equipment_image">Seleccionar imagen</label>
                <input class="equipment-image-card__input" type="file" id="equipment_image" name="equipment_image" accept="image/*">
            </section>

            <section class="equipment-panel" aria-labelledby="equipment-general-title">
                <div class="equipment-panel__header">
                    <h3 class="equipment-panel__title" id="equipment-general-title">Informacion general</h3>
                    <span class="equipment-pill">Activo</span>
                </div>

                <div class="equipment-fields">
                    <div class="equipment-field">
                        <label for="code">Codigo del equipo</label>
                        <input class="equipment-input" type="text" id="code" name="code" value="{{ old('code', $equipmentData['code']) }}" autocomplete="off">
                    </div>

                    <div class="equipment-field">
                        <label for="name">Nombre del equipo</label>
                        <input class="equipment-input" type="text" id="name" name="name" value="{{ old('name', $equipmentData['name']) }}" autocomplete="off">
                    </div>

                    <div class="equipment-field">
                        <label for="category">Categoria</label>
                        <select class="equipment-select" id="category" name="category">
                            <option value="">Seleccionar</option>
                            <option value="Endoscopia" @selected(old('category', $equipmentData['category']) === 'Endoscopia')>Endoscopia</option>
                            <option value="Imagenologia" @selected(old('category', $equipmentData['category']) === 'Imagenologia')>Imagenologia</option>
                            <option value="Monitoreo" @selected(old('category', $equipmentData['category']) === 'Monitoreo')>Monitoreo</option>
                            <option value="Quirofano" @selected(old('category', $equipmentData['category']) === 'Quirofano')>Quirofano</option>
                        </select>
                    </div>

                    <div class="equipment-field">
                        <label for="serial_number">Numero de serie</label>
                        <input class="equipment-input" type="text" id="serial_number" name="serial_number" value="{{ old('serial_number', $equipmentData['serial_number']) }}" autocomplete="off">
                    </div>

                    <div class="equipment-field">
                        <label for="brand">Marca</label>
                        <input class="equipment-input" type="text" id="brand" name="brand" value="{{ old('brand', $equipmentData['brand']) }}" autocomplete="off">
                    </div>

                    <div class="equipment-field">
                        <label for="model">Modelo</label>
                        <input class="equipment-input" type="text" id="model" name="model" value="{{ old('model', $equipmentData['model']) }}" autocomplete="off">
                    </div>

                    <div class="equipment-field equipment-field--wide">
                        <label for="description">Descripcion</label>
                        <input class="equipment-input" type="text" id="description" name="description" value="{{ old('description', $equipmentData['description']) }}" autocomplete="off">
                    </div>
                </div>
            </section>

            <section class="equipment-panel" aria-labelledby="equipment-stock-title">
                <div class="equipment-panel__header">
                    <h3 class="equipment-panel__title" id="equipment-stock-title">Stock y control</h3>
                    <span class="equipment-pill">Activo</span>
                </div>

                <div class="equipment-fields equipment-fields--single">
                    <div class="equipment-field">
                        <label for="stock_current">Stock actual</label>
                        <input class="equipment-input" type="number" id="stock_current" name="stock_current" value="{{ old('stock_current', $equipmentData['stock_current']) }}" min="0" step="1">
                    </div>

                    <div class="equipment-field">
                        <label for="stock_max">Stock maximo</label>
                        <input class="equipment-input" type="number" id="stock_max" name="stock_max" value="{{ old('stock_max', $equipmentData['stock_max']) }}" min="0" step="1">
                    </div>

                    <div class="equipment-field">
                        <label for="stock_min">Stock minimo</label>
                        <input class="equipment-input" type="number" id="stock_min" name="stock_min" value="{{ old('stock_min', $equipmentData['stock_min']) }}" min="0" step="1">
                    </div>

                    <div class="equipment-field">
                        <label for="warehouse">Almacen predeterminado</label>
                        <select class="equipment-select" id="warehouse" name="warehouse">
                            <option value="">Seleccionar</option>
                            <option value="Almacen Central" @selected(old('warehouse', $equipmentData['warehouse']) === 'Almacen Central')>Almacen Central</option>
                            <option value="Quirofano 1" @selected(old('warehouse', $equipmentData['warehouse']) === 'Quirofano 1')>Quirofano 1</option>
                            <option value="Quirofano 2" @selected(old('warehouse', $equipmentData['warehouse']) === 'Quirofano 2')>Quirofano 2</option>
                            <option value="Servicio tecnico" @selected(old('warehouse', $equipmentData['warehouse']) === 'Servicio tecnico')>Servicio tecnico</option>
                        </select>
                    </div>
                </div>
            </section>
        </div>

        <div class="equipment-form__row">
            <section class="equipment-panel" aria-labelledby="equipment-extra-title">
                <h3 class="equipment-panel__title" id="equipment-extra-title">Informacion adicional</h3>

                <div class="equipment-fields" style="margin-top:16px;">
                    <div class="equipment-field">
                        <label for="assigned_to">Responsable asignado</label>
                        <input class="equipment-input" type="text" id="assigned_to" name="assigned_to" value="{{ old('assigned_to', $equipmentData['assigned_to']) }}" autocomplete="off">
                    </div>

                    <div class="equipment-field">
                        <label for="department">Departamento</label>
                        <input class="equipment-input" type="text" id="department" name="department" value="{{ old('department', $equipmentData['department']) }}" autocomplete="off">
                    </div>

                    <div class="equipment-field">
                        <label for="service_date">Fecha de puesta en servicio</label>
                        <input class="equipment-input" type="date" id="service_date" name="service_date" value="{{ old('service_date', $equipmentData['service_date']) }}">
                    </div>

                    <div class="equipment-field">
                        <label for="next_maintenance">Proximo mantenimiento</label>
                        <input class="equipment-input" type="date" id="next_maintenance" name="next_maintenance" value="{{ old('next_maintenance', $equipmentData['next_maintenance']) }}">
                    </div>

                    <div class="equipment-field equipment-field--wide">
                        <label for="notes">Notas</label>
                        <textarea class="equipment-textarea" id="notes" name="notes">{{ old('notes', $equipmentData['notes']) }}</textarea>
                    </div>
                </div>
            </section>

            <section class="equipment-panel" aria-labelledby="equipment-tech-title">
                <h3 class="equipment-panel__title" id="equipment-tech-title">Informacion tecnica</h3>

                <div class="equipment-fields" style="margin-top:16px;">
                    <div class="equipment-field">
                        <label for="voltage">Voltaje</label>
                        <input class="equipment-input" type="text" id="voltage" name="voltage" value="{{ old('voltage', $equipmentData['voltage']) }}" autocomplete="off">
                    </div>

                    <div class="equipment-field">
                        <label for="frequency">Frecuencia</label>
                        <input class="equipment-input" type="text" id="frequency" name="frequency" value="{{ old('frequency', $equipmentData['frequency']) }}" autocomplete="off">
                    </div>

                    <div class="equipment-field">
                        <label for="power">Potencia</label>
                        <input class="equipment-input" type="text" id="power" name="power" value="{{ old('power', $equipmentData['power']) }}" autocomplete="off">
                    </div>

                    <div class="equipment-field">
                        <label for="weight">Peso (kg)</label>
                        <input class="equipment-input" type="number" id="weight" name="weight" value="{{ old('weight', $equipmentData['weight']) }}" min="0" step="0.01">
                    </div>

                    <div class="equipment-field">
                        <label for="dimensions">Dimensiones (cm)</label>
                        <input class="equipment-input" type="text" id="dimensions" name="dimensions" value="{{ old('dimensions', $equipmentData['dimensions']) }}" autocomplete="off">
                    </div>

                    <div class="equipment-field">
                        <label for="color">Color</label>
                        <input class="equipment-input" type="text" id="color" name="color" value="{{ old('color', $equipmentData['color']) }}" autocomplete="off">
                    </div>

                    <div class="equipment-field equipment-field--wide">
                        <label for="technical_specs">Especificaciones tecnicas</label>
                        <textarea class="equipment-textarea" id="technical_specs" name="technical_specs">{{ old('technical_specs', $equipmentData['technical_specs']) }}</textarea>
                    </div>
                </div>
            </section>

            <section class="equipment-panel" aria-labelledby="equipment-provider-title">
                <h3 class="equipment-panel__title" id="equipment-provider-title">Proveedor</h3>

                <div class="equipment-fields" style="margin-top:16px;">
                    <div class="equipment-field">
                        <label for="supplier">Proveedor</label>
                        <input class="equipment-input" type="text" id="supplier" name="supplier" value="{{ old('supplier', $equipmentData['supplier']) }}" autocomplete="off">
                    </div>

                    <div class="equipment-field">
                        <label for="contact">Contacto</label>
                        <input class="equipment-input" type="text" id="contact" name="contact" value="{{ old('contact', $equipmentData['contact']) }}" autocomplete="off">
                    </div>

                    <div class="equipment-field">
                        <label for="phone">Telefono</label>
                        <input class="equipment-input" type="tel" id="phone" name="phone" value="{{ old('phone', $equipmentData['phone']) }}" autocomplete="off">
                    </div>

                    <div class="equipment-field">
                        <label for="email">Correo electronico</label>
                        <input class="equipment-input" type="email" id="email" name="email" value="{{ old('email', $equipmentData['email']) }}" autocomplete="off">
                    </div>

                    <div class="equipment-field">
                        <label for="invoice_number">Numero de factura</label>
                        <input class="equipment-input" type="text" id="invoice_number" name="invoice_number" value="{{ old('invoice_number', $equipmentData['invoice_number']) }}" autocomplete="off">
                    </div>

                    <div class="equipment-field">
                        <label for="invoice_date">Fecha de factura</label>
                        <input class="equipment-input" type="date" id="invoice_date" name="invoice_date" value="{{ old('invoice_date', $equipmentData['invoice_date']) }}">
                    </div>
                </div>
            </section>
        </div>
    </form>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const form = document.getElementById('equipmentCreateForm');
            const imageInput = document.getElementById('equipment_image');
            const imagePreview = document.getElementById('equipmentImagePreview');

            if (form) {
                form.addEventListener('submit', function (event) {
                    event.preventDefault();

                    if (window.showToast) {
                        window.showToast(@json($toastMessage));
                    }
                });
            }

            if (imageInput && imagePreview) {
                imageInput.addEventListener('change', function () {
                    const file = imageInput.files && imageInput.files[0];

                    if (!file) {
                        return;
                    }

                    const reader = new FileReader();
                    reader.onload = function () {
                        imagePreview.innerHTML = '';

                        const image = document.createElement('img');
                        image.src = reader.result;
                        image.alt = 'Vista previa del equipo';
                        imagePreview.appendChild(image);
                    };
                    reader.readAsDataURL(file);
                });
            }
        });
    </script>
@endsection
