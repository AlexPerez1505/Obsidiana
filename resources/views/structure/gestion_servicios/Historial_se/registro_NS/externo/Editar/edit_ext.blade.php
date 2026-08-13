@extends('structure.gestion_servicios.layout')

@section('title', 'Editar servicio externo')

@section('service_content')
    <style>
        .ns-page { max-width: 900px; margin: 0 auto; }

        .ns-header {
            display: flex; align-items: center; justify-content: space-between; gap: 16px;
            flex-wrap: wrap; margin-bottom: 22px;
        }
        .ns-header-title { display: flex; align-items: center; gap: 14px; }
        .ns-icon {
            width: 48px; height: 48px; border-radius: 12px;
            background: rgba(0,122,255,0.12); color: #007AFF;
            display: flex; align-items: center; justify-content: center; flex-shrink: 0;
        }
        .ns-icon svg { width: 24px; height: 24px; }
        .ns-header-title h2 { margin: 0; font-size: 22px; color: #fff; }
        :root[data-theme="light"] .ns-header-title h2 { color: var(--text); }
        .ns-header-title p { margin: 4px 0 0; color: rgba(255,255,255,0.55); font-size: 13px; }
        :root[data-theme="light"] .ns-header-title p { color: var(--muted); }
        .ns-header-actions { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
        .ns-btn {
            display: inline-flex; align-items: center; justify-content: center; gap: 6px;
            padding: 10px 16px; border-radius: 10px; font-size: 13px; font-weight: 700;
            text-decoration: none; cursor: pointer; transition: all .16s ease; border: none;
        }
        .ns-btn svg { width: 16px; height: 16px; }
        .ns-btn--ghost { border: 1px solid rgba(255,255,255,0.12); background: transparent; color: #007AFF; }
        :root[data-theme="light"] .ns-btn--ghost { border-color: rgba(15,23,42,0.14); color: #007AFF; }
        .ns-btn--ghost:hover { border-color: #007AFF; background: rgba(0,122,255,0.08); }
        .ns-btn--primary { background: #007AFF; color: #fff; box-shadow: 0 0 14px rgba(0,122,255,0.35); }
        .ns-btn--primary:hover { background: #005FCC; box-shadow: 0 0 20px rgba(0,122,255,0.5); }

        .ns-section {
            margin-bottom: 22px;
        }
        .ns-section-title {
            display: flex; align-items: center; gap: 10px;
            font-size: 16px; font-weight: 800; color: #fff;
            margin-bottom: 6px;
        }
        :root[data-theme="light"] .ns-section-title { color: var(--text); }
        .ns-section-title svg { width: 20px; height: 20px; color: #007AFF; }
        .ns-section-subtitle { margin: 0 0 20px; font-size: 13px; color: rgba(255,255,255,0.55); }
        :root[data-theme="light"] .ns-section-subtitle { color: var(--muted); }

        .ns-form-grid {
            display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px;
            margin-bottom: 18px;
        }
        @media (max-width: 760px) { .ns-form-grid { grid-template-columns: 1fr; } }
        .ns-field { display: flex; flex-direction: column; gap: 6px; }
        .ns-field label { font-size: 12px; font-weight: 700; color: rgba(255,255,255,0.75); }
        :root[data-theme="light"] .ns-field label { color: var(--text); }
        .ns-field select, .ns-field input, .ns-field textarea {
            padding: 12px 14px; border: 1px solid rgba(255,255,255,0.12); border-radius: 12px;
            background: rgba(8,18,40,0.55); color: #fff; font-size: 14px; outline: none;
            font-family: inherit;
        }
        :root[data-theme="light"] .ns-field select, :root[data-theme="light"] .ns-field input, :root[data-theme="light"] .ns-field textarea { background: #fff; color: var(--text); border-color: rgba(15,23,42,0.14); }
        .ns-field select option { background: #0b1220; color: #fff; }
        .ns-field textarea { min-height: 90px; resize: vertical; }
    </style>

    <div class="ns-page">
        <div class="ns-header">
            <div class="ns-header-title">
                <div class="ns-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                </div>
                <div>
                    <h2>Editar servicio externo</h2>
                    <p>Modifica la información del servicio {{ $service->service_number }}</p>
                </div>
            </div>
            <div class="ns-header-actions">
                <a href="{{ route('gestion.servicios.historial') }}" class="ns-btn ns-btn--ghost">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
                    Regresar
                </a>
                <button type="submit" form="editExtForm" class="ns-btn ns-btn--primary">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                    Guardar cambios
                </button>
            </div>
        </div>

        <form id="editExtForm" method="POST" action="{{ route('gestion.servicios.update.externo', $service) }}" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <div class="catalog-card service-section ns-section">
                <div class="ns-section-title">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
                    Equipo
                </div>
                <p class="ns-section-subtitle">Actualiza los datos del equipo registrado</p>

                <div class="ns-form-grid">
                    <div class="ns-field">
                        <label>Tipo de equipo</label>
                        <input type="text" name="tipo_equipo" value="{{ old('tipo_equipo', $service->serviceEquipment?->type_text ?? '') }}" placeholder="Ej. Equipo médico">
                    </div>
                    <div class="ns-field">
                        <label>Subtipo</label>
                        <input type="text" name="subtipo" value="{{ old('subtipo', $service->serviceEquipment?->subtype_text ?? '') }}" placeholder="Ej. Monitor de signos vitales">
                    </div>
                    <div class="ns-field">
                        <label>Marca</label>
                        <input type="text" name="marca" value="{{ old('marca', $service->serviceEquipment?->brand_text ?? '') }}" placeholder="Ej. Olympus">
                    </div>
                    <div class="ns-field">
                        <label>Modelo</label>
                        <input type="text" name="modelo" value="{{ old('modelo', $service->serviceEquipment?->model_text ?? '') }}" placeholder="Ej. C-90">
                    </div>
                    <div class="ns-field">
                        <label>Numero de serie</label>
                        <input type="text" name="serie" value="{{ old('serie', $service->serviceEquipment?->serial_number ?? '') }}" placeholder="Ej. SN-893-832">
                    </div>
                </div>

                <div class="ns-field" style="margin-bottom: 16px;">
                    <label>Descripcion del equipo</label>
                    <textarea name="descripcion_equipo" placeholder="Describe el equipo y su funcion">{{ old('descripcion_equipo', $service->serviceEquipment?->description ?? '') }}</textarea>
                </div>
                <div class="ns-field" style="margin-bottom: 6px;">
                    <label>Observaciones</label>
                    <textarea name="observaciones" placeholder="Observaciones adicionales del equipo">{{ old('observaciones', $service->serviceEquipment?->observations ?? '') }}</textarea>
                </div>
            </div>

            <div class="catalog-card service-section ns-section">
                <div class="ns-section-title">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                    Tecnico externo
                </div>
                <p class="ns-section-subtitle">Selecciona el tecnico asignado al servicio</p>

                <div class="ns-form-grid" style="grid-template-columns: 1fr;">
                    <div class="ns-field">
                        <label>Tecnico externo</label>
                        <select name="external_technician_id" required>
                            @foreach ($technicians as $tech)
                                <option value="{{ $tech->id }}" {{ old('external_technician_id', $service->external_technician_id) == $tech->id ? 'selected' : '' }}>
                                    {{ trim($tech->nombre . ' ' . ($tech->apellidos ?? '')) }} — {{ $tech->telefono ?: 'Sin telefono' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
        </form>
    </div>
@endsection
