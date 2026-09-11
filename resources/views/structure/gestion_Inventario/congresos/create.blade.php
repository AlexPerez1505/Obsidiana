@extends('layouts.dashboard')

@section('title', 'Nuevo congreso')
@section('page-title', 'Nuevo congreso')
@section('page-sub', 'Gestión de Inventario > Congresos > Crear')

@section('content')
    @include('structure.gestion_Inventario.congresos._form', [
        'congress' => new \App\Models\Congress(),
        'accion' => route('inventory.congresos.store'),
        'metodo' => 'POST',
    ])
@endsection
