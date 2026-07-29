<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Código de verificación</title>
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
                            <p style="margin:0 0 24px; font-size:15px; color:#4b5563;">
                                Usa el siguiente código para verificar tu correo electrónico. El código caduca en 10 minutos.
                            </p>
                            <div style="text-align:center; margin:0 0 24px;">
                                <span style="display:inline-block; font-size:34px; letter-spacing:10px; font-weight:bold; color:#007aff; background:#e5f1ff; padding:16px 24px; border-radius:10px;">
                                    {{ $code }}
                                </span>
                            </div>
                            <p style="margin:0; font-size:13px; color:#9ca3af;">
                                Si no creaste una cuenta, puedes ignorar este mensaje.
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
