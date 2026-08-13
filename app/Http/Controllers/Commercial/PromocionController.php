<?php

namespace App\Http\Controllers\Commercial;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Paquete;
use App\Models\Producto;
use App\Models\Promocion;
use App\Models\PromocionEnvio;
use App\Services\WhatsAppService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class PromocionController extends Controller
{
    /**
     * Lista las promociones creadas.
     */
    public function index(): View
    {
        $promociones = Promocion::with(['categoria', 'producto', 'paquete', 'envios'])
            ->latest()
            ->get();

        return view('structure.commercial_management.promociones.index', [
            'promociones' => $promociones,
        ]);
    }

    /**
     * Muestra el formulario de creación de promoción.
     */
    public function create(): View
    {
        return view('structure.commercial_management.promociones.create', [
            'categorias' => Category::query()->orderBy('nombre')->get(),
            'productos' => Producto::query()->orderBy('tipo_equipo')->get(),
            'paquetes' => Paquete::query()->orderBy('nombre')->get(),
        ]);
    }

    /**
     * Guarda una nueva promoción en estado "borrador".
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:255'],
            'mensaje' => ['required', 'string', 'max:1000'],
            'imagen' => ['nullable', 'image', 'max:5120'],
            'categoria_id' => ['nullable', 'exists:categorias,id'],
            'producto_id' => ['nullable', 'exists:productos,id'],
            'paquete_id' => ['nullable', 'exists:paquetes,id'],
        ]);

        if ($request->hasFile('imagen')) {
            $data['imagen_path'] = $request->file('imagen')->store('promociones', 'public');
        }

        $data['plantilla_whatsapp'] = config('services.whatsapp.default_template');
        $data['creado_por'] = auth()->id();
        $data['estado'] = 'borrador';

        $promocion = Promocion::create($data);

        return redirect()
            ->route('commercial.promociones.show', $promocion)
            ->with('status', 'Promoción guardada. Revisa los destinatarios antes de enviarla.');
    }

    /**
     * Muestra el detalle de una promoción, sus destinatarios candidatos y métricas de envío.
     */
    public function show(Promocion $promocion): View
    {
        $promocion->load(['categoria', 'producto', 'paquete', 'envios.cliente']);

        $destinatarios = $this->resolverDestinatarios($promocion);

        $idsYaEnviados = $promocion->envios()->where('estado', 'enviado')->pluck('cliente_id');

        $whatsapp = app(WhatsAppService::class);

        return view('structure.commercial_management.promociones.show', [
            'promocion' => $promocion,
            'destinatarios' => $destinatarios,
            'idsYaEnviados' => $idsYaEnviados,
            'whatsappConfigurado' => $whatsapp->isConfigured(),
        ]);
    }

    /**
     * Envía la promoción por WhatsApp a todos los clientes candidatos que no la hayan recibido aún.
     */
    public function send(Promocion $promocion): RedirectResponse
    {
        $whatsapp = app(WhatsAppService::class);

        if (! $whatsapp->isConfigured()) {
            return redirect()
                ->route('commercial.promociones.show', $promocion)
                ->with('error', 'No se puede enviar: faltan las credenciales de WhatsApp en el archivo .env.');
        }

        $promocion->update(['estado' => 'enviando']);

        $mediaId = null;
        if ($promocion->imagen_path) {
            $absolutePath = Storage::disk('public')->path($promocion->imagen_path);
            $mimeType = Storage::disk('public')->mimeType($promocion->imagen_path) ?: 'image/jpeg';
            $mediaId = $whatsapp->uploadMedia($absolutePath, $mimeType);
        }

        $destinatarios = $this->resolverDestinatarios($promocion);
        $idsYaEnviados = $promocion->envios()->where('estado', 'enviado')->pluck('cliente_id')->all();

        $enviados = 0;
        $fallidos = 0;
        $omitidos = 0;

        foreach ($destinatarios as $cliente) {
            if (in_array($cliente->id, $idsYaEnviados, true)) {
                continue;
            }

            $telefono = $whatsapp->normalizePhone($cliente->telefono);

            if (! $telefono) {
                PromocionEnvio::updateOrCreate(
                    ['promocion_id' => $promocion->id, 'cliente_id' => $cliente->id],
                    ['canal' => 'whatsapp', 'destino_usado' => $cliente->telefono, 'estado' => 'sin_destino']
                );
                $omitidos++;
                continue;
            }

            $resultado = $whatsapp->sendImageTemplate(
                $telefono,
                $promocion->plantilla_whatsapp ?: config('services.whatsapp.default_template'),
                config('services.whatsapp.template_language', 'es_MX'),
                $mediaId,
                $promocion->mensaje
            );

            PromocionEnvio::updateOrCreate(
                ['promocion_id' => $promocion->id, 'cliente_id' => $cliente->id],
                [
                    'canal' => 'whatsapp',
                    'destino_usado' => $telefono,
                    'estado' => $resultado['success'] ? 'enviado' : 'fallido',
                    'referencia_externa' => $resultado['wamid'],
                    'error_detalle' => $resultado['error'],
                ]
            );

            $resultado['success'] ? $enviados++ : $fallidos++;
        }

        $promocion->update(['estado' => 'completada']);

        return redirect()
            ->route('commercial.promociones.show', $promocion)
            ->with('status', "Envío finalizado: {$enviados} enviados, {$fallidos} fallidos, {$omitidos} sin teléfono válido.");
    }

    /**
     * Elimina una promoción y su historial de envíos.
     */
    public function destroy(Promocion $promocion): RedirectResponse
    {
        if ($promocion->imagen_path) {
            Storage::disk('public')->delete($promocion->imagen_path);
        }

        $promocion->delete();

        return redirect()->route('commercial.promociones.index')->with('status', 'Promoción eliminada correctamente.');
    }

    /**
     * Resuelve los clientes candidatos a recibir la promoción, respetando siempre
     * el consentimiento (recibe_promocion) y el segmento definido (categoría opcional).
     */
    private function resolverDestinatarios(Promocion $promocion)
    {
        return Customer::query()
            ->where('recibe_promocion', true)
            ->where('activo', true)
            ->when($promocion->categoria_id, fn ($q) => $q->where('categoria_id', $promocion->categoria_id))
            ->orderBy('nombre')
            ->get();
    }
}
