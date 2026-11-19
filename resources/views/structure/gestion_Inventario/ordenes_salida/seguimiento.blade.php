@extends('layouts.dashboard')

@section('title', 'En qué va ' . $orden->folio)
@section('page-title', 'En qué va la salida')
@section('page-sub', 'Ventas > ' . ($orden->venta?->folio ?? $orden->folio) . ' > Seguimiento')

@push('head')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Caveat:wght@500;700&family=Patrick+Hand&display=swap" rel="stylesheet">
@endpush

@php
    use App\Models\OrdenSalidaEtapa as E;

    $venta = $orden->venta;
    $cliente = trim(($venta?->customer?->nombre ?? '').' '.($venta?->customer?->apellido ?? '')) ?: 'el cliente';
    $recien = request('recien');
    $hechas = $etapas->filter->terminada()->count();
    $entregada = $orden->estado === \App\Models\OrdenSalida::ENTREGADA;
    $actual = $etapas->first(fn ($e) => ! $e->terminada());
    // Avance del camino: 0, ½ entre estaciones… hasta 1 cuando ya se entregó.
    $avance = $entregada ? 1 : ($etapas->isEmpty() ? 0 : min(1, ($hechas + ($actual?->estado === E::EN_CURSO ? 0.5 : 0)) / max(1, $etapas->count())));
    $frases = [
        E::INGENIERIA => ['pendiente' => 'Esperando al ingeniero', 'en_curso' => 'Revisando el equipo…', 'terminada' => '¡Revisado y probado!'],
        E::EMPAQUE => ['pendiente' => 'Todavía no se empaca', 'en_curso' => 'Emplayando y empacando…', 'terminada' => '¡Bien empacado!'],
        E::ENTREGA => ['pendiente' => 'Esperando su salida', 'en_curso' => 'En camino…', 'terminada' => '¡Entregado!'],
    ];
    $porEtapa = $orden->evidencias->groupBy('etapa');
@endphp

