@extends('structure.Configuracion.layout')

@section('title', 'Editar Congreso')
@section('page-title', 'Editar Congreso')

@section('configuracion_content')
    @include('structure.Configuracion.Catalogos.Congresos._form', [
        'congress' => $congress,
        'accion' => route('configuracion.congresos.update', $congress),
        'metodo' => 'PUT',
    ])
@endsection
