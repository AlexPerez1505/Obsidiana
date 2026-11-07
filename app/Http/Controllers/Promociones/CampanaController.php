<?php

namespace App\Http\Controllers\Promociones;

use App\Http\Controllers\Controller;
use App\Models\Campana;
use App\Models\CampanaDestinatario;
use App\Models\CampanaItem;
use App\Models\Cupon;
use App\Models\Customer;
use App\Models\MktEvento;
use App\Services\Promociones\Audiencia;
use App\Services\Promociones\CatalogoOferta;
use App\Services\Promociones\Lanzador;
use App\Services\Promociones\MensajeCampana;
use App\Services\Promociones\Resultados;
use App\Services\WhatsApp\EstadoWhatsApp;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CampanaController extends Controller
{
    public function __construct(
        private readonly Audiencia $audiencia,
        private readonly Lanzador $lanzador,
        private readonly MensajeCampana $mensajes,
        private readonly Resultados $resultados,
        private readonly CatalogoOferta $catalogo,
    ) {
    }

    /** Tarjetas y calendario; los datos llegan de CampanasApiController. */
    public function index(): View
    {
        return view('structure.promociones.campanas.index', [
            'boton' => config('promociones.whatsapp.plantilla_boton_texto'),
            'baja' => (bool) config('promociones.whatsapp.plantilla_boton_baja'),
        ]);
    }

    public function create(): View
    {
        return $this->formulario(new Campana(['filtros' => ['modo' => 'segmento', 'segmento' => 'todos']]));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validar($request);

        $campana = DB::transaction(function () use ($data, $request) {
            $c = Campana::create($this->atributos($data, $request) + [
                'estado' => Campana::BORRADOR,
                'creado_por' => auth()->id(),
            ]);
            $this->guardarItems($c, $data['items']);
            MktEvento::registrar('creada', 'Creó la campaña', $c);

            return $c;
        });

        if (! empty($data['programar_para'])) {
            $this->lanzador->programar($campana, Carbon::parse($data['programar_para']));

            return redirect()->route('promociones.campanas.show', $campana)
                ->with('status', 'Campaña programada para el '.$campana->fresh()->programada_para->format('d/m/Y H:i').'. Manda una prueba antes de esa hora.');
        }

        return redirect()->route('promociones.campanas.show', $campana)
            ->with('status', 'Campaña guardada como borrador. Manda una prueba y luego prográmala o envíala.');
    }

    public function edit(Campana $campana): View|RedirectResponse
    {
        if (! $campana->editable()) {
            return redirect()->route('promociones.campanas.show', $campana)->withErrors(['campana' => 'Una campaña enviada, pausada o cancelada ya no se edita. Duplícala para hacer otra.']);
        }

        return $this->formulario($campana);
    }

    public function update(Request $request, Campana $campana): RedirectResponse
    {
        if (! $campana->editable()) {
            return back()->withErrors(['campana' => 'Esta campaña ya no se puede editar.']);
        }

        $data = $this->validar($request, $campana);

        DB::transaction(function () use ($campana, $data, $request) {
            $antes = $campana->only(['nombre', 'mensaje', 'cupon_id', 'vigencia_fin']);
            $campana->update($this->atributos($data, $request, $campana) + ['actualizado_por' => auth()->id()]);
            $this->guardarItems($campana, $data['items']);
            $cambios = array_keys(array_diff_assoc(array_map('strval', $campana->only(array_keys($antes))), array_map('strval', $antes)));
            MktEvento::registrar('editada', 'Editó la campaña'.($cambios ? ' ('.implode(', ', $cambios).')' : ''), $campana);
        });

        if (! empty($data['programar_para'])) {
            $this->lanzador->programar($campana->fresh(), Carbon::parse($data['programar_para']));
        } elseif ($campana->estado === Campana::PROGRAMADA && $request->boolean('quitar_programacion')) {
            $campana->update(['estado' => Campana::BORRADOR, 'programada_para' => null]);
            MktEvento::registrar('desprogramada', 'Quitó la programación (queda en borrador)', $campana);
        }

        return redirect()->route('promociones.campanas.show', $campana)->with('status', 'Campaña actualizada.');
    }

    public function show(Request $request, Campana $campana): View
    {
        $campana->load(['cupon', 'creador', 'items']);

        $filtroEstado = (string) $request->get('estado', '');
        $buscar = trim((string) $request->get('q', ''));

        $destinatarios = $campana->destinatarios()->with('cliente', 'cupon')
            ->when($filtroEstado === 'prueba', fn ($q) => $q->where('es_prueba', true), fn ($q) => $q->where('es_prueba', false))
            ->when($filtroEstado !== '' && $filtroEstado !== 'prueba', fn ($q) => $q->where('estado', $filtroEstado))
            ->when($buscar !== '', fn ($q) => $q->whereHas('cliente', fn ($c) => $c->where('nombre', 'like', "%{$buscar}%")->orWhere('apellido', 'like', "%{$buscar}%")))
            ->latest('id')->paginate(20, ['*'], 'pagina')->withQueryString();

        $audiencia = $campana->puedeEnviarse() ? $this->audiencia->calcular($campana->filtros ?? []) : null;

        return view('structure.promociones.campanas.show', [
            'campana' => $campana,
            'r' => $this->resultados->deCampana($campana),
            'destinatarios' => $destinatarios,
            'filtroEstado' => $filtroEstado,
            'buscar' => $buscar,
            'audiencia' => $audiencia,
            'vistaPrevia' => $this->mensajes->vistaPrevia($campana),
            'productos' => $this->catalogo->itemsDe($campana),
            'eventos' => $campana->eventos()->with('usuario')->limit(40)->get(),
            'numerosPrueba' => config('promociones.numeros_prueba'),
            'whatsapp' => app(EstadoWhatsApp::class),
            'pruebas' => $campana->destinatarios()->where('es_prueba', true)->latest('id')->limit(5)->get(),
        ]);
    }

    /** Cuántos recibirían la campaña con estos filtros (vista previa en el formulario). */
    public function audiencia(Request $request): JsonResponse
    {
        $r = $this->audiencia->calcular($this->filtrosDe($request->input('filtros', [])));

        return response()->json([
            'base' => $r['base'],
            'elegibles' => $r['elegibles']->count(),
            'excluidos' => $r['excluidos'],
            'muestra' => $r['elegibles']->take(8)->map(fn (Customer $c) => trim($c->nombre.' '.$c->apellido))->values(),
        ]);
    }

    public function prueba(Request $request, Campana $campana): RedirectResponse
    {
        $data = $request->validate([
            'telefono' => ['required', 'string', 'max:20'],
            'cliente_id' => ['nullable', 'exists:clientes,id'],
        ]);

        $d = $this->lanzador->enviarPrueba($campana, $data['telefono'], isset($data['cliente_id']) ? Customer::find($data['cliente_id']) : null);

        $msg = match ($d->estado) {
            CampanaDestinatario::ENVIADO => 'Prueba enviada por WhatsApp. Ábrela en el teléfono autorizado.',
            CampanaDestinatario::SIMULADO => 'Prueba registrada en modo simulado (WhatsApp no está en modo real). Puedes abrir el enlace de la prueba desde aquí.',
            default => null,
        };

        return $msg
            ? back()->with('status', $msg)
            : back()->withErrors(['campana' => 'WhatsApp rechazó la prueba: '.($d->error ?? 'sin detalle').($d->error_codigo ? " (código {$d->error_codigo})" : '')]);
    }

    public function enviar(Request $request, Campana $campana): RedirectResponse
    {
        $data = $request->validate([
            'cuando' => ['required', 'in:ahora,programar'],
            'programada_para' => ['required_if:cuando,programar', 'nullable', 'date'],
        ]);

        if ($data['cuando'] === 'programar') {
            $this->lanzador->programar($campana, Carbon::parse($data['programada_para']));

            return back()->with('status', 'Campaña programada para el '.$campana->fresh()->programada_para->format('d/m/Y H:i').'.');
        }

        $n = $this->lanzador->iniciarEnvio($campana);

        return back()->with('status', "Envío iniciado: {$n} mensaje(s) en cola.");
    }

    public function pausar(Request $request, Campana $campana): RedirectResponse|JsonResponse
    {
        $this->lanzador->pausar($campana);

        return $this->respuesta($request, 'Campaña pausada. Los mensajes que faltan se quedan en espera.');
    }

    public function reanudar(Request $request, Campana $campana): RedirectResponse|JsonResponse
    {
        $this->lanzador->reanudar($campana);

        return $this->respuesta($request, 'Campaña reanudada.');
    }

    public function cancelar(Request $request, Campana $campana): RedirectResponse|JsonResponse
    {
        $n = $this->lanzador->cancelar($campana);

        return $this->respuesta($request, 'Campaña cancelada'.($n ? ": {$n} mensaje(s) pendientes ya no se enviarán." : '.'));
    }

    public function duplicar(Request $request, Campana $campana): RedirectResponse|JsonResponse
    {
        $copia = $this->lanzador->duplicar($campana);

        if ($request->expectsJson()) {
            return response()->json(['mensaje' => 'Campaña duplicada como borrador.', 'url' => route('promociones.campanas.edit', $copia)]);
        }

        return redirect()->route('promociones.campanas.edit', $copia)->with('status', 'Campaña duplicada. Ajusta lo que necesites.');
    }

    private function respuesta(Request $request, string $mensaje): RedirectResponse|JsonResponse
    {
        return $request->expectsJson() ? response()->json(['mensaje' => $mensaje]) : back()->with('status', $mensaje);
    }

    // ------------------------------------------------------------------

    private function formulario(Campana $campana): View
    {
        $campana->loadMissing('items');

        return view('structure.promociones.campanas.form', [
            'campana' => $campana,
            'cupones' => Cupon::whereNull('cliente_id')->where('estado', 'activo')
                ->where(fn ($q) => $q->whereNull('vence_en')->orWhere('vence_en', '>', now()))
                ->orderBy('codigo')->get(),
            'itemsIniciales' => $this->catalogo->itemsDe($campana),
            'productosSegmento' => $this->catalogo->resolver((array) ($campana->filtros['productos'] ?? [])),
            'clientesIniciales' => Customer::whereIn('id', (array) ($campana->filtros['clientes'] ?? []))->get(['id', 'nombre', 'apellido', 'telefono']),
            'segmentos' => Audiencia::SEGMENTOS,
            'plantillaTexto' => config('promociones.whatsapp.plantilla_texto'),
        ]);
    }

    private function validar(Request $request, ?Campana $campana = null): array
    {
        return $request->validate([
            'nombre' => ['required', 'string', 'max:255'],
            'titulo_oferta' => ['nullable', 'string', 'max:120'],
            'mensaje' => ['required', 'string', 'max:600'],
            'plantilla' => ['nullable', 'string', 'max:120', 'regex:/^[a-z0-9_]+$/'],
            'imagen' => ['nullable', 'image', 'mimes:jpeg,jpg,png', 'max:5120'],
            'quitar_imagen' => ['nullable', 'boolean'],
            'items' => ['required', 'array', 'min:1', 'max:30'],
            'items.*' => ['string', 'regex:/^(producto|paquete)-\d+$/'],
            'cupon_id' => ['nullable', Rule::exists('mkt_cupones', 'id')->whereNull('cliente_id')],
            'cupon_exclusivo' => ['nullable', 'boolean'],
            'vigencia_inicio' => ['nullable', 'date'],
            'vigencia_fin' => array_merge(['nullable', 'date', 'after:now'], $request->filled('vigencia_inicio') ? ['after:vigencia_inicio'] : []),
            'programar_para' => ['nullable', 'date', 'after:now'],
            'filtros' => ['required', 'array'],
            'filtros.modo' => ['required', 'in:segmento,manual'],
            'filtros.segmento' => ['nullable', Rule::in(array_keys(Audiencia::SEGMENTOS))],
            'filtros.dias' => ['nullable', 'integer', 'min:1', 'max:3650'],
            'filtros.min_compras' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'filtros.productos' => ['nullable', 'array'],
            'filtros.productos.*' => ['string', 'regex:/^(producto|paquete)-\d+$/'],
            'filtros.monto_min' => ['nullable', 'numeric', 'min:0'],
            'filtros.monto_max' => ['nullable', 'numeric', 'gte:filtros.monto_min'],
            'filtros.clientes' => ['nullable', 'array', 'required_if:filtros.modo,manual'],
            'filtros.clientes.*' => ['integer', 'exists:clientes,id'],
        ], [
            'items.required' => 'Agrega al menos un producto a la oferta.',
            'filtros.clientes.required_if' => 'Elige al menos un cliente.',
            'vigencia_fin.after' => 'La vigencia debe terminar después de que empieza y en el futuro.',
        ]);
    }

    private function atributos(array $data, Request $request, ?Campana $campana = null): array
    {
        $attrs = [
            'nombre' => $data['nombre'],
            'titulo_oferta' => $data['titulo_oferta'] ?? null,
            'mensaje' => $data['mensaje'],
            'plantilla' => $data['plantilla'] ?? null,
            'cupon_id' => $data['cupon_id'] ?? null,
            'cupon_exclusivo' => (bool) ($data['cupon_exclusivo'] ?? false) && ! empty($data['cupon_id']),
            'vigencia_inicio' => $data['vigencia_inicio'] ?? null,
            'vigencia_fin' => $data['vigencia_fin'] ?? null,
            'filtros' => $this->filtrosDe($data['filtros']),
        ];

        if ($attrs['cupon_exclusivo'] && ! Cupon::find($attrs['cupon_id'])?->esPlantilla()) {
            throw ValidationException::withMessages(['cupon_id' => 'Para dar un código personal a cada cliente elige un cupón exclusivo sin cliente (molde).']);
        }
        if (! $attrs['cupon_exclusivo'] && $attrs['cupon_id'] && Cupon::find($attrs['cupon_id'])?->esPlantilla()) {
            throw ValidationException::withMessages(['cupon_id' => 'Ese cupón es un molde de códigos personales: marca "Un código personal por cliente".']);
        }

        if ($request->hasFile('imagen')) {
            if ($campana?->imagen_path) {
                Storage::disk('public')->delete($campana->imagen_path);
            }
            $attrs['imagen_path'] = $request->file('imagen')->store('promociones', 'public');
        } elseif (! empty($data['quitar_imagen']) && $campana?->imagen_path) {
            Storage::disk('public')->delete($campana->imagen_path);
            $attrs['imagen_path'] = null;
        }

        return $attrs;
    }

    private function filtrosDe(array $f): array
    {
        $modo = ($f['modo'] ?? 'segmento') === 'manual' ? 'manual' : 'segmento';

        return array_filter([
            'modo' => $modo,
            'segmento' => $modo === 'segmento' ? ($f['segmento'] ?? 'todos') : null,
            'dias' => isset($f['dias']) && $f['dias'] !== '' ? (int) $f['dias'] : null,
            'min_compras' => isset($f['min_compras']) && $f['min_compras'] !== '' ? (int) $f['min_compras'] : null,
            'productos' => array_values(array_unique((array) ($f['productos'] ?? []))) ?: null,
            'monto_min' => isset($f['monto_min']) && $f['monto_min'] !== '' ? (float) $f['monto_min'] : null,
            'monto_max' => isset($f['monto_max']) && $f['monto_max'] !== '' ? (float) $f['monto_max'] : null,
            'clientes' => $modo === 'manual' ? array_values(array_unique(array_map('intval', (array) ($f['clientes'] ?? [])))) : null,
        ], fn ($v) => $v !== null);
    }

    private function guardarItems(Campana $c, array $claves): void
    {
        $c->items()->delete();
        foreach (array_values(array_unique($claves)) as $orden => $clave) {
            [$tipo, $id] = explode('-', $clave, 2);
            CampanaItem::create(['campana_id' => $c->id, 'tipo_item' => $tipo, 'ref_id' => (int) $id, 'orden' => $orden]);
        }
    }
}
