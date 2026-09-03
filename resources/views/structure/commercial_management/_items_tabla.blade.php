{{--
    Tabla de equipo de un documento comercial, con su imagen.

    La usan la cotización y la venta, para que las dos se vean igual.
    Espera $items.
--}}

<div class="doc-tabla-wrap">
    <table class="doc-tabla">
        <thead>
            <tr>
                <th style="width:56px;"></th>
                <th>Equipo</th>
                <th>Marca</th>
                <th class="c">Cant.</th>
                <th class="r">P. unitario</th>
                <th class="r">Sobreprecio</th>
                <th class="r">Importe</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($items as $it)
                <tr>
                    <td>
                        <span class="doc-img">
                            @if ($it->imagen)
                                <img src="{{ $it->imagen }}" alt="{{ $it->nombre }}" loading="lazy">
                            @else
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><path d="m3.3 7 8.7 5 8.7-5M12 22V12"/></svg>
                            @endif
                        </span>
                    </td>
                    <td>
                        <div class="doc-nom">{{ $it->nombre }}</div>
                        @if ($it->modelo)
                            <div class="doc-det">Modelo {{ $it->modelo }}</div>
                        @endif
                        @if (! empty($it->no_series))
                            <div class="doc-det">No. Serie: {{ $it->no_series }}</div>
                        @endif
                    </td>
                    <td>{{ $it->marca ?: '—' }}</td>
                    <td class="c">{{ $it->cantidad }}</td>
                    <td class="r">
                        @if ($it->es_regalo)
                            <span class="doc-regalo">Regalo</span>
                        @else
                            ${{ number_format($it->precio_unitario, 2) }}
                        @endif
                    </td>
                    <td class="r">{{ $it->sobreprecio > 0 ? '$' . number_format($it->sobreprecio, 2) : '—' }}</td>
                    <td class="r"><b>@if ($it->es_regalo)—@else ${{ number_format($it->importe(), 2) }} @endif</b></td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
