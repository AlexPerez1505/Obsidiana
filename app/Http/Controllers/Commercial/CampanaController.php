<?php

namespace App\Http\Controllers\Commercial;

use App\Http\Controllers\Controller;
use App\Jobs\EnviarMensajeCampanaJob;
use App\Models\Campana;
use App\Models\CampanaDestinatario;
use App\Models\Category;
use App\Models\Congress;
use App\Models\Customer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Campañas de promoción: el mensaje que de verdad se manda, a quién
 * (por filtro), y el registro de qué pasó con cada destinatario.
 *
 * El filtro de audiencia SIEMPRE se cruza con
 * Customer::puedeRecibirPromociones() al lanzar: no hay forma de
 * saltarse el consentimiento confirmado desde aquí.
 */
class CampanaController extends Controller
{
    public function index(): View
    {
        $campanas = Campana::with('creador')->latest()->paginate(15);

        return view('structure.commercial_management.promociones.campanas.index', [
            'campanas' => $campanas,
        ]);
    }

    public function create(): View
    {
        return view('structure.commercial_management.promociones.campanas.crear', [
            'categorias' => Category::query()->orderBy('nombre')->get(),
            'congresos' => Congress::query()->latest()->get(),
            // Marketing segmenta sobre todo el directorio comercial.
            'totalConfirmados' => Customer::query()->whereNotNull('promocion_confirmada_en')->whereNull('promocion_revocada_en')->count(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:255'],
            'mensaje' => ['required', 'string', 'max:1000'],
            'categoria_id' => ['nullable', 'exists:categorias,id'],
            'congreso_id' => ['nullable', 'exists:congresos_eventos,id'],
            'programada_para' => ['nullable', 'date'],
        ]);

        $campana = Campana::create([
            'nombre' => $data['nombre'],
            'mensaje' => $data['mensaje'],
            'filtros' => array_filter([
                'categoria_id' => $data['categoria_id'] ?? null,
                'congreso_id' => $data['congreso_id'] ?? null,
            ]),
            'creado_por' => auth()->id(),
            'programada_para' => $data['programada_para'] ?? null,
            'estado' => Campana::ESTADO_BORRADOR,
        ]);

        return redirect()->route('commercial.promociones.campanas.show', $campana)
            ->with('status', 'Campaña creada como borrador. Revisa a quién le va a llegar antes de lanzarla.');
    }

    public function show(Campana $campana): View
    {
        return view('structure.commercial_management.promociones.campanas.show', [
            'campana' => $campana,
            'resumen' => $campana->resumenEnvios(),
            'audienciaCalculada' => $campana->puedeLanzarse() ? $this->audienciaDe($campana)->count() : null,
            // Marketing revisa envíos sobre todo el directorio comercial.
            'destinatarios' => $campana->destinatarios()
                ->with('cliente')
                ->latest()
                ->paginate(30),
        ]);
    }

    /**
     * Toma la audiencia según el filtro guardado, cruzada siempre con el
     * consentimiento confirmado, crea un renglón por cada destinatario y
     * encola su envío. No manda nada directo desde aquí: todo pasa por
     * la cola (ver EnviarMensajeCampanaJob) para respetar el ritmo.
     */
    public function lanzar(Campana $campana): RedirectResponse
    {
        if (! $campana->puedeLanzarse()) {
            return back()->withErrors(['campana' => 'Esta campaña ya se lanzó, no se puede lanzar dos veces.']);
        }

        $clientes = $this->audienciaDe($campana)->get();

        if ($clientes->isEmpty()) {
            return back()->withErrors(['campana' => 'No hay ningún cliente que califique para esta campaña (con el filtro elegido y el consentimiento confirmado).']);
        }

        $campana->update(['estado' => Campana::ESTADO_EN_COLA]);

        foreach ($clientes as $cliente) {
            $destinatario = CampanaDestinatario::create([
                'campana_id' => $campana->id,
                'cliente_id' => $cliente->id,
                'estado' => CampanaDestinatario::ESTADO_PENDIENTE,
            ]);

            EnviarMensajeCampanaJob::dispatch($destinatario->id);
        }

        return redirect()->route('commercial.promociones.campanas.show', $campana)
            ->with('status', "Campaña lanzada: se encolaron {$clientes->count()} mensaje(s).");
    }

    public function cancelar(Campana $campana): RedirectResponse
    {
        if (! $campana->puedeLanzarse()) {
            return back()->withErrors(['campana' => 'Ya se lanzó, no se puede cancelar (revisa los destinatarios que aún estén pendientes).']);
        }

        $campana->update(['estado' => Campana::ESTADO_CANCELADA]);

        return redirect()->route('commercial.promociones.campanas.index')->with('status', 'Campaña cancelada.');
    }

    /**
     * La audiencia real de una campaña: el filtro que se guardó, cruzado
     * siempre con el consentimiento confirmado. Se usa tanto para
     * mostrar "a cuántos les va a llegar" antes de lanzar, como para
     * armar la lista real al lanzar.
     */
    private function audienciaDe(Campana $campana): \Illuminate\Database\Eloquent\Builder
    {
        $filtros = $campana->filtros ?? [];

        // Marketing segmenta sobre todo el directorio comercial.
        return Customer::query()
            ->whereNotNull('promocion_confirmada_en')
            ->whereNull('promocion_revocada_en')
            ->when($filtros['categoria_id'] ?? null, fn ($q, $v) => $q->where('categoria_id', $v))
            ->when($filtros['congreso_id'] ?? null, fn ($q, $v) => $q->where('congreso_id', $v));
    }
}
