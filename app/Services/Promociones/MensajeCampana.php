<?php

namespace App\Services\Promociones;

use App\Models\Campana;
use App\Models\CampanaDestinatario;
use App\Models\Cupon;
use App\Models\Customer;
use App\Support\WhatsApp\MensajePlantilla;

/**
 * Arma el mensaje de cada destinatario: las 4 variables de la plantilla
 * aprobada (nombre, oferta, código, vencimiento), la imagen y el botón.
 */
class MensajeCampana
{
    /** @return array<int, string> {{1}} nombre, {{2}} oferta, {{3}} código, {{4}} vence */
    public function variables(Campana $campana, ?Customer $cliente, ?Cupon $cupon, ?string $nombreAlterno = null): array
    {
        $nombre = $cliente ? $this->primerNombre($cliente->nombre) : ($nombreAlterno ?: 'cliente');

        $vence = $cupon?->vence_en ?? $campana->vigencia_fin;

        return [
            MensajePlantilla::limpiarVariable($nombre, 60),
            MensajePlantilla::limpiarVariable($campana->mensaje, 600),
            $cupon ? $cupon->codigo : 'sin código: el precio ya incluye la promoción',
            $vence ? $vence->format('d/m/Y') : 'agotar existencias',
        ];
    }

    public function paraDestinatario(CampanaDestinatario $d): MensajePlantilla
    {
        $campana = $d->campana;
        $cfg = config('promociones.whatsapp');

        return new MensajePlantilla(
            telefono: (string) $d->telefono,
            plantilla: $campana->plantillaWhatsapp(),
            idioma: (string) $cfg['idioma'],
            variables: $this->variables($campana, $d->cliente, $d->cuponAplicable(), $d->es_prueba ? 'equipo' : null),
            imagenUrl: $cfg['plantilla_con_imagen'] ? ($campana->imagenUrl() ?? asset('images/logo.png')) : null,
            botonUrlSufijo: $d->token,
            botonBaja: (bool) $cfg['plantilla_boton_baja'],
            referencia: 'dest:'.$d->id,
        );
    }

    /** Texto de la vista previa con las variables puestas. */
    public function vistaPrevia(Campana $campana, ?Customer $cliente = null): string
    {
        $cupon = $campana->cupon;
        if ($campana->cupon_exclusivo && $cupon) {
            // Cada cliente recibirá su propio código; en la vista previa se muestra el formato.
            $cupon = $cupon->replicate();
            $cupon->codigo = $campana->cupon->codigo.'-XXXXXX';
        }

        $texto = (string) config('promociones.whatsapp.plantilla_texto');
        foreach ($this->variables($campana, $cliente, $cupon, 'María') as $i => $v) {
            $texto = str_replace('{{'.($i + 1).'}}', $v, $texto);
        }

        return $texto;
    }

    private function primerNombre(?string $nombre): string
    {
        $n = trim((string) $nombre);
        $primero = $n === '' ? 'cliente' : explode(' ', $n)[0];

        return mb_convert_case(mb_strtolower($primero), MB_CASE_TITLE);
    }
}
