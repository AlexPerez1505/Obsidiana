{{--
    Las tres etapas de la orden (Ingeniería → Empaque → Entrega) con su
    responsable. Quien asigna elige a las tres personas de una vez; a cada
    una le llega su aviso. Recibe $orden, $etapas, $preparadores, $puedeAsignar.
--}}
@php
    $tonos = ['pendiente' => '', 'en_curso' => 'badge--info', 'terminada' => 'badge--ok'];
    $iconos = [
        'ingenieria' => '<path d="M14.7 6.3a4 4 0 0 0-5.4 5.4L3 18l3 3 6.3-6.3a4 4 0 0 0 5.4-5.4l-2.5 2.5-2.4-.6-.6-2.4z"/>',
        'empaque' => '<path d="M21 8 12 3 3 8v8l9 5 9-5z"/><path d="M3 8l9 5 9-5M12 13v8"/>',
        'entrega' => '<path d="M3 7h11v9H3zM14 10h4l3 3v3h-7z"/><circle cx="7" cy="18" r="1.8"/><circle cx="17" cy="18" r="1.8"/>',
    ];
@endphp
@if ($etapas->isNotEmpty())
<x-ui.card class="ose">
    <div class="ose-cab">
        <div>
            <x-ui.section-title style="margin:0;">Etapas de la salida</x-ui.section-title>
            <p class="muted ose-sub">Cada etapa la hace quien se asigne, con su checklist, evidencia y firma. Empieza cuando termina la anterior.</p>
        </div>
        <div class="ose-links">
            <a href="{{ route('salidas.seguimiento', $orden) }}" class="btn btn--ghost">Ver en qué va</a>
            <a href="{{ route('salidas.expediente', $orden) }}" target="_blank" class="btn btn--ghost">PDF del expediente</a>
        </div>
    </div>

    <form method="POST" action="{{ route('inventory.salidas.etapas.asignar', $orden) }}" class="ose-grid">
        @csrf
        @foreach ($etapas as $e)
            <div class="ose-etapa is-{{ $e->estado }}">
                <div class="ose-et-top">
                    <span class="ose-ico" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">{!! $iconos[$e->etapa] !!}</svg></span>
                    <div class="ose-et-nom">
                        <small>Etapa {{ $e->numero() }}</small>
                        <b>{{ $e->nombre() }}</b>
                    </div>
                    <span class="badge {{ $tonos[$e->estado] ?? '' }}">{{ $e->estadoLabel() }}</span>
                </div>

                @if ($puedeAsignar && ! $orden->cerrada() && ! $e->terminada())
                    <label class="ose-k" for="ose-{{ $e->etapa }}">Responsable</label>
                    <select name="etapas[{{ $e->etapa }}]" id="ose-{{ $e->etapa }}" data-no-mayus>
                        <option value="">— Sin responsable —</option>
                        @foreach ($preparadores as $p)
                            <option value="{{ $p->id }}" @selected((int) $e->asignada_a === $p->id)>{{ $p->name }}</option>
                        @endforeach
                    </select>
                @else
                    <div class="ose-k">Responsable</div>
                    <div class="ose-v">{{ $e->asignadaA?->name ?? 'Sin responsable' }}</div>
                @endif

                <div class="ose-meta">
                    @if ($e->terminada())
                        Terminó {{ $e->terminadaPor?->name }} · {{ $e->terminada_en?->format('d/m H:i') }}
                    @elseif ($e->estado === 'en_curso')
                        En proceso desde {{ $e->iniciada_en?->format('d/m H:i') }}
                    @else
                        Sin empezar
                    @endif
                </div>

                <a href="{{ route('inventory.salidas.etapas.show', [$orden, $e->etapa]) }}" class="ose-abrir">
                    {{ $e->terminada() ? 'Ver etapa' : 'Abrir etapa' }} →
                </a>
            </div>
        @endforeach

        @if ($puedeAsignar && ! $orden->cerrada())
            <div class="ose-pie">
                <span class="muted">A cada responsable le llega un aviso en el sistema, por correo y WhatsApp.</span>
                <button type="submit" class="btn">Guardar responsables</button>
            </div>
        @endif
    </form>
</x-ui.card>

@once
    <style>
        .ose { margin-bottom:18px; }
        .ose-cab { display:flex; justify-content:space-between; align-items:flex-start; gap:10px 16px; flex-wrap:wrap; }
        .ose-sub { margin:4px 0 0; font-size:13px; }
        .ose-links { display:flex; gap:8px; flex-wrap:wrap; }
        .ose-grid { display:grid; grid-template-columns:repeat(3, minmax(0,1fr)); gap:12px; margin-top:14px; }
        @media (max-width:900px) { .ose-grid { grid-template-columns:minmax(0,1fr); } }
        .ose-etapa { display:flex; flex-direction:column; gap:6px; padding:14px; border:1px solid var(--border); border-radius:14px; background:var(--surface); min-width:0; }
        .ose-etapa.is-en_curso { border-color:color-mix(in srgb, var(--primary) 45%, var(--border)); }
        .ose-etapa.is-terminada { background:color-mix(in srgb, #16a34a 6%, var(--surface)); }
        .ose-et-top { display:flex; align-items:center; gap:10px; margin-bottom:4px; }
        .ose-ico { width:36px; height:36px; flex:0 0 36px; border-radius:10px; display:grid; place-items:center; background:var(--surface-2); color:var(--text); }
        .ose-etapa.is-terminada .ose-ico { background:#16a34a; color:#fff; }
        .ose-ico svg { width:20px; height:20px; }
        .ose-et-nom { flex:1; min-width:0; display:flex; flex-direction:column; }
        .ose-et-nom small { color:var(--muted); font-size:11.5px; }
        .ose-et-nom b { font-size:14.5px; }
        .ose-k { color:var(--muted); font-size:12px; }
        .ose-v { font-weight:700; }
        .ose-etapa select { width:100%; }
        .ose-meta { color:var(--muted); font-size:12px; }
        .ose-abrir { margin-top:auto; padding-top:6px; color:var(--primary); font-weight:600; font-size:13.5px; text-decoration:none; }
        .ose-abrir:hover { text-decoration:underline; }
        .ose-pie { grid-column:1 / -1; display:flex; justify-content:space-between; align-items:center; gap:10px; flex-wrap:wrap; font-size:12.5px; }
    </style>
@endonce
@endif
