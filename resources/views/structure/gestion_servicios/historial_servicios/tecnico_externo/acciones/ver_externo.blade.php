@extends('structure.gestion_servicios.layout')

@section('title', 'Servicio externo ' . ($service->service_number ?? ''))

@section('service_content')
    <div class="card" style="margin-bottom:18px;">
        <a href="{{ route('gestion.servicios.historial') }}" class="btn btn--ghost" style="display:inline-flex; align-items:center; gap:8px;">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
            Atrás
        </a>
    </div>
    <div class="card">
        @include('structure.gestion_servicios.historial_servicios.tecnico_externo.acciones.r_ext_ver_historial', ['service' => $service, 'modo_ver' => true])
    </div>
@endsection
