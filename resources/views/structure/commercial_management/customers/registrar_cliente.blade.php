@extends('layouts.dashboard')
@section('title', 'Registrar Cliente')

@section('content')
    <form method="POST" action="{{ route('commercial.clientes.store') }}" style="display:grid; grid-template-columns:1fr 320px; gap:18px; align-items:start;">
        @csrf

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
                    <h2 class="page-title" style="margin:0;">Registrar Cliente</h2>
                </div>
                <div style="display:flex; align-items:center; gap:10px;">
                    <a href="{{ route('commercial.clientes.index') }}" class="btn btn--ghost" style="text-decoration:none;">Cancelar</a>
                    <x-ui.button>Guardar Cliente</x-ui.button>
                </div>
            </div>

            {{-- Datos personales --}}
            <x-ui.card style="margin-bottom:18px;">
                <x-ui.section-title style="margin:0 0 16px;">Datos Personales</x-ui.section-title>
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
                    <x-ui.form-group label="Nombre *" name="nombre" placeholder="Ingrese el nombre" :required="true" />
                    <x-ui.form-group label="Apellido *" name="apellido" placeholder="Ingrese el apellido" :required="true" />
                    <x-ui.form-group label="Teléfono *" name="telefono" type="tel" placeholder="Ingrese el teléfono" :required="true" inputmode="tel" maxlength="10" pattern="\d{10}" />
                    <x-ui.form-group label="Correo Electrónico" name="correo" type="email" placeholder="Ingrese el correo" />
                </div>
            </x-ui.card>

            {{-- Información comercial --}}
            <x-ui.card style="margin-bottom:18px;">
                <x-ui.section-title style="margin:0 0 16px;">Información Comercial</x-ui.section-title>
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
                    <x-ui.form-group for="asesor" label="Asesor de Ventas">
                        <input id="asesor" type="text" value="{{ auth()->user()?->name }}" readonly style="width:100%; padding:11px 12px; border:1px solid var(--border); border-radius:9px; font-size:15px; background:var(--surface); color:var(--text);" />
                    </x-ui.form-group>
                    <x-ui.form-group for="customer_category_id" label="Categoría *">
                        <select id="customer_category_id" name="customer_category_id" required style="width:100%; padding:11px 12px; border:1px solid var(--border); border-radius:9px; font-size:15px; background:var(--surface); color:var(--text);">
                            <option value="" disabled selected>Seleccione una categoría</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}">{{ $category->label }}</option>
                            @endforeach
                        </select>
                    </x-ui.form-group>
                    <x-ui.form-group for="congress_id" label="Congreso Conocido *">
                        <select id="congress_id" name="congress_id" required style="width:100%; padding:11px 12px; border:1px solid var(--border); border-radius:9px; font-size:15px; background:var(--surface); color:var(--text);">
                            <option value="" disabled selected>Seleccione un Congreso</option>
                            @foreach ($congresses as $congress)
                                <option value="{{ $congress->id }}">{{ $congress->label }}</option>
                            @endforeach
                        </select>
                    </x-ui.form-group>
                    <x-ui.form-group label="RFC *" name="rfc" placeholder="PEGJ800815H54" maxlength="13" :required="true" />
                    <x-ui.form-group for="receives_promotion" label="¿Recibe Promoción? *">
                        <select id="receives_promotion" name="receives_promotion" required style="width:100%; padding:11px 12px; border:1px solid var(--border); border-radius:9px; font-size:15px; background:var(--surface); color:var(--text);">
                            <option value="" disabled selected>Seleccione</option>
                            <option value="1">Sí</option>
                            <option value="0">No</option>
                        </select>
                    </x-ui.form-group>
                </div>
            </x-ui.card>

            {{-- Información adicional --}}
            <x-ui.card>
                <x-ui.section-title style="margin:0 0 16px;">Información Adicional</x-ui.section-title>
                <x-ui.form-group label="Dirección y Comentarios" for="comentarios">
                    <textarea id="comentarios" name="comentarios" rows="4" placeholder="Dirección y comentarios" style="width:100%; padding:11px 12px; border:1px solid var(--border); border-radius:9px; font-size:15px; background:var(--surface); color:var(--text); resize:vertical;"></textarea>
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
                <p class="muted" style="margin:8px 0 0; font-size:14px;">Los campos marcados con * son obligatorios</p>
            </x-ui.card>

            <x-ui.card>
                <x-ui.section-title style="margin:0 0 12px;">Consejos</x-ui.section-title>
                <ul style="margin:0; padding-left:18px; font-size:14px; color:var(--text); line-height:1.7;">
                    <li>Verifica que el RFC sea correcto.</li>
                    <li>Asigna un vendedor responsable.</li>
                    <li>Agrega notas relevantes.</li>
                </ul>
            </x-ui.card>

            <x-ui.card>
                <x-ui.section-title style="margin:0 0 14px;">Vista Previa</x-ui.section-title>
                <div style="display:flex; align-items:center; gap:12px; margin-bottom:12px;">
                    <div style="width:40px; height:40px; border-radius:50%; background:var(--primary-soft); display:flex; align-items:center; justify-content:center; color:var(--primary);">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="20" height="20"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                    </div>
                    <span class="muted" style="font-size:14px;">Nombre del Cliente</span>
                </div>
                <div style="font-size:14px; color:var(--text); line-height:1.8;">
                    <p style="margin:0;">Apellido</p>
                    <p style="margin:0;">Teléfono</p>
                    <p style="margin:0;">Asesor</p>
                </div>
            </x-ui.card>
        </div>
    </form>
@endsection
