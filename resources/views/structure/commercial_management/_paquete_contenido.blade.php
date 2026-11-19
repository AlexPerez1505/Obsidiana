{{--
    Lo que trae un renglón de paquete (cotización, venta, PDF y consulta pública).
    Espera $it (renglón). Opcional $pdf = true para la versión de dompdf.
    Se lee del paquete tal como está ahora y se multiplica por la cantidad.
--}}
@if ($it->tipo_item === 'paquete' && $it->paquete_id && ($paq = $it->paquete))
    @php($lineas = $paq->contenido((int) $it->cantidad))
    @if (! empty($lineas))
        @if (! empty($pdf))
            <div class="det" style="margin-top:3px;"><b>Incluye:</b></div>
            @foreach ($lineas as $l)
                <div class="det">&bull; {{ $l['cantidad'] }}&times; {{ trim($l['nombre'].' '.($l['marca'] ?? '').' '.($l['modelo'] ?? '')) }}</div>
            @endforeach
        @else
            <details class="paq-inc" style="margin-top:4px;">
                <summary style="cursor:pointer; font-size:12.5px; color:var(--primary); font-weight:600;">Incluye {{ count($lineas) }} {{ count($lineas) === 1 ? 'artículo' : 'artículos' }}</summary>
                <ul style="margin:6px 0 0; padding-left:16px; font-size:12.5px; color:var(--muted); line-height:1.6;">
                    @foreach ($lineas as $l)
                        <li><b style="color:var(--text);">{{ $l['cantidad'] }}×</b> {{ $l['nombre'] }}{{ $l['marca'] ? ' · '.$l['marca'] : '' }}{{ $l['modelo'] ? ' · '.$l['modelo'] : '' }}</li>
                    @endforeach
                </ul>
            </details>
        @endif
    @endif
@endif
