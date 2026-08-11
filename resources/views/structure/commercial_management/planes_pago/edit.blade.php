@extends('layouts.dashboard')
@section('title', 'Editar Plan de Pago')
@section('page-title', 'Editar Plan de Pago')
@section('page-sub', 'Actualiza los datos de esta plantilla de plan de pago')

@section('content')
    <form method="POST" action="{{ route('commercial.planesPago.update', $plan) }}" style="max-width:640px;">
        @csrf
        @method('PUT')
        <x-ui.card style="margin-bottom:18px;">
            <x-ui.section-title style="margin:0 0 16px;">Datos del Plan de Pago</x-ui.section-title>
            <x-ui.form-group label="Nombre *" name="nombre" :value="$plan->nombre" placeholder="Ej. Plan Mensual, 4 pagos, 5 pagos" :required="true" />
            <div class="rgrid-2">
                <x-ui.form-group label="No. de pagos *" name="numero_pagos" type="number" min="1" max="60" :value="$plan->numero_pagos" :required="true" />
                <x-ui.form-group label="Días entre pagos *" name="dias_entre_pagos" type="number" min="1" :value="$plan->dias_entre_pagos" :required="true" />
            </div>
            <x-ui.form-group for="metodo_pago" label="Método de pago por defecto *">
                <select id="metodo_pago" name="metodo_pago" required style="width:100%; padding:11px 12px; border:1px solid var(--border); border-radius:9px; font-size:15px; background:var(--surface); color:var(--text);">
                    @foreach(['Efectivo', 'Transferencia', 'Tarjeta', 'Cheque'] as $metodo)
                        <option value="{{ $metodo }}" {{ old('metodo_pago', $plan->metodo_pago) === $metodo ? 'selected' : '' }}>{{ $metodo }}</option>
                    @endforeach
                </select>
            </x-ui.form-group>
            <x-ui.form-group label="Descripción" name="descripcion" :value="$plan->descripcion" placeholder="Opcional" />
        </x-ui.card>

        <div style="display:flex; gap:10px;">
            <x-ui.button>Actualizar Plan de Pago</x-ui.button>
            <a href="{{ route('commercial.planesPago.index') }}" class="btn btn--ghost" style="text-decoration:none;">Cancelar</a>
        </div>
    </form>
@endsection
