@extends('layouts.dashboard')

@section('title', 'Reglas de nómina')
@section('page-title', 'Nómina')
@section('page-sub', 'Tú decides cuándo se bonifica y cuándo se descuenta: sin tocar código')

@section('content')
    @include('nomina._tabs')

    <div class="rh-card rh-pad rh-in" style="--i:1; margin-bottom:14px;">
        <b class="rh-h">Reglas automáticas</b>
        <p class="rh-sub" style="margin:6px 0 0; line-height:1.55;">
            Cada regla dice: <i>«cuando pase esto, bonificar o descontar tanto»</i>. Se revisan solas en cada quincena con la asistencia de cada persona y el desglose explica cuál se aplicó y por qué.
            Ejemplos: <i>3 retardos en la quincena = 1 día de sueldo menos</i>, <i>sin retardos ni faltas en el mes = $300</i>, <i>por cada falta, 1 día de sueldo</i>.
        </p>
        <form method="POST" action="{{ route('nomina.ajustes') }}" class="rh-form nr-aj">@csrf
            <label>Un día de sueldo es el sueldo de la quincena ÷ <input type="number" name="dias_sueldo" min="1" max="31" value="{{ $diasSueldo }}" style="width:80px; display:inline-block; margin:0 6px;"> días</label>
            <button class="btn btn--ghost" type="submit">Guardar</button>
        </form>
    </div>

    <div class="rh-actions rh-in" style="--i:2; margin-bottom:14px;">
        <button type="button" class="btn" data-rh-toggle="regla-nueva"><x-ui.icon name="plus" /> Nueva regla</button>
        <span class="rh-sub">{{ $reglas->where('active', true)->count() }} activa(s) de {{ $reglas->count() }}</span>
    </div>

    <div class="rh-acc rh-in {{ $errors->any() && ! old('_edit') ? 'open' : '' }}" id="regla-nueva" data-rh-group="reglas" style="--i:3; margin-bottom:14px;">
        <div class="rh-acc-b"><div class="rh-acc-i"><div class="rh-acc-c">
            @include('nomina._regla_form', ['regla' => null, 'fid' => 'n', 'usuarios' => $usuarios, 'metricas' => $metricas, 'operadores' => $operadores, 'calculos' => $calculos])
        </div></div></div>
    </div>

    <div class="rh-stack-gap">
        @forelse ($reglas as $i => $r)
            <div class="rh-acc rh-in {{ (string) old('_edit') === (string) $r->id ? 'open' : '' }}" id="regla-{{ $r->id }}" data-rh-group="reglas" style="--i:{{ min($i, 10) + 4 }}">
                <div class="rh-acc-h">
                    <span class="rh-ico-box {{ $r->esBono() ? 'ok' : 'bad' }}"><x-ui.icon :name="$r->esBono() ? 'plus' : 'minus'" :size="18" /></span>
                    <div class="rh-acc-main">
                        <div class="rh-acc-t">{{ $r->name }}</div>
                        <div class="rh-acc-s">{{ $r->resumen() }}</div>
                    </div>
                    <div class="rh-acc-side">
                        <span class="rh-chip {{ $r->esBono() ? 'ok' : 'bad' }}">{{ $r->esBono() ? 'Bono' : 'Descuento' }}</span>
                        <form method="POST" action="{{ route('nomina.reglas.alternar', $r) }}" class="rh-noacc" title="{{ $r->active ? 'Desactivar' : 'Activar' }}">@csrf
                            <span class="rh-switch"><input type="checkbox" {{ $r->active ? 'checked' : '' }} onchange="this.form.submit()" aria-label="Regla activa"><span></span></span>
                        </form>
                    </div>
                </div>
                <div class="rh-acc-b"><div class="rh-acc-i"><div class="rh-acc-c">
                    @if ($r->description)<p class="rh-sub" style="margin:0 0 12px;">{{ $r->description }}</p>@endif
                    @include('nomina._regla_form', ['regla' => $r, 'fid' => $r->id, 'usuarios' => $usuarios, 'metricas' => $metricas, 'operadores' => $operadores, 'calculos' => $calculos])
                    <form method="POST" action="{{ route('nomina.reglas.destroy', $r) }}" data-confirm="Las quincenas cerradas conservan lo que ya se aplicó." data-confirm-title="Eliminar regla" style="margin-top:12px;">@csrf @method('DELETE')
                        <button class="btn btn--ghost" type="submit" style="color:var(--danger);">Eliminar regla</button>
                    </form>
                </div></div></div>
            </div>
        @empty
            <div class="rh-card rh-empty"><b>Sin reglas</b>Crea la primera con «Nueva regla».</div>
        @endforelse
    </div>

    <template id="rh-cond-tpl">
        <div class="nr-cond">
            <select data-c="metric" name="">@foreach ($metricas as $k => $m)<option value="{{ $k }}">{{ $m[0] }}</option>@endforeach</select>
            <select data-c="scope"><option value="periodo">en la quincena</option><option value="mes">en el mes</option></select>
            <select data-c="op">@foreach ($operadores as $k => $t)<option value="{{ $k }}">{{ $t }}</option>@endforeach</select>
            <input type="number" step="0.01" min="0" data-c="value" placeholder="valor">
            <button type="button" class="ne-x" data-del-cond title="Quitar condición" aria-label="Quitar condición"><x-ui.icon name="x" /></button>
        </div>
    </template>

    <style>
        .nr-aj { display:flex; flex-wrap:wrap; gap:10px; align-items:center; margin-top:12px; } .nr-aj label { display:flex; align-items:center; flex-wrap:wrap; color:var(--text); font-weight:500; font-size:13px; }
        .nr-paso { display:flex; align-items:center; gap:10px; margin:22px 0 10px; } .nr-n { width:22px; height:22px; border-radius:50%; background:var(--surface-2); border:1px solid var(--border); color:var(--text); display:flex; align-items:center; justify-content:center; font-size:12px; font-weight:700; }
        .nr-cond { display:grid; grid-template-columns:minmax(150px,1.4fr) minmax(130px,1fr) minmax(120px,1fr) 100px auto; gap:8px; align-items:center; margin-bottom:8px; padding:10px; border:1px solid var(--border); border-radius:8px; background:var(--surface-2); animation:rh-in .25s both; }
        .nr-cond select, .nr-cond input { margin:0; width:100%; }
        .nr-rep { display:flex; flex-wrap:wrap; gap:8px 22px; align-items:flex-start; }
        .nr-op { display:flex; align-items:center; gap:8px; color:var(--text) !important; font-weight:500 !important; font-size:13px !important; cursor:pointer; } .nr-op input { margin:0; width:auto; }
        .nr-rep-box { flex-basis:100%; display:none; flex-wrap:wrap; gap:12px; padding:12px; border:1px dashed var(--border); border-radius:10px; }
        .nr-rep-box.on { display:flex; animation:rh-in .3s both; } .nr-rep-box label { min-width:120px; }
        .nr-gente { display:grid; grid-template-columns:repeat(auto-fill, minmax(210px,1fr)); gap:6px 14px; margin-top:10px; padding:12px; border:1px solid var(--border); border-radius:10px; background:var(--surface-2); max-height:220px; overflow:auto; }
        .nr-gente label { display:flex; gap:7px; align-items:center; color:var(--text) !important; font-weight:500 !important; font-size:13px !important; cursor:pointer; } .nr-gente input { margin:0; width:auto; }
        .nr-prev { margin-top:18px; padding:12px 14px; border-radius:8px; background:var(--surface-2); color:var(--text); font-size:13.5px; line-height:1.55; }
        .nr-act { display:flex !important; align-items:center; gap:10px; color:var(--text) !important; font-weight:600 !important; font-size:13px !important; }
        @media (max-width: 720px) { .nr-cond { grid-template-columns:1fr 1fr; } .nr-cond [data-c=value] { grid-column:span 1; } }
    </style>
