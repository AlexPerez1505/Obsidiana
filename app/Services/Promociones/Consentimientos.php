<?php

namespace App\Services\Promociones;

use App\Models\Customer;
use App\Models\MktConsentimiento;
use App\Services\WhatsApp\Telefono;
use Illuminate\Support\Collection;

/**
 * El permiso del cliente para recibir promociones. Sin consentimiento
 * confirmado y vigente, ninguna campaña le llega (Audiencia y el job lo
 * revisan). Cada cambio queda en el historial con su fuente y quién lo hizo.
 */
class Consentimientos
{
    public function otorgar(Customer $cliente, string $fuente, ?string $nota = null): void
    {
        Customer::whereKey($cliente->id)->update([
            'recibe_promocion' => true,
            'promocion_autorizada_en' => now(),
            'promocion_autorizada_por' => auth()->id(),
            'promocion_confirmada_en' => now(),
            'promocion_revocada_en' => null,
        ]);

        MktConsentimiento::create([
            'cliente_id' => $cliente->id, 'accion' => 'otorgado', 'fuente' => $fuente,
            'nota' => $nota ? mb_substr($nota, 0, 255) : null, 'user_id' => auth()->id(),
        ]);
    }

    public function revocar(Customer $cliente, string $fuente, ?string $nota = null): void
    {
        if ($cliente->promocion_revocada_en && ! $cliente->promocion_confirmada_en) {
            return;
        }

        Customer::whereKey($cliente->id)->update([
            'recibe_promocion' => false,
            'promocion_revocada_en' => now(),
        ]);

        MktConsentimiento::create([
            'cliente_id' => $cliente->id, 'accion' => 'revocado', 'fuente' => $fuente,
            'nota' => $nota ? mb_substr($nota, 0, 255) : null, 'user_id' => auth()->id(),
        ]);
    }

    /** Los clientes con ese número (comparando los últimos 10 dígitos). */
    public function clientesConTelefono(string $telefono): Collection
    {
        $ultimos = substr(preg_replace('/\D+/', '', $telefono) ?? '', -4);
        if (strlen($ultimos) < 4) {
            return collect();
        }

        return Customer::where('telefono', 'like', '%'.substr($ultimos, -2).'%')->get()
            ->filter(fn (Customer $c) => Telefono::mismo($c->telefono, $telefono))
            ->values();
    }
}
