<?php

namespace App\Support\WhatsApp;

use App\Contracts\WhatsAppSender;
use App\Models\User;
use App\Services\WhatsApp\Telefono;
use Illuminate\Support\Facades\Log;

/**
 * Avisos internos (para el equipo, no para clientes) por WhatsApp.
 *
 * Cada aviso sale con una plantilla aprobada de categoría Servicio: cuerpo
 * con variables y un botón "Abrir en Obsidiana" cuya URL dinámica es
 * https://obsidiana.grupomedibuy.com/{{1}}; aquí se manda solo la ruta.
 *
 * Solo sale si los avisos están encendidos (WHATSAPP_AVISOS) y la persona
 * tiene un teléfono válido en su perfil. El envío ocurre después de
 * responder la petición, así una pantalla nunca espera a WhatsApp, y un
 * fallo se queda en el log sin tumbar la acción que generó el aviso.
 */
class AvisoInterno
{
    /** ¿A esta persona se le puede mandar un aviso por WhatsApp? */
    public static function puede(object $notifiable): bool
    {
        return config('promociones.whatsapp.avisos_internos')
            && $notifiable instanceof User
            && Telefono::e164($notifiable->phone) !== null;
    }

    /**
     * @param  array<int, string|int|null>  $variables  {{2}}, {{3}}… ({{1}} es el nombre y se pone solo,
     *                                                   salvo con $conNombre = false: entonces empiezan en {{1}})
     */
    public static function mensaje(User $u, string $plantilla, array $variables, ?string $url, ?string $referencia = null, bool $conNombre = true): ?MensajePlantilla
    {
        $telefono = Telefono::e164($u->phone);
        if ($telefono === null) {
            return null;
        }

        $nombre = mb_convert_case(mb_strtolower(explode(' ', trim((string) $u->name))[0] ?: 'equipo'), MB_CASE_TITLE);

        return new MensajePlantilla(
            telefono: $telefono,
            plantilla: $plantilla,
            idioma: (string) config('promociones.whatsapp.idioma'),
            variables: array_map(
                fn ($v) => MensajePlantilla::limpiarVariable((string) $v, 300),
                $conNombre ? [$nombre, ...array_values($variables)] : array_values($variables)
            ),
            botonUrlSufijo: static::ruta($url),
            referencia: $referencia,
        );
    }

    /** La parte de la URL que va después del dominio (lo que llena el {{1}} del botón). */
    public static function ruta(?string $url): string
    {
        if (blank($url)) {
            return 'dashboard';
        }

        $p = parse_url($url);
        $ruta = ltrim($p['path'] ?? '', '/')
            .(isset($p['query']) ? '?'.$p['query'] : '')
            .(isset($p['fragment']) ? '#'.$p['fragment'] : '');

        return $ruta === '' ? 'dashboard' : $ruta;
    }

    /** Lo manda después de la respuesta; nunca lanza excepción. */
    public static function enviar(MensajePlantilla $m): void
    {
        dispatch(function () use ($m) {
            try {
                $r = app(WhatsAppSender::class)->enviarPlantilla($m);
                if (! $r->exito) {
                    Log::warning('[WhatsApp] Aviso interno no enviado', [
                        'plantilla' => $m->plantilla,
                        'telefono' => Telefono::ocultar($m->telefono),
                        'codigo' => $r->codigo,
                        'error' => $r->error,
                    ]);
                }
            } catch (\Throwable $e) {
                Log::warning('[WhatsApp] Aviso interno falló', ['plantilla' => $m->plantilla, 'error' => $e->getMessage()]);
            }
        })->afterResponse();
    }
}
