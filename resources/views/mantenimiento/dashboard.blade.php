@extends('layouts.dashboard')

@section('title', 'Mantenimiento')
@section('page-title', 'Mantenimiento')
@section('page-sub', 'Resumen de Órdenes de servicio')

@push('head')
    <style>
        .mn-stats { display:grid; grid-template-columns:repeat(auto-fit,minmax(190px,1fr)); gap:16px; margin-bottom:24px; }
        .mn-stat { background:var(--surface); border:1px solid var(--border); border-radius:14px; padding:20px; display:flex; align-items:center; gap:14px; }
        .mn-stat .ic { width:48px; height:48px; border-radius:12px; display:flex; align-items:center; justify-content:center; flex:0 0 auto; }
        .mn-stat .ic svg { width:24px; height:24px; }
        .mn-stat .ic.blue { background:var(--primary-soft); color:var(--primary); }
        .mn-stat .ic.amber { background:var(--accent-soft); color:var(--accent); }
        .mn-stat .ic.green { background:var(--green-soft); color:var(--green); }
        .mn-stat .ic.danger { background:var(--danger-soft); color:var(--danger); }
        .mn-stat .n { font-size:26px; font-weight:800; line-height:1; }
        .mn-stat .l { color:var(--muted); font-size:13px; margin-top:3px; }

        .mn-badge { display:inline-block; padding:3px 10px; border-radius:999px; font-size:12px; font-weight:600; }
        .mn-badge.warn { background:var(--accent-soft); color:var(--accent); }
        .mn-badge.info { background:var(--primary-soft); color:var(--primary); }
        .mn-badge.ok { background:var(--green-soft); color:var(--green); }
        .mn-badge.danger { background:var(--danger-soft); color:var(--danger); }
        .mn-badge.neutral { background:var(--surface-2); color:var(--muted); }
    </style>
@endpush

@section('content')
    <div class="content-actions">
        <a href="{{ route('gestion.servicios.historial') }}" class="btn btn--ghost">Historial de servicios</a>
        <a href="{{ route('gestion.servicios.registro') }}" class="btn">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="15" height="15"><path d="M12 5v14M5 12h14"/></svg>
            Nueva orden de servicio
        </a>
    </div>

    <div class="mn-stats">
        <div class="mn-stat">
            <span class="ic blue"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg></span>
            <div><div class="n">{{ $stats['total'] }}</div><div class="l">Total de órdenes</div></div>
        </div>
        <div class="mn-stat">
            <span class="ic amber"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.1 2.1 0 0 1 3 3L12 15l-4 1 1-4z"/></svg></span>
            <div><div class="n">{{ $stats['registrados'] }}</div><div class="l">Registradas</div></div>
        </div>
        <div class="mn-stat">
            <span class="ic blue"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="9"/><polyline points="12 7 12 12 15.5 14"/></svg></span>
            <div><div class="n">{{ $stats['en_progreso'] }}</div><div class="l">En progreso</div></div>
        </div>
        <div class="mn-stat">
            <span class="ic green"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><polyline points="20 6 9 17 4 12"/></svg></span>
            <div><div class="n">{{ $stats['entregados'] }}</div><div class="l">Entregadas</div></div>
        </div>
        <div class="mn-stat">
            <span class="ic danger"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg></span>
            <div><div class="n">{{ $stats['cancelados'] }}</div><div class="l">Canceladas</div></div>
        </div>
    </div>

    <x-ui.card>
        <x-ui.section-title style="margin:0 0 14px;">Últimas órdenes de servicio</x-ui.section-title>

        @if ($ultimas->isEmpty())
            <div class="empty-state">
                <p>Todavía no hay órdenes de servicio registradas.</p>
                <a href="{{ route('gestion.servicios.registro') }}" class="btn">Crear la primera</a>
            </div>
        @else
            <table>
                <thead>
                    <tr>
                        <th>Orden</th>
                        <th>Cliente</th>
                        <th>Estado</th>
                        <th>Fecha</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($ultimas as $fila)
                        @php
                            $badge = match ($fila['estado']) {
                                'entregado' => 'ok',
                                'en_progreso' => 'info',
                                'cancelado' => 'danger',
                                'registrado' => 'warn',
                                default => 'neutral',
                            };
                        @endphp
                        <tr>
                            <td>{{ $fila['os'] }}</td>
                            <td>{{ $fila['cliente'] }}</td>
                            <td><span class="mn-badge {{ $badge }}">{{ ucfirst(str_replace('_', ' ', $fila['estado'])) }}</span></td>
                            <td>{{ $fila['fecha'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </x-ui.card>
@endsection
