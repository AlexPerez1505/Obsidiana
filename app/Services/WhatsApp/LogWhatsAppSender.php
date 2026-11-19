<?php

namespace App\Services\WhatsApp;

use App\Contracts\WhatsAppSender;
use App\Support\WhatsApp\MensajePlantilla;
use App\Support\WhatsApp\WhatsAppEnvioResultado;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Modo de pruebas: no manda nada. Deja constancia en el log (con el número
 * oculto y sin los textos personalizados) y regresa un envío "simulado",
 * que no cuenta como enviado en los resultados.
 */
class LogWhatsAppSender implements WhatsAppSender
{
    public function enviarPlantilla(MensajePlantilla $mensaje): WhatsAppEnvioResultado
    {
        $id = 'simulado-'.Str::uuid();

        Log::info('[WhatsApp:simulado] Plantilla no enviada (modo de pruebas)', [
            'telefono' => Telefono::ocultar($mensaje->telefono),
            'plantilla' => $mensaje->plantilla,
            'variables' => count($mensaje->variables),
            'referencia' => $mensaje->referencia,
            'message_id' => $id,
        ]);

        return WhatsAppEnvioResultado::simulado($id);
    }

    public function esReal(): bool
    {
        return false;
    }
}
