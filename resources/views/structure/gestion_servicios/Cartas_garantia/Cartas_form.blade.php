@extends('structure.gestion_servicios.layout')

@section('title', 'Carta de garantía')

@section('service_content')
<style>
.form-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); gap:18px; margin-top:18px; }
.form-group label { font-size:13px; font-weight:700; margin-bottom:6px; display:block; }
.form-group input, .form-group select, .form-group textarea { width:100%; padding:11px 12px; border:1px solid var(--border); border-radius:9px; background:var(--surface); color:var(--text); font-size:14px; }
.form-group input:focus, .form-group select:focus, .form-group textarea:focus { border-color:var(--primary); outline:none; }
.form-group input::placeholder, .form-group textarea::placeholder { color:#aaa; }
.form-group input[type="file"] { padding:9px 12px; }
.wizard-header { display:flex; align-items:center; gap:14px; margin-bottom:22px; }
.wizard-icon { width:52px; height:52px; border-radius:14px; background:var(--primary-soft); color:var(--primary); display:flex; align-items:center; justify-content:center; }
.wizard-actions { display:flex; align-items:center; gap:10px; margin-top:22px; justify-content:flex-end; }
</style>

<div class="card">
    <div class="wizard-header">
        <div class="wizard-icon">
            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                <path d="M9 12l2 2 4-4"/>
            </svg>
        </div>
        <div>
            <h1 class="section-title" style="font-size:24px; margin:0;">Carta de garantía</h1>
            <p class="muted" style="margin:4px 0 0;">Registra una nueva carta de garantía para un producto</p>
        </div>
    </div>

    <form id="carta-form" method="POST" action="{{ route('cartas.garantia.store') }}" enctype="multipart/form-data" autocomplete="off">
        @csrf

        <div class="form-grid">
            <div class="form-group">
                <label for="id_tipo_equipo">Tipo de equipo</label>
                <select name="id_tipo_equipo" id="id_tipo_equipo" required>
                    <option value="">Selecciona un tipo</option>
                    @foreach($productos ?? [] as $producto)
                        @if($producto->tipo_equipo)
                            <option value="{{ $producto->id }}" {{ old('id_tipo_equipo') == $producto->id ? 'selected' : '' }}>
                                {{ $producto->tipo_equipo }}
                            </option>
                        @endif
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label for="id_subtipo">Subtipo</label>
                <select name="id_subtipo" id="id_subtipo" required>
                    <option value="">Selecciona un subtipo</option>
                    @foreach($productos ?? [] as $producto)
                        @if($producto->subtipo)
                            <option value="{{ $producto->id }}" {{ old('id_subtipo') == $producto->id ? 'selected' : '' }}>
                                {{ $producto->subtipo }}
                            </option>
                        @endif
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label for="nombre">Nombre de la carta</label>
                <input type="text" name="nombre" id="nombre" value="{{ old('nombre') }}" placeholder="Ej. Garantía estándar de 12 meses" required>
            </div>

            <div class="form-group" style="grid-column: 1 / -1;">
                <label for="archivo_carta">Archivo de la carta</label>
                <input type="file" name="archivo_carta" id="archivo_carta" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.webp" required>
            </div>
        </div>

        <div class="wizard-actions">
            <button type="button" class="btn btn--ghost" onclick="history.back()">
                Cancelar
            </button>
            <button type="submit" class="btn">
                Guardar carta
            </button>
        </div>
    </form>
</div>
@endsection
