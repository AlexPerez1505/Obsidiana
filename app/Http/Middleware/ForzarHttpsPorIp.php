<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Cuando se entra al sistema por la IP de la red local (celulares en el
 * stand), siempre por HTTPS.
 *
 * Dos razones. La cámara del celular solo se habilita en origen seguro.
 * Y, más importante, el navegador guarda las cookies por host sin
 * distinguir puerto: si alguien entra por http://IP:8000 y luego por
 * https://IP, acaba con dos cookies de sesión del mismo nombre (una
 * "Secure" y otra no), Laravel toma la equivocada, pierde la sesión y todo
 * termina en "419 Page Expired". Redirigiendo antes de abrir sesión, por
 * http nunca se crea esa segunda cookie.
 *
 * Solo aplica cuando el host es una IP: localhost y obsidiana.test siguen
 * funcionando por http para desarrollar.
 */
class ForzarHttpsPorIp
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->secure() && filter_var($request->getHost(), FILTER_VALIDATE_IP)) {
            // Sin puerto: el HTTPS lo atiende Apache en el 443.
            $destino = 'https://'.$request->getHost().$request->getRequestUri();

            return redirect()->to($destino, 302);
        }

        return $next($request);
    }
}
