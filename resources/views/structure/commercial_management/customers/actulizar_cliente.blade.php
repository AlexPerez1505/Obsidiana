@extends('layouts.dashboard')
@section('title', 'Actualizar Cliente')

@section('content')
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
                    <x-ui.form-group label="Apellido" name="apellido" placeholder="Ingrese el apellido" :value="$customer->apellido" />
                    <x-ui.form-group label="Teléfono" name="telefono" type="tel" placeholder="Ingrese el teléfono" :value="$customer->telefono" inputmode="tel" maxlength="20" />
                    <x-ui.form-group label="Correo (Gmail)" name="gmail" type="email" placeholder="Ingrese el correo" :value="$customer->gmail" />
                </div>
            </x-ui.card>

            {{-- Información comercial --}}
            <x-ui.card style="margin-bottom:18px;">
                <x-ui.section-title style="margin:0 0 16px;">Información Comercial</x-ui.section-title>
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
                    <x-ui.form-group for="asesor" label="Asesor de Ventas">
                        <input id="asesor" type="text" value="{{ $customer->asesor?->name ?? auth()->user()?->name }}" readonly style="width:100%; padding:11px 12px; border:1px solid var(--border); border-radius:9px; font-size:15px; background:var(--surface); color:var(--text);" />
                    </x-ui.form-group>
                    <x-ui.form-group for="categoria_id" label="Categoría">
                        <select id="categoria_id" name="categoria_id" style="width:100%; padding:11px 12px; border:1px solid var(--border); border-radius:9px; font-size:15px; background:var(--surface); color:var(--text);">
                            <option value="">Sin categoría</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}" @selected(old('categoria_id', $customer->categoria_id) == $category->id)>{{ $category->nombre }}</option>
                            @endforeach
                        </select>
                    </x-ui.form-group>
                    <x-ui.form-group for="congreso_id" label="Congreso Conocido">
                        <select id="congreso_id" name="congreso_id" style="width:100%; padding:11px 12px; border:1px solid var(--border); border-radius:9px; font-size:15px; background:var(--surface); color:var(--text);">
                            <option value="">Sin congreso</option>
                            @foreach ($congresses as $congress)
                                <option value="{{ $congress->id }}" @selected(old('congreso_id', $customer->congreso_id) == $congress->id)>{{ $congress->nombre }}</option>
                            @endforeach
                        </select>
                    </x-ui.form-group>
                    <x-ui.form-group for="recibe_promocion" label="¿Recibe Promoción?">
                        <input type="hidden" name="recibe_promocion" value="0">
                        <label class="ui-switch">
                            <input type="checkbox" id="recibe_promocion" name="recibe_promocion" value="1" @checked(old('recibe_promocion', $customer->recibe_promocion ? '1' : '0') == '1')>
                            <span class="slider"></span>
                        </label>
                    </x-ui.form-group>
                    <x-ui.form-group for="activo" label="¿Cliente Activo?">
                        <input type="hidden" name="activo" value="0">
                        <label class="ui-switch">
                            <input type="checkbox" id="activo" name="activo" value="1" @checked(old('activo', $customer->activo ? '1' : '0') == '1')>
                            <span class="slider"></span>
                        </label>
                    </x-ui.form-group>
                </div>
            </x-ui.card>

            {{-- Información adicional --}}
            <x-ui.card>
                <x-ui.section-title style="margin:0 0 16px;">Información Adicional</x-ui.section-title>
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:16px;">
                    <x-ui.form-group label="Dirección" name="direccion" placeholder="Dirección del cliente" :value="$customer->direccion" />
                </div>
                <x-ui.form-group label="Comentarios" for="comentarios">
                    <textarea id="comentarios" name="comentarios" rows="4" placeholder="Comentarios" style="width:100%; padding:11px 12px; border:1px solid var(--border); border-radius:9px; font-size:15px; background:var(--surface); color:var(--text); resize:vertical;">{{ old('comentarios', $customer->comentarios) }}</textarea>
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
                    <p style="margin:0;">{{ $customer->telefono ?: 'Sin teléfono' }}</p>
                    <p style="margin:0;">{{ $customer->asesor?->name ?? 'Sin asesor' }}</p>
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
@endsection
