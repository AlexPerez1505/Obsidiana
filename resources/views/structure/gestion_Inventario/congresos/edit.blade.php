@extends('layouts.dashboard')

@section('title', 'Editar congreso')
@section('page-title', 'Editar congreso')
@section('page-sub', 'Gestión de Inventario > Congresos > Editar')

@section('content')
    @include('structure.gestion_Inventario.congresos._form', [
        'congress' => $congress,
        'accion' => route('inventory.congresos.update', $congress),
        'metodo' => 'PUT',
    ])
@endsection
