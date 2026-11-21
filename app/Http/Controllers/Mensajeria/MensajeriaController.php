<?php

namespace App\Http\Controllers\Mensajeria;

use App\Http\Controllers\Controller;
use App\Models\WaConversacion;
use App\Models\WaMensaje;
use App\Services\Mensajeria\ApiWhatsApp;
use App\Services\Mensajeria\Mensajeria;
use App\Services\Mensajeria\PlantillaArmada;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Mensajería de WhatsApp: la pantalla (bandeja + chat) y los datos JSON que
 * pide su JavaScript. Todo lo de Meta pasa por el servidor; el navegador
 * nunca ve el token.
 */
class MensajeriaController extends Controller
{
    private const POR_PAGINA = 40;

    public function __construct(
        private readonly Mensajeria $mensajeria,
        private readonly ApiWhatsApp $api,
    ) {}

    public function index(Request $request): View
    {
        return view('structure.mensajeria.index', [
            'configurado' => $this->api->configurado(),
            'bandejas' => array_values($this->api->numeros()),
            'puedeResponder' => $request->user()->can('mensajeria.responder'),
            'abrir' => $request->integer('c') ?: null,
            'rev' => Mensajeria::revision(),
        ]);
    }

    /** Conversaciones: las más recientes primero; búsqueda por nombre o teléfono. */
    public function conversaciones(Request $request): JsonResponse
    {
        $q = trim((string) $request->query('q', ''));
        $digitos = preg_replace('/\D+/', '', $q);

        $lista = WaConversacion::query()
            ->with(['ultimoMensaje', 'cliente:id,nombre,apellido'])
            ->when($request->query('bandeja'), fn ($w, $b) => $w->where('phone_number_id', $b))
            ->when($request->query('filtro') === 'sin_leer', fn ($w) => $w->where('no_leidos', '>', 0))
            ->when($q !== '', function ($w) use ($q, $digitos) {
                $w->where(function ($x) use ($q, $digitos) {
                    $x->where('nombre_perfil', 'like', "%{$q}%")
                        ->orWhereHas('cliente', fn ($c) => $c->where('nombre', 'like', "%{$q}%")->orWhere('apellido', 'like', "%{$q}%"));
                    if (strlen($digitos) >= 3) {
                        $x->orWhere('telefono', 'like', "%{$digitos}%");
                    }
                });
            })
            ->when($request->query('antes'), fn ($w, $a) => $w->where('ultima_actividad_en', '<', $a))
            ->orderByDesc('ultima_actividad_en')->orderByDesc('id')
            ->limit(30)
            ->get();

        return response()->json([
            'conversaciones' => $lista->map(fn ($c) => $this->mensajeria->presentarConversacion($c))->values(),
            'hay_mas' => $lista->count() === 30,
            'rev' => Mensajeria::revision(),
        ]);
    }

    /** Mensajes de una conversación, de 40 en 40 hacia atrás. */
    public function mensajes(Request $request, WaConversacion $conversacion): JsonResponse
    {
        $antes = $request->integer('antes');
        $lote = $conversacion->mensajes()->with('autor:id,name')
            ->when($antes, fn ($q) => $q->where('id', '<', $antes))
            ->orderByDesc('id')->limit(self::POR_PAGINA + 1)->get();

        $hayMas = $lote->count() > self::POR_PAGINA;
        $lote = $lote->take(self::POR_PAGINA)->reverse()->values();

        return response()->json([
            'conversacion' => $this->mensajeria->presentarConversacion($conversacion->load('ultimoMensaje', 'cliente')),
            'mensajes' => $this->mensajeria->presentarMensajes($lote),
            'hay_mas' => $hayMas,
        ]);
    }

