<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Garantias\GarantiaController;
use App\Models\Garantia;
use App\Services\Garantias\AtencionDeReclamaciones;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * "Mi garantía": lo que ve el cliente desde el enlace o el QR de su póliza.
 *
 * Sin sesión, igual que la consulta pública de ventas: se entra por un UUID
 * y solo se ve ESA garantía (y las de su misma torre). Desde aquí el cliente
 * puede descargar la póliza, ver el avance de sus tickets y reportar una falla.
 */
class GarantiaPublicaController extends Controller
{
    public function show(string $token): View
    {
        $g = $this->buscar($token);

        $componentes = $g->grupo && $g->venta_item_id
            ? Garantia::where('venta_item_id', $g->venta_item_id)->where('estado', 'activa')->orderBy('id')->get()
            : collect([$g]);

        return view('publico.garantia', [
            'g' => $g,
            'componentes' => $componentes,
            'tickets' => $g->reclamaciones()->get(),
        ]);
    }

    public function poliza(string $token)
    {
        $g = $this->buscar($token);

        return GarantiaController::pdfPoliza($g)->stream('Poliza-'.$g->folio.'.pdf');
    }

    public function reclamar(Request $request, string $token, AtencionDeReclamaciones $atencion): RedirectResponse
    {
        $g = $this->buscar($token);

        $data = $request->validate([
            'garantia_id' => ['nullable', 'integer'],
            'contacto_nombre' => ['required', 'string', 'max:160'],
            'contacto_telefono' => ['required', 'string', 'max:40'],
            'contacto_correo' => ['nullable', 'email', 'max:160'],
            'sintomas' => ['required', 'string', 'min:10', 'max:3000'],
            'es_seguridad' => ['nullable', 'boolean'],
            'archivos' => ['nullable', 'array', 'max:5'],
            'archivos.*' => ['file', 'max:30720', 'mimes:jpg,jpeg,png,webp,heic,pdf,mp4,mov'],
        ], [], ['contacto_nombre' => 'nombre', 'contacto_telefono' => 'teléfono', 'sintomas' => 'descripción de la falla']);

        // Solo puede reportar componentes de su misma póliza.
        $destino = $g;
        if (! empty($data['garantia_id']) && (int) $data['garantia_id'] !== $g->id && $g->venta_item_id) {
            $destino = Garantia::where('venta_item_id', $g->venta_item_id)->findOrFail($data['garantia_id']);
        }

        $rec = $atencion->abrir([
            'garantia_id' => $destino->id,
            'canal' => 'portal',
            'contacto_nombre' => $data['contacto_nombre'],
            'contacto_telefono' => $data['contacto_telefono'],
            'contacto_correo' => $data['contacto_correo'] ?? null,
            'sintomas' => $data['sintomas'],
            'es_seguridad' => (bool) ($data['es_seguridad'] ?? false),
            'prioridad' => 'media',
        ], null, $request->file('archivos', []));

        return redirect()->route('publico.garantia', $token)
            ->with('status', 'Recibimos tu reporte. Tu número de seguimiento es '.$rec->folio.'.');
    }

    private function buscar(string $token): Garantia
    {
        return Garantia::with(['customer', 'venta'])
            ->where('public_token', $token)
            ->where('estado', '!=', 'anulada')
            ->firstOrFail();
    }
}
