@extends('layouts.dashboard')

@section('title', 'Incidencias del día')
@section('page-title', 'Asistencia')
@section('page-sub', 'Campo, home office, comisión, vacaciones, incapacidad y permisos: lo que justifica no checar')

@section('content')
    @include('asistencia._tabs')

    @can('asistencia.gestionar')
        <div class="rh-actions rh-in" style="--i:1; margin-bottom:12px;">
            <button type="button" class="btn" data-rh-toggle="md-nueva"><x-ui.icon name="plus" /> Registrar incidencia</button>
            <span class="rh-sub">Ese día no se genera falta. Si además checa, la checada cuenta normal.</span>
        </div>
        <div class="rh-acc rh-in {{ $errors->any() ? 'open' : '' }}" id="md-nueva" style="--i:2; margin-bottom:18px;">
            <div class="rh-acc-b"><div class="rh-acc-i"><div class="rh-acc-c">
                <form method="POST" action="{{ route('asistencia.modalidades.store') }}" class="rh-form">@csrf
                    <div class="rh-grid">
                        <label>Empleado
                            <select name="user_id" required><option value="">— Elige —</option>
                                @foreach ($empleados as $e)<option value="{{ $e->id }}" @selected(old('user_id') == $e->id)>{{ $e->name }}</option>@endforeach
                            </select>
                        </label>
                        <label>Tipo
                            <select name="type" id="md-tipo" required>
                                @foreach ($tipos as $k => $t)<option value="{{ $k }}" @selected(old('type') === $k)>{{ $t }}</option>@endforeach
                            </select>
                        </label>
                        <label>Desde <input type="date" name="date_from" value="{{ old('date_from', today()->toDateString()) }}" required></label>
                        <label>Hasta <input type="date" name="date_to" value="{{ old('date_to', today()->toDateString()) }}" required></label>
                    </div>
                    <div class="rh-grid" id="md-comision" style="margin-top:12px;">
                        <label>Destino <input type="text" name="destination" value="{{ old('destination') }}" placeholder="Hospital ABC"></label>
                        <label>Inicio <input type="time" name="time_start" value="{{ old('time_start') }}"></label>
                        <label>Fin estimado <input type="time" name="time_end" value="{{ old('time_end') }}"></label>
                        <label class="md-chk"><input type="checkbox" name="requires_fingerprint" value="1" @checked(old('requires_fingerprint'))> También checa huella ese día</label>
                    </div>
                    <label style="display:block; margin-top:12px;">Motivo <input type="text" name="reason" value="{{ old('reason') }}" placeholder="Instalación de torre laparoscópica…"></label>
                    <div class="rh-actions" style="margin-top:14px;"><button class="btn" type="submit">Registrar</button></div>
                </form>
            </div></div></div>
        </div>
    @endcan

    <form method="GET" class="rh-form md-filtro rh-in" style="--i:3">
        <label>Mes <input type="month" name="mes" value="{{ $mes->format('Y-m') }}" onchange="this.form.submit()"></label>
        <label>Empleado
            <select name="empleado" onchange="this.form.submit()"><option value="">Todos</option>
                @foreach ($empleados as $e)<option value="{{ $e->id }}" @selected($filtroEmpleado == $e->id)>{{ $e->name }}</option>@endforeach
            </select>
        </label>
    </form>

    <div class="rh-card rh-in" style="--i:4; overflow:hidden;">
        <table class="rh-t">
            <thead><tr><th>Empleado</th><th>Tipo</th><th>Periodo</th><th>Detalle</th><th></th></tr></thead>
            <tbody>
                @forelse ($lista as $m)
                    <tr>
                        <td data-label="Empleado"><b>{{ $m->user->name }}</b></td>
                        <td data-label="Tipo"><span class="rh-chip info">{{ $m->tipoLabel() }}</span></td>
                        <td data-label="Periodo">{{ $m->date_from->format('d/m/Y') }}@if (! $m->date_from->isSameDay($m->date_to)) al {{ $m->date_to->format('d/m/Y') }}@endif</td>
                        <td data-label="Detalle" class="rh-full rh-sub">
                            {{ $m->destination }} @if ($m->time_start){{ substr($m->time_start, 0, 5) }}–{{ $m->time_end ? substr($m->time_end, 0, 5) : '?' }}@endif
                            {{ $m->reason }} @if ($m->requires_fingerprint)· también checa @endif
                            <div>Registró {{ $m->creador?->name ?? '—' }}</div>
                        </td>
                        <td data-label="" style="text-align:right;">
                            @can('asistencia.gestionar')
                                <form method="POST" action="{{ route('asistencia.modalidades.destroy', $m) }}" data-confirm="Los días vuelven a evaluarse y pueden quedar como falta." data-confirm-title="Eliminar incidencia">@csrf @method('DELETE')
                                    <button class="btn btn--ghost" type="submit" style="padding:4px 10px;">Eliminar</button>
                                </form>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5"><div class="rh-empty"><b>Sin incidencias</b>No hay incidencias registradas en este mes.</div></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <style>
        .md-chk { display:flex !important; gap:8px; align-items:center; color:var(--text) !important; font-weight:500 !important; padding-bottom:9px; }
        .md-chk input { width:auto !important; margin:0 !important; }
        .md-filtro { display:flex; flex-wrap:wrap; gap:12px; margin-bottom:14px; } .md-filtro label { min-width:170px; }
    </style>
@endsection

@push('scripts')
<script>
    (function () {
        var t = document.getElementById('md-tipo'), c = document.getElementById('md-comision');
        if (!t) return;
        function f() { c.style.display = (t.value === 'comision' || t.value === 'campo') ? '' : 'none'; }
        t.addEventListener('change', f); f();
    })();
</script>
@endpush
