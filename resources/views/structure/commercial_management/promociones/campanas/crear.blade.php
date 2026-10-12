@extends('structure.commercial_management.layout')

@section('title', 'Nueva campaña')
@section('page-title', 'Nueva campaña de promoción')

@section('commercial_content')
    <div class="content-actions">
        <a href="{{ route('commercial.promociones.campanas.index') }}" class="btn btn--ghost">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="15" height="15"><path d="M19 12H5"/><path d="M12 19l-7-7 7-7"/></svg>
            Regresar
        </a>
    </div>

    <x-ui.card style="margin-top:14px; margin-bottom:18px;">
        <p class="muted" style="margin:0; font-size:13.5px;">
            Esta campaña solo le llegará a clientes que ya confirmaron ellos mismos que quieren recibir promociones
            (<a href="{{ route('commercial.promociones.index', ['estado' => 'confirmado']) }}">{{ $totalConfirmados }} clientes confirmados hoy</a>).
            El filtro de abajo es adicional, para no mandarle a todos si no quieres.
        </p>
    </x-ui.card>

    <form method="POST" action="{{ route('commercial.promociones.campanas.store') }}">
        @csrf
        <x-ui.card style="margin-bottom:18px;">
            <x-ui.section-title style="margin:0 0 16px;">Mensaje</x-ui.section-title>

            <x-ui.form-group label="Nombre de la campaña *" name="nombre" placeholder="Ej. Promoción de septiembre" :required="true" />

            <x-ui.form-group label="Mensaje que se va a mandar *" for="mensaje">
                <textarea id="mensaje" name="mensaje" rows="5" maxlength="1000" required
                          placeholder="Hola {nombre}, tenemos una promoción especial para ti..."
                          style="width:100%; padding:11px 12px; border:1px solid var(--border); border-radius:9px; font-size:15px; background:var(--surface); color:var(--text); resize:vertical;">{{ old('mensaje') }}</textarea>
                <small style="color:var(--muted);">Máximo 1000 caracteres.</small>
            </x-ui.form-group>

            <x-ui.form-group label="Programar para (opcional)" for="programada_para">
                <input id="programada_para" type="datetime-local" name="programada_para" value="{{ old('programada_para') }}"
                       style="width:100%; padding:11px 12px; border:1px solid var(--border); border-radius:9px; font-size:15px; background:var(--surface); color:var(--text);">
                <small style="color:var(--muted);">Por ahora es solo informativo: la campaña se manda al presionar "Lanzar" en la pantalla siguiente, no sola a esta hora.</small>
            </x-ui.form-group>
        </x-ui.card>

        <x-ui.card style="margin-bottom:18px;">
            <x-ui.section-title style="margin:0 0 6px;">A quién (opcional)</x-ui.section-title>
            <p class="muted" style="margin:0 0 14px; font-size:13.5px;">
                Déjalo vacío para que le llegue a todos los clientes confirmados. Si eliges algo, se le manda solo a los que además cumplan esto.
            </p>
            <div class="rgrid-2" style="display:grid; grid-template-columns:repeat(auto-fit, minmax(260px, 1fr)); gap:12px 18px;">
                <x-ui.form-group label="Categoría" for="categoria_id">
                    <select id="categoria_id" name="categoria_id"
                            style="width:100%; padding:11px 12px; border:1px solid var(--border); border-radius:9px; font-size:15px; background:var(--surface); color:var(--text);">
                        <option value="">Cualquiera</option>
                        @foreach ($categorias as $categoria)
                            <option value="{{ $categoria->id }}" {{ old('categoria_id') == $categoria->id ? 'selected' : '' }}>{{ $categoria->nombre }}</option>
                        @endforeach
                    </select>
                </x-ui.form-group>

                <x-ui.form-group label="Congreso de origen" for="congreso_id">
                    <select id="congreso_id" name="congreso_id"
                            style="width:100%; padding:11px 12px; border:1px solid var(--border); border-radius:9px; font-size:15px; background:var(--surface); color:var(--text);">
                        <option value="">Cualquiera</option>
                        @foreach ($congresos as $congreso)
                            <option value="{{ $congreso->id }}" {{ old('congreso_id') == $congreso->id ? 'selected' : '' }}>{{ $congreso->nombre }}</option>
                        @endforeach
                    </select>
                </x-ui.form-group>
            </div>
        </x-ui.card>

        <div style="display:flex; gap:10px;">
            <x-ui.button>Guardar como borrador</x-ui.button>
            <a href="{{ route('commercial.promociones.campanas.index') }}" class="btn btn--ghost">Cancelar</a>
        </div>
    </form>
@endsection
