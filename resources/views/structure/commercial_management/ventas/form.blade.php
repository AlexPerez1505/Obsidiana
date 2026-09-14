@extends('structure.commercial_management.erp')

@section('title', $venta ? 'Editar venta' : 'Nueva venta')

@php
    $esEdicion = (bool) $venta;
    if ($esEdicion) {
        $accion = route('commercial.ventas.update', $venta);
    } else {
        $accion = $origenId
            ? route('commercial.ventas.store', ['cotizacion' => $origenId])
            : route('commercial.ventas.store');
    }
@endphp

@section('erp_content')
    @include('structure.commercial_management._cotizador_form', [
        'accion' => $accion,
        'metodo' => $esEdicion ? 'PUT' : 'POST',
        'initial' => $initial,
        'rClientes' => route('commercial.cotizaciones.clientes.buscar'),
        'rProductos' => route('commercial.cotizaciones.productos.buscar'),
        'rFichas' => route('commercial.cotizaciones.fichas.buscar'),
        'backRoute' => route('commercial.ventas.index'),
        'titulo' => $esEdicion ? 'Editar venta '.$venta->folio : ($origenId ? 'Nueva venta (desde cotización)' : 'Nueva venta'),
        'subtitulo' => ($planBloqueado ?? false)
            ? 'Esta venta ya tiene cobros aplicados: puedes cambiar equipo y montos, pero el plan de pagos se conserva y la diferencia se reparte entre lo que falta por cobrar.'
            : 'Puedes modificar productos, montos y plan de pagos antes de guardar.',
        'textoGuardar' => 'Guardar venta',
        'avisosStock' => $avisosStock ?? [],
        'planBloqueado' => $planBloqueado ?? false,
    ])
@endsection
