<?php

namespace App\Http\Controllers\Garantias;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Garantia;
use App\Models\GarantiaCobertura;
use App\Models\User;
use App\Services\Garantias\AtencionDeReclamaciones;
use App\Services\Garantias\GeneradorDeGarantias;
use App\Services\Garantias\IndicadoresDeGarantias;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Expedientes de garantía: listado, alta manual, ficha, cambios auditados,
 * anulación y póliza en PDF. No hay ruta para borrar: solo se anulan.
 */
class GarantiaController extends Controller
{
    public function __construct(
        private AtencionDeReclamaciones $atencion,
        private IndicadoresDeGarantias $indicadores,
    ) {}

    public function index(Request $request): View
    {
        $filtros = $request->only(['q', 'semaforo', 'tipo', 'cliente', 'grupo']);
        $dias = (int) config('garantias.dias_por_vencer', 30);

        $garantias = Garantia::query()
            ->with(['customer', 'venta', 'reclamacionesAbiertas'])
            ->buscar($filtros['q'] ?? null)
            ->when($filtros['tipo'] ?? null, fn ($q, $t) => $q->where('tipo', $t))
            ->when($filtros['cliente'] ?? null, fn ($q, $c) => $q->where('customer_id', $c))
            ->when($filtros['semaforo'] ?? null, function ($q, $s) use ($dias) {
                match ($s) {
                    'vigente' => $q->activas()->whereDate('vence', '>', today()->addDays($dias)),
                    'por_vencer' => $q->porVencer(),
                    'vencida' => $q->activas()->whereDate('vence', '<', today()),
                    'reclamacion' => $q->activas()->whereHas('reclamacionesAbiertas'),
                    'en_reparacion' => $q->whereHas('reclamacionesAbiertas', fn ($r) => $r->where('etapa', 'atencion')),
                    'anulada' => $q->whereIn('estado', ['anulada', 'sustituida']),
                    default => null,
                };
            })
            ->when(! in_array($filtros['semaforo'] ?? null, ['anulada'], true), fn ($q) => $q->where('estado', '!=', 'anulada'))
            ->orderByRaw('venta_id is null')
            ->orderByDesc('venta_id')
            ->orderBy('grupo')
            ->orderBy('id')
            ->paginate(40)
            ->withQueryString();

        return view('structure.garantias.index', [
            'garantias' => $garantias,
            'filtros' => $filtros,
            'cliente' => isset($filtros['cliente']) ? Customer::find($filtros['cliente']) : null,
            'conteos' => [
                'activas' => Garantia::vigentes()->count(),
                'por_vencer' => Garantia::porVencer()->count(),
                'vencidas' => Garantia::activas()->whereDate('vence', '<', today())->count(),
            ],
        ]);
    }

    public function create(Request $request): View
    {
        return view('structure.garantias.create', [
            'coberturas' => GarantiaCobertura::where('activo', true)->orderBy('nombre')->get(),
            'responsables' => $this->responsables(),
            'clientes' => Customer::orderBy('nombre')->get(['id', 'nombre', 'apellido']),
        ]);
    }