    /**
     * Lo que cambió desde la última consulta. Si la revisión no cambió, se
     * contesta al instante sin tocar la base.
     */
    public function sync(Request $request): JsonResponse
    {
        $rev = Mensajeria::revision();
        if ((string) $request->query('rev') === $rev) {
            return response()->json(['rev' => $rev, 'sin_cambios' => true]);
        }

        $desde = $request->query('desde') ? \Illuminate\Support\Carbon::parse($request->query('desde')) : now()->subMinute();
        $ahora = now();

        $convs = WaConversacion::with(['ultimoMensaje', 'cliente:id,nombre,apellido'])
            ->where('updated_at', '>=', $desde)
            ->orderByDesc('ultima_actividad_en')->limit(50)->get();

        $mensajes = [];
        if ($id = $request->integer('conv')) {
            $lote = WaMensaje::with('autor:id,name')->where('conversacion_id', $id)
                ->where('updated_at', '>=', $desde)->orderBy('id')->limit(100)->get();
            $mensajes = $this->mensajeria->presentarMensajes($lote);
        }

        return response()->json([
            'rev' => $rev,
            // Un poco hacia atrás: lo que se guardó en el mismo segundo no se pierde.
            'desde' => $ahora->copy()->subSeconds(3)->toIso8601String(),
            'conversaciones' => $convs->map(fn ($c) => $this->mensajeria->presentarConversacion($c))->values(),
            'mensajes' => $mensajes,
            'no_leidos' => (int) WaConversacion::where('no_leidos', '>', 0)->count(),
        ]);
    }

    public function leer(WaConversacion $conversacion): JsonResponse
    {
        $this->mensajeria->marcarLeida($conversacion);

        return response()->json(['ok' => true]);
    }

    public function enviar(Request $request, WaConversacion $conversacion): JsonResponse
    {
        $data = $request->validate([
            'texto' => ['nullable', 'string', 'max:4096'],
            'archivo' => ['nullable', 'file', 'max:102400'],
            'voz' => ['nullable', 'boolean'],
            'uuid' => ['nullable', 'uuid'],
            'responde_a' => ['nullable', 'string', 'max:191'],
        ]);

        $respondeA = $data['responde_a'] ?? null;
        if ($respondeA && ! $conversacion->mensajes()->where('wamid', $respondeA)->exists()) {
            $respondeA = null;
        }

        $m = $request->hasFile('archivo')
            ? $this->mensajeria->enviarArchivo($conversacion, $request->file('archivo'), $request->user(), $data['texto'] ?? null, (bool) ($data['voz'] ?? false), $data['uuid'] ?? null, $respondeA)
            : $this->mensajeria->enviarTexto($conversacion, (string) ($data['texto'] ?? ''), $request->user(), $data['uuid'] ?? null, $respondeA);

        return response()->json(['mensaje' => $this->mensajeria->presentarMensajes([$m->load('autor:id,name')])[0]]);
    }

    public function reintentar(WaMensaje $mensaje): JsonResponse
    {
        $m = $this->mensajeria->reintentar($mensaje);

        return response()->json(['mensaje' => $this->mensajeria->presentarMensajes([$m->load('autor:id,name')])[0]]);
    }

    public function reintentarMedia(WaMensaje $mensaje): JsonResponse
    {
        if ($mensaje->direccion !== WaMensaje::ENTRANTE || ! $mensaje->media_id) {
            throw ValidationException::withMessages(['archivo' => 'Este archivo no se puede volver a pedir a WhatsApp.']);
        }

        $this->mensajeria->bajarMedia($mensaje);

        return response()->json(['mensaje' => $this->mensajeria->presentarMensajes([$mensaje->fresh()->load('autor:id,name')])[0]]);
    }

    /** El archivo de un mensaje (solo con permiso de Mensajería). Soporta rangos para audio y video. */
    public function media(Request $request, WaMensaje $mensaje): BinaryFileResponse
    {
        abort_unless($mensaje->media_path && Storage::disk('local')->exists($mensaje->media_path), 404);

        $r = response()->file(Storage::disk('local')->path($mensaje->media_path), [
            'Content-Type' => $mensaje->media_mime ?: 'application/octet-stream',
            'Cache-Control' => 'private, max-age=86400',
            'X-Content-Type-Options' => 'nosniff',
        ]);
        $nombre = $mensaje->media_nombre ?: basename($mensaje->media_path);
        $r->setContentDisposition($request->boolean('descargar') ? 'attachment' : 'inline', $nombre, \Illuminate\Support\Str::ascii($nombre) ?: 'archivo');

        return $r;
    }

