@extends('layouts.dashboard')

@section('title', 'Checadas')
@section('page-title', 'Asistencia')
@section('page-sub', 'Cada toque del lector, tal como se registró')

@section('content')
    @include('asistencia._tabs')

    <form method="GET" class="rh-form ck-filtro rh-in" style="--i:1">
        <label>Día <input type="date" name="fecha" value="{{ $dia->toDateString() }}" onchange="this.form.submit()"></label>
        <label>Empleado
            <select name="empleado" onchange="this.form.submit()"><option value="">Todos</option>
                @foreach ($empleados as $e)<option value="{{ $e->id }}" @selected($filtroEmpleado == $e->id)>{{ $e->name }}</option>@endforeach
            </select>
        </label>
        @if ($puedeGestionar)<button type="button" class="btn btn--ghost" data-rh-toggle="ck-manual" style="align-self:flex-end;"><x-ui.icon name="plus" /> Checada manual</button>@endif
    </form>

    @if ($puedeGestionar)
        <div class="rh-acc rh-in {{ $errors->any() ? 'open' : '' }}" id="ck-manual" style="--i:2; margin-bottom:16px;">
            <div class="rh-acc-b"><div class="rh-acc-i"><div class="rh-acc-c">
                <p class="rh-sub" style="margin:0 0 12px;">Para cuando alguien olvidó checar o falló el lector. Queda marcada como manual con tu nombre y el motivo; la checada real nunca se edita.</p>
                <form method="POST" action="{{ route('asistencia.checadas.manual') }}" class="rh-form">@csrf
                    <div class="rh-grid">
                        <label>Empleado
                            <select name="user_id" required>
                                <option value="">— Elige —</option>
                                @foreach ($empleados as $e)<option value="{{ $e->id }}" @selected(old('user_id') == $e->id)>{{ $e->name }}</option>@endforeach
                            </select>
                        </label>
                        <label>Fecha <input type="date" name="fecha" value="{{ old('fecha', $dia->lte(today()) ? $dia->toDateString() : today()->toDateString()) }}" max="{{ today()->toDateString() }}" required></label>
                        <label>Hora <input type="time" name="hora" value="{{ old('hora') }}" required></label>
                        <label>Motivo <input type="text" name="motivo" value="{{ old('motivo') }}" placeholder="Olvidó checar, lector sin energía…" required></label>
                    </div>
                    <div class="rh-actions" style="margin-top:14px;"><button class="btn" type="submit">Registrar checada</button></div>
                </form>
            </div></div></div>
        </div>
    @endif

    <div class="rh-card rh-in" style="--i:3; overflow:hidden;">
        <table class="rh-t">
            <thead><tr><th>Hora</th><th>Empleado</th><th>Tipo</th><th>Origen</th><th>Lector</th><th></th></tr></thead>
            <tbody>
                @forelse ($checadas as $p)
                    <tr class="{{ $p->voided_at ? 'anul' : '' }}">
                        <td data-label="Hora" class="ck-h">{{ $p->horaLocal()->format('H:i:s') }}</td>
                        <td data-label="Empleado"><a href="{{ route('asistencia.empleado', [$p->user, 'mes' => $dia->format('Y-m')]) }}">{{ $p->user->name }}</a></td>
                        <td data-label="Tipo">#{{ $p->seq }} · {{ $p->kindLabel() }}</td>
                        <td data-label="Origen">
                            @if ($p->method === 'manual')<span class="rh-chip warn">Manual</span> <span class="rh-sub">{{ $p->note }} · {{ $p->creadaPor?->name }}</span>
                            @else Huella{{ $p->confidence !== null ? ' · '.$p->confidence.'%' : '' }}@endif
                        </td>
                        <td data-label="Lector" class="rh-sub">{{ $p->device?->name ?? '—' }}</td>
                        <td data-label="" style="text-align:right;">
                            @if ($p->voided_at)
                                <span class="rh-sub">Anulada {{ $p->voided_at->setTimezone(config('asistencia.timezone'))->format('d/m H:i') }} por {{ $p->anuladaPor?->name }}: {{ $p->void_reason }}</span>
                            @elseif ($puedeGestionar)
                                <form method="POST" action="{{ route('asistencia.checadas.anular', $p) }}" onsubmit="return anular(this)" style="margin:0;">@csrf
                                    <input type="hidden" name="motivo">
                                    <button class="btn btn--ghost" type="submit" style="padding:4px 10px;">Anular</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6"><div class="rh-empty"><b>Sin checadas</b>No hay checadas en esta fecha con ese filtro.</div></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <style>
        .ck-filtro { display:flex; flex-wrap:wrap; gap:12px; margin-bottom:14px; } .ck-filtro label { min-width:170px; }
        .ck-h { font-variant-numeric:tabular-nums; font-weight:700; }
        .rh-t tr.anul td:not(:last-child) { text-decoration:line-through; color:var(--muted); }
    </style>
@endsection

@push('scripts')
<script>
    // Pide el motivo con una ventana del sistema y luego envía el formulario
    async function anular(form) {
        if (form.dataset.listo) return true;
        const texto = await window.pedirMotivo();
        if (!texto) return false;
        form.motivo.value = texto; form.dataset.listo = '1'; form.requestSubmit();
        return false;
    }
    window.pedirMotivo = function () {
        return new Promise(function (resolve) {
            var m = document.createElement('div');
            m.style.cssText = 'position:fixed;inset:0;background:rgba(0,0,0,.45);display:flex;align-items:center;justify-content:center;z-index:9999;padding:16px;animation:rh-in .2s both';
            m.innerHTML = '<div style="background:var(--surface);color:var(--text);border:1px solid var(--border);border-radius:10px;padding:20px;max-width:420px;width:100%"><h3 style="margin:0 0 6px">Anular checada</h3><p style="margin:0 0 12px;font-size:13px;color:var(--muted)">La checada no se borra: queda tachada con tu nombre y este motivo.</p><input type="text" id="mot" maxlength="255" placeholder="Motivo" style="width:100%"><div style="display:flex;gap:8px;justify-content:flex-end;margin-top:14px"><button type="button" class="btn btn--ghost" id="mc">Cancelar</button><button type="button" class="btn btn--danger" id="mo">Anular</button></div></div>';
            document.body.appendChild(m);
            var i = m.querySelector('#mot'); i.focus();
            function fin(v) { document.body.removeChild(m); resolve(v); }
            m.querySelector('#mc').onclick = function () { fin(null); };
            m.querySelector('#mo').onclick = function () { var v = i.value.trim(); if (v) fin(v); else i.focus(); };
            i.onkeydown = function (e) { if (e.key === 'Enter') m.querySelector('#mo').click(); if (e.key === 'Escape') fin(null); };
        });
    };
</script>
@endpush
