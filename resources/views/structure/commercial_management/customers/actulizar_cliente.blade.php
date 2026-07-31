@extends('layouts.dashboard')
@section('title', 'Actualizar Cliente')

@section('content')
    @php
        $apellidos = explode(' ', $customer->apellido, 2);
        $apellido_paterno = $apellidos[0] ?? '';
        $apellido_materno = $apellidos[1] ?? '';
    @endphp

    <form method="POST" action="{{ route('commercial.clientes.update', $customer) }}" style="display:grid; grid-template-columns:1fr 320px; gap:18px; align-items:start;">
        @csrf
        @method('PUT')

        <div>
            {{-- Header --}}
            <div class="card" style="margin-bottom:18px; display:flex; align-items:center; justify-content:space-between; gap:16px; flex-wrap:wrap;">
                <div style="display:flex; align-items:center; gap:12px;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="32" height="32" style="color:var(--text);">
                        <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                        <circle cx="8.5" cy="7" r="4"/>
                        <line x1="20" y1="8" x2="20" y2="14"/>
                        <line x1="23" y1="11" x2="17" y2="11"/>
                    </svg>
                    <h2 class="page-title" style="margin:0;">Actualizar Cliente</h2>
                </div>
                <div style="display:flex; align-items:center; gap:10px;">
                    <a href="{{ route('commercial.clientes.index') }}" class="btn btn--ghost" style="text-decoration:none; display:inline-flex; align-items:center; gap:6px;">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5"/><path d="M12 19l-7-7 7-7"/></svg>
                        Regresar
                    </a>
                    <x-ui.button>Actualizar Cliente</x-ui.button>
                </div>
            </div>

            {{-- Datos personales --}}
            <x-ui.card style="margin-bottom:18px;">
                <x-ui.section-title style="margin:0 0 16px;">Datos Personales</x-ui.section-title>
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
                    <x-ui.form-group label="Nombre *" name="nombre" placeholder="Ingrese el nombre" :value="$customer->nombre" :required="true" />
                    <x-ui.form-group label="Apellido Paterno *" name="apellido_paterno" placeholder="Ingrese el apellido paterno" :value="$apellido_paterno" :required="true" />
                    <x-ui.form-group label="Apellido Materno *" name="apellido_materno" placeholder="Ingrese el apellido materno" :value="$apellido_materno" :required="true" />
                    <x-ui.form-group label="Teléfono (con lada) *" name="telefono" type="tel" placeholder="Ingrese el teléfono con lada" :value="$customer->telefono" :required="true" inputmode="tel" maxlength="12" pattern="\d{10,12}" />
                    <x-ui.form-group label="Correo Electrónico" name="correo" type="email" placeholder="Ingrese el correo" :value="$customer->correo" />
                </div>
            </x-ui.card>

            {{-- Información comercial --}}
            <x-ui.card style="margin-bottom:18px;">
                <x-ui.section-title style="margin:0 0 16px;">Información Comercial</x-ui.section-title>
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
                    <x-ui.form-group for="asesor" label="Asesor de Ventas">
                        <input id="asesor" type="text" value="{{ $customer->seller?->name ?? auth()->user()?->name }}" readonly style="width:100%; padding:11px 12px; border:1px solid var(--border); border-radius:9px; font-size:15px; background:var(--surface); color:var(--text);" />
                    </x-ui.form-group>
                    <x-ui.form-group for="category_id" label="Categoría *">
                        <select id="category_id" name="category_id" required style="width:100%; padding:11px 12px; border:1px solid var(--border); border-radius:9px; font-size:15px; background:var(--surface); color:var(--text);">
                            <option value="" disabled>Seleccione una categoría</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}" @selected(old('category_id', $customer->category_id) == $category->id)>{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </x-ui.form-group>
                    <x-ui.form-group for="congress_id" label="Congreso Conocido *">
                        <select id="congress_id" name="congress_id" required style="width:100%; padding:11px 12px; border:1px solid var(--border); border-radius:9px; font-size:15px; background:var(--surface); color:var(--text);">
                            <option value="" disabled>Seleccione un Congreso</option>
                            @foreach ($congresses as $congress)
                                <option value="{{ $congress->id }}" @selected(old('congress_id', $customer->congress_id) == $congress->id)>{{ $congress->label }}</option>
                            @endforeach
                        </select>
                    </x-ui.form-group>
                    <x-ui.form-group label="RFC *" name="rfc" placeholder="PEGJ800815H54" maxlength="13" :value="$customer->rfc" :required="true" />
                    <x-ui.form-group for="receives_promotion" label="¿Recibe Promoción? *">
                        <input type="hidden" name="receives_promotion" value="0">
                        <label class="ui-switch">
                            <input type="checkbox" id="receives_promotion" name="receives_promotion" value="1" @checked(old('receives_promotion', $customer->receives_promotion ? '1' : '0') == '1')>
                            <span class="slider"></span>
                        </label>
                    </x-ui.form-group>
                </div>
            </x-ui.card>

            {{-- Información adicional --}}
            <x-ui.card>
                <x-ui.section-title style="margin:0 0 16px;">Información Adicional</x-ui.section-title>
                <x-ui.form-group label="Dirección y Comentarios" for="comentarios">
                    <textarea id="comentarios" name="comentarios" rows="4" placeholder="Dirección y comentarios" style="width:100%; padding:11px 12px; border:1px solid var(--border); border-radius:9px; font-size:15px; background:var(--surface); color:var(--text); resize:vertical;">{{ old('comentarios', $customer->comentarios) }}</textarea>
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
                <p class="muted" style="margin:0; font-size:14px;">Modifica los datos básicos del cliente.</p>
                <p class="muted" style="margin:8px 0 0; font-size:14px;">Los campos marcados con * son obligatorios</p>
            </x-ui.card>

            <x-ui.card>
                <x-ui.section-title style="margin:0 0 12px;">Consejos</x-ui.section-title>
                <ul style="margin:0; padding-left:18px; font-size:14px; color:var(--text); line-height:1.7;">
                    <li>Verifica que el RFC sea correcto.</li>
                    <li>Revisa la categoría asignada.</li>
                    <li>Actualiza las notas si es necesario.</li>
                </ul>
            </x-ui.card>

            <x-ui.card>
                <x-ui.section-title style="margin:0 0 14px;">Vista Previa</x-ui.section-title>
                <div style="display:flex; align-items:center; gap:12px; margin-bottom:12px;">
                    <div style="width:40px; height:40px; border-radius:50%; background:var(--primary-soft); display:flex; align-items:center; justify-content:center; color:var(--primary);">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="20" height="20"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                    </div>
                    <span class="muted" style="font-size:14px;">{{ $customer->nombre }} {{ $customer->apellido }}</span>
                </div>
                <div style="font-size:14px; color:var(--text); line-height:1.8;">
                    <p style="margin:0;">{{ $customer->apellido }}</p>
                    <p style="margin:0;">{{ $customer->telefono }}</p>
                    <p style="margin:0;">{{ $customer->seller?->name ?? 'Sin asesor' }}</p>
                </div>
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

    <script>
        document.getElementById('rfc').addEventListener('input', function (e) {
            e.target.value = e.target.value.toUpperCase();
        });
    </script>
@endsection
