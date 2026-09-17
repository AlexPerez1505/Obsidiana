@extends('structure.commercial_management.erp')

@section('title', 'Enviar equipo a mantenimiento externo')

@section('erp_content')
    <div class="erp-head">
        <div class="erp-head-l">
            <h1 class="erp-h1">Enviar equipo</h1>
            <span class="erp-count">Mantenimiento externo</span>
        </div>
        <a href="{{ route('gestion.servicios.externo') }}" class="erp-btn ghost">Volver a Externo</a>
    </div>

    @if ($errors->any())
        <div class="erp-card" style="border-color:var(--danger); padding:14px 18px; color:var(--danger); font-weight:700;">
            <ul style="margin:0; padding-left:18px;">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="erp-card" style="padding:20px;">
        <form method="POST" action="{{ route('gestion.servicios.externo.store') }}">
            @csrf

            <h3 style="margin:0 0 14px;">Cliente y destinatario</h3>
            <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(240px, 1fr)); gap:16px; margin-bottom:22px;">
                <div>
                    <label for="customer_id" style="display:block; font-weight:700; margin-bottom:6px;">Cliente</label>
                    <select id="customer_id" name="customer_id" required style="width:100%; padding:10px 12px; border:1px solid var(--border); border-radius:10px; background:var(--surface); color:var(--text);">
                        <option value="">Selecciona un cliente</option>
                        @foreach ($customers as $customer)
                            <option value="{{ $customer->id }}" @selected(old('customer_id') == $customer->id)>
                                {{ trim(($customer->nombre ?? '') . ' ' . ($customer->apellido ?? '')) ?: 'Sin nombre' }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="external_recipient_user_id" style="display:block; font-weight:700; margin-bottom:6px;">Enviar a</label>
                    <select id="external_recipient_user_id" name="external_recipient_user_id" required style="width:100%; padding:10px 12px; border:1px solid var(--border); border-radius:10px; background:var(--surface); color:var(--text);">
                        <option value="">Selecciona una cuenta de Mantenimiento Externo</option>
                        @foreach ($destinatarios as $destinatario)
                            <option value="{{ $destinatario->id }}" @selected(old('external_recipient_user_id') == $destinatario->id)>
                                {{ $destinatario->name }} ({{ $destinatario->email }})
                            </option>
                        @endforeach
                    </select>
                    @if ($destinatarios->isEmpty())
                        <p style="color:var(--danger); font-size:12px; margin-top:6px;">Todavía no hay ninguna cuenta con el rol Mantenimiento Externo.</p>
                    @endif
                </div>
            </div>

            <h3 style="margin:0 0 14px;">Datos del equipo</h3>
            <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(200px, 1fr)); gap:16px; margin-bottom:18px;">
                <div>
                    <label for="tipo_equipo" style="display:block; font-weight:700; margin-bottom:6px;">Tipo de equipo</label>
                    <input type="text" id="tipo_equipo" name="tipo_equipo" list="tipo_equipo_list" value="{{ old('tipo_equipo') }}" required placeholder="Ej. Equipo médico" style="width:100%; padding:10px 12px; border:1px solid var(--border); border-radius:10px; background:var(--surface); color:var(--text);">
                    <datalist id="tipo_equipo_list">
                        @foreach ($equipmentTypes->unique('name')->sortBy('name')->values() as $type)
                            <option value="{{ $type->name }}">
                        @endforeach
                    </datalist>
                </div>
                <div>
                    <label for="subtipo" style="display:block; font-weight:700; margin-bottom:6px;">Subtipo</label>
                    <input type="text" id="subtipo" name="subtipo" value="{{ old('subtipo') }}" placeholder="Ej. Monitor de signos vitales" style="width:100%; padding:10px 12px; border:1px solid var(--border); border-radius:10px; background:var(--surface); color:var(--text);">
                </div>
                <div>
                    <label for="marca" style="display:block; font-weight:700; margin-bottom:6px;">Marca</label>
                    <input type="text" id="marca" name="marca" list="marca_list" value="{{ old('marca') }}" placeholder="Ej. Olympus" style="width:100%; padding:10px 12px; border:1px solid var(--border); border-radius:10px; background:var(--surface); color:var(--text);">
                    <datalist id="marca_list">
                        @foreach ($brands->unique('name')->sortBy('name')->values() as $brand)
                            <option value="{{ $brand->name }}">
                        @endforeach
                    </datalist>
                </div>
                <div>
                    <label for="modelo" style="display:block; font-weight:700; margin-bottom:6px;">Modelo</label>
                    <input type="text" id="modelo" name="modelo" value="{{ old('modelo') }}" placeholder="Ej. C-90" style="width:100%; padding:10px 12px; border:1px solid var(--border); border-radius:10px; background:var(--surface); color:var(--text);">
                </div>
                <div>
                    <label for="serie" style="display:block; font-weight:700; margin-bottom:6px;">Número de serie</label>
                    <input type="text" id="serie" name="serie" value="{{ old('serie') }}" placeholder="Ej. SN-893-832" style="width:100%; padding:10px 12px; border:1px solid var(--border); border-radius:10px; background:var(--surface); color:var(--text);">
                </div>
            </div>

            <div style="margin-bottom:22px;">
                <label for="descripcion_equipo" style="display:block; font-weight:700; margin-bottom:6px;">Descripción (opcional)</label>
                <textarea id="descripcion_equipo" name="descripcion_equipo" rows="4" placeholder="Describe el equipo y el motivo del envío" style="width:100%; padding:10px 12px; border:1px solid var(--border); border-radius:10px; background:var(--surface); color:var(--text); font-family:inherit;">{{ old('descripcion_equipo') }}</textarea>
            </div>

            <div style="display:flex; justify-content:flex-end;">
                <button type="submit" class="erp-btn">Enviar a mantenimiento externo</button>
            </div>
        </form>
    </div>
@endsection
