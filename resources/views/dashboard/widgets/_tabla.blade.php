{{--
    Tabla que aparece solo cuando la tarjeta se hace grande, para que no
    quede un número solo en medio de mucho espacio.

    Espera $filas: [['etiqueta' => .., 'valor' => .., 'extra' => .., 'alerta' => bool], ...]
--}}

@if (! empty($filas))
    <table class="dw-tabla">
        <tbody>
            @foreach ($filas as $fila)
                <tr>
                    <td class="dw-tabla-e">{{ $fila['etiqueta'] }}</td>
                    @if (isset($fila['extra']))
                        <td class="dw-tabla-x">{{ $fila['extra'] }}</td>
                    @endif
                    <td class="dw-tabla-v {{ ! empty($fila['alerta']) ? 'es-alerta' : '' }}">{{ $fila['valor'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endif
