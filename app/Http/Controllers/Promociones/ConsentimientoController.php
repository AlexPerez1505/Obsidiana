<?php

namespace App\Http\Controllers\Promociones;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Services\Promociones\Consentimientos;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** Registrar o quitar el permiso de un cliente para recibir promociones (desde su perfil). */
class ConsentimientoController extends Controller
{
    public function __construct(private readonly Consentimientos $consentimientos)
    {
    }

    public function otorgar(Request $request, Customer $cliente): RedirectResponse
    {
        $data = $request->validate([
            'medio' => ['required', 'in:verbal,escrito,formulario,whatsapp'],
            'nota' => ['nullable', 'string', 'max:200'],
        ], ['medio.required' => 'Indica cómo dio su consentimiento el cliente.']);

        $medios = ['verbal' => 'De palabra', 'escrito' => 'Por escrito', 'formulario' => 'En un formulario', 'whatsapp' => 'Por WhatsApp'];
        $this->consentimientos->otorgar($cliente, 'panel', $medios[$data['medio']].($data['nota'] ? ': '.$data['nota'] : ''));

        return back()->with('status', 'Consentimiento registrado: el cliente ya puede recibir promociones.');
    }

    public function revocar(Request $request, Customer $cliente): RedirectResponse
    {
        $data = $request->validate(['nota' => ['nullable', 'string', 'max:200']]);
        $this->consentimientos->revocar($cliente, 'panel', $data['nota'] ?? 'Baja registrada en el sistema');

        return back()->with('status', 'Listo: el cliente ya no recibirá promociones.');
    }
}
