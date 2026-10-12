@extends('layouts.dashboard')

@section('title', 'Empleados y sueldos')
@section('page-title', 'Nómina')
@section('page-sub', 'Captura el sueldo una sola vez: cada quincena se calcula sola')

@section('content')
    @include('nomina._tabs')

    @php
        $mon = fn ($n) => '$'.number_format($n, 2);
        $iniciales = fn ($n) => collect(explode(' ', trim($n)))->filter()->take(2)->map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)))->implode('');
    @endphp

    <div class="rh-note rh-in" style="--i:1; margin-bottom:14px;">
        <x-ui.icon name="info" :size="18" />
        <div>
            El <b>sueldo quincenal</b> y los <b>conceptos fijos</b> (bono de transporte, préstamo…) se guardan aquí una vez y se aplican cada quincena.
            Los bonos y descuentos que dependen de la asistencia (retardos, faltas, puntualidad…) los defines en <a href="{{ route('nomina.reglas') }}">Reglas</a>.
            Lo que solo pasa una vez se agrega desde el desglose de la quincena.
        </div>
    </div>

    @if ($sinSueldo)
        <x-ui.alert type="default" style="margin-bottom:14px;"><b>{{ $sinSueldo }} persona(s) sin sueldo.</b> Ábrelas y captura su sueldo quincenal.</x-ui.alert>
    @endif

    <div class="nq-barra rh-in" style="--i:2; display:flex; flex-wrap:wrap; gap:10px; margin-bottom:12px;">
        <input type="search" id="ne-buscar" placeholder="Buscar persona…" style="flex:1; min-width:200px;" aria-label="Buscar persona">
    </div>

    <div class="rh-stack-gap" id="ne-lista">
        @foreach ($usuarios as $i => $u)
            @php $cs = $conceptos->get($u->id, collect()); $abierta = session('abrir') === 'u'.$u->id; @endphp
            <div class="rh-acc rh-in {{ $abierta ? 'open' : '' }}" id="u{{ $u->id }}" data-nombre="{{ mb_strtolower($u->name) }}" style="--i:{{ min($i, 12) + 3 }}">
                <div class="rh-acc-h">
                    <span class="ne-av">{{ $iniciales($u->name) }}</span>
                    <div class="rh-acc-main">
                        <div class="rh-acc-t">{{ $u->name }}</div>
                        <div class="rh-acc-s">{{ $u->position ?: $u->cargo ?: 'Sin puesto' }} · {{ $u->checador_id ?: 'sin no. de checador' }}
                            @unless ($conHorario->has($u->id))<span class="rh-chip warn">Sin horario</span>@endunless</div>
                    </div>
                    <div class="rh-acc-side">
                        @if ($cs->count())<span class="rh-chip info">{{ $cs->count() }} concepto(s)</span>@endif
                        @if ($u->sueldo_quincenal !== null)<b>${{ number_format($u->sueldo_quincenal, 2) }}</b><span class="rh-sub">/ quincena</span>
                        @else<span class="rh-chip bad">Sin sueldo</span>@endif
                    </div>
                </div>
                <div class="rh-acc-b"><div class="rh-acc-i"><div class="rh-acc-c">
                    <form method="POST" action="{{ route('nomina.sueldo', $u) }}" class="rh-form ne-sueldo">@csrf @method('PUT')
                        <label>Sueldo quincenal
                            <input type="number" step="0.01" min="0" name="sueldo_quincenal" value="{{ $u->sueldo_quincenal }}" placeholder="Ej. 6000">
                        </label>
                        <button class="btn" type="submit">Guardar sueldo</button>
                        @if ($u->sueldo_quincenal)<span class="rh-sub">Mensual aprox. {{ $mon($u->sueldo_quincenal * 2) }} · un día = {{ $mon($u->sueldo_quincenal / \App\Models\PayrollSetting::diasSueldo()) }}</span>@endif
                    </form>

                    <h4 class="ne-h">Bonos y descuentos fijos</h4>
                    @forelse ($cs as $c)
                        <div class="ne-con {{ $c->esBono() ? 'ok' : 'bad' }}">
                            <span><b><x-ui.icon :name="$c->esBono() ? 'plus' : 'minus'" :size="14" /> {{ $c->name }}</b><small>{{ $c->vigenciaTexto() }}{{ $c->note ? ' · '.$c->note : '' }}</small></span>
                            <b>{{ $c->esBono() ? '+' : '−' }}{{ $mon($c->amount) }}</b>
                            <form method="POST" action="{{ route('nomina.conceptos.destroy', $c) }}" data-confirm="Se deja de aplicar en las próximas quincenas. Las cerradas no cambian." data-confirm-title="Quitar concepto">@csrf @method('DELETE')
                                <button class="ne-x" type="submit" title="Quitar" aria-label="Quitar {{ $c->name }}"><x-ui.icon name="x" /></button>
                            </form>
                        </div>
                    @empty
                        <p class="rh-sub" style="margin:4px 0 10px;">Sin conceptos fijos.</p>
                    @endforelse

                    <button type="button" class="btn btn--ghost" data-rh-toggle="nc{{ $u->id }}" style="margin-top:8px;"><x-ui.icon name="plus" /> Agregar bono o descuento fijo</button>
                    <div class="rh-acc plain" id="nc{{ $u->id }}" style="margin-top:12px;">
                        <div class="rh-acc-b"><div class="rh-acc-i"><div class="rh-acc-c">
                            <form method="POST" action="{{ route('nomina.conceptos.store', $u) }}" class="rh-form">@csrf
                                <div class="rh-grid">
                                    <label>Tipo <select name="kind"><option value="bono">Bono</option><option value="descuento">Descuento</option></select></label>
                                    <label>Nombre <input type="text" name="name" placeholder="Transporte, préstamo, despensa…" required></label>
                                    <label>Monto por quincena $ <input type="number" step="0.01" min="0.01" name="amount" required></label>
                                    <label>Desde (opcional) <input type="date" name="valid_from"></label>
                                    <label>Hasta (opcional) <input type="date" name="valid_until"></label>
                                    <label>Nota (opcional) <input type="text" name="note"></label>
                                </div>
                                <div class="rh-actions" style="margin-top:12px;"><button class="btn" type="submit">Agregar</button><span class="rh-sub">Se aplica cada quincena dentro de las fechas (sin fechas = siempre).</span></div>
                            </form>
                        </div></div></div>
                    </div>
                </div></div></div>
            </div>
        @endforeach
    </div>

    <style>
        .ne-av { flex:0 0 36px; height:36px; border-radius:50%; background:var(--primary-soft); color:var(--primary); display:flex; align-items:center; justify-content:center; font-weight:700; font-size:13px; }
        .ne-sueldo { display:flex; flex-wrap:wrap; gap:12px; align-items:flex-end; } .ne-sueldo label { min-width:220px; }
        .ne-h { margin:20px 0 8px; font-size:13px; text-transform:uppercase; letter-spacing:.03em; color:var(--muted); }
        .ne-con { display:grid; grid-template-columns:1fr auto auto; gap:12px; align-items:center; padding:10px 12px; border:1px solid var(--border); border-radius:8px; margin-bottom:8px; background:var(--surface-2); }
        .ne-con small { display:block; color:var(--muted); font-size:12px; font-weight:400; margin-top:2px; } .ne-con form { margin:0; }
        .ne-con.ok > b { color:var(--green); } .ne-con.bad > b { color:var(--danger); }
        .ne-x { border:0; background:none; color:var(--muted); cursor:pointer; font-size:15px; padding:4px 6px; border-radius:6px; transition:color .15s, background .15s; } .ne-x:hover { color:var(--danger); background:var(--danger-soft); }
    </style>
@endsection

@push('scripts')
<script>
    (function () {
        var b = document.getElementById('ne-buscar'), cards = document.querySelectorAll('#ne-lista .rh-acc[data-nombre]');
        b.addEventListener('input', function () { var q = b.value.trim().toLowerCase(); cards.forEach(function (c) { c.style.display = !q || c.dataset.nombre.indexOf(q) !== -1 ? '' : 'none'; }); });
    })();
</script>
@endpush
