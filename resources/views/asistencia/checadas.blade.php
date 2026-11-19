@extends('layouts.dashboard')

@section('title', 'Checadas')
@section('page-title', 'Asistencia')
@section('page-sub', 'Quién entró, quién salió y qué falta, persona por persona')

@php
    use App\Models\AsistenciaJustificacion as J;
    use App\Services\Asistencia\ResumenDeChecadas as R;

    $tz = config('asistencia.timezone');
    $esHoy = $dia->equalTo($hoy);

    // Línea del día de 6:00 a 22:00
    $ini = 6 * 60; $rango = 16 * 60;
    $pct = function (?string $hm) use ($ini, $rango) {
        if (! $hm) return null;
        [$h, $m] = array_map('intval', explode(':', $hm));
        return max(0, min(100, (($h * 60 + $m) - $ini) / $rango * 100));
    };
    $hm = fn (string $t) => preg_replace('/^0(\d)/', '$1', substr($t, 0, 5));
    $ahoraHm = now()->setTimezone($tz)->format('H:i');
    $duracion = fn (int $min) => $min >= 60 ? intdiv($min, 60).' h'.($min % 60 ? ' '.($min % 60).' min' : '') : $min.' min';
    $iniciales = fn (string $n) => collect(preg_split('/\s+/', trim($n)))->filter()->take(2)->map(fn ($p) => mb_substr($p, 0, 1))->implode('');

    $grupo = fn (array $f) => match (true) {
        in_array($f['estado'], R::PROBLEMAS, true) || $f['justificacion']?->estado === J::RESPONDIDA => 'atender',
        in_array($f['estado'], ['trabajando', 'por_llegar'], true) => 'curso',
        in_array($f['estado'], ['completo', 'justificado'], true) => 'listos',
        default => 'sin',
    };
    $porGrupo = $filas->countBy($grupo);
    $pedibles = $filas->where('puede_pedir', true);
@endphp