@endsection

@push('scripts')
<script>
(function () {
    var METRICAS = @json(collect($metricas)->map(fn ($m) => $m[0]));
    var UNID = @json(collect($metricas)->map(fn ($m) => $m[1]));
    var OPS = @json($operadores);
    var tpl = document.getElementById('rh-cond-tpl');

    function iniciar(form) {
        var box = form.querySelector('[data-conds-box]');
        var q = function (s) { return form.querySelector(s); };
        var conds = []; try { conds = JSON.parse(form.dataset.conds || '[]'); } catch (e) {}
        if (!conds.length) conds = [{ metric: 'retardos', scope: 'periodo', op: '>=', value: 1 }];

        function renumerar() {
            box.querySelectorAll('.nr-cond').forEach(function (row, i) {
                row.querySelectorAll('[data-c]').forEach(function (el) { el.name = 'conditions[' + i + '][' + el.dataset.c + ']'; });
            });
            form.querySelector('[data-add-cond]').style.display = box.children.length >= 5 ? 'none' : '';
            box.querySelectorAll('[data-del-cond]').forEach(function (b) { b.style.visibility = box.children.length > 1 ? 'visible' : 'hidden'; });
            actualizar();
        }
        function agregar(c) {
            var row = tpl.content.firstElementChild.cloneNode(true);
            row.querySelector('[data-c=metric]').value = c.metric;
            row.querySelector('[data-c=scope]').value = c.scope || 'periodo';
            row.querySelector('[data-c=op]').value = c.op;
            row.querySelector('[data-c=value]').value = c.value;
            row.querySelector('[data-del-cond]').onclick = function () { row.remove(); renumerar(); };
            row.addEventListener('input', actualizar); row.addEventListener('change', actualizar);
            box.appendChild(row); renumerar();
        }
        conds.forEach(agregar);
        form.querySelector('[data-add-cond]').onclick = function () { agregar({ metric: 'faltas', scope: 'periodo', op: '=', value: 0 }); };

        function actualizar() {
            var cm = q('[data-f=calc]').value, kind = q('[data-f=kind]').value;
            q('[data-lbl-monto]').textContent = cm === 'monto' ? 'Monto $' : (cm === 'dias_sueldo' ? 'Días de sueldo' : '% del sueldo');
            var rep = form.querySelector('[data-f=rep]:checked').value === 'por_cada';
            q('[data-rep-box]').classList.toggle('on', rep);
            var sc = q('[data-f=scope]').value;
            q('[data-gente]').style.display = sc === 'todos' ? 'none' : '';

            var usaMes = Array.prototype.some.call(box.querySelectorAll('[data-c=scope]'), function (s) { return s.value === 'mes'; });
            q('[data-aviso-mes]').style.display = usaMes ? '' : 'none';
            var timing = q('[data-f=timing]');
            if (usaMes) timing.value = 'cierre_mes';
            timing.disabled = false;

            var partes = Array.prototype.map.call(box.querySelectorAll('.nr-cond'), function (r) {
                var m = r.querySelector('[data-c=metric]').value, s = r.querySelector('[data-c=scope]').value, o = r.querySelector('[data-c=op]').value, v = r.querySelector('[data-c=value]').value;
                return (METRICAS[m] || m).toLowerCase() + ' ' + (s === 'mes' ? 'en el mes' : 'en la quincena') + ' ' + (OPS[o] || o) + ' ' + (v === '' ? '…' : v);
            }).join(' y ');
            var amt = parseFloat(q('[data-f=amount]').value);
            var cuanto = isNaN(amt) ? '…' : (cm === 'monto' ? '$' + amt.toLocaleString('es-MX', { minimumFractionDigits: 2 }) : (cm === 'dias_sueldo' ? amt + (amt === 1 ? ' día' : ' días') + ' de sueldo' : amt + '% del sueldo'));
            var cada = q('[data-f=every]').value, rm = q('[data-f=rmetric]').value;
            var veces = rep ? (parseInt(cada, 10) === 1 ? ' por cada ' + (UNID[rm] || '').replace('(s)', '') : ' por cada ' + (cada || '…') + ' ' + (METRICAS[rm] || '').toLowerCase()) : '';
            q('[data-prev]').innerHTML = '<b>Así quedará:</b> cuando ' + (partes || '…') + ': <b>' + (kind === 'bono' ? 'bonificar ' : 'descontar ') + cuanto + '</b>' + veces +
                (timing.value === 'cierre_mes' ? ', al cerrar el mes.' : ', cada quincena.');
        }
        form.addEventListener('input', actualizar); form.addEventListener('change', actualizar);
        actualizar();
    }

    document.querySelectorAll('[data-regla-form]').forEach(iniciar);
})();
</script>
@endpush
