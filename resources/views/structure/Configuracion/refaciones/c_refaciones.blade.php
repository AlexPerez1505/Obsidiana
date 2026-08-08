@extends('structure.Configuracion.layout')

@section('title', 'Nueva refacción')

@section('configuracion_content')
    <style>
        .form-group { margin-bottom: 18px; }
        .form-label { display: block; margin-bottom: 8px; font-size: 14px; color: #fff; font-weight: 600; }
        .form-input {
            width: 100%; padding: 12px 14px; border: 1px solid rgba(0,168,255,0.55);
            border-radius: 10px; font-size: 15px; background: rgba(4,10,24,0.72);
            color: #fff; outline: none; transition: border-color .18s, box-shadow .18s;
        }
        .form-input:focus { border-color: #00A8FF; box-shadow: 0 0 0 3px rgba(0,168,255,0.18); }
        .form-input::placeholder { color: rgba(255,255,255,0.4); }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 18px; }
    </style>

    <div class="catalog-card" style="margin-bottom:22px;">
        <div style="display:flex; align-items:center; justify-content:space-between; gap:18px; flex-wrap:wrap;">
            <div>
                <h2 class="page-title" style="margin:0; font-size:24px; font-weight:700;">Nueva refacción</h2>
                <p class="page-subtitle" style="margin:4px 0 0; font-size:14px;">Registra una refacción o repuesto en el catálogo.</p>
            </div>
            <div style="display:flex; align-items:center; gap:12px;">
                <a href="{{ route('configuracion.refaciones.index') }}" style="background:rgba(8,18,40,0.55); color:#fff; border:1px solid rgba(0,168,255,0.45); padding:10px 18px; border-radius:12px; font-size:14px; font-weight:600; text-decoration:none; display:inline-flex; align-items:center; gap:8px;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5"/><path d="M12 19l-7-7 7-7"/></svg>
                    Regresar
                </a>
                <button type="submit" form="refacion-form" class="btn" style="background:#00A8FF; color:#fff; border:1px solid rgba(255,255,255,0.15); padding:10px 18px; border-radius:12px; font-size:14px; font-weight:600; cursor:pointer; display:inline-flex; align-items:center; gap:8px;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                    Guardar refacción
                </button>
            </div>
        </div>
    </div>

    <div class="catalog-card">
        @if (session('status'))
            <div class="alert alert--ok" style="margin:0 0 18px;">{{ session('status') }}</div>
        @endif

        <form method="POST" action="{{ route('configuracion.refaciones.store') }}" id="refacion-form" autocomplete="off">
            @csrf

            <div class="form-group">
                <label class="form-label" for="subtype_name">Subtipo *</label>
                <input type="text" id="subtype_name" name="subtype_name" class="form-input" value="{{ old('subtype_name') }}" list="subtype_list" placeholder="Ej. Monitor de signos vitales" required>
                <datalist id="subtype_list">
                    @foreach ($subtypes as $subtype)
                        <option value="{{ $subtype }}"></option>
                    @endforeach
                </datalist>
            </div>

            <div class="form-group">
                <label class="form-label" for="name">Nombre *</label>
                <input type="text" id="name" name="name" class="form-input" value="{{ old('name') }}" placeholder="Ej. Fusible principal" required>
            </div>

            <div class="form-group">
                <label class="form-label" for="description">Descripción</label>
                <textarea id="description" name="description" class="form-input" rows="3" placeholder="Detalles de la refacción">{{ old('description') }}</textarea>
            </div>

            <div class="form-group">
                <label class="form-label" for="stock">Stock</label>
                <input type="number" id="stock" name="stock" class="form-input" value="{{ old('stock', 0) }}" placeholder="0">
            </div>

            <div class="form-group">
                <label class="form-label" for="compatible_with">Compatible con</label>
                <input type="text" id="compatible_with" name="compatible_with" class="form-input" value="{{ old('compatible_with') }}" placeholder="Ej. Modelo X, Serie Y">
            </div>
        </form>
    </div>
@endsection