    public function plantillas(Request $request): JsonResponse
    {
        $lista = $this->api->plantillas($request->boolean('fresco'));
        if ($lista === null) {
            return response()->json(['plantillas' => [], 'error' => 'No se pudieron cargar las plantillas de WhatsApp. Revisa la conexión con Meta.'], 200);
        }

        return response()->json([
            'plantillas' => collect($lista)->map(function ($p) {
                $arm = new PlantillaArmada($p);
                $motivo = $arm->soportada();

                return [
                    'nombre' => $p['name'],
                    'idioma' => $p['language'],
                    'categoria' => $p['category'] ?? null,
                    'estado' => $p['status'] ?? null,
                    'motivo' => $p['rejected_reason'] ?? null,
                    'calidad' => $p['quality_score']['score'] ?? null,
                    'formato' => $p['parameter_format'] ?? 'POSITIONAL',
                    'componentes' => $p['components'] ?? [],
                    'media' => $arm->mediaEncabezado(),
                    'usable' => $motivo === '',
                    'no_usable' => $motivo ?: null,
                ];
            })->sortBy([['usable', 'desc'], ['nombre', 'asc']])->values(),
        ]);
    }

    public function enviarPlantilla(Request $request, WaConversacion $conversacion): JsonResponse
    {
        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:512'],
            'idioma' => ['required', 'string', 'max:15'],
            'valores' => ['nullable', 'string', 'max:20000'], // JSON {header, body, botones}
            'media' => ['nullable', 'file', 'max:102400'],
            'uuid' => ['nullable', 'uuid'],
        ]);

        $valores = json_decode((string) ($data['valores'] ?? '{}'), true) ?: [];

        $m = $this->mensajeria->enviarPlantilla($conversacion, $data['nombre'], $data['idioma'], $valores, $request->file('media'), $request->user(), $data['uuid'] ?? null);

        return response()->json(['mensaje' => $this->mensajeria->presentarMensajes([$m->load('autor:id,name')])[0]]);
    }

    public function contacto(WaConversacion $conversacion): JsonResponse
    {
        $c = $conversacion->cliente;
        $cliente = null;

        if ($c) {
            $ventas = $c->ventas()->latest('id')->limit(3)->get(['id', 'folio', 'total', 'estado', 'created_at']);
            $cliente = [
                'id' => $c->id,
                'nombre' => trim($c->nombre.' '.$c->apellido),
                'telefono' => $c->telefono,
                'correo' => $c->gmail,
                'direccion' => $c->direccion,
                'rfc' => $c->rfc,
                'url' => route('commercial.clientes.show', $c),
                'ventas_total' => $c->ventas()->count(),
                'cotizaciones_total' => $c->cotizaciones()->count(),
                'ventas' => $ventas->map(fn ($v) => [
                    'folio' => $v->folio, 'total' => (float) $v->total, 'estado' => $v->estado,
                    'fecha' => $v->created_at?->format('d/m/Y'), 'url' => route('commercial.ventas.show', $v->id),
                ]),
            ];
        }

        return response()->json([
            'nombre_perfil' => $conversacion->nombre_perfil,
            'telefono' => Mensajeria::telefonoBonito($conversacion->telefono),
            'cliente' => $cliente,
            'desde' => $conversacion->created_at?->toIso8601String(),
            'mensajes' => $conversacion->mensajes()->count(),
            'nuevo_cliente_url' => route('commercial.clientes.create', ['telefono' => $conversacion->telefono]),
        ]);
    }

    public function nueva(Request $request): JsonResponse
    {
        $data = $request->validate(['telefono' => ['required', 'string', 'max:30'], 'bandeja' => ['nullable', 'string', 'max:40']]);
        $c = $this->mensajeria->iniciar($data['telefono'], $data['bandeja'] ?? null);

        return response()->json(['conversacion' => $this->mensajeria->presentarConversacion($c->load('ultimoMensaje', 'cliente'))]);
    }
}
