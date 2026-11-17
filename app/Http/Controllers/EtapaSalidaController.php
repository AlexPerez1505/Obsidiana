<?php

namespace App\Http\Controllers;

use App\Models\OrdenSalida;
use App\Models\OrdenSalidaEtapa;
use App\Models\User;
use App\Models\VentaBitacora;
use App\Services\Almacen\ChecklistDeSalida;
use App\Services\Almacen\EtapasDeSalida;
use App\Services\Almacen\ExpedienteDeSalida;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Las tres etapas de la orden de salida (ingeniería, empaque, entrega), la
 * pantalla de seguimiento para quien vendió y los admins, y el expediente
 * en PDF.
 */
class EtapaSalidaController extends Controller
{
    public function __construct(private readonly EtapasDeSalida $etapas) {}

    public function show(Request $request, OrdenSalida $orden, string $etapa): View
    {
        $e = $this->etapa($orden, $etapa);
        $orden->load(['venta.customer', 'venta.seller', 'items', 'etapas.asignadaA', 'evidencias.subidoPor']);
        $user = $request->user();

        // Empaque arranca con las piezas de la orden (y lo que agregó ingeniería).
        if ($e->etapa === OrdenSalidaEtapa::EMPAQUE && $e->extras === null) {
            $e->extras = $this->etapas->piezasSugeridas($orden);
        }

        return view('structure.gestion_Inventario.ordenes_salida.etapa', [
            'orden' => $orden,
            'etapa' => $e->load(['asignadaA', 'asignadaPor', 'iniciadaPor', 'terminadaPor', 'editadaPor']),
            'etapas' => $orden->etapas,
            'puedeEditar' => $this->etapas->puedeEditar($user, $e),
            'razon' => $e->terminada() ? null : $this->etapas->razonParaNoTrabajar($user, $e),
            'puedeCorregir' => $this->etapas->puedeCorregir($user, $e),
            'faltantes' => $e->terminada() ? [] : $this->etapas->faltantes($e),
            'evidencias' => $orden->evidencias->where('etapa', $e->etapa)->values(),
            'puedeAsignar' => $user->can('salidas.asignar'),
            'candidatos' => $user->can('salidas.asignar') ? $this->etapas->candidatos() : collect(),
        ]);
    }

    /** Arma (una sola vez) el checklist con IA de la etapa y lo guarda. */
    public function checklistIa(Request $request, OrdenSalida $orden, string $etapa, ChecklistDeSalida $ia): JsonResponse
    {
        $e = $this->etapa($orden, $etapa);

        if (! $e->checklist) {
            abort_unless($this->etapas->puedeEditar($request->user(), $e), 403, 'No puedes trabajar esta etapa.');
            $resultado = $ia->para($orden->load('items'), $e->etapa);
            $e->update(['checklist' => $resultado['puntos'], 'checklist_ia' => $resultado['ia']]);

            return response()->json(['puntos' => $resultado['puntos'], 'ia' => $resultado['ia'], 'aviso' => $resultado['aviso']]);
        }

        return response()->json(['puntos' => $e->checklist, 'ia' => $e->checklist_ia, 'aviso' => null]);
    }

    public function asignar(Request $request, OrdenSalida $orden): RedirectResponse
    {
        $data = $request->validate([
            'etapas' => ['required', 'array'],
            'etapas.*' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        $porEtapa = collect($data['etapas'])->only(array_keys(OrdenSalidaEtapa::ETAPAS))->all();
        $mensajes = $this->etapas->asignarVarias($orden, $porEtapa, $request->user());

        return back()->with('status', implode(' ', $mensajes));
    }

    public function guardar(Request $request, OrdenSalida $orden, string $etapa): RedirectResponse
    {
        $e = $this->etapa($orden, $etapa);
        $user = $request->user();

        if (! $this->etapas->puedeEditar($user, $e)) {
            return back()->withErrors(['etapa' => $e->terminada()
                ? 'Esta etapa ya se terminó: pulsa «Editar» para corregirla.'
                : ($this->etapas->razonParaNoTrabajar($user, $e) ?? 'No puedes trabajar esta etapa.')]);
        }

        $data = $request->validate(EtapasDeSalida::reglas($e->etapa));
        $estabaTerminada = $e->terminada();
        $mensaje = $this->etapas->guardar($e, $data, $user);

        // Al terminar una etapa se ve el avance completo (con su sello); lo demás se queda en la etapa.
        if (! $estabaTerminada && $e->terminada()) {
            return redirect()->route('salidas.seguimiento', [$orden, 'recien' => $e->etapa])->with('status', $mensaje);
        }

        return redirect()->route('inventory.salidas.etapas.show', [$orden, $e->etapa])->with('status', $mensaje);
    }

    /** Abre una etapa terminada para corregirla (después de 72 h, con NIP). */
    public function desbloquear(Request $request, OrdenSalida $orden, string $etapa): RedirectResponse
    {
        $e = $this->etapa($orden, $etapa);
        $data = $request->validate(['nip' => ['nullable', 'string', 'max:20']]);

        $this->etapas->desbloquear($e, $request->user(), $data['nip'] ?? null);

        return redirect()->route('inventory.salidas.etapas.show', [$orden, $e->etapa])
            ->with('status', 'Puedes corregir '.$e->nombre().' durante los próximos '.EtapasDeSalida::MINUTOS_DESBLOQUEO.' minutos.');
    }

    /** En qué va la salida: para quien vendió, los admins y almacén. */
    public function seguimiento(Request $request, OrdenSalida $orden): View
    {
        $this->autorizarSeguimiento($request->user(), $orden);

        $orden->load(['venta.customer', 'venta.seller', 'items', 'evidencias']);
        $etapas = $this->etapas->asegurar($orden);

        $bitacora = $orden->venta
            ? VentaBitacora::with('user:id,name')->where('venta_id', $orden->venta_id)
                ->whereIn('tipo', ['salida_etapa', 'salida_asignada'])->latest('id')->limit(30)->get()
            : collect();

        return view('structure.gestion_Inventario.ordenes_salida.seguimiento', [
            'orden' => $orden,
            'etapas' => $etapas,
            'bitacora' => $bitacora,
            'puedeAbrirEtapas' => $request->user()->can('salidas.ver'),
        ]);
    }

    public function expediente(Request $request, OrdenSalida $orden, ExpedienteDeSalida $expediente)
    {
        $this->autorizarSeguimiento($request->user(), $orden);

        return $expediente->pdf($orden)->stream("Expediente {$orden->folio}.pdf");
    }

    private function etapa(OrdenSalida $orden, string $etapa): OrdenSalidaEtapa
    {
        abort_unless(array_key_exists($etapa, OrdenSalidaEtapa::ETAPAS), 404);
        $this->etapas->asegurar($orden);

        $e = $orden->etapas()->where('etapa', $etapa)->first();
        abort_unless($e, 404, 'Esta orden no tiene etapas (ya estaba cerrada antes de que existieran).');

        return $e->setRelation('orden', $orden);
    }

    /** Almacén (salidas.ver), los admins y quien vendió (o quien ve todas las ventas). */
    private function autorizarSeguimiento(User $user, OrdenSalida $orden): void
    {
        $venta = $orden->venta;

        abort_unless(
            $user->isAdmin()
            || $user->can('salidas.ver')
            || ($venta && $user->can('ventas.ver') && ($user->can('ventas.ver_todas') || (int) $venta->seller_id === (int) $user->id)),
            403
        );
    }
}
