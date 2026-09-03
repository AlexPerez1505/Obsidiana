@extends('layouts.dashboard')
@section('title', 'Registrar Cliente')
@section('page-title', 'Registrar Cliente')

@section('content')
    <form method="POST" action="{{ route('commercial.clientes.store') }}" class="rgrid-sidebar">
        @csrf
        <input type="hidden" name="return_to" value="{{ $returnTo ?? '' }}">

        <div>
            {{-- Datos personales --}}
            <x-ui.card style="margin-bottom:18px;">
                <x-ui.section-title style="margin:0 0 16px;">Datos Personales</x-ui.section-title>
                <div class="rgrid-2">
                    <x-ui.form-group label="Nombre *" name="nombre" placeholder="Ingrese el nombre" :required="true" />
                    <x-ui.form-group label="Apellido *" name="apellido" placeholder="Ingrese el apellido" :required="true" />
                    <x-ui.form-group label="Teléfono *" name="telefono" type="tel" placeholder="Ingrese el teléfono" inputmode="tel" maxlength="20" :required="true" />
<<<<<<< HEAD
                    <x-ui.form-group label="RFC" name="rfc" placeholder="Ingrese el RFC" maxlength="13" />
=======
                    <x-ui.form-group label="RFC" name="rfc" placeholder="Ingrese el RFC (opcional)" maxlength="13" />
>>>>>>> b0bc525046ab11c3972c63fbb675c09cb03e2a0b
                    <x-ui.form-group label="Correo (Gmail)" name="gmail" type="email" placeholder="Ingrese el correo" />
                </div>
            </x-ui.card>

            {{-- Información comercial --}}
            <x-ui.card style="margin-bottom:18px;">
                <x-ui.section-title style="margin:0 0 16px;">Información Comercial</x-ui.section-title>
                <div class="rgrid-2">
                    <x-ui.form-group for="asesor" label="Asesor de Ventas">
                        <input id="asesor" type="text" value="{{ auth()->user()?->name }}" readonly />
                    </x-ui.form-group>
                    <x-ui.form-group for="categoria_id" label="Categoría">
                        <select id="categoria_id" name="categoria_id">
                            <option value="" @selected(! old('categoria_id'))>Sin categoría</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}" @selected(old('categoria_id') == $category->id)>{{ $category->nombre }}</option>
                            @endforeach
                        </select>
                    </x-ui.form-group>
                    <x-ui.form-group for="congreso_id" label="Congreso Conocido">
                        <select id="congreso_id" name="congreso_id">
                            <option value="" @selected(! old('congreso_id'))>Sin congreso</option>
                            @foreach ($congresses as $congress)
                                <option value="{{ $congress->id }}" @selected(old('congreso_id') == $congress->id)>{{ $congress->nombre }}</option>
                            @endforeach
                        </select>
                    </x-ui.form-group>
                    <div style="grid-column:1 / -1;">
                        <x-ui.form-group for="como_conocio" label="¿Cómo conoció al cliente?">
                            <input id="como_conocio" type="text" name="como_conocio" value="{{ old('como_conocio') }}"
                                   placeholder="Ej. recomendación, redes sociales, llamada... (solo si no fue en un congreso)"
                                   {{ old('congreso_id') ? 'disabled' : '' }}>
                            <small style="color:var(--muted);">Solo aplica si no eligio un congreso ; si elige uno arriba, este campo se desactiva.</small>
                        </x-ui.form-group>
                    </div>
                    <x-ui.form-group for="recibe_promocion" label="¿Recibe Promoción?">
                        <input type="hidden" name="recibe_promocion" value="0">
                        <label class="ui-switch">
                            <input type="checkbox" id="recibe_promocion" name="recibe_promocion" value="1" @checked(old('recibe_promocion') == '1' || old('recibe_promocion') === true)>
                            <span class="slider"></span>
                        </label>
                    </x-ui.form-group>
                </div>
            </x-ui.card>

            {{-- Información adicional --}}
            <x-ui.card>
                <x-ui.section-title style="margin:0 0 16px;">Información Adicional</x-ui.section-title>
                <div style="margin-bottom:16px;">
                    <x-ui.form-group label="Dirección *" name="direccion" placeholder="Dirección del cliente" :required="true" />
                </div>
                <x-ui.form-group label="Comentarios" for="comentarios">
                    <textarea id="comentarios" name="comentarios" rows="4" placeholder="Comentarios">{{ old('comentarios') }}</textarea>
                </x-ui.form-group>
            </x-ui.card>
        </div>

        {{-- Sidebar --}}
        <div style="display:flex; flex-direction:column; gap:18px;">
            <x-ui.card>
                <div style="display:flex; align-items:center; gap:10px; margin-bottom:10px;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="var(--green)" stroke-width="2" width="26" height="26"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
                    <x-ui.section-title style="margin:0;">Información</x-ui.section-title>
                </div>
                <p class="muted" style="margin:0; font-size:14px;">Completa los datos básicos del cliente.</p>
                <p class="muted" style="margin:8px 0 0; font-size:14px;">RFC, correo, categoría, congreso y comentarios son opcionales.</p>
            </x-ui.card>

            <x-ui.card>
                <x-ui.section-title style="margin:0 0 12px;">Consejos</x-ui.section-title>
                <ul style="margin:0; padding-left:18px; font-size:14px; color:var(--text); line-height:1.7;">
                    <li>Asigna una categoría para segmentar promociones.</li>
                    <li>El asesor se asigna automáticamente (usuario actual).</li>
                    <li>Agrega notas relevantes en comentarios.</li>
                </ul>
            </x-ui.card>
        </div>

        {{-- Pie de acciones --}}
        <div class="page-foot">
            @if(!empty($returnTo))
                <a href="{{ $returnTo }}" class="btn btn--ghost">Cancelar</a>
            @endif
            <x-ui.button>Guardar Cliente</x-ui.button>
        </div>
    </form>

    <style>
        .ui-switch { position: relative; display: inline-block; width: 50px; height: 26px; }
        .ui-switch input { opacity: 0; width: 0; height: 0; }
        .ui-switch .slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #ccc; border-radius: 26px; transition: .4s; }
        .ui-switch .slider:before { position: absolute; content: ""; height: 22px; width: 22px; left: 2px; bottom: 2px; background-color: white; border-radius: 50%; transition: .4s; box-shadow: 0 1px 3px rgba(0,0,0,0.3); }
        .ui-switch input:checked + .slider { background-color: var(--green, #22c55e); }
        .ui-switch input:checked + .slider:before { transform: translateX(24px); }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const congresoSelect = document.getElementById('congreso_id');
            const comoConocio = document.getElementById('como_conocio');

            if (!congresoSelect || !comoConocio) return;

            const sincronizar = () => {
                const tieneCongreso = !!congresoSelect.value;
                comoConocio.disabled = tieneCongreso;
                if (tieneCongreso) comoConocio.value = '';
            };

            congresoSelect.addEventListener('change', sincronizar);
            sincronizar();
        });
    </script>
@endsection
