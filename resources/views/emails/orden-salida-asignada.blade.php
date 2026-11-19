@php
    $o = $orden;
    $url = route('inventory.salidas.show', $o);
    $cliente = trim(($o->venta?->customer?->nombre ?? '').' '.($o->venta?->customer?->apellido ?? ''));
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Te toca preparar el equipo</title>
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
                                {{ $o->asignadaPor?->name ?? 'Alguien' }} te asignó preparar el equipo que ya se vendió.
                                Entra a la orden para marcar cada partida conforme la prepares.
                            </p>

                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f7f8fa; border-radius:10px; margin:0 0 20px;">
                                <tr><td style="padding:14px 18px; font-size:14px; color:#4b5563; line-height:1.7;">
                                    <div><b style="color:#1f2937;">Orden de salida:</b> {{ $o->folio }}</div>
                                    <div><b style="color:#1f2937;">Venta:</b> {{ $o->venta?->folio ?? '—' }}</div>
                                    @if ($cliente)<div><b style="color:#1f2937;">Cliente:</b> {{ $cliente }}</div>@endif
                                    @if ($o->venta?->customer?->direccion)<div><b style="color:#1f2937;">Entrega en:</b> {{ $o->venta->customer->direccion }}</div>@endif
                                    @if ($o->notas)<div><b style="color:#1f2937;">Notas:</b> {{ $o->notas }}</div>@endif
                                </td></tr>
                            </table>

                            @if ($o->items->isNotEmpty())
                                <p style="margin:0 0 8px; font-size:14px; font-weight:bold;">Qué hay que preparar</p>
                                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 24px; font-size:14px; color:#4b5563;">
                                    @foreach ($o->items as $item)
                                        <tr>
                                            <td style="padding:6px 0; border-bottom:1px solid #eef0f3;">
                                                {{ $item->nombre }}
                                                @if ($item->no_series)<div style="font-size:12px; color:#9ca3af;">Series: {{ $item->no_series }}</div>@endif
                                            </td>
                                            <td style="padding:6px 0; border-bottom:1px solid #eef0f3; text-align:right; white-space:nowrap;">× {{ $item->cantidad }}</td>
                                        </tr>
                                    @endforeach
                                </table>
                            @endif

                            <div style="text-align:center; margin:0 0 24px;">
                                <a href="{{ $url }}" style="display:inline-block; background:#007aff; color:#ffffff; text-decoration:none; font-weight:bold; font-size:15px; padding:12px 22px; border-radius:10px;">Abrir la orden</a>
                            </div>

                            <p style="margin:0; font-size:13px; color:#9ca3af;">
                                Cuando termines el checklist, deja la orden "lista para salir" para que alguien firme la entrega.
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
