@extends('layouts.dashboard')
@section('title', 'Nuevo paquete')
@section('page-title', 'Nuevo paquete')
@section('page-sub', 'Junta varios productos del inventario en un paquete')

@push('head')
    <link rel="stylesheet" href="{{ asset('css/paquete-form.css') }}?v={{ filemtime(public_path('css/paquete-form.css')) }}">
@endpush

@section('content')
    @include('structure.gestion_Inventario.paquetes._formulario', [
        'accion' => route('inventory.paquetes.store'),
        'metodo' => 'POST',
        'boton' => 'Guardar paquete',
        'paquete' => null,
        'productos' => $productos,
        'seleccionados' => $seleccionados,
        'mensajePrecarga' => $preseleccionados->isNotEmpty()
            ? "Ya están agregados los {$preseleccionados->count()} producto(s) que marcaste. Ajusta las cantidades a la derecha."
            : null,
    ])
@endsection
