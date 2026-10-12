@extends('layouts.dashboard')

@section('title', 'Flota')
@section('page-title', 'Flota y mantenimiento')
@section('page-sub', 'Kilometraje, combustible y servicios de cada vehículo')

@section('content')
    @if ($conAlertas)
        <x-ui.alert type="err" style="margin-bottom:14px;">{{ $conAlertas }} vehículo(s) con mantenimiento por atender.</x-ui.alert>
    @endif

    <div class="card" style="padding:0; overflow-x:auto;">
        <table class="fl-tabla">
            <thead>
                <tr><th>Vehículo</th><th>Odómetro</th><th>Km del mes</th><th>Rendimiento</th><th>Gasto del mes</th><th>Último servicio</th><th>Avisos</th><th></th></tr>
            </thead>
            <tbody>
                @forelse ($filas as $f)
                    @php $v = $f['vehiculo']; @endphp
                    <tr>
                        <td><b>{{ $v->plate_number }}</b><div class="fl-s">{{ trim($v->brand.' '.$v->model.' '.$v->year) }}</div></td>
                        <td>{{ $f['odometro'] !== null ? number_format($f['odometro']).' km' : '—' }}</td>
                        <td>{{ $f['km_mes'] ? number_format($f['km_mes'], 1).' km' : '—' }}</td>
                        <td>{{ $f['rendimiento'] ? $f['rendimiento'].' km/l' : '—' }}</td>
                        <td>${{ number_format($f['gasto_mes'], 2) }}</td>
                        <td>@if ($f['ultimo_mant']){{ $f['ultimo_mant']->tipoLabel() }}<div class="fl-s">{{ $f['ultimo_mant']->fecha->format('d/m/Y') }}</div>@else —@endif</td>
                        <td>
                            @forelse ($f['alertas'] as $a)<div class="fl-alerta"><x-ui.icon name="alert" :size="14" /> {{ $a }}</div>@empty<span class="fl-s">Al día</span>@endforelse
                        </td>
                        <td style="text-align:right;"><a href="{{ route('logistica.flota.show', $v) }}" class="btn btn--ghost" style="padding:6px 12px;">Abrir</a></td>
                    </tr>
                @empty
                    <tr><td colspan="8"><div class="empty-state"><h3>Sin vehículos</h3><p>Registra vehículos en Administración, sección Vehículos.</p></div></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <style>
        .fl-tabla { width:100%; border-collapse:collapse; font-size:13.5px; }
        .fl-tabla th { padding:11px 14px; background:var(--surface-2); color:var(--muted); font-size:11.5px; font-weight:600; letter-spacing:.02em; text-transform:uppercase; text-align:left; }
        .fl-tabla td { padding:12px 14px; border-bottom:1px solid var(--border); vertical-align:middle; }
        .fl-tabla tr:last-child td { border-bottom:0; }
        .fl-s { font-size:12px; color:var(--muted); margin-top:1px; }
        .fl-alerta { color:var(--accent); font-weight:600; font-size:12.5px; }
    </style>
@endsection
