<?php

namespace App\Http\Controllers\Promociones;

use App\Http\Controllers\Controller;
use App\Models\MktWebhookEvento;
use App\Services\WhatsApp\EstadoWhatsApp;
use App\Services\WhatsApp\MetaCloudSender;
use Illuminate\View\View;

/** Qué falta configurar de WhatsApp y cómo debe ser la plantilla. Nunca muestra secretos. */
class ConfiguracionController extends Controller
{
    public function __invoke(EstadoWhatsApp $estado): View
    {
        $plantillas = $estado->real() ? app(MetaCloudSender::class)->plantillas() : null;

        return view('structure.promociones.configuracion', [
            'estado' => $estado,
            'plantillas' => $plantillas,
            'ultimoAviso' => MktWebhookEvento::latest('id')->value('created_at'),
            'webhookUrl' => route('webhooks.whatsapp.recibir'),
            'ofertaUrl' => url('/p').'/{{1}}',
        ]);
    }
}
