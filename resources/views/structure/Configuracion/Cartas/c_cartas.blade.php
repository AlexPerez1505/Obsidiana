@extends('structure.Configuracion.layout')

@section('title', 'Nueva carta de garantía')

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
        .form-input[type="file"] { padding: 10px; }
        .form-input:disabled { opacity: 0.55; cursor: not-allowed; }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 18px; }
        .file-hint { color: rgba(255,255,255,0.55); font-size: 12px; margin-top: 6px; }
    </style>

    <div class="catalog-card" style="margin-bottom:22px;">
        <div style="display:flex; align-items:center; justify-content:space-between; gap:18px; flex-wrap:wrap;">
            <div>
                <h2 class="page-title" style="margin:0; font-size:24px; font-weight:700;">Nueva carta de garantía</h2>
                <p class="page-subtitle" style="margin:4px 0 0; font-size:14px;">Registra una carta o documento vinculado a un tipo y subtipo de equipo.</p>
            </div>
            <div style="display:flex; align-items:center; gap:12px;">
                <a href="{{ route('configuracion.cartas.index') }}" style="background:rgba(8,18,40,0.55); color:#fff; border:1px solid rgba(0,168,255,0.45); padding:10px 18px; border-radius:12px; font-size:14px; font-weight:600; text-decoration:none; display:inline-flex; align-items:center; gap:8px;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5"/><path d="M12 19l-7-7 7-7"/></svg>
                    Regresar
                </a>
                <button type="submit" form="carta-form" class="btn" style="background:#00A8FF; color:#fff; border:1px solid rgba(255,255,255,0.15); padding:10px 18px; border-radius:12px; font-size:14px; font-weight:600; cursor:pointer; display:inline-flex; align-items:center; gap:8px;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                    Guardar carta
                </button>
            </div>
        </div>
    </div>

    <div class="catalog-card">
        @if (session('status'))
            <div class="alert alert--ok" style="margin:0 0 18px;">{{ session('status') }}</div>
        @endif

        <form method="POST" action="{{ route('configuracion.cartas.store') }}" id="carta-form" enctype="multipart/form-data" autocomplete="off">
            @csrf

            <div class="form-grid">
                <div class="form-group">
                    <label class="form-label" for="equipment_type_name">Tipo de equipo *</label>
                    <input type="text" id="equipment_type_name" name="equipment_type_name" class="form-input" value="{{ old('equipment_type_name') }}" list="equipment_type_list" placeholder="Ej. Equipo médico" required>
                    <datalist id="equipment_type_list">
                        @foreach ($equipmentTypes as $type)
                            <option value="{{ $type }}"></option>
                        @endforeach
                    </datalist>
                </div>

                <div class="form-group">
                    <label class="form-label" for="subtype_name">Subtipo *</label>
                    <input type="text" id="subtype_name" name="subtype_name" class="form-input" value="{{ old('subtype_name') }}" list="subtype_list" placeholder="Selecciona un tipo primero" required disabled>
                    <datalist id="subtype_list"></datalist>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="refaccion_name">Tipo de refacción *</label>
                <input type="text" id="refaccion_name" name="refaccion_name" class="form-input" value="{{ old('refaccion_name') }}" list="refaccion_list" placeholder="Ej. Fusible principal" required>
                <datalist id="refaccion_list">
                    @foreach ($refacciones as $refaccion)
                        <option value="{{ $refaccion }}"></option>
                    @endforeach
                </datalist>
            </div>

            <div class="form-group">
                <label class="form-label" for="description">Descripción</label>
                <textarea id="description" name="description" class="form-input" rows="4" placeholder="Detalles o contenido de la carta">{{ old('description') }}</textarea>
            </div>

            <div class="form-group">
                <label class="form-label" for="archivo">Archivo o imagen</label>
                <input type="file" id="archivo" name="archivo" class="form-input" accept="image/*,.pdf,.doc,.docx,.xls,.xlsx,.txt">
                <p class="file-hint">Puedes subir imágenes, PDF, Word, Excel o archivos de texto.</p>
            </div>
        </form>
    </div>

    <script>
        (function () {
            var typeInput = document.getElementById('equipment_type_name');
            var subtypeInput = document.getElementById('subtype_name');
            var subtypeList = document.getElementById('subtype_list');

            function loadSubtypes() {
                var value = typeInput.value.trim();
                subtypeInput.value = '';
                subtypeInput.disabled = true;
                subtypeList.innerHTML = '';

                if (!value) return;

                fetch('{{ route('configuracion.tipos_equipo.subtypes') }}?equipment_type_name=' + encodeURIComponent(value))
                    .then(function (res) { return res.json(); })
                    .then(function (data) {
                        data.forEach(function (item) {
                            var option = document.createElement('option');
                            option.value = item.name;
                            subtypeList.appendChild(option);
                        });
                        subtypeInput.disabled = false;
                        subtypeInput.placeholder = 'Ej. Monitor de signos vitales';
                    })
                    .catch(function () {
                        subtypeInput.placeholder = 'Selecciona un tipo primero';
                    });
            }

            typeInput.addEventListener('input', function () {
                clearTimeout(typeInput._t);
                typeInput._t = setTimeout(loadSubtypes, 250);
            });

            if (typeInput.value.trim()) {
                loadSubtypes();
            }
        })();
    </script>
@endsection
