<?php

namespace App\Http\Controllers\Promociones;

use App\Http\Controllers\Controller;
use App\Models\CampanaDestinatario;
use App\Models\Cotizacion;
use App\Services\Promociones\CatalogoOferta;
use App\Services\Promociones\Consentimientos;
use App\Services\Promociones\OfertaPublica;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Las páginas que abre el cliente desde WhatsApp (sin sesión). El token
 * del enlace es personal y aleatorio; aun así, para confirmar un pedido se
 * piden los últimos 4 dígitos del teléfono al que llegó la promoción.
 */
class OfertaPublicaController extends Controller
{
    public function __construct(
        private readonly OfertaPublica $oferta,
        private readonly CatalogoOferta $catalogo,
    ) {
    }

    public function show(string $token): View
    {
        $d = $this->destinatario($token);
        $this->oferta->registrarClic($d);

        $campana = $d->campana;
        $cupon = $d->cuponAplicable();

        return view('publico.promocion.oferta', [
            'd' => $d,
            'campana' => $campana,
            'cupon' => $cupon,
            'productos' => $this->catalogo->itemsDe($campana),
            'vigente' => $campana->ofertaVigente(),
            'nombre' => $d->cliente ? explode(' ', trim($d->cliente->nombre))[0] : null,
            'pedido' => $d->cliente_id ? Cotizacion::where('campana_destinatario_id', $d->id)->latest('id')->first() : null,
        ]);
    }

    public function calcular(Request $request, string $token): JsonResponse
    {
        $d = $this->destinatario($token);
        $data = $request->validate(['cantidades' => ['array'], 'cantidades.*' => ['integer', 'min:0', 'max:99']]);

        return response()->json($this->oferta->calcular($d, $data['cantidades'] ?? []));
    }

    public function confirmar(Request $request, string $token): RedirectResponse
    {
        $d = $this->destinatario($token);
        $data = $request->validate([
            'cantidades' => ['array'],
            'cantidades.*' => ['integer', 'min:0', 'max:99'],
            'ultimos4' => ['required', 'digits:4'],
            'sin_cupon' => ['nullable', 'boolean'],
        ], ['ultimos4.digits' => 'Escribe los últimos 4 dígitos de tu teléfono.']);

        $cot = $this->oferta->confirmar($d, $data['cantidades'] ?? [], $data['ultimos4'], (bool) ($data['sin_cupon'] ?? false));

        return redirect()->route('promo.oferta', $token)->with('pedido_ok', $cot->folio);
    }

    public function baja(string $token): View
    {
        $d = $this->destinatario($token);

        return view('publico.promocion.baja', ['d' => $d, 'hecho' => session('baja_ok')]);
    }

    public function confirmarBaja(string $token, Consentimientos $consentimientos): RedirectResponse
    {
        $d = $this->destinatario($token);
        if ($d->cliente) {
            $consentimientos->revocar($d->cliente, 'enlace', 'Se dio de baja desde el enlace de la campaña "'.$d->campana->nombre.'"');
        }

        return redirect()->route('promo.baja', $token)->with('baja_ok', true);
    }

    private function destinatario(string $token): CampanaDestinatario
    {
        abort_unless(preg_match('/^[A-Za-z0-9]{20,48}$/', $token), 404);

        return CampanaDestinatario::with(['campana.items', 'campana.cupon.alcances', 'cliente', 'cupon.alcances'])
            ->where('token', $token)->firstOrFail();
    }
}
