@extends('layouts.dashboard')
@section('title', 'Nuevo Plan de Pago')
@section('page-title', 'Nuevo Plan de Pago')
@section('page-sub', 'Crea una plantilla reutilizable de plan de pago (ej. Mensual, 4 pagos, 5 pagos)')

@section('content')
    <form method="POST" action="{{ route('commercial.planesPago.store') }}" style="max-width:640px;">
        @csrf
        <x-ui.card style="margin-bottom:18px;">
            <x-ui.section-title style="margin:0 0 16px;">Datos del Plan de Pago</x-ui.section-title>
            <x-ui.form-group label="Nombre *" name="nombre" placeholder="Ej. Plan Mensual, 4 pagos, 5 pagos" :required="true" />
            <div class="rgrid-2">
                <x-ui.form-group label="No. de pagos *" name="numero_pagos" type="number" min="1" max="60" value="1" :required="true" />
                <x-ui.form-group label="Días entre pagos *" name="dias_entre_pagos" type="number" min="1" value="30" :required="true" />
            </div>
            <x-ui.form-group for="metodo_pago" label="Método de pago por defecto *">
                <select id="metodo_pago" name="metodo_pago" required style="width:100%; padding:11px 12px; border:1px solid var(--border); border-radius:9px; font-size:15px; background:var(--surface); color:var(--text);">
                    <option value="Efectivo">Efectivo</option>
                    <option value="Transferencia">Transferencia</option>
                    <option value="Tarjeta">Tarjeta</option>
                    <option value="Cheque">Cheque</option>
                </select>
            </x-ui.form-group>
            <x-ui.form-group label="Descripción" name="descripcion" placeholder="Opcional" />
        </x-ui.card>

        <div style="display:flex; gap:10px;">
            <x-ui.button>Guardar Plan de Pago</x-ui.button>
            <a href="{{ route('commercial.planesPago.index') }}" class="btn btn--ghost" style="text-decoration:none;">Cancelar</a>
        </div>
    </form>
@endsection
