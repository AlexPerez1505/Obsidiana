@php
    $s = $solicitud;
    $url = route('commercial.solicitudes-web.show', $s);
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nueva solicitud web</title>
</head>
<body style="margin:0; padding:0; background:#f4f4f7; font-family:Arial, Helvetica, sans-serif; color:#1f2937;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f4f4f7; padding:32px 0;">
        <tr>
            <td align="center">
                <table role="presentation" width="520" cellpadding="0" cellspacing="0" style="background:#ffffff; border-radius:12px; overflow:hidden; box-shadow:0 1px 3px rgba(0,0,0,0.08);">
                    <tr>
                        <td style="background:#007aff; padding:24px 32px;">
                            <h1 style="margin:0; color:#ffffff; font-size:20px;">{{ config('app.name') }}</h1>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:32px;">
                            <p style="margin:0 0 12px; font-size:16px;">{{ $s->esContacto() ? 'Llegó un mensaje desde el formulario de contacto de la tienda en línea.' : 'Llegó una solicitud de cotización desde la tienda en línea.' }}</p>

                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f7f8fa; border-radius:10px; margin:0 0 20px;">
                                <tr><td style="padding:14px 18px; font-size:14px; color:#4b5563;">
                                    <div style="margin-bottom:6px;"><b style="color:#1f2937;">Folio:</b> {{ $s->folio }}</div>
                                    @if ($s->motivoTexto())<div style="margin-bottom:6px;"><b style="color:#1f2937;">Motivo:</b> {{ $s->motivoTexto() }}</div>@endif
                                    <div style="margin-bottom:6px;"><b style="color:#1f2937;">Nombre:</b> {{ $s->nombre }}{{ $s->empresa ? ' · '.$s->empresa : '' }}</div>
                                    <div style="margin-bottom:6px;"><b style="color:#1f2937;">Teléfono:</b> {{ $s->telefono }}</div>
                                    @if ($s->correo)<div style="margin-bottom:6px;"><b style="color:#1f2937;">Correo:</b> {{ $s->correo }}</div>@endif
                                    @if ($s->ciudad)<div style="margin-bottom:6px;"><b style="color:#1f2937;">Ciudad:</b> {{ $s->ciudad }}</div>@endif
                                    @if ($s->especialidad)<div style="margin-bottom:6px;"><b style="color:#1f2937;">Especialidad:</b> {{ $s->especialidad }}</div>@endif
                                    @if ($s->mensaje)<div><b style="color:#1f2937;">Mensaje:</b> {{ $s->mensaje }}</div>@endif
                                </td></tr>
                            </table>

                            @if (! empty($s->articulos))
                            <p style="margin:0 0 8px; font-size:14px; font-weight:bold;">Equipos solicitados</p>
                            <ul style="margin:0 0 24px; padding-left:18px; font-size:14px; color:#4b5563;">
                                @foreach ($s->articulos as $a)
                                    <li>{{ $a['cantidad'] }} × {{ $a['nombre'] }} {{ trim(($a['marca'] ?? '').' '.($a['modelo'] ?? '')) }}</li>
                                @endforeach
                            </ul>
                            @endif

                            <a href="{{ $url }}" style="display:inline-block; background:#007aff; color:#ffffff; text-decoration:none; padding:12px 22px; border-radius:8px; font-size:14px;">Ver en Obsidiana</a>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
