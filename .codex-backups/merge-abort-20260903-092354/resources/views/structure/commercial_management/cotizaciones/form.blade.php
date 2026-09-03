@extends('structure.commercial_management.erp')

@section('title', $cotizacion ? 'Editar cotización' : 'Nueva cotización')

@php
    $esEdicion = (bool) $cotizacion;
    $accion = $esEdicion
        ? route('commercial.cotizaciones.update', $cotizacion)
        : route('commercial.cotizaciones.store');

    $initial = \App\Support\DocumentoInitial::build($cotizacion, $clientePre);
@endphp

@section('erp_content')
    @include('structure.commercial_management._cotizador_form', [
        'accion' => $accion,
        'metodo' => $esEdicion ? 'PUT' : 'POST',
        'initial' => $initial,
        'rClientes' => route('commercial.cotizaciones.clientes.buscar'),
        'rProductos' => route('commercial.cotizaciones.productos.buscar'),
        'rFichas' => route('commercial.cotizaciones.fichas.buscar'),
        'backRoute' => route('commercial.cotizaciones.index'),
        'titulo' => $esEdicion ? 'Editar cotización '.$cotizacion->folio : 'Nueva cotización',
        'subtitulo' => 'Equipo médico · individual o en paquete',
        'textoGuardar' => 'Guardar cotización',
    ])
@endsection
