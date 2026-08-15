@extends('layouts.dashboard')
@section('title', 'Registrar Cliente')

@section('content')
    <form method="POST" action="{{ route('commercial.clientes.store') }}" style="width:100%;">
        @csrf
        <input type="hidden" name="return_to" value="{{ $returnTo ?? '' }}">

        <div style="width:100%;">
            {{-- Header --}}
            <div class="card" style="margin-bottom:18px; display:flex; align-items:center; justify-content:space-between; gap:16px; flex-wrap:wrap;">
                <div style="display:flex; align-items:center; gap:12px;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="32" height="32" style="color:var(--text);">
                        <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                        <circle cx="8.5" cy="7" r="4"/>
                        <line x1="20" y1="8" x2="20" y2="14"/>
                        <line x1="23" y1="11" x2="17" y2="11"/>
                    </svg>
                    <h2 class="page-title" style="margin:0;">Registrar Cliente</h2>
                </div>
                <div style="display:flex; align-items:center; gap:10px;">
                    @if(!empty($returnTo))
                        <a href="{{ $returnTo }}" class="btn btn--ghost" style="text-decoration:none; display:inline-flex; align-items:center; gap:6px;">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5"/><path d="M12 19l-7-7 7-7"/></svg>
                            Regresar
                        </a>
                    @endif
                    <x-ui.button>Guardar Cliente</x-ui.button>
                </div>
            </div>

            {{-- Datos personales --}}
            <x-ui.card style="margin-bottom:18px;">
                <x-ui.section-title style="margin:0 0 16px;">Datos Personales</x-ui.section-title>
                <div class="rgrid-2">
                    <x-ui.form-group label="Nombre *" name="nombre" placeholder="Ingrese el nombre" :required="true" />
                    <x-ui.form-group label="Apellido *" name="apellido" placeholder="Ingrese el apellido" :required="true" />
                    <x-ui.form-group label="Teléfono *" name="telefono" type="tel" placeholder="Ingrese el teléfono" inputmode="tel" maxlength="20" :required="true" />
                    <x-ui.form-group label="RFC" name="rfc" placeholder="Ingrese el RFC" maxlength="13" />
                    <x-ui.form-group label="Correo (Gmail)" name="gmail" type="email" placeholder="Ingrese el correo" />
                </div>
            </x-ui.card>

            {{-- Información comercial --}}
            <x-ui.card style="margin-bottom:18px;">
                <x-ui.section-title style="margin:0 0 16px;">Información Comercial</x-ui.section-title>
                <div class="rgrid-2">
                    <x-ui.form-group for="asesor" label="Asesor de Ventas">
                        <input id="asesor" type="text" value="{{ auth()->user()?->name }}" readonly style="width:100%; padding:11px 12px; border:1px solid var(--border); border-radius:9px; font-size:15px; background:var(--surface); color:var(--text);" />
                    </x-ui.form-group>
                    <x-ui.form-group for="categoria_id" label="Categoría *">
                        <select id="categoria_id" name="categoria_id" required style="width:100%; padding:11px 12px; border:1px solid var(--border); border-radius:9px; font-size:15px; background:var(--surface); color:var(--text);">
                            <option value="" disabled @selected(! old('categoria_id'))>Selecciona una categoría</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}" @selected(old('categoria_id') == $category->id)>{{ $category->nombre }}</option>
                            @endforeach
                        </select>
                    </x-ui.form-group>
                    <x-ui.form-group for="congreso_id" label="Congreso Conocido *">
                        <select id="congreso_id" name="congreso_id" required style="width:100%; padding:11px 12px; border:1px solid var(--border); border-radius:9px; font-size:15px; background:var(--surface); color:var(--text);">
                            <option value="" disabled @selected(! old('congreso_id'))>Selecciona un congreso</option>
                            @foreach ($congresses as $congress)
                                <option value="{{ $congress->id }}" @selected(old('congreso_id') == $congress->id)>{{ $congress->nombre }}</option>
                            @endforeach
                        </select>
                    </x-ui.form-group>
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
                    <textarea id="comentarios" name="comentarios" rows="4" placeholder="Comentarios" style="width:100%; padding:11px 12px; border:1px solid var(--border); border-radius:9px; font-size:15px; background:var(--surface); color:var(--text); resize:vertical;">{{ old('comentarios') }}</textarea>
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
                <p class="muted" style="margin:8px 0 0; font-size:14px;">Correo y comentarios son opcionales.</p>
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
    </form>

    <style>
        .ui-switch { position: relative; display: inline-block; width: 50px; height: 26px; }
        .ui-switch input { opacity: 0; width: 0; height: 0; }
        .ui-switch .slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #ccc; border-radius: 26px; transition: .4s; }
        .ui-switch .slider:before { position: absolute; content: ""; height: 22px; width: 22px; left: 2px; bottom: 2px; background-color: white; border-radius: 50%; transition: .4s; box-shadow: 0 1px 3px rgba(0,0,0,0.3); }
        .ui-switch input:checked + .slider { background-color: var(--green, #22c55e); }
        .ui-switch input:checked + .slider:before { transform: translateX(24px); }
    </style>

@endsection
