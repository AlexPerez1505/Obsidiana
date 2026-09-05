@extends('structure.commercial_management.erp')

@section('title', 'Agregar carta de garantía')

@section('erp_content')
    <div class="content-actions">
        <a href="{{ route('gestion.servicios.garantia.index') }}" class="erp-btn btn--ghost">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5"/><path d="M12 19l-7-7 7-7"/></svg>
            Regresar
        </a>
    </div>

    <div class="erp-card" style="max-width:720px; margin:0 auto;">
        <h3 style="display:flex; align-items:center; gap:10px; font-size:18px; margin:0 0 18px;">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" color="var(--primary)"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
            Agregar carta de garantía
        </h3>
        <p class="muted" style="margin:0 0 22px; font-size:13px;">Completa los datos y adjunta los archivos de la carta. Puedes subir cualquier tipo de archivo.</p>

        <form method="POST" action="{{ route('gestion.servicios.garantia.guardar_carta') }}" enctype="multipart/form-data">
            @csrf

            <div class="form-group" style="margin-bottom:16px;">
                <label for="nombre">Nombre de la carta <span style="color:var(--danger)">*</span></label>
                <input type="text" id="nombre" name="nombre" value="{{ old('nombre') }}" placeholder="Ej. Carta de garantía endoscopio" required class="qinput">
            </div>

            <div class="form-group" style="margin-bottom:16px;">
                <label for="tipo_equipo">Tipo de equipo <span style="color:var(--danger)">*</span></label>
                <input type="text" id="tipo_equipo" name="tipo_equipo" value="{{ old('tipo_equipo') }}" list="tipos-equipo" placeholder="Escribe o selecciona un tipo" required class="qinput">
                <datalist id="tipos-equipo">
                    @foreach ($equipmentTypes as $type)
                        <option value="{{ $type->name }}">
                    @endforeach
                </datalist>
            </div>

            <div class="form-group" style="margin-bottom:22px;">
                <label for="archivos">Archivos de la carta</label>
                <input type="file" id="archivos" name="archivos[]" multiple accept="*" style="display:none;">
                <label for="archivos" class="upload-card" style="display:flex; flex-direction:column; align-items:center; justify-content:center; gap:8px; padding:28px; border:1px dashed var(--border); border-radius:12px; cursor:pointer; color:var(--muted); background:var(--surface); text-align:center;">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                    <span style="font-size:14px; font-weight:600;">Haz clic para adjuntar archivos</span>
                    <span style="font-size:12px;">Cualquier formato, hasta 20 MB por archivo.</span>
                    <span id="archivos-seleccionados" style="font-size:12px; color:var(--text); font-weight:600; margin-top:4px;"></span>
                </label>
            </div>

            <div style="display:flex; gap:10px; justify-content:flex-end;">
                <a href="{{ route('gestion.servicios.garantia.index') }}" class="btn btn--ghost">Cancelar</a>
                <button type="submit" class="btn">Guardar carta</button>
            </div>
        </form>
    </div>

    <script>
        const inputArchivos = document.getElementById('archivos');
        const labelArchivos = document.querySelector('label[for="archivos"]');
        const seleccionadosEl = document.getElementById('archivos-seleccionados');

        inputArchivos?.addEventListener('change', () => {
            const count = inputArchivos.files.length;
            if (count === 0) {
                seleccionadosEl.textContent = '';
            } else if (count === 1) {
                seleccionadosEl.textContent = inputArchivos.files[0].name;
            } else {
                seleccionadosEl.textContent = count + ' archivos seleccionados';
            }
        });
    </script>
@endsection
