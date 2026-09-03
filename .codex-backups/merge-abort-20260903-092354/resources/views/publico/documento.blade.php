<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    {{-- Es un documento privado por enlace: que no lo indexe ningún buscador. --}}
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $tipo }} {{ $doc->folio }} · Grupo MediBuy</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --bg:#f5f7fa; --surface:#fff; --surface-2:#f7f8fa;
            --text:#333; --muted:#888; --border:#ebebeb;
            --primary:#007aff; --green:#15803d; --green-soft:#e6ffe6;
        }
        * { box-sizing:border-box; }
        body { margin:0; padding:0; background:var(--bg); color:var(--text);
               font-family:Quicksand, system-ui, sans-serif; font-size:15px; line-height:1.5; }

        .pw { max-width:820px; margin:0 auto; padding:24px 16px 48px; }

        .pw-head { display:flex; align-items:center; gap:14px; flex-wrap:wrap;
                   padding:22px 24px; margin-bottom:16px;
                   background:var(--surface); border:1px solid var(--border); border-radius:14px;
                   border-top:3px solid var(--primary); }
        .pw-marca { font-size:20px; font-weight:700; }
        .pw-marca small { display:block; color:var(--muted); font-size:10.5px;
                          font-weight:500; letter-spacing:1.2px; }
        .pw-folio { margin-left:auto; text-align:right; }
        .pw-folio .t { color:var(--muted); font-size:11px; text-transform:uppercase; letter-spacing:1px; }
        .pw-folio .n { color:var(--primary); font-size:19px; font-weight:700; }
        .pw-folio .f { color:var(--muted); font-size:12.5px; }

        .pw-card { padding:22px 24px; margin-bottom:16px;
                   background:var(--surface); border:1px solid var(--border); border-radius:14px; }
        .pw-card h2 { margin:0 0 16px; padding-bottom:12px; border-bottom:1px solid var(--border);
                      font-size:15px; font-weight:700; }

        .pw-datos { display:grid; grid-template-columns:repeat(auto-fit,minmax(180px,1fr)); gap:16px; }
        .pw-dato .k { color:var(--muted); font-size:12.5px; }
        .pw-dato .v { margin-top:2px; font-size:15px; font-weight:600; overflow-wrap:anywhere; }

        table { width:100%; border-collapse:collapse; }
        th { padding:9px 10px; background:var(--surface-2); color:var(--muted);
             font-size:11px; font-weight:600; text-transform:uppercase; letter-spacing:.4px; text-align:left; }
        td { padding:11px 10px; border-bottom:1px solid var(--border); font-size:14px; }
        tr:last-child td { border-bottom:0; }
        .r { text-align:right; } .c { text-align:center; }
        .regalo { color:var(--green); font-weight:700; font-size:12px; }

        .pw-tot { margin-left:auto; width:min(320px,100%); }
        .pw-tot div { display:flex; justify-content:space-between; gap:16px; padding:7px 0; font-size:14px; }
        .pw-tot .grande { margin-top:6px; padding-top:12px; border-top:2px solid var(--primary);
                          font-size:18px; font-weight:700; }

        .pw-nota { padding:13px 15px; background:var(--surface-2);
                   border:1px solid var(--border); border-radius:10px; font-size:14px; }

        .pw-btns { display:flex; gap:10px; flex-wrap:wrap; margin-bottom:16px; }
        .pw-btn { display:inline-flex; align-items:center; gap:8px; padding:11px 18px;
                  border:1px solid var(--primary); border-radius:10px; background:var(--primary);
                  color:#fff; font-family:inherit; font-size:14px; font-weight:600; text-decoration:none; }
        .pw-btn:hover { background:#0062cc; border-color:#0062cc; }
        .pw-btn--ghost { background:var(--surface); color:var(--text); border-color:var(--border); }
        .pw-btn--ghost:hover { background:var(--surface-2); }
        .pw-btn svg { width:16px; height:16px; }

        .pw-pie { margin-top:26px; color:var(--muted); font-size:12.5px; text-align:center; line-height:1.7; }

        .pw-chip { display:inline-flex; align-items:center; gap:6px; padding:3px 11px;
                   border-radius:999px; background:var(--green-soft); color:var(--green);
                   font-size:12px; font-weight:600; }

        .pw-barra { margin-top:16px; height:6px; border-radius:3px; background:var(--surface-2); overflow:hidden; }
        .pw-barra span { display:block; height:100%; background:var(--green); }
        .pw-avance { margin:8px 0 0; color:var(--muted); font-size:12.5px; }

        .pw-est { display:inline-block; padding:2px 9px; border-radius:999px;
                  font-size:11.5px; font-weight:600; white-space:nowrap; }
        .pw-est.es-pagado { background:var(--green-soft); color:var(--green); }
        .pw-est.es-parcial { background:#fff3e8; color:#b45309; }
        .pw-est.es-vencido { background:#ffebeb; color:#cc4b4b; }
        .pw-est.es-pendiente { background:var(--surface-2); color:var(--muted); }

        .pw-link { color:var(--primary); font-size:13px; font-weight:600; text-decoration:none; white-space:nowrap; }
        .pw-link:hover { text-decoration:underline; }

        .pw-img { display:flex; align-items:center; justify-content:center; width:44px; height:44px;
                  border:1px solid var(--border); border-radius:9px; background:var(--surface-2);
                  color:var(--muted); overflow:hidden; }
        .pw-img img { width:100%; height:100%; object-fit:cover; }
        .pw-img svg { width:19px; height:19px; }

        @media (max-width:560px) {
            .pw-folio { margin-left:0; text-align:left; width:100%; }
            .pw-btn { flex:1; justify-content:center; }
            .pw-tabla-wrap { overflow-x:auto; }
            .pw-tabla-wrap table { min-width:520px; }
        }
    </style>
</head>
<body>
<div class="pw">
    <div class="pw-head">
        <div class="pw-marca">Grupo MediBuy <small>EQUIPO MÉDICO</small></div>
        <div class="pw-folio">
            <div class="t">{{ $tipo }}</div>
            <div class="n">{{ $doc->folio }}</div>
            <div class="f">{{ $doc->created_at?->format('d/m/Y') }}</div>
        </div>
    </div>

    <div class="pw-btns">
        <a href="{{ $rutaPdf }}" class="pw-btn" target="_blank" rel="noopener">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="m7 10 5 5 5-5"/><path d="M12 15V3"/></svg>
            Descargar el PDF
        </a>
        <a href="https://wa.me/?text={{ urlencode($tipo . ' ' . $doc->folio . ' — ' . url()->current()) }}"
           class="pw-btn pw-btn--ghost" target="_blank" rel="noopener">Compartir</a>
    </div>

    <div class="pw-card">
        <h2>Datos generales</h2>
        <div class="pw-datos">
            <div class="pw-dato">
                <div class="k">Cliente</div>
                <div class="v">{{ trim(($doc->customer->nombre ?? '') . ' ' . ($doc->customer->apellido ?? '')) ?: '—' }}</div>
            </div>
            <div class="pw-dato">
                <div class="k">Modalidad</div>
                <div class="v">
                    {{ ucfirst($doc->modalidad) }}@if ($doc->modalidad === 'financiamiento') · {{ $doc->num_meses }} meses @endif
                </div>
            </div>
            <div class="pw-dato">
                <div class="k">Atendió</div>
                <div class="v">{{ $doc->seller?->name ?: '—' }}</div>
            </div>
            @if ($doc->lugar_propuesta)
                <div class="pw-dato">
                    <div class="k">Congreso</div>
                    <div class="v">{{ $doc->lugar_propuesta }}</div>
                </div>
            @endif
        </div>
    </div>

    <div class="pw-card">
        <h2>Equipo</h2>
        <div class="pw-tabla-wrap">
            <table>
                <thead>
                    <tr>
                        <th style="width:56px;"></th>
                        <th>Equipo</th><th>Marca</th>
                        <th class="c">Cant.</th><th class="r">Importe</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($doc->items as $it)
                        <tr>
                            <td>
                                <span class="pw-img">
                                    @if ($it->imagen)
                                        <img src="{{ $it->imagen }}" alt="{{ $it->nombre }}" loading="lazy">
                                    @else
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><path d="m3.3 7 8.7 5 8.7-5M12 22V12"/></svg>
                                    @endif
                                </span>
                            </td>
                            <td>
                                <div style="font-weight:600;">{{ $it->nombre }}</div>
                                @if ($it->modelo)
                                    <div style="color:var(--muted); font-size:12.5px;">Modelo {{ $it->modelo }}</div>
                                @endif
                            </td>
                            <td>{{ $it->marca ?: '—' }}</td>
                            <td class="c">{{ $it->cantidad }}</td>
                            <td class="r">
                                @if ($it->es_regalo)
                                    <span class="regalo">REGALO</span>
                                @else
                                    ${{ number_format($it->importe(), 2) }}
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="pw-tot">
            <div><span>Subtotal</span><b>${{ number_format($doc->subtotal, 2) }}</b></div>
            @if ($doc->descuento_monto > 0)
                <div><span>Descuento</span><b>-${{ number_format($doc->descuento_monto, 2) }}</b></div>
            @endif
            @if ($doc->envio > 0)
                <div><span>Envío</span><b>${{ number_format($doc->envio, 2) }}</b></div>
            @endif
            @if ($doc->aplica_iva)
                <div><span>IVA (16%)</span><b>${{ number_format($doc->iva_monto, 2) }}</b></div>
            @endif
            <div class="grande"><span>Total</span><b>${{ number_format($doc->total, 2) }}</b></div>
        </div>
    </div>

    {{-- Estado de cobranza: solo tiene sentido en una venta. --}}
    @if (! empty($esVenta))
        <div class="pw-card">
            <h2>Estado de tu cuenta</h2>

            <div class="pw-datos">
                <div class="pw-dato">
                    <div class="k">Total</div>
                    <div class="v">${{ number_format($doc->montoExigible(), 2) }}</div>
                </div>
                <div class="pw-dato">
                    <div class="k">Pagado</div>
                    <div class="v" style="color:var(--green);">${{ number_format($doc->totalCobrado(), 2) }}</div>
                </div>
                <div class="pw-dato">
                    <div class="k">Saldo</div>
                    <div class="v">${{ number_format($doc->saldo(), 2) }}</div>
                </div>
            </div>

            <div class="pw-barra"><span style="width:{{ $doc->avance() }}%"></span></div>
            <p class="pw-avance">{{ $doc->estadoPagoLabel() }} · {{ $doc->avance() }}% cubierto</p>
        </div>
    @endif

    @if ($doc->pagos->count())
        <div class="pw-card">
            <h2>{{ ! empty($esVenta) ? 'Calendario de pagos' : 'Plan de pagos' }}</h2>
            <div class="pw-tabla-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Pago</th><th>Fecha</th>
                            @if (! empty($esVenta))<th>Estado</th>@endif
                            <th class="r">Monto</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($doc->pagos as $p)
                            <tr>
                                <td>{{ $p->nombre }}</td>
                                <td>{{ optional($p->fecha)->format('d/m/Y') ?: '—' }}</td>
                                @if (! empty($esVenta))
                                    <td><span class="pw-est es-{{ $p->estado() }}">{{ $p->estadoLabel() }}</span></td>
                                @endif
                                <td class="r">${{ number_format($p->monto, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    {{-- Recibos que el cliente puede descargar solo. --}}
    @if (! empty($esVenta) && $doc->cobros->count())
        <div class="pw-card">
            <h2>Tus pagos</h2>
            <div class="pw-tabla-wrap">
                <table>
                    <thead>
                        <tr><th>Recibo</th><th>Fecha</th><th>Método</th><th class="r">Monto</th><th></th></tr>
                    </thead>
                    <tbody>
                        @foreach ($doc->cobros as $c)
                            <tr>
                                <td>{{ $c->folio }}</td>
                                <td>{{ $c->fecha->format('d/m/Y') }}</td>
                                <td>{{ $c->metodoLabel() }}</td>
                                <td class="r">${{ number_format((float) $c->monto, 2) }}</td>
                                <td class="r">
                                    <a href="{{ route('publico.venta.recibo', [$doc->public_token, $c->id]) }}"
                                       target="_blank" rel="noopener" class="pw-link">Recibo</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    @if ($doc->fichas->isNotEmpty())
        <div class="pw-card">
            <h2>Fichas técnicas</h2>
            @foreach ($doc->fichas as $f)
                <div style="padding:9px 0; border-bottom:1px solid var(--border);">
                    {{ $f->titulo }}
                    @if ($f->archivo)<span class="pw-chip">Incluida en el PDF</span>@endif
                </div>
            @endforeach
        </div>
    @endif

    @if ($doc->nota_cliente)
        <div class="pw-card">
            <h2>Nota</h2>
            <div class="pw-nota">{{ $doc->nota_cliente }}</div>
        </div>
    @endif

    <p class="pw-pie">
        Grupo MediBuy · Equipo médico<br>
        Este documento es informativo y no constituye un compromiso de compra. Precios en MXN.
    </p>
</div>
</body>
</html>