@section('content')
    @if (session('status'))
        <x-ui.alert type="ok" style="margin-bottom:14px;">{{ session('status') }}</x-ui.alert>
    @endif

    <div class="sg-hoja">
        <div class="sg-top">
            <div>
                <div class="sg-folio">{{ $orden->folio }} · {{ $venta?->folio }}</div>
                <h1 class="sg-titulo">
                    @if ($entregada)
                        ¡El equipo de {{ $cliente }} ya se entregó!
                    @elseif ($actual)
                        El equipo de {{ $cliente }} va en <span class="sg-subraya">{{ mb_strtolower($actual->nombre()) }}</span>
                    @else
                        Equipo de {{ $cliente }}
                    @endif
                </h1>
                <div class="sg-asesor">Asesor: {{ $venta?->seller?->name ?? '—' }} · {{ $orden->items->count() }} partida(s)</div>
            </div>
            <div class="sg-acc">
                <a href="{{ route('salidas.expediente', $orden) }}" target="_blank" class="btn">PDF del expediente</a>
                @if ($puedeAbrirEtapas)
                    <a href="{{ route('inventory.salidas.show', $orden) }}" class="btn btn--ghost">Abrir la orden</a>
                @endif
                @if ($venta)
                    @can('ventas.ver')
                        <a href="{{ route('commercial.ventas.show', $venta) }}" class="btn btn--ghost">Ver venta</a>
                    @endcan
                @endif
            </div>
        </div>

        @if ($etapas->isEmpty())
            <p class="sg-nota">Esta orden se cerró antes de que existieran las etapas: estado {{ mb_strtolower($orden->estadoLabel()) }}
                @if ($orden->entregada_en) · salió el {{ $orden->entregada_en->format('d/m/Y H:i') }} @endif.</p>
        @else
            {{-- ===================== El camino ===================== --}}
            <div class="sg-camino" style="--avance: {{ $avance }}">
                <svg class="sg-ruta" viewBox="0 0 1000 120" preserveAspectRatio="none" aria-hidden="true">
                    <path class="sg-ruta-base" d="M20,70 C120,20 220,110 330,62 S520,20 600,66 S800,112 980,58" />
                    <path class="sg-ruta-hecha" pathLength="1" d="M20,70 C120,20 220,110 330,62 S520,20 600,66 S800,112 980,58" />
                </svg>

                <div class="sg-estaciones">
                    @foreach ($etapas as $e)
                        @php
                            $r = $e->resumenChecklist();
                            $ev = $porEtapa->get($e->etapa, collect());
                            $medios = $ev->map(fn ($x) => ['tipo' => $x->esVideo() ? 'video' : 'foto', 'url' => $x->url()])->values();
                        @endphp
                        <section class="sg-est is-{{ $e->estado }} {{ $recien === $e->etapa ? 'is-recien' : '' }} {{ $actual?->id === $e->id ? 'is-actual' : '' }}"
                                 style="--i: {{ $loop->index }}" @if ($medios->isNotEmpty()) data-medios="{{ $medios->toJson() }}" @endif>
                            <div class="sg-dibujo" aria-hidden="true">
                                @if ($e->etapa === E::INGENIERIA)
                                    {{-- Ingeniero: lupa y llave --}}
                                    <svg viewBox="0 0 120 100"><g class="sg-trazo">
                                        <circle cx="48" cy="42" r="22" /><path d="M64 58 L86 82 M82 86 l8 -8" />
                                        <path d="M40 34 q8 -8 16 0" /><path d="M90 20 l14 -6 m-10 18 l14 -2 m-18 -24 l4 -14" class="sg-chispas"/>
                                        <path d="M14 82 q30 6 60 0" />
                                    </g></svg>
                                @elseif ($e->etapa === E::EMPAQUE)
                                    {{-- Caja con cinta --}}
                                    <svg viewBox="0 0 120 100"><g class="sg-trazo">
                                        <path d="M22 38 L60 22 L98 38 L98 78 L60 94 L22 78 Z" /><path d="M22 38 L60 54 L98 38 M60 54 L60 94" />
                                        <path d="M41 30 L79 46" class="sg-cinta"/><path d="M8 50 q-4 10 4 16 M112 52 q4 10 -4 16" class="sg-chispas"/>
                                    </g></svg>
                                @else
                                    {{-- Camioncito --}}
                                    <svg viewBox="0 0 120 100"><g class="sg-trazo">
                                        <path d="M10 34 L70 34 L70 74 L10 74 Z" /><path d="M70 46 L92 46 L108 62 L108 74 L70 74" />
                                        <circle cx="30" cy="80" r="9" /><circle cx="90" cy="80" r="9" /><path d="M80 52 l10 0 l6 8" />
                                        <path d="M2 50 h-0 M-4 58 h6 M0 66 h4" class="sg-chispas"/>
                                    </g></svg>
                                @endif
                                @if ($e->terminada())
                                    <span class="sg-sello">{{ $e->etapa === E::ENTREGA ? '¡Entregado!' : '¡Listo!' }}</span>
                                @endif
                            </div>

                            <div class="sg-tarjeta">
                                <div class="sg-num">Etapa {{ $e->numero() }}</div>
                                <h2>{{ $e->nombre() }}</h2>
                                <p class="sg-frase">{{ $frases[$e->etapa][$e->estado] ?? $e->estadoLabel() }}</p>

                                <dl class="sg-datos">
                                    <div><dt>Responsable</dt><dd>{{ $e->asignadaA?->name ?? 'Sin asignar' }}</dd></div>
                                    @if ($e->terminada())
                                        <div><dt>Terminó</dt><dd>{{ $e->terminadaPor?->name }} · {{ $e->terminada_en?->format('d/m/Y H:i') }}</dd></div>
                                    @elseif ($e->iniciada_en)
                                        <div><dt>Comenzó</dt><dd>{{ $e->iniciada_en->format('d/m/Y H:i') }}</dd></div>
                                    @endif
                                    @if ($r['total'])
                                        <div><dt>Checklist</dt><dd>
                                            <span class="sg-ok">✓ {{ $r['si'] }}</span>
                                            @if ($r['no'])<span class="sg-mal">✗ {{ $r['no'] }}</span>@endif
                                            @if ($r['na'])<span class="sg-na">N/A {{ $r['na'] }}</span>@endif
                                            <span class="sg-de">de {{ $r['total'] }}</span>
                                        </dd></div>
                                    @endif
                                    @if ($e->etapa === E::ENTREGA && ! empty($e->datos['modalidad']))
                                        <div><dt>Cómo</dt><dd>{{ E::MODALIDADES[$e->datos['modalidad']] ?? '' }}
                                            @if (! empty($e->datos['guia'])) · {{ $e->datos['paqueteria'] ?? '' }} guía {{ $e->datos['guia'] }} @endif</dd></div>
                                    @endif
                                </dl>

                                @if ($e->notas)
                                    <p class="sg-notas">“{{ \Illuminate\Support\Str::limit($e->notas, 160) }}”</p>
                                @endif

                                <div class="sg-pie">
                                    @if ($medios->isNotEmpty())
                                        <button type="button" class="sg-fotos" data-galeria data-indice="0">📷 {{ $medios->count() }} de evidencia</button>
                                    @else
                                        <span class="sg-sin">Sin evidencia aún</span>
                                    @endif
                                    @if ($puedeAbrirEtapas)
                                        <a href="{{ route('inventory.salidas.etapas.show', [$orden, $e->etapa]) }}" class="sg-abrir">Abrir →</a>
                                    @endif
                                </div>
                            </div>
                        </section>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- ===================== Bitácora ===================== --}}
        <div class="sg-bitacora">
            <h3>Lo que ha pasado</h3>
            @forelse ($bitacora as $b)
                <div class="sg-evento" style="--i: {{ $loop->index }}">
                    <span class="sg-cuando">{{ $b->created_at?->format('d/m H:i') }}</span>
                    <span class="sg-que">{{ $b->descripcion }}</span>
                </div>
            @empty
                <p class="sg-nota">Todavía no hay movimientos.</p>
            @endforelse
        </div>
    </div>

    @include('partials.galeria')

    <style>
        .sg-hoja { --tinta: var(--text); --lapiz: color-mix(in srgb, var(--text) 55%, transparent); --verde: #16a34a; --azul: var(--primary, #2563eb);
                   position:relative; padding:26px 26px 30px; border:1px solid var(--border); border-radius:18px; background-color:var(--surface);
                   background-image:radial-gradient(color-mix(in srgb, var(--text) 9%, transparent) 1px, transparent 1.2px); background-size:22px 22px;
                   font-family:'Patrick Hand', 'Comic Sans MS', system-ui, sans-serif; color:var(--tinta); overflow:hidden; }
        .sg-top { display:flex; justify-content:space-between; gap:12px 20px; flex-wrap:wrap; align-items:flex-start; }
        .sg-acc { display:flex; gap:8px; flex-wrap:wrap; font-family:system-ui, sans-serif; }
        .sg-folio { font-size:15px; color:var(--muted); letter-spacing:.04em; }
        .sg-titulo { margin:2px 0 4px; font-family:'Caveat', cursive; font-size:clamp(30px, 4.4vw, 44px); line-height:1.05; font-weight:700; }
        .sg-subraya { position:relative; white-space:nowrap; color:var(--azul); }
        .sg-subraya::after { content:''; position:absolute; left:-2%; right:-2%; bottom:2px; height:10px; border-radius:50%;
                             border-bottom:3px solid var(--azul); transform:rotate(-1.5deg); clip-path:inset(0 100% 0 0); animation:sg-revela .9s .5s ease-out forwards; }
        .sg-asesor { color:var(--muted); font-size:17px; }
        .sg-nota { color:var(--muted); font-size:18px; }

        /* El camino */
        .sg-camino { position:relative; margin-top:22px; }
        .sg-ruta { position:absolute; left:0; right:0; top:58px; width:100%; height:120px; overflow:visible; pointer-events:none; }
        .sg-ruta path { fill:none; stroke-linecap:round; }
        .sg-ruta-base { stroke:var(--lapiz); stroke-width:3; stroke-dasharray:2 11; }
        .sg-ruta-hecha { stroke:var(--verde); stroke-width:5; stroke-dasharray:1; stroke-dashoffset:1; animation:sg-camina 1.8s .3s ease-in-out forwards; }
        @keyframes sg-camina { to { stroke-dashoffset:calc(1 - var(--avance)); } }

        .sg-estaciones { position:relative; display:grid; grid-template-columns:repeat(3, minmax(0,1fr)); gap:18px; }
        .sg-est { display:flex; flex-direction:column; align-items:center; gap:10px; opacity:0; transform:translateY(10px);
                  animation:sg-entra .6s calc(.25s + var(--i) * .25s) ease-out forwards; }
        @keyframes sg-entra { to { opacity:1; transform:none; } }

        .sg-dibujo { position:relative; width:150px; height:125px; display:grid; place-items:center;
                     background:var(--surface); border-radius:50% 46% 52% 48% / 48% 52% 46% 54%; border:2.5px solid var(--lapiz); }
        .sg-est.is-actual .sg-dibujo { border-color:var(--azul); animation:sg-tiembla .5s 1.4s ease-in-out 3; }
        .sg-est.is-terminada .sg-dibujo { border-color:var(--verde); }
        @keyframes sg-tiembla { 0%,100% { transform:rotate(0); } 25% { transform:rotate(-3deg); } 75% { transform:rotate(3deg); } }
        .sg-dibujo svg { width:104px; height:88px; overflow:visible; }
        .sg-trazo * { fill:none; stroke:var(--tinta); stroke-width:3.2; stroke-linecap:round; stroke-linejoin:round;
                      stroke-dasharray:400; stroke-dashoffset:400; animation:sg-dibuja 1.6s calc(.4s + var(--i) * .3s) ease-out forwards; }
        .sg-est.is-pendiente .sg-trazo * { stroke:var(--lapiz); }
        .sg-est.is-en_curso .sg-trazo * { stroke:var(--azul); }
        .sg-est.is-terminada .sg-trazo * { stroke:var(--verde); }
        .sg-trazo .sg-chispas { stroke-width:2.4; }
        .sg-est.is-pendiente .sg-chispas { display:none; }
        .sg-trazo .sg-cinta { stroke:#d97706 !important; stroke-width:5; }
        @keyframes sg-dibuja { to { stroke-dashoffset:0; } }
        @keyframes sg-revela { to { clip-path:inset(0 0 0 0); } }

        .sg-sello { position:absolute; right:-18px; top:-10px; padding:2px 10px; border:3px solid var(--verde); border-radius:10px;
                    color:var(--verde); background:var(--surface); font-family:'Caveat', cursive; font-weight:700; font-size:22px; transform:rotate(12deg) scale(0);
                    animation:sg-sella .45s calc(1.6s + var(--i) * .3s) cubic-bezier(.3,1.6,.5,1) forwards; }
        .sg-est.is-recien .sg-sello { animation-delay:.9s; animation-duration:.6s; font-size:26px; }
        @keyframes sg-sella { to { transform:rotate(12deg) scale(1); } }

        .sg-tarjeta { width:100%; padding:14px 16px; background:var(--surface); border:2px solid var(--lapiz);
                      border-radius:255px 15px 225px 15px / 15px 225px 15px 255px; }
        .sg-est.is-actual .sg-tarjeta { border-color:var(--azul); }
        .sg-est.is-terminada .sg-tarjeta { border-color:var(--verde); }
        .sg-num { color:var(--muted); font-size:15px; }
        .sg-tarjeta h2 { margin:0; font-family:'Caveat', cursive; font-size:30px; line-height:1; }
        .sg-frase { margin:2px 0 8px; font-size:19px; color:var(--azul); }
        .sg-est.is-terminada .sg-frase { color:var(--verde); }
        .sg-est.is-pendiente .sg-frase { color:var(--muted); }
        .sg-datos { margin:0; display:flex; flex-direction:column; gap:4px; font-size:16px; }
        .sg-datos div { display:flex; gap:8px; flex-wrap:wrap; }
        .sg-datos dt { color:var(--muted); min-width:92px; }
        .sg-datos dd { margin:0; }
        .sg-ok { color:var(--verde); font-weight:700; margin-right:6px; } .sg-mal { color:var(--danger, #dc2626); font-weight:700; margin-right:6px; }
        .sg-na { color:var(--muted); margin-right:6px; } .sg-de { color:var(--muted); }
        .sg-notas { margin:8px 0 0; font-size:16px; color:var(--muted); }
        .sg-pie { display:flex; justify-content:space-between; align-items:center; gap:8px; margin-top:10px; padding-top:8px; border-top:2px dashed var(--border); }
        .sg-fotos { padding:0; border:0; background:none; color:var(--azul); font:inherit; font-size:17px; cursor:pointer; text-decoration:underline wavy; text-underline-offset:4px; }
        .sg-sin { color:var(--muted); font-size:16px; }
        .sg-abrir { color:var(--azul); font-size:17px; text-decoration:none; }

        /* Bitácora */
        .sg-bitacora { margin-top:28px; }
        .sg-bitacora h3 { margin:0 0 8px; font-family:'Caveat', cursive; font-size:30px; }
        .sg-evento { display:flex; gap:14px; padding:6px 0 6px 18px; border-left:3px dashed var(--lapiz); position:relative; font-size:17px;
                     opacity:0; animation:sg-entra .4s calc(.6s + var(--i) * .06s) ease-out forwards; }
        .sg-evento::before { content:''; position:absolute; left:-8px; top:12px; width:12px; height:12px; border-radius:50%; background:var(--surface); border:2.5px solid var(--azul); }
        .sg-cuando { color:var(--muted); white-space:nowrap; min-width:80px; }

        @media (max-width:820px) {
            .sg-ruta { display:none; }
            .sg-estaciones { grid-template-columns:minmax(0,1fr); gap:22px; }
            .sg-est { flex-direction:row; align-items:flex-start; }
            .sg-dibujo { width:96px; height:84px; flex:0 0 96px; }
            .sg-dibujo svg { width:70px; height:60px; }
            .sg-sello { font-size:17px; right:-10px; }
            .sg-hoja { padding:18px 14px 22px; }
        }
        @media (prefers-reduced-motion: reduce) {
            .sg-hoja *, .sg-hoja *::after { animation-duration:.01s !important; animation-delay:0s !important; }
        }
    </style>
@endsection
