@extends('layouts.dashboard')

@section('title', 'Eventos de empresa')
@section('page-title', 'Asistencia')
@section('page-sub', 'Vacaciones del equipo, comidas o salidas sin checar y días con horario especial')

@section('content')
    @include('asistencia._tabs')

    <div class="ev-tipos">
        <div class="rh-card rh-pad rh-in ev-tipo" style="--i:1"><span class="rh-ico-box"><x-ui.icon name="sun" :size="18" /></span><div><b>Descanso / vacaciones del equipo</b><span>No se espera a nadie: sin falta ni retardo. Cuenta como vacaciones en la nómina.</span></div></div>
        <div class="rh-card rh-pad rh-in ev-tipo" style="--i:2"><span class="rh-ico-box"><x-ui.icon name="utensils" :size="18" /></span><div><b>Día sin checar</b><span>Comida, evento o salida del equipo. Se trabaja pero no se pide huella: cuenta como asistencia y no hay retardo.</span></div></div>
        <div class="rh-card rh-pad rh-in ev-tipo" style="--i:3"><span class="rh-ico-box"><x-ui.icon name="clock" :size="18" /></span><div><b>Horario especial</b><span>Ese día entran y salen a otra hora (ej. 24 de diciembre de 9 a 14), incluso un día que normalmente no se trabaja.</span></div></div>
    </div>

    <div class="rh-actions rh-in" style="--i:4; margin:16px 0 12px;">
        <button type="button" class="btn" data-rh-toggle="ev-nuevo"><x-ui.icon name="plus" /> Nuevo evento</button>
        <span class="rh-sub">Los días que ya pasaron se corrigen solos al registrarlo.</span>
    </div>

    <div class="rh-acc rh-in {{ $errors->any() ? 'open' : '' }}" id="ev-nuevo" style="--i:5; margin-bottom:6px;">
        <div class="rh-acc-b"><div class="rh-acc-i"><div class="rh-acc-c">
            <form method="POST" action="{{ route('asistencia.eventos.store') }}" class="rh-form">@csrf
                <div class="rh-grid">
                    <label style="grid-column:span 2;">Nombre <input type="text" name="name" value="{{ old('name') }}" placeholder="Vacaciones de diciembre, Comida de fin de año…" required></label>
                    <label>Tipo
                        <select name="type" id="ev-tipo" required>
                            @foreach ($tipos as $k => $t)<option value="{{ $k }}" @selected(old('type') === $k)>{{ $t }}</option>@endforeach
                        </select>
                    </label>
                    <label>Desde <input type="date" name="date_from" value="{{ old('date_from', today()->toDateString()) }}" required></label>
                    <label>Hasta <input type="date" name="date_to" value="{{ old('date_to', today()->toDateString()) }}" required></label>
                </div>

                <div class="rh-grid" id="ev-horas" style="display:none; margin-top:12px;">
                    <label>Entrada <input type="time" name="time_in" value="{{ old('time_in') }}"></label>
                    <label>Salida <input type="time" name="time_out" value="{{ old('time_out') }}"></label>
                    <label>Tolerancia (min) <input type="number" name="tolerance_minutes" min="0" max="60" value="{{ old('tolerance_minutes') }}" placeholder="la de cada quien"></label>
                </div>

                <div class="ev-quien">
                    <span class="ev-t">Aplica a</span>
                    <label class="ev-r"><input type="radio" name="scope" value="todos" @checked(old('scope', 'todos') === 'todos')> Todo el equipo</label>
                    <label class="ev-r"><input type="radio" name="scope" value="seleccion" @checked(old('scope') === 'seleccion')> Solo algunas personas</label>
                </div>
                <div class="ev-gente" id="ev-gente" style="display:none;">
                    @foreach ($empleados as $e)
                        <label><input type="checkbox" name="user_ids[]" value="{{ $e->id }}" @checked(in_array($e->id, old('user_ids', [])))> {{ $e->name }}</label>
                    @endforeach
                </div>

                <label style="display:block; margin-top:12px;">Nota (opcional) <input type="text" name="note" value="{{ old('note') }}"></label>
                <div class="rh-actions" style="margin-top:14px;"><button class="btn" type="submit">Registrar evento</button></div>
            </form>
        </div></div></div>
    </div>

    <h3 class="ev-h3">Próximos y en curso</h3>
    <div class="rh-card rh-in" style="--i:6; overflow:hidden;">
        @forelse ($proximos as $e)
            @include('asistencia._evento_fila', ['e' => $e, 'empleados' => $empleados])
        @empty
            <div class="rh-empty"><b>No hay eventos próximos</b>Registra vacaciones del equipo, comidas o días con horario especial.</div>
        @endforelse
    </div>

    @if ($pasados->count())
        <div class="rh-acc rh-in" id="ev-pasados" style="--i:7; margin-top:16px;">
            <div class="rh-acc-h"><div class="rh-acc-main"><div class="rh-acc-t">Eventos pasados</div><div class="rh-acc-s">Los últimos {{ $pasados->count() }}</div></div></div>
            <div class="rh-acc-b"><div class="rh-acc-i"><div class="rh-acc-c" style="padding:0;">
                @foreach ($pasados as $e)
                    @include('asistencia._evento_fila', ['e' => $e, 'empleados' => $empleados])
                @endforeach
            </div></div></div>
        </div>
    @endif

    <p class="rh-sub" style="margin-top:14px;">Los días festivos oficiales (que no generan falta para nadie) se manejan en <a href="{{ route('asistencia.horarios') }}">Horarios y festivos</a>.</p>

    <style>
        .ev-tipos { display:grid; grid-template-columns:repeat(auto-fit, minmax(250px,1fr)); gap:12px; }
        .ev-tipo { display:flex; gap:12px; align-items:flex-start; }
        .ev-tipo b { display:block; font-size:14px; }
        .ev-tipo div span { display:block; margin-top:4px; color:var(--muted); font-size:13px; line-height:1.5; }
        .ev-quien { display:flex; flex-wrap:wrap; gap:18px; align-items:center; margin-top:16px; }
        .ev-t { font-size:12px; font-weight:700; color:var(--muted); text-transform:uppercase; }
        .rh-form .ev-r { display:flex; gap:7px; align-items:center; color:var(--text); font-weight:500; cursor:pointer; margin:0; } .ev-r input { margin:0; width:auto; }
        .ev-gente { display:grid; grid-template-columns:repeat(auto-fill, minmax(210px,1fr)); gap:6px 14px; margin-top:10px; padding:12px; border:1px solid var(--border); border-radius:8px; background:var(--surface-2); max-height:220px; overflow:auto; animation:rh-in .25s both; }
        .rh-form .ev-gente label { display:flex; gap:7px; align-items:center; color:var(--text); font-weight:500; font-size:13px; cursor:pointer; } .ev-gente input { margin:0; width:auto; }
        .ev-h3 { margin:24px 0 10px; font-size:15px; }
    </style>
@endsection

@push('scripts')
<script>
    (function () {
        var tipo = document.getElementById('ev-tipo'), horas = document.getElementById('ev-horas'), gente = document.getElementById('ev-gente');
        function f() {
            horas.style.display = tipo.value === 'horario_especial' ? '' : 'none';
            var sel = document.querySelector('input[name=scope]:checked');
            gente.style.display = sel && sel.value === 'seleccion' ? '' : 'none';
        }
        tipo.addEventListener('change', f);
        document.querySelectorAll('input[name=scope]').forEach(function (r) { r.addEventListener('change', f); });
        f();
    })();
</script>
@endpush
