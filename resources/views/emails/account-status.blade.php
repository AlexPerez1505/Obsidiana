@php
    $banned = $type === 'banned';
    $accent = $banned ? '#dc2626' : '#007aff';
    $loginUrl = url('/login');
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Estado de tu cuenta</title>
</head>
<body style="margin:0; padding:0; background:#f4f4f7; font-family:Arial, Helvetica, sans-serif; color:#1f2937;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f4f4f7; padding:32px 0;">
        <tr>
            <td align="center">
                <table role="presentation" width="480" cellpadding="0" cellspacing="0" style="background:#ffffff; border-radius:12px; overflow:hidden; box-shadow:0 1px 3px rgba(0,0,0,0.08);">
                    <tr>
                        <td style="background:{{ $accent }}; padding:24px 32px;">
                            <h1 style="margin:0; color:#ffffff; font-size:20px;">{{ config('app.name') }}</h1>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:32px;">
                            <p style="margin:0 0 12px; font-size:16px;">Hola {{ $name }},</p>

                            @if ($type === 'approved')
                                <p style="margin:0 0 20px; font-size:15px; color:#4b5563;">
                                    ¡Buenas noticias! Un administrador <strong>aprobó tu cuenta</strong> y ya tienes acceso.
                                    Ya puedes iniciar sesión.
                                </p>
                            @elseif ($type === 'reactivated')
                                <p style="margin:0 0 20px; font-size:15px; color:#4b5563;">
                                    Tu cuenta fue <strong>reactivada</strong> y ya tienes acceso de nuevo.
                                    Ya puedes iniciar sesión.
                                </p>
                            @else
                                <p style="margin:0 0 12px; font-size:15px; color:#4b5563;">
                                    Te informamos que tu cuenta fue <strong>desactivada</strong> y por ahora no puedes iniciar sesión.
                                </p>
                                @if (!empty($reason))
                                    <p style="margin:0 0 20px; font-size:14px; color:#991b1b; background:#fef2f2; border:1px solid #fecaca; border-radius:8px; padding:10px 12px;">
                                        Motivo: {{ $reason }}
                                    </p>
                                @endif
                                <p style="margin:0 0 20px; font-size:14px; color:#6b7280;">
                                    Si crees que es un error, responde a este correo o contacta al administrador.
                                </p>
                            @endif

                            @unless ($banned)
                                <div style="text-align:center; margin:8px 0 8px;">
                                    <a href="{{ $loginUrl }}" style="display:inline-block; background:{{ $accent }}; color:#ffffff; text-decoration:none; font-weight:bold; font-size:15px; padding:12px 24px; border-radius:9px;">
                                        Iniciar sesión
                                    </a>
                                </div>
                            @endunless
                        </td>
                    </tr>
                </table>
                <p style="margin:16px 0 0; font-size:12px; color:#9ca3af;">© {{ date('Y') }} {{ config('app.name') }}</p>
            </td>
        </tr>
    </table>
</body>
</html>
