{{--
    Equipo que el cliente entrega a cuenta, en la pantalla de la cotización
    o la venta. Recibe $doc. Para el PDF hay una versión en _documento_pdf.
--}}
@php $equiposCuenta = $doc->equipos_a_cuenta ?? []; @endphp
@if (count($equiposCuenta))
    <div class="doc-cuenta">
        <div class="doc-cuenta-t">Equipo a cuenta</div>
        @foreach ($equiposCuenta as $r)
            <div class="doc-cuenta-fila">
                <div class="doc-cuenta-eq">{{ collect([$r['tipo'] ?? '', $r['subtipo'] ?? ''])->filter()->implode(' · ') ?: 'Equipo' }}</div>
                <div class="doc-cuenta-sub">
                    {{ trim(($r['marca'] ?? '').' '.($r['modelo'] ?? '')) ?: 'Sin marca ni modelo' }}
                    @if (! empty($r['no_serie'])) · Serie {{ $r['no_serie'] }} @endif
                </div>
            </div>
        @endforeach
    </div>

    @once
        <style>
            .doc-cuenta { margin-top:14px; padding-top:12px; border-top:1px dashed var(--border); }
            .doc-cuenta-t { font-size:12.5px; color:var(--muted); font-weight:600; margin-bottom:6px; }
            .doc-cuenta-fila { padding:8px 10px; border:1px solid var(--border); border-radius:10px; margin-top:6px; }
            .doc-cuenta-eq { font-weight:700; font-size:14px; overflow-wrap:anywhere; }
            .doc-cuenta-sub { color:var(--muted); font-size:13px; overflow-wrap:anywhere; }
        </style>
    @endonce
@endif