@section('content')
    @include('asistencia._tabs')

    {{-- Día --}}
    <div class="ck-top rh-in" style="--i:1">
        <div class="ck-dia">
            <a class="btn btn--ghost ck-nav" href="{{ route('asistencia.checadas', ['fecha' => $dia->copy()->subDay()->toDateString()]) }}" title="Día anterior"><x-ui.icon name="chevron-left" /></a>
            <div class="ck-dia-t">
                <b>{{ $esHoy ? 'Hoy' : ucfirst($dia->translatedFormat('l')) }}</b>
                <span>{{ $dia->translatedFormat('d \d\e F \d\e Y') }}</span>
            </div>
            @if ($dia->lt($hoy))
                <a class="btn btn--ghost ck-nav" href="{{ route('asistencia.checadas', ['fecha' => $dia->copy()->addDay()->toDateString()]) }}" title="Día siguiente"><x-ui.icon name="chevron-right" /></a>
            @endif
            <form method="GET" class="ck-fecha"><input type="date" name="fecha" value="{{ $dia->toDateString() }}" max="{{ $hoy->toDateString() }}" onchange="this.form.submit()" aria-label="Elegir día"></form>
            @unless ($esHoy)<a class="btn btn--ghost" href="{{ route('asistencia.checadas') }}">Ir a hoy</a>@endunless
        </div>
        @if ($puedeGestionar)
            <button type="button" class="btn btn--ghost" data-rh-toggle="ck-manual"><x-ui.icon name="plus" /> Checada manual</button>
        @endif
    </div>

    {{-- Números del día --}}
    <div class="rh-kpis ck-kpis rh-in" style="--i:2">
        <div class="rh-kpi"><div class="rh-kpi-v">{{ $conteos['esperados'] }}</div><div class="rh-kpi-k">Les tocaba trabajar</div></div>
        <div class="rh-kpi ok"><div class="rh-kpi-v">{{ $conteos['completos'] }}</div><div class="rh-kpi-k">Entrada y salida completas</div></div>
        @if ($esHoy)<div class="rh-kpi info"><div class="rh-kpi-v">{{ $conteos['en_curso'] }}</div><div class="rh-kpi-k">Trabajando o por llegar</div></div>@endif
        <div class="rh-kpi {{ $conteos['sin_salida'] ? 'mal' : '' }}"><div class="rh-kpi-v">{{ $conteos['sin_salida'] }}</div><div class="rh-kpi-k">No registraron salida</div></div>
        <div class="rh-kpi {{ $conteos['sin_registro'] ? 'mal' : '' }}"><div class="rh-kpi-v">{{ $conteos['sin_registro'] }}</div><div class="rh-kpi-k">Sin entrada ni salida</div></div>
        <div class="rh-kpi {{ $conteos['retardos'] ? 'warn' : '' }}"><div class="rh-kpi-v">{{ $conteos['retardos'] }}</div><div class="rh-kpi-k">Llegaron tarde</div></div>
        @if ($conteos['tempranos'])<div class="rh-kpi warn"><div class="rh-kpi-v">{{ $conteos['tempranos'] }}</div><div class="rh-kpi-k">Salieron antes de su hora</div></div>@endif
        @if ($conteos['por_revisar'])<div class="rh-kpi info"><div class="rh-kpi-v">{{ $conteos['por_revisar'] }}</div><div class="rh-kpi-k">Justificaciones por revisar</div></div>@endif
    </div>

    {{-- Aviso para pedir justificación a todos los que les falta algo --}}
    @if ($puedeGestionar && $pedibles->isNotEmpty())
        <div class="ck-aviso rh-in" style="--i:3">
            <span class="rh-ico-box bad"><x-ui.icon name="alert" /></span>
            <div class="ck-aviso-t">
                <b>{{ $pedibles->count() === 1 ? 'A 1 persona le falta un registro' : 'A '.$pedibles->count().' personas les falta un registro' }}</b>
                <span>{{ $pedibles->map(fn ($f) => $f['user']->name)->implode(', ') }}</span>
            </div>
            <form method="POST" action="{{ route('asistencia.justificaciones.pedir') }}" style="margin:0;"
                  data-confirm="Les llegará un aviso en el sistema y un correo pidiéndoles que expliquen qué pasó: {{ $pedibles->map(fn ($f) => $f['user']->name)->implode(', ') }}."
                  data-confirm-title="Pedir justificación" data-confirm-ok="Enviar avisos">@csrf
                <input type="hidden" name="fecha" value="{{ $dia->toDateString() }}">
                @foreach ($pedibles as $f)<input type="hidden" name="user_ids[]" value="{{ $f['user']->id }}">@endforeach
                <button class="btn" type="submit"><x-ui.icon name="note" /> {{ $pedibles->count() === 1 ? 'Pedirle que justifique' : 'Pedir a todos que justifiquen' }}</button>
            </form>
        </div>
    @endif

    {{-- Checada manual --}}
    @if ($puedeGestionar)
        <div class="rh-acc rh-in {{ $errors->hasAny(['user_id', 'hora', 'fecha']) ? 'open' : '' }}" id="ck-manual" style="--i:3; margin-bottom:14px;">
            <div class="rh-acc-b"><div class="rh-acc-i"><div class="rh-acc-c">
                <p class="rh-sub" style="margin:0 0 12px;">Para cuando alguien olvidó checar o falló el lector. Queda marcada como manual con tu nombre y el motivo; la checada real nunca se edita.</p>
                <form method="POST" action="{{ route('asistencia.checadas.manual') }}" class="rh-form" id="ck-manual-form">@csrf
                    <div class="rh-grid">
                        <label>Empleado
                            <select name="user_id" required>
                                <option value="">— Elige —</option>
                                @foreach ($empleados as $e)<option value="{{ $e->id }}" @selected(old('user_id') == $e->id)>{{ $e->name }}</option>@endforeach
                            </select>
                        </label>
                        <label>Fecha <input type="date" name="fecha" value="{{ old('fecha', $dia->toDateString()) }}" max="{{ $hoy->toDateString() }}" required></label>
                        <label>Hora <input type="time" name="hora" value="{{ old('hora') }}" required></label>
                        <label>Motivo <input type="text" name="motivo" value="{{ old('motivo') }}" placeholder="Olvidó checar, lector sin energía…" required></label>
                    </div>
                    <div class="rh-actions" style="margin-top:14px;"><button class="btn" type="submit">Registrar checada</button></div>
                </form>
            </div></div></div>
        </div>
    @endif

    {{-- Filtros --}}
    <div class="ck-filtros rh-in" style="--i:4">
        <div class="rh-tabs ck-seg" role="tablist">
            <button type="button" class="rh-tab on" data-ck-grupo="">Todos <span>{{ $filas->count() }}</span></button>
            <button type="button" class="rh-tab" data-ck-grupo="atender">Por atender <span>{{ $porGrupo['atender'] ?? 0 }}</span></button>
            @if ($esHoy)<button type="button" class="rh-tab" data-ck-grupo="curso">En curso <span>{{ $porGrupo['curso'] ?? 0 }}</span></button>@endif
            <button type="button" class="rh-tab" data-ck-grupo="listos">Completos <span>{{ $porGrupo['listos'] ?? 0 }}</span></button>
            <button type="button" class="rh-tab" data-ck-grupo="sin">Sin turno <span>{{ $porGrupo['sin'] ?? 0 }}</span></button>
        </div>
        <input type="search" class="ck-buscar" placeholder="Buscar persona…" data-ck-buscar aria-label="Buscar persona" data-no-mayus>
    </div>

    {{-- Una tarjeta por persona --}}
    <div class="ck-lista">
        @forelse ($filas as $i => $f)
            @php
                $u = $f['user'];
                $e = R::ESTADOS[$f['estado']];
                $j = $f['justificacion'];
                $validas = $f['checadas']->whereNull('voided_at');
                if ($f['estado'] === 'completo' && ($f['temprano'] || $f['retardo'])) {
                    $e = ['texto' => $f['temprano'] ? 'Salió antes' : 'Con retardo', 'tono' => 'warn'];
                }
                $tono = $e['tono'] ?: 'plain';
            @endphp
            <div class="rh-acc ck-p ck-{{ $tono }} rh-in" id="persona-{{ $u->id }}" style="--i:{{ min($i + 5, 14) }}"
                 data-ck-g="{{ $grupo($f) }}" data-ck-n="{{ mb_strtolower($u->name) }}">
                <div class="rh-acc-h" tabindex="0">
                    <span class="ck-av ck-av-{{ $tono }}">{{ $iniciales($u->name) }}</span>
                    <div class="rh-acc-main">
                        <div class="ck-nom">{{ $u->name }}</div>
                        <div class="ck-frase">{{ $f['frase'] }}</div>
                        <div class="ck-meta">
                            @if ($f['horario'])<span><x-ui.icon name="clock" /> Horario {{ $hm($f['horario']['in']) }}–{{ $hm($f['horario']['out']) }}</span>@endif
                            @if ($f['minutos'])<span><x-ui.icon name="hourglass" /> Trabajó {{ $duracion($f['minutos']) }}</span>@endif
                            @if ($f['retardo'])<span class="ck-tarde"><x-ui.icon name="alert" /> Llegó {{ $duracion($f['retardo']) }} tarde</span>@endif
                            @if ($f['temprano'])<span class="ck-tarde"><x-ui.icon name="alert" /> Salió {{ $duracion($f['temprano']) }} antes de su hora</span>@endif
                        </div>
                    </div>
                    <div class="rh-acc-side">
                        @if ($j)
                            @php $jt = [J::PENDIENTE => 'warn', J::RESPONDIDA => 'info', J::ACEPTADA => 'ok', J::RECHAZADA => 'bad'][$j->estado] ?? 'plain'; @endphp
                            <span class="rh-chip {{ $jt }}">{{ [J::PENDIENTE => 'Se le pidió justificar', J::RESPONDIDA => 'Justificó · por revisar', J::ACEPTADA => 'Justificación aceptada', J::RECHAZADA => 'Justificación rechazada'][$j->estado] }}</span>
                        @endif
                        <span class="rh-chip {{ $tono }}">{{ $e['texto'] }}</span>
                        <svg class="rh-chev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6"/></svg>
                    </div>
                </div>

                {{-- Línea del día: el horario en gris, lo trabajado en color --}}
                @if ($f['horario'] || $f['tramos'])
                    <div class="ck-linea" aria-hidden="true">
                        @if ($f['horario'])
                            <span class="ck-turno" style="left:{{ $pct($f['horario']['in']) }}%; width:{{ max(0, $pct($f['horario']['out']) - $pct($f['horario']['in'])) }}%"></span>
                        @endif
                        @foreach ($f['tramos'] as $t)
                            @php
                                $fin = $t['sale'] ?? ($f['estado'] === 'trabajando' ? $ahoraHm : null);
                                $a = $pct($t['entra']);
                            @endphp
                            @if ($fin)
                                <span class="ck-tramo {{ $t['sale'] ? '' : 'vivo' }}" style="left:{{ $a }}%; width:{{ max(0.6, $pct($fin) - $a) }}%"></span>
                            @else
                                <span class="ck-tramo abierto" style="left:{{ $a }}%"></span>
                            @endif
                        @endforeach
                        <span class="ck-hr" style="left:0">6</span><span class="ck-hr" style="left:25%">10</span><span class="ck-hr" style="left:50%">14</span><span class="ck-hr" style="left:75%">18</span><span class="ck-hr" style="left:100%">22</span>
                    </div>
                @endif

                <div class="rh-acc-b"><div class="rh-acc-i"><div class="rh-acc-c">
                    <div class="ck-cuerpo">
                        {{-- Justificación --}}
                        <section class="ck-sec">
                            <h4>Justificación</h4>
                            @if (! $j)
                                @if ($f['puede_pedir'])
                                    <p class="rh-sub">{{ $u->name }} no ha explicado qué pasó. Al pedírselo le llega un aviso en el sistema y un correo con un enlace para responder.</p>
                                @else
                                    <p class="rh-sub">No hace falta: el día no tiene registros pendientes.</p>
                                @endif
                            @else
                                <div class="ck-just">
                                    <div class="ck-just-l"><span>Qué faltaba</span><b>{{ $j->detalle }}</b></div>
                                    <div class="ck-just-l"><span>Se le pidió</span><b>{{ $j->created_at->setTimezone($tz)->translatedFormat('d M H:i') }}{{ $j->solicitante ? ' · '.$j->solicitante->name : '' }}</b></div>
                                    @if ($j->motivo)
                                        <div class="ck-just-l full"><span>Su respuesta</span><b class="ck-motivo">“{{ $j->motivo }}”</b></div>
                                        @if ($j->hora_entrada || $j->hora_salida)
                                            <div class="ck-just-l full"><span>Dice que</span><b>
                                                {{ collect([$j->hora_entrada ? 'entró a las '.substr($j->hora_entrada, 0, 5) : null, $j->hora_salida ? 'salió a las '.substr($j->hora_salida, 0, 5) : null])->filter()->implode(' y ') }}
                                            </b></div>
                                        @endif
                                    @elseif ($j->estado === J::PENDIENTE)
                                        <div class="ck-just-l full"><span>Su respuesta</span><b class="rh-sub">Todavía no responde.</b></div>
                                    @endif
                                    @if ($j->revisada_en)
                                        <div class="ck-just-l full"><span>Revisión</span><b>{{ $j->estadoLabel() }} por {{ $j->revisor?->name ?? '—' }} el {{ $j->revisada_en->setTimezone($tz)->translatedFormat('d M H:i') }}{{ $j->nota_revision ? ': '.$j->nota_revision : '' }}</b></div>
                                    @endif
                                </div>

                                @if ($puedeGestionar && $j->estado === J::RESPONDIDA)
                                    <div class="ck-revisar">
                                        <input type="text" class="ck-nota" maxlength="255" placeholder="Nota para {{ $u->name }} (opcional)" data-ck-nota>
                                        <form method="POST" action="{{ route('asistencia.justificaciones.rechazar', $j) }}" style="margin:0;" data-ck-con-nota
                                              data-confirm="Se le avisará a {{ $u->name }} que no se aceptó. El día se queda como está." data-confirm-title="Rechazar justificación" data-confirm-ok="Rechazar" data-confirm-danger>@csrf
                                            <input type="hidden" name="nota">
                                            <button class="btn btn--ghost" type="submit"><x-ui.icon name="x" /> Rechazar</button>
                                        </form>
                                        <form method="POST" action="{{ route('asistencia.justificaciones.aceptar', $j) }}" style="margin:0;" data-ck-con-nota
                                              data-confirm="{{ ($j->hora_entrada || $j->hora_salida) ? 'Se registrarán como checadas manuales las horas que dio, con su motivo.' : ($j->problema === 'sin_registro' ? 'El día quedará como permiso justificado (no cuenta como falta).' : 'Se marcará el día como justificado.') }}"
                                              data-confirm-title="Aceptar justificación" data-confirm-ok="Aceptar">@csrf
                                            <input type="hidden" name="nota">
                                            <button class="btn" type="submit"><x-ui.icon name="check" /> Aceptar</button>
                                        </form>
                                    </div>
                                @endif
                            @endif

                            @if ($puedeGestionar && $f['puede_pedir'])
                                <form method="POST" action="{{ route('asistencia.justificaciones.pedir') }}" style="margin:12px 0 0;"
                                      data-confirm="Le llegará a {{ $u->name }} un aviso en el sistema y un correo: “{{ $f['frase'] }}”. Tendrá que escribir el motivo."
                                      data-confirm-title="Pedir justificación" data-confirm-ok="Enviar aviso">@csrf
                                    <input type="hidden" name="fecha" value="{{ $dia->toDateString() }}">
                                    <input type="hidden" name="user_ids[]" value="{{ $u->id }}">
                                    <button class="btn" type="submit"><x-ui.icon name="note" /> {{ $j ? 'Volver a pedir que justifique' : 'Pedir que justifique' }}</button>
                                </form>
                            @endif
                        </section>

                        {{-- Checadas tal como llegaron --}}
                        <section class="ck-sec">
                            <h4>Registros del lector</h4>
                            @if ($f['checadas']->isEmpty())
                                <p class="rh-sub">No hay ningún registro este día.</p>
                            @else
                                <ul class="ck-regs">
                                    @foreach ($f['checadas'] as $p)
                                        <li class="{{ $p->voided_at ? 'anul' : '' }}">
                                            <span class="ck-rh">{{ $p->horaLocal()->format('H:i') }}</span>
                                            <span class="ck-rt">
                                                {{ $p->kindLabel() }}
                                                <small>
                                                    @if ($p->method === 'manual')Manual · {{ $p->note }}{{ $p->creadaPor ? ' · '.$p->creadaPor->name : '' }}
                                                    @else Huella{{ $p->confidence !== null ? ' '.$p->confidence.'%' : '' }}{{ $p->device ? ' · '.$p->device->name : '' }}@endif
                                                    @if ($p->voided_at) · Anulada por {{ $p->anuladaPor?->name }}: {{ $p->void_reason }}@endif
                                                </small>
                                            </span>
                                            @if (! $p->voided_at && $puedeGestionar)
                                                <form method="POST" action="{{ route('asistencia.checadas.anular', $p) }}" onsubmit="return anular(this)" style="margin:0;">@csrf
                                                    <input type="hidden" name="motivo">
                                                    <button class="btn btn--ghost ck-mini" type="submit">Anular</button>
                                                </form>
                                            @endif
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                            <div class="ck-links">
                                @if ($puedeGestionar)<button type="button" class="btn btn--ghost ck-mini" data-ck-manual="{{ $u->id }}"><x-ui.icon name="plus" /> Agregar checada</button>@endif
                                <a class="btn btn--ghost ck-mini" href="{{ route('asistencia.empleado', [$u, 'mes' => $dia->format('Y-m')]) }}"><x-ui.icon name="calendar" /> Ver su mes</a>
                            </div>
                        </section>
                    </div>
                </div></div></div>
            </div>
        @empty
            <div class="rh-card"><div class="rh-empty"><b>Sin personal</b>No hay empleados con horario para este día.</div></div>
        @endforelse
        <div class="rh-card ck-nada" hidden><div class="rh-empty"><b>Nadie aquí</b>No hay personas con ese filtro.</div></div>
    </div>

    <style>
        .ck-top { display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap; margin-bottom:14px; }
        .ck-dia { display:flex; align-items:center; gap:8px; flex-wrap:wrap; }
        .ck-nav { padding:6px 8px; }
        .ck-dia-t { display:flex; flex-direction:column; line-height:1.2; min-width:150px; padding:0 4px; }
        .ck-dia-t b { font-size:17px; } .ck-dia-t span { font-size:13px; color:var(--muted); }
        .ck-fecha input { padding:6px 8px; }
        .ck-kpis { margin-bottom:14px; grid-template-columns:repeat(auto-fit, minmax(140px, 1fr)); }

        .ck-aviso { display:flex; align-items:center; gap:14px; padding:14px 16px; margin-bottom:14px; border:1px solid var(--border); border-left:3px solid var(--danger); border-radius:10px; background:var(--surface); }
        .ck-aviso-t { flex:1; min-width:0; display:flex; flex-direction:column; gap:2px; }
        .ck-aviso-t span { font-size:13px; color:var(--muted); }

        .ck-filtros { display:flex; align-items:center; justify-content:space-between; gap:10px; flex-wrap:wrap; margin-bottom:12px; }
        .ck-seg button.rh-tab { border:0; background:transparent; cursor:pointer; font-family:inherit; }
        .ck-seg button.rh-tab + .rh-tab { border-left:1px solid var(--border); } .ck-seg button.rh-tab.on { background:var(--primary); }
        .ck-seg .rh-tab.on span { color:rgba(255,255,255,.8); }
        .ck-seg .rh-tab span { margin-left:4px; font-size:11px; color:var(--muted); font-variant-numeric:tabular-nums; }
        .ck-buscar { min-width:220px; padding:7px 10px; }

        .ck-lista { display:flex; flex-direction:column; gap:10px; }
        .ck-p { border-left:3px solid var(--border); }
        .ck-p.ck-bad { border-left-color:var(--danger); } .ck-p.ck-warn { border-left-color:var(--accent); }
        .ck-p.ck-ok { border-left-color:var(--green); } .ck-p.ck-info { border-left-color:var(--primary); }
        .ck-p .rh-acc-h { align-items:flex-start; }
        .ck-av { flex:0 0 38px; width:38px; height:38px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:13px; font-weight:700; background:var(--surface-2); color:var(--muted); }
        .ck-av-bad { background:var(--danger-soft); color:var(--danger); } .ck-av-warn { background:var(--accent-soft); color:var(--accent); }
        .ck-av-ok { background:var(--green-soft); color:var(--green); } .ck-av-info { background:var(--primary-soft); color:var(--primary); }
        .ck-nom { font-weight:600; font-size:14px; }
        .ck-frase { font-size:15px; margin-top:2px; line-height:1.4; font-variant-numeric:tabular-nums; }
        .ck-meta { display:flex; flex-wrap:wrap; gap:4px 14px; margin-top:6px; font-size:12px; color:var(--muted); }
        .ck-meta span { display:inline-flex; align-items:center; gap:4px; } .ck-meta svg { width:13px; height:13px; }
        .ck-tarde { color:var(--accent) !important; }

        .ck-linea { position:relative; height:8px; margin:0 16px 22px 66px; background:var(--surface-2); border-radius:4px; }
        .ck-turno { position:absolute; top:0; bottom:0; border-radius:4px; background:var(--border); }
        .ck-tramo { position:absolute; top:0; bottom:0; border-radius:4px; background:var(--green); }
        .ck-bad .ck-tramo { background:var(--danger); } .ck-info .ck-tramo, .ck-tramo.vivo { background:var(--primary); }
        .ck-tramo.abierto { width:10px; background:none; border-left:3px solid var(--danger); border-radius:0; }
        .ck-tramo.abierto::after { content:'?'; position:absolute; left:4px; top:-4px; font-size:11px; font-weight:700; color:var(--danger); }
        .ck-hr { position:absolute; top:11px; transform:translateX(-50%); font-size:10px; color:var(--muted); font-variant-numeric:tabular-nums; }

        .ck-cuerpo { display:grid; grid-template-columns:1fr 1fr; gap:22px; }
        .ck-sec h4 { margin:0 0 10px; font-size:12px; font-weight:600; text-transform:uppercase; letter-spacing:.04em; color:var(--muted); }
        .ck-sec p { margin:0; }
        .ck-just { display:grid; grid-template-columns:1fr 1fr; gap:10px 16px; }
        .ck-just-l { display:flex; flex-direction:column; gap:2px; font-size:13px; } .ck-just-l.full { grid-column:1 / -1; }
        .ck-just-l span { font-size:12px; color:var(--muted); } .ck-just-l b { font-weight:500; }
        .ck-motivo { font-size:14px; }
        .ck-revisar { display:flex; gap:8px; flex-wrap:wrap; align-items:center; margin-top:14px; }
        .ck-nota { flex:1 1 200px; padding:7px 10px; }
        .ck-regs { list-style:none; margin:0; padding:0; display:flex; flex-direction:column; }
        .ck-regs li { display:flex; align-items:center; gap:12px; padding:8px 0; border-bottom:1px solid var(--border); }
        .ck-regs li:last-child { border-bottom:0; }
        .ck-rh { font-weight:700; font-variant-numeric:tabular-nums; min-width:44px; }
        .ck-rt { flex:1; min-width:0; font-size:13px; display:flex; flex-direction:column; } .ck-rt small { color:var(--muted); font-size:12px; }
        .ck-regs li.anul .ck-rh, .ck-regs li.anul .ck-rt { text-decoration:line-through; color:var(--muted); }
        .ck-mini { padding:4px 10px; font-size:12px; }
        .ck-links { display:flex; gap:8px; flex-wrap:wrap; margin-top:12px; }

        @media (max-width: 760px) {
            .ck-p .rh-acc-h { flex-wrap:wrap; }
            .ck-p .rh-acc-side { width:100%; justify-content:flex-start; padding-left:50px; }
            .ck-p .rh-chev { margin-left:auto; }
            .ck-linea { margin-left:16px; }
            .ck-cuerpo, .ck-just { grid-template-columns:1fr; }
            .ck-aviso { flex-wrap:wrap; } .ck-aviso form, .ck-aviso .btn { width:100%; justify-content:center; }
            .ck-buscar { flex:1; min-width:0; }
            .ck-seg { overflow-x:auto; max-width:100%; }
        }
    </style>
