@php
    $s = $seguimiento;
    $c = $s->customer;
    $cliente = trim(($c->nombre ?? '').' '.($c->apellido ?? '')) ?: 'Cliente';
    $url = route('commercial.clientes.show', $c->id).'#seguimientos';
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recordatorio de seguimiento</title>
</head>
<body style="margin:0; padding:0; background:#f4f4f7; font-family:Arial, Helvetica, sans-serif; color:#1f2937;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f4f4f7; padding:32px 0;">
        <tr>
            <td align="center">
                <table role="presentation" width="480" cellpadding="0" cellspacing="0" style="background:#ffffff; border-radius:12px; overflow:hidden; box-shadow:0 1px 3px rgba(0,0,0,0.08);">
                    <tr>
                        <td style="background:#007aff; padding:24px 32px;">
                            <h1 style="margin:0; color:#ffffff; font-size:20px;">{{ config('app.name') }}</h1>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:32px;">
                            <p style="margin:0 0 12px; font-size:16px;">Hola {{ $name }},</p>
                            <p style="margin:0 0 20px; font-size:15px; color:#4b5563;">
                                {{ $s->vencido() ? 'Tienes un seguimiento vencido' : 'Hoy toca un seguimiento' }} con
                                <b>{{ $cliente }}</b>{{ $c->esProspecto() ? ' (prospecto)' : '' }}.
                            </p>

                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f7f8fa; border-radius:10px; margin:0 0 24px;">
                                <tr><td style="padding:14px 18px; font-size:14px; color:#4b5563;">
                                    <div style="margin-bottom:6px;"><b style="color:#1f2937;">Qué hacer:</b> {{ $s->tipoLabel() }}</div>
                                    <div style="margin-bottom:6px;"><b style="color:#1f2937;">Fecha:</b> {{ $s->fecha?->format('d/m/Y') }}</div>
                                    @if ($s->nota)
                                        <div style="margin-bottom:6px;"><b style="color:#1f2937;">Nota:</b> {{ $s->nota }}</div>
                                    @endif
                                    @if ($c->telefono)
                                        <div style="margin-bottom:6px;"><b style="color:#1f2937;">Teléfono:</b> {{ $c->telefono }}</div>
                                    @endif
                                    @if ($c->gmail)
                                        <div><b style="color:#1f2937;">Correo:</b> {{ $c->gmail }}</div>
                                    @endif
                                </td></tr>
                            </table>

                            <div style="text-align:center; margin:0 0 24px;">
                                <a href="{{ $url }}" style="display:inline-block; background:#007aff; color:#ffffff; text-decoration:none; font-weight:bold; font-size:15px; padding:12px 22px; border-radius:10px;">Abrir en el sistema</a>
                            </div>

                            <p style="margin:0; font-size:13px; color:#9ca3af;">
                                Cuando lo atiendas, márcalo como hecho en la ficha del cliente para que no se vuelva a avisar.
                            </p>
                        </td>
                    </tr>
                </table>
                <p style="margin:16px 0 0; font-size:12px; color:#9ca3af;">© {{ date('Y') }} {{ config('app.name') }}</p>
            </td>
        </tr>
    </table>
</body>
</html>
