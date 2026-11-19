@extends('layouts.dashboard')

@section('title', 'Justificar asistencia')
@section('page-title', 'Justificar asistencia')
@section('page-sub', ucfirst($j->work_date->translatedFormat('l d \d\e F \d\e Y')))

@php
    use App\Models\AsistenciaJustificacion as J;

    $tz = config('asistencia.timezone');
    $tono = [J::PENDIENTE => 'warn', J::RESPONDIDA => 'info', J::ACEPTADA => 'ok', J::RECHAZADA => 'bad'][$j->estado] ?? 'plain';
    $abierta = $j->abierta();
    $pideEntrada = $j->problema === 'sin_registro';
@endphp

@section('content')
    @include('asistencia._kit')

    <div class="js-wrap">
        {{-- Qué pasó --}}
        <div class="rh-card rh-pad rh-in js-que" style="--i:1">
            <span class="rh-ico-box {{ $j->estado === J::ACEPTADA ? 'ok' : 'bad' }}"><x-ui.icon name="{{ $j->estado === J::ACEPTADA ? 'check' : 'alert' }}" /></span>
            <div class="js-que-t">
                <div class="js-k">Lo que registró el lector ese día</div>
                <div class="js-frase">{{ $fila['frase'] ?? $j->detalle }}</div>
                @if (! empty($fila['horario']))<div class="rh-sub">Tu horario era de {{ ltrim($fila['horario']['in'], '0') }} a {{ ltrim($fila['horario']['out'], '0') }}</div>@endif
            </div>
            <span class="rh-chip {{ $tono }}">{{ $j->estadoLabel() }}</span>
        </div>

        @if ($j->estado === J::RECHAZADA || $j->estado === J::ACEPTADA)
            <div class="rh-card rh-pad rh-in js-rev js-{{ $tono }}" style="--i:2">
                <b>{{ $j->estado === J::ACEPTADA ? 'RH aceptó tu justificación' : 'RH no aceptó tu justificación' }}</b>
                <span class="rh-sub">{{ $j->revisor?->name }} · {{ $j->revisada_en?->setTimezone($tz)->translatedFormat('d M H:i') }}</span>
                @if ($j->nota_revision)<p>“{{ $j->nota_revision }}”</p>@endif
                @if ($j->motivo)<div class="js-tu"><span>Lo que escribiste</span>{{ $j->motivo }}</div>@endif
            </div>
        @else
            <form method="POST" action="{{ route('asistencia.justificar.responder', $j) }}" class="rh-card rh-pad rh-form rh-in js-form" style="--i:2">@csrf
                <h3 class="rh-h">{{ $j->estado === J::RESPONDIDA ? 'Tu respuesta (puedes corregirla mientras RH no la revise)' : '¿Qué pasó?' }}</h3>
                <p class="rh-sub" style="margin:4px 0 14px;">{{ $j->solicitante?->name ?? 'RH' }} te pidió explicar este día. Escribe el motivo y, si la recuerdas, la hora real.</p>

                <label>Motivo
                    <textarea name="motivo" rows="4" maxlength="1000" required placeholder="Ej. Salí a una entrega y ya no regresé a la oficina; el lector no tenía luz…">{{ old('motivo', $j->motivo) }}</textarea>
                    @error('motivo')<small class="js-err">{{ $message }}</small>@enderror
                </label>

                <div class="rh-grid js-horas">
                    @if ($pideEntrada)
                        <label>Hora a la que entraste <small class="rh-sub">(opcional)</small>
                            <input type="time" name="hora_entrada" value="{{ old('hora_entrada', $j->hora_entrada ? substr($j->hora_entrada, 0, 5) : '') }}">
                            @error('hora_entrada')<small class="js-err">{{ $message }}</small>@enderror
                        </label>
                    @endif
                    <label>Hora a la que saliste <small class="rh-sub">(opcional)</small>
                        <input type="time" name="hora_salida" value="{{ old('hora_salida', $j->hora_salida ? substr($j->hora_salida, 0, 5) : '') }}">
                        @error('hora_salida')<small class="js-err">{{ $message }}</small>@enderror
                    </label>
                </div>
                <p class="rh-sub" style="margin:10px 0 0;">Si RH lo acepta, esas horas quedan registradas en tu día como checada manual.</p>

                <div class="rh-actions" style="margin-top:16px;">
                    <button class="btn" type="submit"><x-ui.icon name="check" /> {{ $j->estado === J::RESPONDIDA ? 'Guardar cambios' : 'Enviar a RH' }}</button>
                </div>
            </form>
        @endif

        @if ($otras->isNotEmpty())
            <div class="rh-card rh-in" style="--i:3; overflow:hidden;">
                <div class="rh-pad" style="padding-bottom:6px;"><h3 class="rh-h">Tus otras justificaciones</h3></div>
                <ul class="js-otras">
                    @foreach ($otras as $o)
                        <li>
                            <a href="{{ route('asistencia.justificar', $o) }}">
                                <b>{{ ucfirst($o->work_date->translatedFormat('D d M')) }}</b>
                                <span class="rh-sub">{{ $o->detalle }}</span>
                            </a>
                            <span class="rh-chip {{ [J::PENDIENTE => 'warn', J::RESPONDIDA => 'info', J::ACEPTADA => 'ok', J::RECHAZADA => 'bad'][$o->estado] ?? 'plain' }}">{{ $o->estadoLabel() }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>

    <style>
        .js-wrap { max-width:720px; display:flex; flex-direction:column; gap:14px; }
        .js-que { display:flex; align-items:flex-start; gap:14px; }
        .js-que-t { flex:1; min-width:0; display:flex; flex-direction:column; gap:3px; }
        .js-k { font-size:12px; color:var(--muted); text-transform:uppercase; letter-spacing:.04em; font-weight:600; }
        .js-frase { font-size:17px; font-weight:600; line-height:1.35; font-variant-numeric:tabular-nums; }
        .js-form textarea { width:100%; resize:vertical; }
        .js-horas { margin-top:12px; }
        .js-err { color:var(--danger); }
        .js-rev { display:flex; flex-direction:column; gap:4px; border-left:3px solid var(--border); }
        .js-rev.js-ok { border-left-color:var(--green); } .js-rev.js-bad { border-left-color:var(--danger); }
        .js-rev p { margin:6px 0 0; }
        .js-tu { margin-top:10px; font-size:13px; display:flex; flex-direction:column; gap:2px; } .js-tu span { font-size:12px; color:var(--muted); }
        .js-otras { list-style:none; margin:0; padding:0; }
        .js-otras li { display:flex; align-items:center; justify-content:space-between; gap:12px; padding:10px 18px; border-top:1px solid var(--border); }
        .js-otras a { display:flex; flex-direction:column; gap:2px; text-decoration:none; color:inherit; min-width:0; }
        @media (max-width: 600px) { .js-que { flex-wrap:wrap; } .js-otras li { flex-wrap:wrap; } }
    </style>
@endsection