    /** Alta manual: equipos vendidos antes del sistema, reparaciones, extendidas... */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'customer_id' => ['required', 'exists:customers,id'],
            'descripcion' => ['required', 'string', 'max:255'],
            'marca' => ['nullable', 'string', 'max:120'],
            'modelo' => ['nullable', 'string', 'max:120'],
            'no_serie' => ['nullable', 'string', 'max:120'],
            'condicion' => ['required', Rule::in(array_keys(Garantia::CONDICIONES))],
            'cobertura_id' => ['nullable', 'exists:garantia_coberturas,id'],
            'tipo' => ['required', Rule::in(array_keys(Garantia::TIPOS))],
            'meses' => ['required', 'integer', 'min:1', 'max:120'],
            'inicio' => ['required', 'date'],
            'proveedor' => ['nullable', 'string', 'max:160'],
            'precio_venta' => ['nullable', 'numeric', 'min:0'],
            'costo_adquisicion' => ['nullable', 'numeric', 'min:0'],
            'responsable_id' => ['nullable', 'exists:users,id'],
            'notas' => ['nullable', 'string', 'max:2000'],
        ]);

        $cobertura = isset($data['cobertura_id']) ? GarantiaCobertura::find($data['cobertura_id']) : null;
        $inicio = Carbon::parse($data['inicio'])->startOfDay();

        $garantia = Garantia::create($data + [
            'origen' => 'manual',
            'inicio' => $inicio,
            'vence' => Garantia::calcularVence($inicio, (int) $data['meses']),
            'fecha_entrega' => $inicio,
            'fechas_manual' => true,
            'cubre' => $cobertura?->cubre,
            'exclusiones' => $cobertura?->exclusiones,
            'condiciones' => $cobertura?->condiciones,
            'estado' => 'activa',
            'created_by' => $request->user()->id,
        ]);

        $garantia->registrar('creada', 'Garantía registrada a mano · '.$garantia->meses.' meses.');

        return redirect()->route('garantias.show', $garantia)->with('status', 'Garantía '.$garantia->folio.' registrada.');
    }

    public function show(Garantia $garantia): View
    {
        $garantia->load([
            'customer', 'venta', 'cobertura', 'responsable', 'serial', 'anuladaPor',
            'reclamaciones.costos', 'reclamaciones.responsable', 'archivos.usuario', 'eventos.usuario',
        ]);

        // Hermanos de la torre: el resto de componentes del mismo paquete.
        $hermanos = $garantia->grupo && $garantia->venta_item_id
            ? Garantia::where('venta_item_id', $garantia->venta_item_id)->whereKeyNot($garantia->id)->orderBy('id')->get()
            : collect();

        return view('structure.garantias.show', [
            'g' => $garantia,
            'hermanos' => $hermanos,
            'rentabilidad' => $this->indicadores->rentabilidad($garantia),
            'responsables' => $this->responsables(),
            'coberturas' => GarantiaCobertura::where('activo', true)->orderBy('nombre')->get(),
        ]);
    }

    /** Datos que no cambian la cobertura: responsable, proveedor, costos, serie. */
    public function update(Request $request, Garantia $garantia): RedirectResponse
    {
        $data = $request->validate([
            'no_serie' => ['nullable', 'string', 'max:120'],
            'responsable_id' => ['nullable', 'exists:users,id'],
            'proveedor' => ['nullable', 'string', 'max:160'],
            'precio_venta' => ['nullable', 'numeric', 'min:0'],
            'costo_adquisicion' => ['nullable', 'numeric', 'min:0'],
            'condicion' => ['required', Rule::in(array_keys(Garantia::CONDICIONES))],
            'cubre' => ['nullable', 'string', 'max:5000'],
            'exclusiones' => ['nullable', 'string', 'max:5000'],
            'condiciones' => ['nullable', 'string', 'max:5000'],
            'notas' => ['nullable', 'string', 'max:2000'],
        ]);

        $garantia->fill($data);
        $cambios = collect($garantia->getDirty())->keys();

        if ($cambios->isEmpty()) {
            return back()->with('status', 'No había cambios que guardar.');
        }

        $etiquetas = [
            'no_serie' => 'serie', 'responsable_id' => 'responsable', 'proveedor' => 'proveedor',
            'precio_venta' => 'precio de venta', 'costo_adquisicion' => 'costo de adquisición',
            'condicion' => 'condición', 'cubre' => 'qué cubre', 'exclusiones' => 'exclusiones',
            'condiciones' => 'condiciones', 'notas' => 'notas',
        ];
        $antes = collect($garantia->getOriginal())->only($cambios)->all();

        $garantia->save();
        $garantia->registrar('editada', 'Cambió: '.$cambios->map(fn ($c) => $etiquetas[$c] ?? $c)->implode(', ').'.',
            ['antes' => $antes, 'despues' => $garantia->only($cambios->all())]);

        return back()->with('status', 'Garantía actualizada.');
    }

    /** Corregir fechas o plazo: requiere permiso de aprobar y un motivo. */
    public function fechas(Request $request, Garantia $garantia): RedirectResponse
    {
        $data = $request->validate([
            'inicio' => ['required', 'date'],
            'meses' => ['required', 'integer', 'min:1', 'max:120'],
            'tipo' => ['required', Rule::in(array_keys(Garantia::TIPOS))],
            'motivo' => ['required', 'string', 'min:5', 'max:500'],
        ]);

        $inicio = Carbon::parse($data['inicio'])->startOfDay();
        $antes = $garantia->inicio->format('d/m/Y').' – '.$garantia->vence->format('d/m/Y').' ('.$garantia->meses.' meses, '.$garantia->tipoLabel().')';

        $garantia->update([
            'inicio' => $inicio,
            'meses' => (int) $data['meses'],
            'tipo' => $data['tipo'],
            'vence' => Garantia::calcularVence($inicio, (int) $data['meses']),
            'fechas_manual' => true,
        ]);

        $garantia->registrar('fechas', 'Cobertura corregida: '.$antes.' → '.$garantia->inicio->format('d/m/Y').' – '
            .$garantia->vence->format('d/m/Y').' ('.$garantia->meses.' meses, '.$garantia->tipoLabel().'). Motivo: '.$data['motivo']);

        return back()->with('status', 'Cobertura actualizada.');
    }

    public function anular(Request $request, Garantia $garantia): RedirectResponse
    {
        $data = $request->validate(['motivo' => ['required', 'string', 'min:5', 'max:1000']]);

        if ($garantia->anulada()) {
            return back()->withErrors(['motivo' => 'La garantía ya estaba anulada.']);
        }
        if ($garantia->reclamacionesAbiertas()->exists()) {
            return back()->withErrors(['motivo' => 'Tiene reclamaciones abiertas: ciérralas antes de anular.']);
        }

        $garantia->update([
            'estado' => 'anulada',
            'motivo_anulacion' => $data['motivo'],
            'anulada_por' => $request->user()->id,
            'anulada_en' => now(),
        ]);
        $garantia->registrar('anulada', 'Garantía anulada. Motivo: '.$data['motivo']);

        return back()->with('status', 'Garantía anulada. Queda en el historial.');
    }

    public function reactivar(Garantia $garantia): RedirectResponse
    {
        abort_unless($garantia->anulada(), 422);

        $garantia->update(['estado' => 'activa', 'motivo_anulacion' => null, 'anulada_por' => null, 'anulada_en' => null]);
        $garantia->registrar('reactivada', 'Garantía reactivada.');

        return back()->with('status', 'Garantía reactivada.');
    }

    public function archivo(Request $request, Garantia $garantia): RedirectResponse
    {
        $request->validate([
            'archivos' => ['required', 'array', 'max:10'],
            'archivos.*' => ['file', 'max:51200', 'mimes:jpg,jpeg,png,webp,heic,pdf,mp4,mov,doc,docx,xls,xlsx'],
            'tipo' => ['required', Rule::in(array_keys(\App\Models\GarantiaArchivo::TIPOS))],
        ]);

        foreach ($request->file('archivos') as $archivo) {
            $this->atencion->guardarArchivo($archivo, $request->input('tipo'), $request->user(), null, $garantia);
        }

        return back()->with('status', 'Archivos agregados al expediente.');
    }

    /** Póliza escrita: alcance, duración, condiciones y cómo reclamar. */
    public function poliza(Garantia $garantia)
    {
        return $this->pdfPoliza($garantia)->stream('Poliza-'.$garantia->folio.'.pdf');
    }

    public static function pdfPoliza(Garantia $garantia)
    {
        $garantia->load(['customer', 'venta']);

        $hermanos = $garantia->grupo && $garantia->venta_item_id
            ? Garantia::where('venta_item_id', $garantia->venta_item_id)->where('estado', 'activa')->orderBy('id')->get()
            : collect([$garantia]);

        return Pdf::loadView('structure.garantias.pdf.poliza', [
            'g' => $garantia,
            'componentes' => $hermanos,
        ])->setPaper('letter');
    }

    public function sincronizar(GeneradorDeGarantias $generador): RedirectResponse
    {
        $t = $generador->sincronizarTodas();

        return back()->with('status', "Revisadas {$t['ventas']} ventas: {$t['creadas']} garantías nuevas, "
            ."{$t['actualizadas']} actualizadas, {$t['anuladas']} anuladas.");
    }

    private function responsables()
    {
        return User::where('status', User::STATUS_APPROVED)->orderBy('name')->get(['id', 'name']);
    }
}
