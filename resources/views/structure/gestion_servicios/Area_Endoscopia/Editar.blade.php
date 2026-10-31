@extends('structure.gestion_servicios.layout')

@section('title', 'Editar servicio')

@push('head')
<style>
.area-header { display:flex; align-items:center; justify-content:space-between; gap:16px; flex-wrap:wrap; margin-bottom:24px; }
.area-title { font-size:22px; margin:0; }
.area-subtitle { font-size:13px; color:var(--muted); margin:4px 0 0; }
.form-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(240px,1fr)); gap:18px; }
.form-group label { font-size:12px; color:var(--muted); font-weight:700; margin-bottom:6px; display:block; text-transform:uppercase; letter-spacing:.03em; }
.form-group input, .form-group select, .form-group textarea { width:100%; padding:11px 12px; border:1px solid var(--border); border-radius:9px; background:var(--surface); color:var(--text); font-size:14px; }
.form-group input:focus, .form-group select:focus, .form-group textarea:focus { border-color:var(--primary); outline:none; }
.btn { display:inline-flex; align-items:center; gap:8px; padding:10px 18px; border-radius:10px; font-size:14px; font-weight:700; text-decoration:none; background:var(--primary); color:#fff; border:1px solid var(--primary); cursor:pointer; }
</style>
@endpush

@php
    $equipment = $service->serviceEquipment;
@endphp

@section('service_content')
<x-ui.card>
    <div class="area-header">
        <div>
            <h1 class="area-title">Editar servicio</h1>
            <p class="area-subtitle">Folio {{ $service->service_number ?? ('OS-' . $service->id) }}</p>
        </div>
        <a href="{{ route('gestion.servicios.area_endoscopia') }}" class="btn" style="background:transparent; color:var(--primary);">Volver</a>
    </div>

    <form method="POST" action="{{ route('gestion.servicios.area_endoscopia.update', $service) }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div class="form-grid">
            <div class="form-group">
                <label for="customer_id">Cliente</label>
                <select name="customer_id" id="customer_id">
                    <option value="">—</option>
                    @foreach ($customers as $customer)
                        @php $fullName = trim(($customer->nombre ?? '') . ' ' . ($customer->apellido ?? '')) ?: 'Sin nombre'; @endphp
                        <option value="{{ $customer->id }}" {{ $service->customer_id == $customer->id ? 'selected' : '' }}>{{ $fullName }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label for="internal_technician_id">Técnico interno</label>
                <select name="internal_technician_id" id="internal_technician_id">
                    <option value="">—</option>
                    @foreach ($technicians as $technician)
                        <option value="{{ $technician->id }}" {{ $service->internal_technician_id == $technician->id ? 'selected' : '' }}>{{ $technician->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label for="status">Estado</label>
                <select name="status" id="status">
                    @foreach ($statuses as $status)
                        <option value="{{ $status }}" {{ $service->status === $status ? 'selected' : '' }}>{{ ucfirst(str_replace('_', ' ', $status)) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label for="tipo_equipo">Tipo de equipo</label>
                <input type="text" name="tipo_equipo" id="tipo_equipo" value="{{ $equipment?->type_text }}">
            </div>
            <div class="form-group">
                <label for="subtipo">Subtipo</label>
                <input type="text" name="subtipo" id="subtipo" value="{{ $equipment?->subtype_text }}">
            </div>
            <div class="form-group">
                <label for="marca">Marca</label>
                <input type="text" name="marca" id="marca" value="{{ $equipment?->brand_text }}">
            </div>
            <div class="form-group">
                <label for="modelo">Modelo</label>
                <input type="text" name="modelo" id="modelo" value="{{ $equipment?->model_text }}">
            </div>
            <div class="form-group">
                <label for="serie">Número de serie</label>
                <input type="text" name="serie" id="serie" value="{{ $equipment?->serial_number }}">
            </div>
            <div class="form-group" style="grid-column:1/-1;">
                <label for="descripcion_equipo">Descripción del equipo</label>
                <textarea name="descripcion_equipo" id="descripcion_equipo" rows="3">{{ $equipment?->description }}</textarea>
            </div>
            <div class="form-group" style="grid-column:1/-1;">
                <label for="observaciones">Observaciones</label>
                <textarea name="observaciones" id="observaciones" rows="3">{{ $equipment?->observations }}</textarea>
            </div>
        </div>

        <div style="margin-top:24px;">
            <button type="submit" class="btn">Guardar cambios</button>
        </div>
    </form>
</x-ui.card>
@endsection