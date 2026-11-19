@extends('layouts.dashboard')

@section('title', 'Alta de garantía')
@section('page-title', 'Alta manual de garantía')
@section('page-sub', 'Equipos vendidos antes del sistema, reparaciones o garantías extendidas')

@section('content')
    @include('structure.garantias._estilos')

    <x-ui.page-header title="Nueva garantía" :back="route('garantias.index')" />

    <div class="gt-aviso azul">
        <div>Las ventas registradas en el sistema generan su garantía solas. Usa esta alta solo para lo que no pasó por una venta.</div>
    </div>

    <form method="POST" action="{{ route('garantias.store') }}" class="card" style="padding:0;">
        @csrf
        <div class="gt-form">
            <div class="gt-form-grid">
                <x-ui.form-group label="Cliente *" for="customer_id">
                    <select id="customer_id" name="customer_id" required>
                        <option value="">— Elige —</option>
                        @foreach ($clientes as $c)
                            <option value="{{ $c->id }}" @selected(old('customer_id') == $c->id)>{{ trim($c->nombre.' '.$c->apellido) }}</option>
                        @endforeach
                    </select>
                </x-ui.form-group>
                <x-ui.form-group label="Equipo o componente *" name="descripcion" required placeholder="Gastroscopio, procesador de video…" />
                <x-ui.form-group label="Marca" name="marca" placeholder="Olympus, Stryker…" />
                <x-ui.form-group label="Modelo" name="modelo" placeholder="GIF-H190" />
                <x-ui.form-group label="Número de serie" name="no_serie" />
                <x-ui.form-group label="Condición *" for="condicion">
                    <select id="condicion" name="condicion">
                        @foreach (\App\Models\Garantia::CONDICIONES as $k => $t)<option value="{{ $k }}" @selected(old('condicion') === $k)>{{ $t }}</option>@endforeach
                    </select>
                </x-ui.form-group>
            </div>

            <div class="gt-form-grid" style="margin-top:6px; padding-top:6px; border-top:1px solid var(--border);">
                <x-ui.form-group label="Cobertura base" for="cobertura_id">
                    <select id="cobertura_id" name="cobertura_id">
                        <option value="">— Sin cobertura tipo —</option>
                        @foreach ($coberturas as $c)
                            <option value="{{ $c->id }}" data-tipo="{{ $c->tipo }}" data-meses="{{ $c->meses }}" @selected(old('cobertura_id') == $c->id)>{{ $c->nombre }} ({{ $c->meses }} meses)</option>
                        @endforeach
                    </select>
                </x-ui.form-group>
                <x-ui.form-group label="Tipo de garantía *" for="tipo">
                    <select id="tipo" name="tipo">
                        @foreach (\App\Models\Garantia::TIPOS as $k => $t)<option value="{{ $k }}" @selected(old('tipo') === $k)>{{ $t }}</option>@endforeach
                    </select>
                </x-ui.form-group>
                <x-ui.form-group label="Meses de cobertura *" for="meses">
                    <input type="number" id="meses" name="meses" min="1" max="120" value="{{ old('meses', 12) }}" required>
                </x-ui.form-group>
                <x-ui.form-group label="Inicio de cobertura *" for="inicio">
                    <input type="date" id="inicio" name="inicio" value="{{ old('inicio', today()->toDateString()) }}" required>
                </x-ui.form-group>
                <x-ui.form-group label="Técnico responsable" for="responsable_id">
                    <select id="responsable_id" name="responsable_id">
                        <option value="">— Nadie —</option>
                        @foreach ($responsables as $u)<option value="{{ $u->id }}" @selected(old('responsable_id') == $u->id)>{{ $u->name }}</option>@endforeach
                    </select>
                </x-ui.form-group>
                <x-ui.form-group label="Proveedor" name="proveedor" />
                <x-ui.form-group label="Precio de venta" for="precio_venta"><input type="number" step="0.01" min="0" id="precio_venta" name="precio_venta" value="{{ old('precio_venta') }}"></x-ui.form-group>
                <x-ui.form-group label="Costo de adquisición" for="costo_adquisicion"><input type="number" step="0.01" min="0" id="costo_adquisicion" name="costo_adquisicion" value="{{ old('costo_adquisicion') }}"></x-ui.form-group>
            </div>
            <x-ui.form-group label="Notas" for="notas"><textarea id="notas" name="notas" rows="2">{{ old('notas') }}</textarea></x-ui.form-group>
        </div>
        <div class="gt-acciones">
            <a href="{{ route('garantias.index') }}" class="btn btn--ghost">Cancelar</a>
            <button type="submit" class="btn">Registrar garantía</button>
        </div>
    </form>

    @push('scripts')
    <script>
    // Elegir una cobertura tipo propone su tipo y sus meses.
    document.getElementById('cobertura_id').addEventListener('change', function () {
        var op = this.selectedOptions[0];
        if (!op || !op.dataset.tipo) return;
        document.getElementById('tipo').value = op.dataset.tipo;
        document.getElementById('meses').value = op.dataset.meses;
    });
    </script>
    @endpush
@endsection
