<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    {{-- Privado por enlace: que no lo indexe ningún buscador. --}}
    <meta name="robots" content="noindex, nofollow">
    <title>Mi garantía {{ $g->folio }} · {{ config('medibuy.empresa.nombre') }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --bg:#f5f7fa; --surface:#fff; --surface-2:#f7f8fa;
            --text:#333; --muted:#888; --border:#ebebeb;
            --primary:#007aff; --green:#15803d; --green-soft:#e6ffe6;
            --amber:#b45309; --amber-soft:#fff3e8; --red:#dc2626; --red-soft:#ffebeb;
        }
        * { box-sizing:border-box; }
        body { margin:0; background:var(--bg); color:var(--text); font-family:Quicksand, system-ui, sans-serif; font-size:15px; line-height:1.5; }
        .pw { max-width:820px; margin:0 auto; padding:24px 16px 48px; }

        .pw-head { display:flex; align-items:center; gap:14px; flex-wrap:wrap; padding:22px 24px; margin-bottom:16px;
                   background:var(--surface); border:1px solid var(--border); border-radius:14px; border-top:3px solid var(--primary); }
        .pw-marca { font-size:20px; font-weight:700; }
        .pw-marca small { display:block; color:var(--muted); font-size:10.5px; font-weight:500; letter-spacing:1.2px; }
        .pw-folio { margin-left:auto; text-align:right; }
        .pw-folio .t { color:var(--muted); font-size:11px; text-transform:uppercase; letter-spacing:1px; }
        .pw-folio .n { color:var(--primary); font-size:19px; font-weight:700; }

        .pw-card { padding:22px 24px; margin-bottom:16px; background:var(--surface); border:1px solid var(--border); border-radius:14px; }
        .pw-card h2 { margin:0 0 16px; padding-bottom:12px; border-bottom:1px solid var(--border); font-size:15px; font-weight:700; }
        .pw-equipo { font-size:20px; font-weight:700; margin:0 0 4px; }
        .pw-datos { display:grid; grid-template-columns:repeat(auto-fit,minmax(170px,1fr)); gap:16px; margin-top:16px; }
        .pw-dato .k { color:var(--muted); font-size:12.5px; }
        .pw-dato .v { margin-top:2px; font-size:15px; font-weight:600; overflow-wrap:anywhere; }

        .chip { display:inline-block; padding:3px 11px; border-radius:999px; font-size:12.5px; font-weight:700; }
        .chip.ok { background:var(--green-soft); color:var(--green); }
        .chip.warn { background:var(--amber-soft); color:var(--amber); }
        .chip.mal { background:var(--red-soft); color:var(--red); }
        .chip.info { background:#e6f0ff; color:var(--primary); }

        table { width:100%; border-collapse:collapse; }
        th { padding:9px 10px; background:var(--surface-2); color:var(--muted); font-size:11px; font-weight:600; text-transform:uppercase; letter-spacing:.4px; text-align:left; }
        td { padding:11px 10px; border-bottom:1px solid var(--border); font-size:14px; }
        tr:last-child td { border-bottom:0; }
        .tabla { overflow-x:auto; }

        .pw-acciones { display:flex; gap:10px; flex-wrap:wrap; margin-top:18px; }
        .btn { display:inline-flex; align-items:center; justify-content:center; gap:8px; padding:11px 18px; border-radius:10px; border:1px solid var(--primary);
               background:var(--primary); color:#fff; font:inherit; font-weight:700; font-size:14px; text-decoration:none; cursor:pointer; }
        .btn.ghost { background:var(--surface); color:var(--text); border-color:var(--border); }

        .ticket { padding:14px 0; border-bottom:1px solid var(--border); }
        .ticket:last-child { border-bottom:0; }
        .ticket .t { font-weight:700; }
        .ticket .s { color:var(--muted); font-size:13px; }
        .pasos { display:grid; grid-template-columns:repeat(6,1fr); gap:4px; margin-top:10px; }
        .pasos span { height:5px; border-radius:5px; background:var(--border); }
        .pasos span.on { background:var(--primary); }
        .pasos span.ok { background:var(--green); }

        label { display:block; font-size:13px; font-weight:600; margin:14px 0 6px; }
        input[type=text], input[type=tel], input[type=email], select, textarea {
            width:100%; padding:11px 12px; border:1px solid var(--border); border-radius:10px; font:inherit; font-size:15px; background:#fff; color:var(--text); }
        textarea { min-height:110px; resize:vertical; }
        .check { display:flex; gap:10px; align-items:flex-start; font-weight:500; }
        .check input { margin-top:4px; }
        .grid2 { display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); gap:0 14px; }

        .aviso { padding:13px 15px; border-radius:10px; margin-bottom:16px; font-size:14px; }
        .aviso.ok { background:var(--green-soft); color:var(--green); font-weight:600; }
        .aviso.mal { background:var(--red-soft); color:var(--red); }
        .nota { color:var(--muted); font-size:13px; margin-top:10px; }
        .pie { text-align:center; color:var(--muted); font-size:12.5px; margin-top:24px; }
    </style>
</head>
<body>
@php
    $semaforo = $g->semaforo();
    $chip = ['vigente' => 'ok', 'por_vencer' => 'warn', 'vencida' => 'mal', 'reclamacion' => 'info', 'en_reparacion' => 'warn', 'sustituida' => 'mal'][$semaforo] ?? 'info';
    $texto = ['vigente' => 'Vigente', 'por_vencer' => 'Vigente · próxima a vencer', 'vencida' => 'Vencida',
              'reclamacion' => 'Vigente · reporte en atención', 'en_reparacion' => 'En reparación', 'sustituida' => 'Sustituida'][$semaforo] ?? '';
    $etapas = array_keys(\App\Models\GarantiaReclamacion::ETAPAS);
    $con = config('medibuy.contacto');
@endphp
<div class="pw">
    <div class="pw-head">
        <div class="pw-marca">{{ config('medibuy.empresa.nombre') }}<small>MI GARANTÍA</small></div>
        <div class="pw-folio"><div class="t">Póliza</div><div class="n">{{ $g->folio }}</div></div>
    </div>

    @if (session('status'))<div class="aviso ok">{{ session('status') }}</div>@endif
    @if ($errors->any())<div class="aviso mal">@foreach ($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>@endif

    <div class="pw-card">
        <p class="pw-equipo">{{ $g->grupo ?: $g->equipoNombre() }}</p>
        <span class="chip {{ $chip }}">{{ $texto }}</span>
        <div class="pw-datos">
            <div class="pw-dato"><div class="k">Cliente</div><div class="v">{{ $g->clienteNombre() }}</div></div>
            @unless ($g->grupo)
                <div class="pw-dato"><div class="k">Número de serie</div><div class="v">{{ $g->no_serie ?: '—' }}</div></div>
            @endunless
            <div class="pw-dato"><div class="k">Cobertura</div><div class="v">{{ $g->tipoLabel() }}</div></div>
            <div class="pw-dato"><div class="k">Vigente hasta</div><div class="v">{{ $g->vence->format('d/m/Y') }}</div></div>
        </div>

        @if ($componentes->count() > 1)
            <div class="tabla" style="margin-top:18px;">
                <table>
                    <thead><tr><th>Componente</th><th>Serie</th><th>Vence</th></tr></thead>
                    <tbody>
                        @foreach ($componentes as $c)
                            <tr><td>{{ $c->equipoNombre() }}</td><td>{{ $c->no_serie ?: '—' }}</td><td>{{ $c->vence->format('d/m/Y') }}</td></tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        <div class="pw-acciones">
            <a class="btn" href="#reportar">Reportar una falla</a>
            <a class="btn ghost" href="{{ route('publico.garantia.poliza', $g->public_token) }}" target="_blank">Descargar póliza</a>
            @if ($con['telefono'])<a class="btn ghost" href="tel:{{ preg_replace('/[^0-9+]/', '', $con['telefono']) }}">Llamar a soporte</a>@endif
        </div>
    </div>

    @if ($tickets->isNotEmpty())
        <div class="pw-card">
            <h2>Mis reportes</h2>
            @foreach ($tickets as $t)
                @php $n = array_search($t->etapa, $etapas, true); @endphp
                <div class="ticket">
                    <div class="t">{{ $t->folio }} · {{ $t->abierta() ? $t->etapaLabel() : 'Atendido' }}</div>
                    <div class="s">Reportado el {{ $t->recibida_en->format('d/m/Y') }}{{ $t->cerrada_en ? ' · cerrado el '.$t->cerrada_en->format('d/m/Y') : '' }}</div>
                    <div class="pasos">
                        @foreach ($etapas as $i => $e)<span class="{{ ! $t->abierta() ? 'ok' : ($i <= $n ? 'on' : '') }}"></span>@endforeach
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    <div class="pw-card" id="reportar">
        <h2>Reportar una falla</h2>
        <form method="POST" action="{{ route('publico.garantia.reclamar', $g->public_token) }}" enctype="multipart/form-data">
            @csrf
            @if ($componentes->count() > 1)
                <label for="garantia_id">¿Qué componente falla?</label>
                <select id="garantia_id" name="garantia_id">
                    @foreach ($componentes as $c)<option value="{{ $c->id }}" @selected($c->id === $g->id)>{{ $c->equipoNombre() }}{{ $c->no_serie ? ' · '.$c->no_serie : '' }}</option>@endforeach
                </select>
            @endif
            <div class="grid2">
                <div><label for="contacto_nombre">Tu nombre *</label><input type="text" id="contacto_nombre" name="contacto_nombre" value="{{ old('contacto_nombre') }}" required maxlength="160"></div>
                <div><label for="contacto_telefono">Teléfono *</label><input type="tel" id="contacto_telefono" name="contacto_telefono" value="{{ old('contacto_telefono') }}" required maxlength="40"></div>
            </div>
            <label for="contacto_correo">Correo</label>
            <input type="email" id="contacto_correo" name="contacto_correo" value="{{ old('contacto_correo') }}" maxlength="160">
            <label for="sintomas">¿Qué está pasando? *</label>
            <textarea id="sintomas" name="sintomas" required minlength="10" maxlength="3000" placeholder="Describe la falla, desde cuándo ocurre y en qué momento del procedimiento.">{{ old('sintomas') }}</textarea>
            <label for="archivos">Fotos o video (opcional)</label>
            <input type="file" id="archivos" name="archivos[]" multiple accept="image/*,video/*,application/pdf">
            <label class="check"><input type="checkbox" name="es_seguridad" value="1" @checked(old('es_seguridad'))>
                <span>La falla afectó o pudo afectar a un paciente o a quien usaba el equipo.</span></label>
            <div class="pw-acciones"><button class="btn" type="submit">Enviar reporte</button></div>
            <p class="nota">Recibirás un número de seguimiento. Nuestro equipo de servicio te contactará para revisar el equipo.</p>
        </form>
    </div>

    <div class="pie">{{ config('medibuy.empresa.nombre') }}{{ $con['correo'] ? ' · '.$con['correo'] : '' }}</div>
</div>
</body>
</html>
