@extends('structure.gestion_servicios.layout')

@section('title', 'Nueva refacción')

@push('head')
<style>
.form-group { margin-bottom:18px; }
.form-group label { font-size:13px; font-weight:700; color:var(--muted); margin-bottom:6px; display:block; text-transform:uppercase; letter-spacing:.03em; }
.form-group input, .form-group select, .form-group textarea { width:100%; padding:11px 12px; border:1px solid var(--border); border-radius:9px; background:var(--surface); color:var(--text); font-size:14px; }
.form-group input:focus, .form-group select:focus, .form-group textarea:focus { border-color:var(--primary); outline:none; }
.photo-preview { width:100%; max-width:240px; height:160px; border:2px dashed var(--border); border-radius:12px; display:flex; align-items:center; justify-content:center; color:var(--muted); overflow:hidden; background:var(--surface); margin-top:8px; }
.photo-preview img { width:100%; height:100%; object-fit:cover; }
.form-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(240px,1fr)); gap:18px; }
.btn { display:inline-flex; align-items:center; gap:8px; padding:10px 18px; border-radius:10px; font-size:14px; font-weight:700; text-decoration:none; background:var(--primary); color:#fff; border:1px solid var(--primary); cursor:pointer; }
</style>
@endpush

@section('service_content')
<x-ui.card>
    <div style="display:flex; align-items:center; justify-content:space-between; gap:16px; flex-wrap:wrap; margin-bottom:22px;">
        <div>
            <h1 style="font-size:22px; margin:0;">Nueva refacción</h1>
            <p style="font-size:13px; color:var(--muted); margin:4px 0 0;">Registra una refacción con fotografía</p>
        </div>
        <a href="{{ route('gestion.servicios.refacciones.index') }}" class="btn" style="background:transparent; color:var(--primary);">Volver</a>
    </div>

    @if ($errors->any())
        <x-ui.card style="margin-bottom:22px; border-color:#ef4444;">
            <strong style="color:#ef4444;">Revisa los siguientes campos:</strong>
            <ul style="margin:6px 0 0; padding-left:18px; color:#ef4444;">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </x-ui.card>
    @endif

    <form method="POST" action="{{ route('gestion.servicios.refacciones.store') }}" enctype="multipart/form-data" autocomplete="off">
        @csrf

        <div class="form-grid">
            <div class="form-group">
                <label for="subtype_name">Subtipo *</label>
                <input type="text" id="subtype_name" name="subtype_name" list="subtype_list" value="{{ old('subtype_name') }}" placeholder="Ej. Monitor de signos vitales" required>
                <datalist id="subtype_list">
                    @foreach ($subtypes as $subtype)
                        <option value="{{ $subtype }}"></option>
                    @endforeach
                </datalist>
            </div>

            <div class="form-group">
                <label for="name">Nombre *</label>
                <input type="text" id="name" name="name" value="{{ old('name') }}" placeholder="Ej. Fusible principal" required>
            </div>

            <div class="form-group">
                <label for="stock">Stock</label>
                <input type="number" id="stock" name="stock" value="{{ old('stock', 0) }}" min="0" placeholder="0">
            </div>

            <div class="form-group">
                <label for="price">Precio unitario</label>
                <input type="number" id="price" name="price" value="{{ old('price', 0) }}" min="0" step="0.01" placeholder="0.00">
            </div>

            <div class="form-group">
                <label for="compatible_with">Compatible con</label>
                <input type="text" id="compatible_with" name="compatible_with" value="{{ old('compatible_with') }}" placeholder="Ej. Modelo X, Serie Y">
            </div>
        </div>

        <div class="form-group" style="margin-top:18px;">
            <label for="description">Descripción</label>
            <textarea id="description" name="description" rows="3" placeholder="Detalles de la refacción">{{ old('description') }}</textarea>
        </div>

        <div class="form-group" style="margin-top:18px;">
            <label for="photo">Fotografía</label>
            <input type="file" id="photo" name="photo" accept="image/*" onchange="previewPhoto(this)">
            <div class="photo-preview" id="photo-preview">
                <span style="font-size:13px;">Vista previa</span>
            </div>
        </div>

        <div style="margin-top:24px;">
            <button type="submit" class="btn">Guardar refacción</button>
        </div>
    </form>
</x-ui.card>
@endsection

@push('scripts')
<script>
    function previewPhoto(input) {
        const preview = document.getElementById('photo-preview');
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function (e) {
                preview.innerHTML = '<img src="' + e.target.result + '" alt="Vista previa">';
            };
            reader.readAsDataURL(input.files[0]);
        } else {
            preview.innerHTML = '<span style="font-size:13px;">Vista previa</span>';
        }
    }
</script>
@endpush