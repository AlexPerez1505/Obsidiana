@php
    $s = $solicitud;
    $url = route('commercial.ventas.cobros.index', $s->venta_id).'#pagos-por-aprobar';
    $cliente = trim(($s->venta?->customer?->nombre ?? '').' '.($s->venta?->customer?->apellido ?? ''));
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pago por aprobar</title>
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
                                {{ $s->subidoPor?->name ?? 'Un vendedor' }} subió un pago que necesita tu aprobación.
                                {{ $s->metodo === 'efectivo' ? 'Apruébalo cuando recibas el efectivo.' : 'Apruébalo cuando lo veas reflejado en la cuenta.' }}
                            </p>

                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f7f8fa; border-radius:10px; margin:0 0 24px;">
                                <tr><td style="padding:14px 18px; font-size:14px; color:#4b5563; line-height:1.7;">
                                    <div><b style="color:#1f2937;">Monto:</b> ${{ number_format((float) $s->monto, 2) }}</div>
                                    <div><b style="color:#1f2937;">Método:</b> {{ $s->metodoLabel() }}</div>
                                    <div><b style="color:#1f2937;">Fecha del pago:</b> {{ $s->fecha->format('d/m/Y') }}</div>
                                    <div><b style="color:#1f2937;">Venta:</b> {{ $s->venta?->folio }}{{ $cliente ? ' · '.$cliente : '' }}</div>
                                    @if ($s->referencia)<div><b style="color:#1f2937;">Referencia:</b> {{ $s->referencia }}</div>@endif
                                    @if ($s->nota)<div><b style="color:#1f2937;">Nota:</b> {{ $s->nota }}</div>@endif
                                    <div><b style="color:#1f2937;">Comprobante:</b> {{ count($s->archivos ?? []) ? count($s->archivos).' archivo(s) adjunto(s) en el sistema' : 'sin comprobante (efectivo)' }}</div>
                                </td></tr>
                            </table>

                            <div style="text-align:center; margin:0 0 24px;">
                                <a href="{{ $url }}" style="display:inline-block; background:#007aff; color:#ffffff; text-decoration:none; font-weight:bold; font-size:15px; padding:12px 22px; border-radius:10px;">Revisar el pago</a>
                            </div>

                            <p style="margin:0; font-size:13px; color:#9ca3af;">
                                Para aprobarlo se pide tu NIP. Mientras no se apruebe, el pago no cuenta en el saldo de la venta.
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
