@extends('structure.Configuracion.layout')

@section('title', 'Crear Congreso')
@section('page-title', 'Nuevo Congreso')

@section('configuracion_content')
    @include('structure.Configuracion.Catalogos.Congresos._form', [
        'congress' => new \App\Models\Congress(),
        'accion' => route('configuracion.congresos.store'),
        'metodo' => 'POST',
    ])
@endsection
