@extends('layouts.dashboard')
@section('title', 'Editar paquete')
@section('page-title', 'Editar paquete')
@section('page-sub', $paquete->nombre)

@push('head')
    <link rel="stylesheet" href="{{ asset('css/paquete-form.css') }}?v={{ filemtime(public_path('css/paquete-form.css')) }}">
@endpush

@section('content')
    @include('structure.gestion_Inventario.paquetes._formulario', [
        'accion' => route('inventory.paquetes.update', $paquete),
        'metodo' => 'PUT',
        'boton' => 'Guardar cambios',
        'paquete' => $paquete,
        'productos' => $productos,
        'seleccionados' => $seleccionados,
    ])
@endsection
