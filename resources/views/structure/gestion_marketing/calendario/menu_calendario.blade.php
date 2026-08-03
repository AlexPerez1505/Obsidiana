@extends('layouts.dashboard')

@section('title', 'Calendario de contenido')
@section('page-title', 'Calendario de contenido')
@section('page-sub', 'El plan del mes por categoría y responsable. Cada pieza pasa por el tablero de aprobación antes de publicarse.')

@section('content')
<style>
    .pe-header { margin-bottom: 22px; }
    .pe-tag { display:inline-block; padding:6px 12px; border-radius:20px; background:var(--green-soft); color:var(--green); font-size:11px; font-weight:800; letter-spacing:0.06em; text-transform:uppercase; }
    .pe-title { margin:12px 0 6px; font-size:32px; font-weight:800; letter-spacing:-0.02em; }
    .pe-sub { color:var(--muted); font-size:15px; line-height:1.55; max-width:760px; margin:0; }

    .pe-toolbar { display:flex; align-items:center; justify-content:space-between; gap:16px; flex-wrap:wrap; margin:24px 0 18px; }
    .pe-month { display:flex; align-items:center; gap:10px; }
    .pe-arrow, .pe-today { display:inline-flex; align-items:center; justify-content:center; border:1px solid var(--border); background:var(--surface); color:var(--text); border-radius:10px; padding:8px 12px; font-weight:700; font-size:14px; text-decoration:none; transition:background .15s; }
    .pe-arrow:hover, .pe-today:hover { background:var(--surface-2); }
    .pe-arrow svg { width:18px; height:18px; }
    .pe-label { font-size:16px; font-weight:700; min-width:120px; text-align:center; }

    .pe-filters { display:flex; flex-direction:column; gap:12px; margin-bottom:18px; }
    .pe-filter-row { display:flex; flex-wrap:wrap; align-items:center; gap:10px; }
    .pe-pill { display:inline-flex; align-items:center; gap:8px; padding:6px 12px; border-radius:999px; border:1px solid var(--border); background:var(--surface); color:var(--text); font-size:13px; font-weight:600; cursor:pointer; user-select:none; transition:background .15s; }
    .pe-pill:hover { background:var(--surface-2); }
    .pe-pill input { display:none; }
    .pe-dot { width:10px; height:10px; border-radius:50%; flex:0 0 auto; }
    .pe-avatar { width:20px; height:20px; border-radius:50%; display:flex; align-items:center; justify-content:center; color:#fff; font-size:10px; font-weight:800; flex:0 0 auto; }

    .calendar-wrap { border:1px solid var(--border); border-radius:18px; overflow:hidden; background:var(--surface); box-shadow:var(--shadow); }
    .calendar { display:grid; grid-template-columns:repeat(7, 1fr); min-width:720px; gap:1px; background:var(--border); }
    .cal-head { background:var(--primary); color:#fff; text-align:center; padding:12px 6px; font-size:12px; font-weight:700; letter-spacing:0.06em; }
    .cal-cell { min-height:110px; padding:10px; position:relative; background:var(--surface); transition:background .15s; }
    .cal-cell.out { background:var(--surface-2); opacity:0.6; }
    .cal-cell.today { box-shadow:inset 0 0 0 2px var(--primary); }
    .cal-date { font-size:14px; font-weight:700; color:var(--text); }
    .cal-empty { position:absolute; inset:0; display:flex; align-items:center; justify-content:center; color:var(--muted); font-size:18px; font-weight:700; }
    .cal-hoy { position:absolute; top:8px; right:8px; padding:3px 8px; border-radius:999px; background:var(--green); color:#fff; font-size:10px; font-weight:800; letter-spacing:0.04em; }

    @media (max-width:1024px) {
        .pe-title { font-size:26px; }
        .calendar-wrap { overflow-x:auto; border-radius:14px; }
    }
</style>

@php
$year  = (int) request('year', now()->year);
$month = (int) request('month', now()->month);
$current = now()->setDate($year, $month, 1)->startOfDay();
$today = now()->startOfDay();
$monthNames = ['enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre'];
$dayLabels = ['LUN','MAR','MIÉ','JUE','VIE','SÁB','DOM'];

$startOfMonth = $current->copy()->startOfMonth();
$endOfMonth = $current->copy()->endOfMonth();
$startOfCalendar = $startOfMonth->copy()->subDays(($startOfMonth->dayOfWeek + 6) % 7);
$endOfCalendar = $endOfMonth->copy()->addDays((7 - $endOfMonth->dayOfWeek) % 7);

$weeks = [];
$cursor = $startOfCalendar->copy();
while ($cursor->lte($endOfCalendar)) {
    $week = [];
    for ($i = 0; $i < 7; $i++) {
        $week[] = $cursor->copy();
        $cursor->addDay();
    }
    $weeks[] = $week;
}

$prev = $current->copy()->subMonthNoOverflow();
$next = $current->copy()->addMonthNoOverflow();

$categories = [
    ['Educación', '#3b82f6'],
    ['Equipos', '#ef4444'],
    ['Aplicaciones', '#f97316'],
    ['Antes/después congreso', '#22c55e'],
    ['Congreso', '#a855f7'],
    ['Conversación', '#ec4899'],
    ['Fechas especiales', '#06b6d4'],
    ['Promo', '#f59e0b'],
    ['Publicación', '#0ea5e9'],
    ['Revisión', '#eab308'],
];

$responsables = [
    ['Víctor', '#007aff', 'V'],
    ['Megan', '#22c55e', 'M'],
    ['Jennifer', '#a855f7', 'J'],
    ['Vídeo', '#f59e0b', '<svg viewBox="0 0 24 24" fill="currentColor" width="9" height="9" style="display:block;"><polygon points="5 3 19 12 5 21 5 3"/></svg>'],
];
@endphp

<div class="pe-header">
    <span class="pe-tag">PLAN EDITORIAL - {{ strtoupper($monthNames[$current->month - 1]) }} {{ $current->year }}</span>
    <h1 class="pe-title">Calendario de contenido</h1>
    <p class="pe-sub">El plan del mes por categoría y responsable. Cada pieza pasa por el tablero de aprobación antes de publicarse.</p>
</div>

<div class="pe-toolbar">
    <div class="pe-month">
        <a href="{{ route('marketing.calendario.index', ['month' => $prev->month, 'year' => $prev->year]) }}" class="pe-arrow" aria-label="Mes anterior">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
        </a>
        <span class="pe-label">{{ ucfirst($monthNames[$current->month - 1]) }} {{ $current->year }}</span>
        <a href="{{ route('marketing.calendario.index', ['month' => $next->month, 'year' => $next->year]) }}" class="pe-arrow" aria-label="Mes siguiente">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
        </a>
        <a href="{{ route('marketing.calendario.index') }}" class="pe-today">Hoy</a>
    </div>
</div>

<div class="pe-filters">
    <div class="pe-filter-row" id="category-filters">
        @foreach ($categories as $c)
            <label class="pe-pill">
                <input type="checkbox" value="{{ $c[0] }}">
                <span class="pe-dot" style="background-color:{{ $c[1] }};"></span>
                {{ $c[0] }}
            </label>
        @endforeach
    </div>

    <div class="pe-filter-row" id="responsable-filters">
        @if (isset($users) && $users->isNotEmpty())
            @php $userColors = ['#007aff','#22c55e','#a855f7','#f59e0b','#ef4444','#06b6d4']; @endphp
            @foreach ($users as $i => $user)
                <label class="pe-pill">
                    <input type="checkbox" value="{{ $user->id }}">
                    <span class="pe-avatar" style="background-color:{{ $userColors[$i % count($userColors)] }};">
                        {{ strtoupper(substr($user->name, 0, 1)) }}
                    </span>
                    {{ $user->name }}
                </label>
            @endforeach
        @else
            @foreach ($responsables as $r)
                <label class="pe-pill">
                    <input type="checkbox" value="{{ $r[0] }}">
                    <span class="pe-avatar" style="background-color:{{ $r[1] }};">
                        {!! $r[2] !!}
                    </span>
                    {{ $r[0] }}
                </label>
            @endforeach
        @endif
    </div>
</div>

<div class="calendar-wrap">
    <div class="calendar">
        @foreach ($dayLabels as $label)
            <div class="cal-head">{{ $label }}</div>
        @endforeach

        @foreach ($weeks as $week)
            @foreach ($week as $day)
                <div class="cal-cell {{ $day->month !== $current->month ? 'out' : '' }} {{ $day->isSameDay($today) ? 'today' : '' }}">
                    @if ($day->month === $current->month)
                        <div class="cal-date">{{ $day->day }}</div>
                        @if ($day->isSameDay($today))
                            <span class="cal-hoy">HOY</span>
                        @endif
                        <div class="cal-empty">—</div>
                    @endif
                </div>
            @endforeach
        @endforeach
    </div>
</div>
@endsection
