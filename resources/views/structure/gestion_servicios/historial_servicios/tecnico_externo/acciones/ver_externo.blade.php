@extends('structure.gestion_servicios.layout')

@section('title', 'Servicio externo ' . ($service->service_number ?? ''))

@section('service_content')
    @include('structure.gestion_servicios.historial_servicios.tecnico_externo.acciones.r_ext_ver_historial', ['service' => $service, 'showSave' => true])
@endsection
