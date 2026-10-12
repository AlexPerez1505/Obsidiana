@extends('layouts.dashboard')

@section('title', $empleado->name)
@section('page-title', $empleado->name)
@section('page-sub', 'Asistencia de '.$mes->translatedFormat('F Y').' · '.($empleado->checador_id ?: 'sin número de checador'))

@section('content')
    @include('asistencia._tabs')

    <div class="rh-card rh-pad rh-in ae-barra" style="--i:1">
        <a class="btn btn--ghost" href="{{ route('asistencia.panel') }}"><x-ui.icon name="arrow-left" /> Panel</a>
        <div class="ae-mes">
            <a class="btn btn--ghost" href="{{ route('asistencia.empleado', [$empleado, 'mes' => $mes->copy()->subMonth()->format('Y-m')]) }}" aria-label="Mes anterior"><x-ui.icon name="chevron-left" /></a>
            <b>{{ $mes->translatedFormat('F Y') }}</b>
            <a class="btn btn--ghost" href="{{ route('asistencia.empleado', [$empleado, 'mes' => $mes->copy()->addMonth()->format('Y-m')]) }}" aria-label="Mes siguiente"><x-ui.icon name="chevron-right" /></a>
        </div>
        <span class="rh-sub ae-hor">
            @if ($empleado->horario)Horario {{ $empleado->horario->resumen() }} · tolerancia {{ $empleado->horario->tolerance_minutes }} min
            @else Sin horario asignado @endif
            · <x-ui.icon name="fingerprint" :size="14" /> {{ $empleado->huellas->where("status", "active")->count() }}
        </span>
    </div>

    <div class="rh-kpis" style="margin:16px 0;">
        <div class="rh-kpi ok rh-in" style="--i:2"><div class="rh-kpi-v" data-count="{{ $resumen['puntuales'] }}">{{ $resumen['puntuales'] }}</div><div class="rh-kpi-k">Puntuales</div></div>
        <div class="rh-kpi warn rh-in" style="--i:3"><div class="rh-kpi-v" data-count="{{ $resumen['retardos'] }}">{{ $resumen['retardos'] }}</div><div class="rh-kpi-k">Retardos</div></div>
        <div class="rh-kpi mal rh-in" style="--i:4"><div class="rh-kpi-v" data-count="{{ $resumen['faltas'] }}">{{ $resumen['faltas'] }}</div><div class="rh-kpi-k">Faltas</div></div>
        <div class="rh-kpi info rh-in" style="--i:5"><div class="rh-kpi-v" data-count="{{ $resumen['justificadas'] }}">{{ $resumen['justificadas'] }}</div><div class="rh-kpi-k">Justificadas</div></div>
        <div class="rh-kpi rh-in" style="--i:6"><div class="rh-kpi-v" data-count="{{ $resumen['horas'] }}" data-dec="1" data-suffix=" h">{{ $resumen['horas'] }} h</div><div class="rh-kpi-k">Horas trabajadas</div></div>
    </div>

    <div class="rh-card rh-in" style="--i:7; overflow:hidden;">
        <table class="rh-t">
            <thead><tr><th>Día</th><th>Checadas</th><th>Entrada</th><th>Salida</th><th>Trabajado</th><th>Estado</th></tr></thead>
            <tbody>
                @foreach ($dias as $d)
                    @php $f = $d['fila']; $fecha = $d['fecha']; @endphp
                    <tr class="{{ ! $d['esperado'] ? 'ae-desc' : '' }} {{ $fecha->isToday() ? 'ae-hoy' : '' }}">
                        <td data-label="Día"><b>{{ $fecha->translatedFormat('D d') }}</b>@if ($d['festivo'])<span class="rh-sub"> · festivo</span>@endif</td>
                        <td data-label="Checadas" class="ae-ch">
                            @forelse ($d['checadas'] as $p)
                                <span class="{{ $p->voided_at ? 'anul' : '' }}" title="{{ $p->kindLabel() }}{{ $p->method === 'manual' ? ' · manual: '.$p->note : '' }}{{ $p->voided_at ? ' · ANULADA: '.$p->void_reason : '' }}">{{ $p->horaLocal()->format('H:i') }}{{ $p->method === "manual" ? "*" : "" }}</span>
                            @empty
                                <span class="rh-sub">—</span>
                            @endforelse
                        </td>
                        <td data-label="Entrada">{{ $f && $f->check_in ? substr($f->check_in, 0, 5) : '—' }}</td>
                        <td data-label="Salida">@if ($f && $f->check_out){{ substr($f->check_out, 0, 5) }}@elseif ($f && $f->incomplete)<span class="rh-sub">abierta</span>@else —@endif</td>
                        <td data-label="Trabajado">{{ $f && $f->worked_minutes ? intdiv($f->worked_minutes, 60).':'.str_pad($f->worked_minutes % 60, 2, '0', STR_PAD_LEFT) : '—' }}</td>
                        <td data-label="Estado">
                            @if ($f)
                                <span class="rh-chip {{ ['puntual' => 'ok', 'retardo' => 'warn', 'falta' => 'bad', 'justificada' => 'info'][$f->status] ?? '' }}">{{ $f->statusLabel() }}{{ $f->status === 'retardo' ? ' +'.$f->late_minutes.' min' : '' }}</span>
                                @if ($d['modalidad'])<span class="rh-sub"> · {{ $d['modalidad']['label'] }}</span>@endif
                            @elseif ($d['modalidad'])
                                <span class="rh-chip info">{{ $d['modalidad']['label'] }}</span>
                            @elseif (! $d['esperado'])
                                <span class="rh-sub">Descanso</span>
                            @else
                                <span class="rh-sub">{{ $fecha->isFuture() ? 'Por venir' : 'Sin cerrar' }}</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <p class="rh-sub" style="margin-top:10px;">* checada manual · las checadas anuladas aparecen tachadas y conservan su motivo.</p>

    <style>
        .ae-barra { display:flex; flex-wrap:wrap; align-items:center; gap:12px 18px; }
        .ae-mes { display:flex; align-items:center; gap:10px; } .ae-mes b { min-width:130px; text-align:center; text-transform:capitalize; }
        .ae-hor { margin-left:auto; }
        .rh-t tr.ae-desc td { background:var(--surface-2); color:var(--muted); }
        .rh-t tr.ae-hoy td { background:var(--surface-2); }
        .ae-ch span { display:inline-block; margin-right:8px; font-variant-numeric:tabular-nums; }
        .ae-ch span.anul { text-decoration:line-through; color:var(--danger); }
        @media (max-width: 720px) { .ae-hor { margin-left:0; flex-basis:100%; } .ae-mes { flex:1; justify-content:space-between; } }
    </style>
@endsection
