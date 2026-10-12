{{-- Formulario de regla (nueva o editar). Recibe $regla (PayrollRule|null), $fid, $usuarios, $metricas, $operadores, $calculos --}}
@php
    $usarOld = $regla ? (string) old('_edit') === (string) $regla->id : ! old('_edit');
    $o = fn ($k, $d = null) => $usarOld ? old($k, $d) : $d;
    $r = $regla;
    $conds = $o('conditions', $r?->conditions ?? [['metric' => 'retardos', 'scope' => 'periodo', 'op' => '>=', 'value' => 1]]);
    $ids = array_map('intval', $o('user_ids', $r?->user_ids ?? []));
    $modo = $o('repeat_mode', $r?->repeat_mode ?? 'una_vez');
    $scope = $o('scope', $r?->scope ?? 'todos');
    $act = $r ? $r->active : true;
@endphp
<form method="POST" action="{{ $r ? route('nomina.reglas.update', $r) : route('nomina.reglas.store') }}" class="rh-form nr-form" data-regla-form data-conds='@json($conds)'>
    @csrf
    @if ($r) @method("PUT") <input type="hidden" name="_edit" value="{{ $r->id }}"> @endif

    <div class="rh-grid" style="grid-template-columns:repeat(auto-fit, minmax(240px, 1fr));">
        <label>Nombre de la regla <input type="text" name="name" value="{{ $o('name', $r?->name) }}" placeholder="Ej. Bono de asistencia perfecta" required></label>
        <label>Descripción (opcional) <input type="text" name="description" value="{{ $o('description', $r?->description) }}"></label>
    </div>

    <div class="nr-paso"><span class="nr-n">1</span><b>Cuando…</b> <span class="rh-sub">(todas las condiciones deben cumplirse)</span></div>
    <div class="nr-conds" data-conds-box></div>
    <button type="button" class="btn btn--ghost" data-add-cond style="margin-top:8px;"><x-ui.icon name="plus" /> Y también…</button>

    <div class="nr-paso"><span class="nr-n">2</span><b>Entonces…</b></div>
    <div class="rh-grid">
        <label>Qué hacer
            <select name="kind" data-f="kind">
                <option value="bono" @selected($o('kind', $r?->kind) === 'bono')>Dar un bono</option>
                <option value="descuento" @selected($o('kind', $r?->kind ?? 'descuento') === 'descuento')>Hacer un descuento</option>
            </select>
        </label>
        <label>De cuánto
            <select name="calc" data-f="calc">
                @foreach ($calculos as $k => $t)<option value="{{ $k }}" @selected($o('calc', $r?->calc ?? 'monto') === $k)>{{ ucfirst($t) }}</option>@endforeach
            </select>
        </label>
        <label><span data-lbl-monto>Monto</span> <input type="number" step="0.01" min="0.01" name="amount" data-f="amount" value="{{ $o('amount', $r?->amount) }}" required></label>
    </div>

    <div class="nr-paso"><span class="nr-n">3</span><b>¿Cuántas veces?</b></div>
    <div class="nr-rep">
        <label class="nr-op"><input type="radio" name="repeat_mode" value="una_vez" data-f="rep" @checked($modo === 'una_vez')> Una sola vez si se cumple</label>
        <label class="nr-op"><input type="radio" name="repeat_mode" value="por_cada" data-f="rep" @checked($modo === 'por_cada')> Se repite: por cada…</label>
        <div class="nr-rep-box" data-rep-box>
            <label>Cada <input type="number" min="1" name="repeat_every" data-f="every" value="{{ $o('repeat_every', $r?->repeat_every ?? 1) }}" style="width:90px;"></label>
            <label>de
                <select name="repeat_metric" data-f="rmetric">
                    @foreach ($metricas as $k => $m)<option value="{{ $k }}" @selected($o('repeat_metric', $r?->repeat_metric ?? 'faltas') === $k)>{{ $m[0] }}</option>@endforeach
                </select>
            </label>
            <label>Máximo de veces (opcional) <input type="number" min="1" name="repeat_max" value="{{ $o('repeat_max', $r?->repeat_max) }}" style="width:110px;"></label>
        </div>
    </div>

    <div class="nr-paso"><span class="nr-n">4</span><b>¿Cuándo y a quién?</b></div>
    <div class="rh-grid">
        <label>Cuándo se aplica
            <select name="timing" data-f="timing">
                <option value="periodo" @selected($o('timing', $r?->timing ?? 'periodo') === 'periodo')>Cada quincena</option>
                <option value="cierre_mes" @selected($o('timing', $r?->timing) === 'cierre_mes')>Solo al cerrar el mes (2.ª quincena)</option>
            </select>
        </label>
        <label>A quién
            <select name="scope" data-f="scope">
                <option value="todos" @selected($scope === 'todos')>A todos</option>
                <option value="incluir" @selected($scope === 'incluir')>Solo a las personas que elija</option>
                <option value="excluir" @selected($scope === 'excluir')>A todos menos a las que elija</option>
            </select>
        </label>
    </div>
    <div class="nr-gente" data-gente style="display:none;">
        @foreach ($usuarios as $u)
            <label><input type="checkbox" name="user_ids[]" value="{{ $u->id }}" @checked(in_array($u->id, $ids))> {{ $u->name }}</label>
        @endforeach
    </div>
    <p class="rh-sub" data-aviso-mes style="display:none; margin:8px 0 0;">ℹ Una condición «en el mes» solo se puede decidir cuando el mes termina, así que la regla se aplica en la segunda quincena.</p>

    <div class="nr-prev" data-prev></div>

    <div class="rh-actions" style="margin-top:14px;">
        <label class="nr-act"><span class="rh-switch"><input type="checkbox" name="active" value="1" @checked($o('active', $act))><span></span></span> Regla activa</label>
        <button class="btn" type="submit" style="margin-left:auto;">{{ $r ? 'Guardar cambios' : 'Crear regla' }}</button>
    </div>
</form>
