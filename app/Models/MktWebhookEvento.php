<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Huella de cada aviso de WhatsApp ya procesado (Meta reintenta y puede duplicar). */
class MktWebhookEvento extends Model
{
    protected $table = 'mkt_webhook_eventos';

    public const UPDATED_AT = null;

    protected $fillable = ['huella', 'tipo', 'wamid', 'valor'];
}
