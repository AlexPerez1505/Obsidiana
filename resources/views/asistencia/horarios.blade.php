@extends('layouts.dashboard')

@section('title', 'Horarios y festivos')
@section('page-title', 'Asistencia')
@section('page-sub', 'Quién debe checar, a qué hora cada día y con cuánta tolerancia')

@section('content')
    @include('asistencia._tabs')

    @php $sinHorario = $usuarios->filter(fn ($u) => ! $u->horario)->count(); @endphp

    <div class="rh-note rh-in" style="--i:1;">
        <x-ui.icon name="info" :size="18" />
        <div>
            Cada día tiene su propia entrada y salida: por ejemplo lunes a viernes de 9 a 18 y <b>sábado de 9 a 10</b>.
            Para vacaciones de todo el equipo, una comida donde no se checa o un día con horario distinto usa
            <a href="{{ route('asistencia.eventos') }}">Eventos de empresa</a>. Quien no tiene horario no genera faltas ni retardos.
        </div>
    </div>

    {{-- ============ Aplicar a varios ============ --}}
    <div class="rh-acc rh-in {{ $errors->any() ? 'open' : '' }}" id="masivo" style="--i:2; margin-top:14px;" data-rh-open-on-error>
        <div class="rh-acc-h">
            <span class="rh-ico-box"><x-ui.icon name="users" :size="18" /></span>
            <div class="rh-acc-main"><div class="rh-acc-t">Aplicar un horario a varias personas</div><div class="rh-acc-s">Arma el horario una vez y elige a quién va</div></div>
        </div>
        <div class="rh-acc-b"><div class="rh-acc-i"><div class="rh-acc-c">
            <form method="POST" action="{{ route('asistencia.horarios.masivo') }}" id="form-masivo" class="rh-form">@csrf
                @include('asistencia._horario_grid', ['h' => null, 'base' => $base])
                <div class="hr-pie">
                    <label>Tolerancia (min) <input type="number" name="tolerance_minutes" min="0" max="60" value="{{ $base['tolerancia_min'] }}" required style="width:100px;"></label>
                    <label class="hr-op"><input type="checkbox" name="active" value="1" checked> Toma asistencia</label>
                    <label class="hr-op"><input type="radio" name="alcance" value="seleccion" checked> Solo a las personas marcadas abajo</label>
                    <label class="hr-op"><input type="radio" name="alcance" value="todos"> A todos</label>
                    <button class="btn" type="submit" style="margin-left:auto;">Aplicar horario</button>
                </div>
            </form>
        </div></div></div>
    </div>

    @if ($sinHorario)
        <div class="hr-barra rh-in" style="--i:3">
            <span class="rh-chip warn">{{ $sinHorario }} sin horario</span>
            <form method="POST" action="{{ route('asistencia.horarios.base') }}" data-confirm="Se crea el horario base ({{ $base['time_in'] }}–{{ $base['time_out'] }}, lunes a viernes) para las {{ $sinHorario }} persona(s) que aún no tienen." data-confirm-title="Aplicar horario base" data-confirm-ok="Aplicar" style="margin:0;">@csrf
                <button class="btn btn--ghost" type="submit">Aplicar horario base a los {{ $sinHorario }}</button>
            </form>
        </div>
    @endif

    {{-- ============ Por persona ============ --}}
    <div class="hr-bus rh-in" style="--i:4"><input type="search" id="hr-buscar" placeholder="Buscar persona…" aria-label="Buscar persona"></div>
    <div class="rh-stack-gap" id="hr-lista">
        @foreach ($usuarios as $i => $u)
            @php $h = $u->horario; @endphp
            <div class="rh-acc rh-in" id="h{{ $u->id }}" data-nombre="{{ mb_strtolower($u->name) }}" style="--i:{{ min($i, 10) + 5 }}">
                <div class="rh-acc-h">
                    <label class="hr-sel rh-noacc" title="Marcar para aplicar el horario de arriba"><input type="checkbox" name="user_ids[]" value="{{ $u->id }}" form="form-masivo" aria-label="Marcar a {{ $u->name }}"></label>
                    <div class="rh-acc-main">
                        <div class="rh-acc-t">{{ $u->name }}</div>
                        <div class="rh-acc-s">{{ $h ? $h->resumen() : 'Sin horario asignado' }}</div>
                    </div>
                    <div class="rh-acc-side">
                        @if (! $h)<span class="rh-chip warn">Sin horario</span>
                        @elseif (! $h->active)<span class="rh-chip">Pausado</span>
                        @else<span class="rh-chip ok">Activo</span>@endif
                        <span class="rh-chip plain" title="Huellas registradas"><x-ui.icon name="fingerprint" :size="14" /> {{ $u->huellas->where('status', 'active')->count() }}</span>
                    </div>
                </div>
                <div class="rh-acc-b"><div class="rh-acc-i"><div class="rh-acc-c">
                    <form method="POST" action="{{ route('asistencia.horarios.guardar', $u) }}" class="rh-form">@csrf @method('PUT')
                        @include('asistencia._horario_grid', ['h' => $h, 'base' => $base])
                        <div class="hr-pie">
                            <label>No. de checador <input type="text" name="checador_id" value="{{ $u->checador_id }}" placeholder="EMP-0024" style="width:140px;"></label>
                            <label>Tolerancia (min) <input type="number" name="tolerance_minutes" min="0" max="60" value="{{ $h?->tolerance_minutes ?? $base['tolerancia_min'] }}" required style="width:100px;"></label>
                            <label class="hr-op"><input type="checkbox" name="active" value="1" @checked($h ? $h->active : true)> Toma asistencia</label>
                            <button class="btn" type="submit" style="margin-left:auto;">Guardar horario</button>
                        </div>
                    </form>
                </div></div></div>
            </div>
        @endforeach
    </div>

    {{-- ============ Festivos ============ --}}
    <div class="rh-acc rh-in" id="festivos" style="--i:6; margin-top:18px;">
        <div class="rh-acc-h">
            <span class="rh-ico-box"><x-ui.icon name="flag" :size="18" /></span>
            <div class="rh-acc-main"><div class="rh-acc-t">Días festivos</div><div class="rh-acc-s">No generan falta y aparecen en la <a href="{{ route('admin.agenda.index') }}">Agenda</a></div></div>
            <div class="rh-acc-side"><span class="rh-chip plain">{{ $festivos->count() }} este año</span></div>
        </div>
        <div class="rh-acc-b"><div class="rh-acc-i"><div class="rh-acc-c">
            <form method="POST" action="{{ route('asistencia.festivos.oficiales') }}" class="rh-form hr-fest hr-ofi">@csrf
                <label>Cargar festivos oficiales de México del año <input type="number" name="anio" value="{{ now()->year }}" min="2020" max="2100" style="width:110px;"></label>
                <button class="btn btn--ghost" type="submit">Cargar festivos oficiales</button>
                <span class="rh-sub">Año Nuevo, Constitución, Juárez, Día del Trabajo, Independencia, Revolución y Navidad (y 1 de octubre cuando cambia el Ejecutivo). No duplica.</span>
            </form>
            <form method="POST" action="{{ route('asistencia.festivos.store') }}" class="rh-form hr-fest">@csrf
                <label>Fecha <input type="date" name="date" required></label>
                <label style="flex:1; min-width:200px;">Nombre <input type="text" name="name" placeholder="Día de Muertos" required></label>
                <button class="btn" type="submit">Agregar festivo</button>
            </form>
            <div style="margin-top:12px;">
                @forelse ($festivos as $f)
                    <div class="hr-festitem">
                        <span><b>{{ $f->date->translatedFormat('D d M Y') }}</b> · {{ $f->name }}</span>
                        <form method="POST" action="{{ route('asistencia.festivos.destroy', $f) }}" data-confirm="Ese día volverá a ser laborable." data-confirm-title="Quitar festivo">@csrf @method('DELETE')<button class="rh-x" type="submit" aria-label="Quitar {{ $f->name }}"><x-ui.icon name="x" /></button></form>
                    </div>
                @empty
                    <div class="rh-sub">Sin festivos registrados este año.</div>
                @endforelse
            </div>
        </div></div></div>
    </div>

    <style>
        .hr-barra { display:flex; flex-wrap:wrap; align-items:center; gap:12px; margin:14px 0 0; }
        .hr-bus { margin:16px 0 12px; } .hr-bus input { width:100%; }
        .hr-pie { display:flex; flex-wrap:wrap; gap:14px; align-items:flex-end; margin-top:14px; }
        .hr-op { display:flex !important; align-items:center; gap:7px; color:var(--text) !important; font-weight:500 !important; font-size:13px !important; padding-bottom:9px; cursor:pointer; }
        .hr-op input { margin:0; width:auto; }
        .hr-sel { display:flex; cursor:pointer; padding:4px; } .hr-sel input { width:18px; height:18px; margin:0; cursor:pointer; }
        .hr-fest { display:flex; flex-wrap:wrap; gap:12px; align-items:flex-end; margin-bottom:14px; }
        .hr-ofi { padding-bottom:14px; border-bottom:1px solid var(--border); }
        .hr-festitem { display:flex; justify-content:space-between; align-items:center; padding:9px 0; border-top:1px solid var(--border); font-size:13.5px; }
        .hr-festitem form { margin:0; }        /* Rejilla de horario por día */
        .hg { display:grid; grid-template-columns:repeat(auto-fill, minmax(124px, 1fr)); gap:8px; }
        .hg-d { display:flex; flex-direction:column; gap:6px; padding:9px; border:1px solid var(--border); border-radius:11px; background:var(--surface-2); opacity:.55; transition:opacity .2s, border-color .2s, transform .2s, background .2s; }
        .hg-d.on { opacity:1; border-color:var(--muted); background:var(--surface); }
        .hg-t { display:flex; align-items:center; gap:7px; cursor:pointer; font-size:13px !important; font-weight:700 !important; color:var(--text) !important; }
        .hg-t input { margin:0; width:auto; }
        .hg-d input[type=time] { width:100%; padding:5px 6px; font-size:13px; margin:0; }
    </style>
@endsection

@push('scripts')
<script>
    document.querySelectorAll('.hg-d input[type=checkbox]').forEach(function (c) {
        c.addEventListener('change', function () { c.closest('.hg-d').classList.toggle('on', c.checked); });
    });
    var hb = document.getElementById('hr-buscar');
    hb.addEventListener('input', function () {
        var q = hb.value.trim().toLowerCase();
        document.querySelectorAll('#hr-lista .rh-acc[data-nombre]').forEach(function (c) { c.style.display = !q || c.dataset.nombre.indexOf(q) !== -1 ? '' : 'none'; });
    });
</script>
@endpush
