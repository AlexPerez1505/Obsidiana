<?php

namespace App\Http\Resources\Api\V1\Servicios;

use Illuminate\Support\Facades\Storage;

/**
 * Evidencias, fotos, cartas y flyers viven en el disco público; la app
 * necesita la URL completa, no la ruta interna.
 */
trait UrlDeArchivo
{
    protected function urlDeArchivo(?string $ruta): ?string
    {
        return $ruta ? Storage::disk('public')->url($ruta) : null;
    }
}
