<?php

namespace App\Http\Controllers\Api\V1\Tienda;

use App\Http\Controllers\Controller;
use App\Mail\TiendaSolicitudMail;
use App\Models\Customer;
use App\Models\TiendaSolicitud;
use App\Services\Tienda\CatalogoTienda;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Tienda en línea (pública, sin token).
 *
 * La consume el sitio en React. Solo lectura del catálogo y el envío de
 * solicitudes de cotización; nada de precios.
 *
 * @tags Tienda pública
 */
class TiendaController extends Controller
{
    public function __construct(private CatalogoTienda $catalogo) {}

    /** Datos de contacto y textos que la tienda necesita para arrancar. */
    public function config(): JsonResponse
    {
        return response()->json([
            'empresa' => config('medibuy.empresa.nombre'),
            'whatsapp' => preg_replace('/\D+/', '', (string) config('medibuy.tienda.whatsapp')),
            'correo' => config('medibuy.tienda.correo'),
            'telefono' => config('medibuy.contacto.telefono'),
            'ubicacion' => config('medibuy.empresa.ubicacion'),
            'motivos' => collect(TiendaSolicitud::MOTIVOS)
                ->map(fn ($texto, $valor) => ['valor' => $valor, 'texto' => $texto])->values(),
        ]);
    }

    /** Categorías y marcas para los filtros. */
    public function filtros(): JsonResponse
    {
        return response()->json($this->catalogo->filtros());
    }

    /**
     * Catálogo paginado.
     *
     * Filtros: q, categoria, marca, fuente (producto|equipo|paquete),
     * orden (recientes|nombre|marca), page, per_page (máx. 48).
     */
    public function catalogo(Request $request): JsonResponse
    {
        $filtros = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'categoria' => ['nullable', 'string', 'max:120'],
            'marca' => ['nullable', 'string', 'max:120'],
            'fuente' => ['nullable', 'in:'.implode(',', CatalogoTienda::FUENTES)],
            'orden' => ['nullable', 'in:recientes,nombre,marca'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:48'],
        ]);

        $todos = $this->catalogo->buscar($filtros);
        $porPagina = (int) ($filtros['per_page'] ?? 24);
        $pagina = (int) ($filtros['page'] ?? 1);

        return response()->json([
            'data' => $todos->forPage($pagina, $porPagina)
                ->map(fn ($a) => CatalogoTienda::publico($a))->values(),
            'meta' => [
                'total' => $todos->count(),
                'pagina' => $pagina,
                'por_pagina' => $porPagina,
                'ultima_pagina' => max(1, (int) ceil($todos->count() / $porPagina)),
            ],
        ]);
    }

    /** Detalle de un artículo por su clave (producto-24, equipo-1, paquete-3). */
    public function articulo(string $clave): JsonResponse
    {
        $articulo = $this->catalogo->buscarPorClave($clave);
        abort_unless($articulo, 404, 'Ese artículo ya no está disponible.');

        $relacionados = $this->catalogo->todo()
            ->where('clave', '!=', $articulo['clave'])
            ->filter(fn ($a) => $a['categoria'] === $articulo['categoria'] || ($articulo['marca'] && $a['marca'] === $articulo['marca']))
            ->take(4)
            ->map(fn ($a) => CatalogoTienda::publico($a))
            ->values();

        return response()->json([
            'data' => CatalogoTienda::publico($articulo),
            'relacionados' => $relacionados,
        ]);
    }

    /**
     * Solicitud de cotización.
     *
     * Se guarda en Obsidiana (Comercial → Solicitudes web) y se avisa por
     * correo si está configurado. Si el teléfono o correo ya pertenecen a
     * un cliente, la solicitud queda ligada a él.
     */
    public function solicitar(Request $request): JsonResponse
    {
        // Campo trampa: los bots lo llenan, las personas no lo ven.
        if (filled($request->input('sitio_web'))) {
            return response()->json(['folio' => 'WEB-'.now()->format('Y').'-0000'], 201);
        }

        $esContacto = $request->input('tipo') === 'contacto';

        $datos = $request->validate([
            'tipo' => ['nullable', 'in:cotizacion,contacto'],
            'motivo' => [$esContacto ? 'required' : 'nullable', 'in:'.implode(',', array_keys(TiendaSolicitud::MOTIVOS))],
            'nombre' => ['required', 'string', 'max:120'],
            'empresa' => ['nullable', 'string', 'max:160'],
            'correo' => ['nullable', 'email', 'max:160'],
            'telefono' => ['required', 'string', 'max:30', 'regex:/^[0-9+()\-\s]{8,}$/'],
            'ciudad' => ['nullable', 'string', 'max:120'],
            'especialidad' => ['nullable', 'string', 'max:120'],
            'mensaje' => [$esContacto ? 'required' : 'nullable', 'string', 'max:2000'],
            'articulos' => [$esContacto ? 'nullable' : 'required', 'array', 'min:1', 'max:30'],
            'articulos.*.clave' => ['required', 'string', 'max:30'],
            'articulos.*.cantidad' => ['required', 'integer', 'min:1', 'max:99'],
        ], [
            'telefono.regex' => 'Escribe un teléfono válido (al menos 8 dígitos).',
            'articulos.required' => 'Agrega al menos un equipo a tu solicitud.',
            'motivo.required' => 'Elige el motivo de tu mensaje.',
            'mensaje.required' => 'Cuéntanos en qué te podemos ayudar.',
        ], [
            'telefono' => 'teléfono',
            'correo' => 'correo',
            'empresa' => 'hospital o clínica',
        ]);

        // Se toma la información del catálogo, no lo que mande el navegador.
        $articulos = collect($datos['articulos'] ?? [])
            ->map(function ($fila) {
                $a = $this->catalogo->buscarPorClave($fila['clave']);

                return $a ? [
                    'clave' => $a['clave'],
                    'fuente' => $a['fuente'],
                    'id' => $a['id'],
                    'nombre' => $a['nombre'],
                    'marca' => $a['marca'],
                    'modelo' => $a['modelo'],
                    'disponible' => $a['disponible'],
                    'cantidad' => (int) $fila['cantidad'],
                ] : null;
            })
            ->filter()
            ->values();

        abort_if(! $esContacto && $articulos->isEmpty(), 422, 'Los equipos que elegiste ya no están disponibles. Actualiza la página.');

        $similar = Customer::buscarSimilar($datos['telefono'], $datos['correo'] ?? null);

        $solicitud = TiendaSolicitud::create([
            ...collect($datos)->except('articulos')->all(),
            'folio' => TiendaSolicitud::siguienteFolio(),
            'tipo' => $esContacto ? 'contacto' : 'cotizacion',
            'articulos' => $articulos->isEmpty() ? null : $articulos->all(),
            'estado' => 'nueva',
            'customer_id' => $similar['cliente']->id ?? null,
            'ip' => $request->ip(),
        ]);

        if ($destino = config('medibuy.tienda.avisar_a')) {
            try {
                Mail::to(array_map('trim', explode(',', $destino)))->send(new TiendaSolicitudMail($solicitud));
            } catch (\Throwable $e) {
                Log::warning('No se pudo avisar la solicitud web '.$solicitud->folio.': '.$e->getMessage());
            }
        }

        return response()->json([
            'folio' => $solicitud->folio,
            'message' => $esContacto
                ? 'Recibimos tu mensaje. Te responderemos muy pronto.'
                : 'Recibimos tu solicitud. Un asesor te contactará muy pronto.',
        ], 201);
    }
}