@endsection

@push('scripts')
<script>
    (function () {
        // Filtros por grupo y búsqueda (solo esconden; los datos ya están en la página)
        var grupo = '', texto = '';
        function filtrar() {
            var vistos = 0;
            document.querySelectorAll('.ck-p').forEach(function (p) {
                var ok = (!grupo || p.dataset.ckG === grupo) && (!texto || p.dataset.ckN.indexOf(texto) !== -1);
                p.hidden = !ok; if (ok) vistos++;
            });
            var nada = document.querySelector('.ck-nada'); if (nada) nada.hidden = vistos > 0 || !document.querySelector('.ck-p');
        }
        document.addEventListener('click', function (e) {
            var b = e.target.closest('[data-ck-grupo]'); if (!b) return;
            document.querySelectorAll('[data-ck-grupo]').forEach(function (x) { x.classList.toggle('on', x === b); });
            grupo = b.dataset.ckGrupo; filtrar();
        });
        document.addEventListener('input', function (e) {
            if (!e.target.matches('[data-ck-buscar]')) return;
            texto = e.target.value.trim().toLowerCase(); filtrar();
        });
        document.addEventListener('obs:vivo', filtrar);

        // "Agregar checada" de una persona: abre el formulario ya con su nombre
        document.addEventListener('click', function (e) {
            var b = e.target.closest('[data-ck-manual]'); if (!b) return;
            var f = document.getElementById('ck-manual-form'); if (!f) return;
            f.user_id.value = b.dataset.ckManual;
            window.rhAbrir('ck-manual');
            setTimeout(function () { f.hora.focus(); }, 350);
        });

        // Aceptar / rechazar llevan la nota que RH escribió junto a los botones
        document.addEventListener('submit', function (e) {
            var f = e.target; if (!f.matches || !f.matches('[data-ck-con-nota]')) return;
            var n = f.parentElement.querySelector('[data-ck-nota]');
            f.nota.value = n ? n.value.trim() : '';
        }, true);
    })();

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
