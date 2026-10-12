@extends('layouts.dashboard')

@section('title', 'Historial de nómina')
@section('page-title', 'Nómina')
@section('page-sub', 'Quincenas cerradas: sus montos quedan congelados aunque cambien las reglas')

@section('content')
    @include('nomina._tabs')

    <div class="rh-card rh-in" style="--i:1; overflow:hidden;">
        <table class="rh-t">
            <thead><tr><th>Quincena</th><th>Empleados</th><th>Sueldos</th><th>Bonos</th><th>Descuentos</th><th>Neto</th><th>Estado</th><th></th></tr></thead>
            <tbody>
                @forelse ($corridas as $c)
                    <tr>
                        <td data-label="Quincena"><b>{{ $c->period_start->format('d/m') }} – {{ $c->period_end->format('d/m/Y') }}</b>
                            <div class="rh-sub">{{ $c->closed_at?->setTimezone(config('asistencia.timezone'))->format('d/m/Y H:i') }}{{ $c->cerro ? ' · '.$c->cerro->name : '' }}</div></td>
                        <td data-label="Empleados">{{ $c->employees }}</td>
                        <td data-label="Sueldos">${{ number_format($c->total_sueldos, 2) }}</td>
                        <td data-label="Bonos" style="color:var(--green)">+${{ number_format($c->total_bonos, 2) }}</td>
                        <td data-label="Descuentos" style="color:var(--danger)">−${{ number_format($c->total_descuentos, 2) }}</td>
                        <td data-label="Neto"><b>${{ number_format($c->total_neto, 2) }}</b></td>
                        <td data-label="Estado"><span class="rh-chip {{ $c->status === 'pagada' ? 'ok' : 'info' }}">{{ $c->estadoLabel() }}</span></td>
                        <td data-label="" style="text-align:right; white-space:nowrap;">
                            <a class="btn btn--ghost" style="padding:5px 12px;" href="{{ route('nomina.index', ['desde' => $c->period_start->toDateString()]) }}">Ver</a>
                            <a class="btn btn--ghost" style="padding:5px 12px;" href="{{ route('nomina.exportar', ['desde' => $c->period_start->toDateString()]) }}">CSV</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8"><div class="rh-empty"><b>Aún no hay quincenas cerradas</b>Cuando termine una quincena, ciérrala desde la pestaña Quincena para congelar sus montos.</div></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
