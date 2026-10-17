<?php

namespace App\Http\Controllers\Garantias;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Garantia;
use App\Models\GarantiaArchivo;
use App\Models\GarantiaCosto;
use App\Models\GarantiaReclamacion;
use App\Models\GarantiaSustitucion;
use App\Models\Service;
use App\Models\User;
use App\Services\Garantias\AtencionDeReclamaciones;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ReclamacionController extends Controller
{
    public function __construct(private AtencionDeReclamaciones $atencion) {}

    public function index(Request $request): View
    {
        $filtros = $request->only(['q', 'vista', 'etapa', 'prioridad', 'responsable']);
        $vista = $filtros['vista'] ?? 'abiertas';

        $reclamaciones = GarantiaReclamacion::query()
            ->with(['garantia', 'customer', 'responsable'])
            ->when($vista === 'abiertas', fn ($q) => $q->abiertas())
            ->when($vista === 'atrasadas', fn ($q) => $q->atrasadas())
            ->when($vista === 'mias', fn ($q) => $q->abiertas()->where('responsable_id', $request->user()->id))
            ->when($vista === 'seguridad', fn ($q) => $q->where('es_seguridad', true))
            ->when($vista === 'cerradas', fn ($q) => $q->where('etapa', GarantiaReclamacion::CERRADA))
            ->when($filtros['etapa'] ?? null, fn ($q, $e) => $q->where('etapa', $e))
            ->when($filtros['prioridad'] ?? null, fn ($q, $p) => $q->where('prioridad', $p))
            ->when($filtros['responsable'] ?? null, fn ($q, $r) => $q->where('responsable_id', $r))
            ->when(trim($filtros['q'] ?? '') !== '', function ($q) use ($filtros) {
                $like = '%'.trim($filtros['q']).'%';
                $q->where(fn ($w) => $w->where('folio', 'like', $like)
                    ->orWhere('no_serie', 'like', $like)
                    ->orWhere('equipo', 'like', $like)
                    ->orWhere('contacto_nombre', 'like', $like)
                    ->orWhere('sintomas', 'like', $like)
                    ->orWhereHas('customer', fn ($c) => $c->whereRaw("CONCAT_WS(' ', nombre, apellido) like ?", [$like]))
                    ->orWhereHas('garantia', fn ($g) => $g->where('descripcion', 'like', $like)->orWhere('modelo', 'like', $like)));
            })
            ->orderByDesc('es_seguridad')
            ->orderByRaw("etapa = 'cerrada'")
            ->orderByRaw("FIELD(prioridad, 'critica', 'alta', 'media', 'baja')")
            ->latest('recibida_en')
            ->paginate(30)
            ->withQueryString();

        return view('structure.garantias.reclamaciones.index', [
            'reclamaciones' => $reclamaciones,
            'filtros' => $filtros,
            'vista' => $vista,
            'responsables' => $this->responsables(),
            'conteos' => [
                'abiertas' => GarantiaReclamacion::abiertas()->count(),
                'atrasadas' => GarantiaReclamacion::atrasadas()->count(),
                'mias' => GarantiaReclamacion::abiertas()->where('responsable_id', $request->user()->id)->count(),
                'seguridad' => GarantiaReclamacion::abiertas()->where('es_seguridad', true)->count(),
            ],
        ]);
    }

    public function create(Request $request): View
    {
        $garantia = $request->filled('garantia') ? Garantia::with('customer')->find($request->get('garantia')) : null;

        return view('structure.garantias.reclamaciones.create', [
            'garantia' => $garantia,
            'responsables' => $this->responsables(),
            'clientes' => Customer::orderBy('nombre')->get(['id', 'nombre', 'apellido']),
        ]);
    }

    /** Busca garantías por serie mientras se captura (JSON). */
    public function buscarSerie(Request $request)
    {
        $q = trim((string) $request->get('q'));
        if (mb_strlen($q) < 2) {
            return response()->json([]);
        }

        return Garantia::with('customer')
            ->buscar($q)
            ->where('estado', '!=', 'anulada')
            ->limit(8)->get()
            ->map(fn (Garantia $g) => [
                'id' => $g->id,
                'folio' => $g->folio,
                'equipo' => $g->equipoNombre(),
                'serie' => $g->no_serie,
                'cliente' => $g->clienteNombre(),
                'vence' => $g->vence->format('d/m/Y'),
                'semaforo' => $g->semaforoTexto(),
                'badge' => $g->semaforoBadge(),
            ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'garantia_id' => ['nullable', 'exists:garantias,id'],
            'no_serie' => ['nullable', 'string', 'max:120'],
            'equipo' => ['nullable', 'string', 'max:255'],
            'customer_id' => ['nullable', 'exists:customers,id'],
            'canal' => ['required', Rule::in(array_keys(GarantiaReclamacion::CANALES))],
            'contacto_nombre' => ['nullable', 'string', 'max:160'],
            'contacto_telefono' => ['nullable', 'string', 'max:40'],
            'contacto_correo' => ['nullable', 'email', 'max:160'],
            'sintomas' => ['required', 'string', 'min:5', 'max:5000'],
            'prioridad' => ['required', Rule::in(array_keys(GarantiaReclamacion::PRIORIDADES))],
            'es_seguridad' => ['nullable', 'boolean'],
            'responsable_id' => ['nullable', 'exists:users,id'],
            'archivos' => ['nullable', 'array', 'max:10'],
            'archivos.*' => ['file', 'max:51200', 'mimes:jpg,jpeg,png,webp,heic,pdf,mp4,mov'],
        ]);

        if (empty($data['garantia_id']) && blank($data['no_serie'] ?? null) && blank($data['equipo'] ?? null)) {
            return back()->withInput()->withErrors(['no_serie' => 'Indica la serie o al menos qué equipo es.']);
        }

        $rec = $this->atencion->abrir($data, $request->user(), $request->file('archivos', []));

        return redirect()->route('garantias.reclamaciones.show', $rec)
            ->with('status', 'Reclamación '.$rec->folio.' registrada.');
    }

    public function show(GarantiaReclamacion $reclamacion): View
    {
        $reclamacion->load([
            'garantia.customer', 'garantia.reclamaciones', 'customer', 'responsable', 'dictaminadaPor',
            'service', 'costos.aprobadoPor', 'costos.creadoPor', 'archivos.usuario', 'sustituciones', 'eventos.usuario',
        ]);

        return view('structure.garantias.reclamaciones.show', [
            'r' => $reclamacion,
            'responsables' => $this->responsables(),
            'servicios' => Service::latest()->limit(50)->get(['id', 'service_number', 'status', 'created_at']),
            'limite' => (float) config('garantias.limite_aprobacion', 5000),
        ]);
    }

    public function update(Request $request, GarantiaReclamacion $reclamacion): RedirectResponse
    {
        $data = $request->validate([
            'responsable_id' => ['nullable', 'exists:users,id'],
            'prioridad' => ['required', Rule::in(array_keys(GarantiaReclamacion::PRIORIDADES))],
            'es_seguridad' => ['nullable', 'boolean'],
        ]);
        $data['es_seguridad'] = (bool) ($data['es_seguridad'] ?? false);

        $reclamacion->fill($data);
        if (! $reclamacion->isDirty()) {
            return back();
        }

        $cambios = [];
        if ($reclamacion->isDirty('responsable_id')) {
            $cambios[] = 'responsable: '.(User::find($data['responsable_id'])?->name ?? 'nadie');
        }
        if ($reclamacion->isDirty('prioridad')) {
            $cambios[] = 'prioridad: '.$reclamacion->prioridadLabel();
            $reclamacion->limite_en = $reclamacion->recibida_en->copy()
                ->addHours((int) (config('garantias.horas_resolucion')[$data['prioridad']] ?? 120));
        }
        if ($reclamacion->isDirty('es_seguridad')) {
            $cambios[] = $data['es_seguridad'] ? 'marcada como posible incidente de seguridad' : 'ya no se considera incidente de seguridad';
        }

        $reclamacion->save();
        $reclamacion->registrar('editada', ucfirst(implode(' · ', $cambios)).'.');

        return back()->with('status', 'Reclamación actualizada.');
    }

    public function vincular(Request $request, GarantiaReclamacion $reclamacion): RedirectResponse
    {
        $data = $request->validate(['garantia_id' => ['required', 'exists:garantias,id']]);
        $this->atencion->vincularGarantia($reclamacion, Garantia::findOrFail($data['garantia_id']));

        return back()->with('status', 'Reclamación ligada a la garantía.');
    }

    public function etapa(Request $request, GarantiaReclamacion $reclamacion): RedirectResponse
    {
        $data = $request->validate([
            'etapa' => ['required', Rule::in(array_keys(GarantiaReclamacion::ETAPAS))],
            'nota' => ['nullable', 'string', 'max:1000'],
        ]);
        $this->atencion->avanzar($reclamacion, $data['etapa'], $data['nota'] ?? null);

        return back()->with('status', 'Etapa: '.$reclamacion->etapaLabel().'.');
    }

    public function diagnostico(Request $request, GarantiaReclamacion $reclamacion): RedirectResponse
    {
        $data = $request->validate([
            'diagnostico' => ['required', 'string', 'max:5000'],
            'pruebas' => ['nullable', 'string', 'max:5000'],
            'causa' => ['nullable', 'string', 'max:2000'],
        ]);
        $this->atencion->diagnosticar($reclamacion, $data);

        return back()->with('status', 'Diagnóstico guardado.');
    }

    public function dictamen(Request $request, GarantiaReclamacion $reclamacion): RedirectResponse
    {
        $data = $request->validate([
            'dictamen' => ['required', Rule::in(['procede', 'parcial', 'no_procede'])],
            'motivo' => ['nullable', 'string', 'max:3000'],
            'fundamento' => ['nullable', 'string', 'max:3000'],
        ]);
        $this->atencion->dictaminar($reclamacion, $data, $request->user());

        return back()->with('status', 'Dictamen registrado: '.$reclamacion->dictamenLabel().'.');
    }

    public function costo(Request $request, GarantiaReclamacion $reclamacion): RedirectResponse
    {
        $data = $request->validate([
            'concepto' => ['required', Rule::in(array_keys(GarantiaCosto::CONCEPTOS))],
            'descripcion' => ['nullable', 'string', 'max:255'],
            'monto' => ['required', 'numeric', 'min:0.01', 'max:99999999'],
            'absorbe' => ['required', Rule::in(array_keys(GarantiaCosto::ABSORBE))],
        ]);
        $costo = $this->atencion->registrarCosto($reclamacion, $data, $request->user());

        return back()->with('status', $costo->estado === 'pendiente'
            ? 'Gasto registrado; supera el límite y quedó esperando aprobación.'
            : 'Gasto registrado.');
    }

    public function resolverCosto(Request $request, GarantiaCosto $costo): RedirectResponse
    {
        $data = $request->validate(['aprobar' => ['required', 'boolean']]);
        $this->atencion->resolverCosto($costo, (bool) $data['aprobar'], $request->user());

        return back()->with('status', $data['aprobar'] ? 'Gasto aprobado.' : 'Gasto rechazado.');
    }

    public function recuperado(Request $request, GarantiaCosto $costo): RedirectResponse
    {
        $data = $request->validate(['recuperado' => ['required', 'numeric', 'min:0']]);
        $this->atencion->marcarRecuperado($costo, (float) $data['recuperado']);

        return back()->with('status', 'Recuperación registrada.');
    }

    public function sustitucion(Request $request, GarantiaReclamacion $reclamacion): RedirectResponse
    {
        $data = $request->validate([
            'tipo' => ['required', Rule::in(array_keys(GarantiaSustitucion::TIPOS))],
            'equipo' => ['required', 'string', 'max:255'],
            'no_serie_entregado' => ['nullable', 'required_if:tipo,definitiva', 'string', 'max:120'],
            'entregado_en' => ['required', 'date'],
            'devolver_antes' => ['nullable', 'date', 'after_or_equal:entregado_en'],
            'condiciones' => ['nullable', 'string', 'max:2000'],
        ], ['no_serie_entregado.required_if' => 'En una sustitución definitiva la serie del equipo nuevo es obligatoria.']);

        $this->atencion->sustituir($reclamacion, $data, $request->user());

        return back()->with('status', 'Registrado.');
    }

    public function devuelto(GarantiaSustitucion $sustitucion): RedirectResponse
    {
        $this->atencion->devolverPrestamo($sustitucion);

        return back()->with('status', 'Préstamo marcado como devuelto.');
    }

    public function servicio(Request $request, GarantiaReclamacion $reclamacion): RedirectResponse
    {
        $data = $request->validate(['service_id' => ['nullable', 'exists:services,id']]);
        $this->atencion->vincularServicio($reclamacion, $data['service_id'] ?? null);

        return back()->with('status', 'Orden de servicio actualizada.');
    }

    public function nota(Request $request, GarantiaReclamacion $reclamacion): RedirectResponse
    {
        $data = $request->validate(['nota' => ['required', 'string', 'max:3000']]);
        $this->atencion->anotar($reclamacion, $data['nota']);

        return back()->with('status', 'Nota agregada.');
    }

    public function archivo(Request $request, GarantiaReclamacion $reclamacion): RedirectResponse
    {
        $request->validate([
            'archivos' => ['required', 'array', 'max:10'],
            'archivos.*' => ['file', 'max:51200', 'mimes:jpg,jpeg,png,webp,heic,pdf,mp4,mov,doc,docx,xls,xlsx'],
            'tipo' => ['required', Rule::in(array_keys(GarantiaArchivo::TIPOS))],
        ]);

        foreach ($request->file('archivos') as $archivo) {
            $this->atencion->guardarArchivo($archivo, $request->input('tipo'), $request->user(), $reclamacion);
        }

        return back()->with('status', 'Archivos agregados.');
    }

    public function cerrar(Request $request, GarantiaReclamacion $reclamacion): RedirectResponse
    {
        $data = $request->validate([
            'resolucion' => ['required', Rule::in(array_keys(GarantiaReclamacion::RESOLUCIONES))],
            'resolucion_detalle' => ['nullable', 'string', 'max:3000'],
            'entregado_a' => ['nullable', 'string', 'max:160'],
            'satisfaccion' => ['nullable', 'integer', 'between:1,5'],
            'comentario_cliente' => ['nullable', 'string', 'max:2000'],
        ]);
        $this->atencion->cerrar($reclamacion, $data);

        return back()->with('status', 'Reclamación cerrada.');
    }

    /** Acuse de recibo (al abrir) o acta de entrega (al cerrar). */
    public function documento(GarantiaReclamacion $reclamacion, string $tipo)
    {
        abort_unless(in_array($tipo, ['acuse', 'acta', 'informe'], true), 404);

        $reclamacion->load(['garantia.customer', 'customer', 'responsable', 'dictaminadaPor', 'costos', 'sustituciones']);

        return Pdf::loadView('structure.garantias.pdf.reclamacion', ['r' => $reclamacion, 'tipo' => $tipo])
            ->setPaper('letter')
            ->stream(ucfirst($tipo).'-'.$reclamacion->folio.'.pdf');
    }

    private function responsables()
    {
        return User::where('status', User::STATUS_APPROVED)->orderBy('name')->get(['id', 'name']);
    }
}
