<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cotizacion;
use App\Models\PaqueteriaEnvio;
use App\Models\PaqueteriaGuia;
use App\Models\Producto;
use App\Models\Equipo;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class PaqueteriaController extends Controller
{
    public function index(): View
    {
        $remisiones = Cotizacion::query()
            ->whereIn('estado', ['aceptada', 'convertida'])
                ->with(['customer', 'items.producto', 'items.equipo'])
            ->latest('updated_at')
            ->get()
            ->map(function (Cotizacion $remision): array {
                $cliente = $remision->customer;
                $destino = collect([$cliente?->municipio, $cliente?->estado])
                    ->filter()
                    ->implode(', ');
                $direccion = $cliente?->direccion ?: collect([$cliente?->calle, $cliente?->colonia])
                    ->filter()
                    ->implode(', ');
                $destino = $destino ?: ($remision->lugar_propuesta ?: $direccion);

                return [
                    'id' => $remision->id,
                    'folio' => $remision->folio ?: (string) $remision->id,
                    'fecha' => $remision->updated_at?->format('Y-m-d'),
                    'cliente' => trim(($cliente?->nombre ?? '').' '.($cliente?->apellido ?? '')),
                    'destino' => $destino,
                    'codigo_postal' => $cliente?->codigo_postal,
                    'direccion' => $direccion,
                    'telefono' => $cliente?->telefono,
                        'articulos' => $remision->items->flatMap(function ($item): array {
                            $nombre = collect([
                                $item->nombre,
                                $item->marca,
                                $item->modelo,
                            ])->filter(fn ($parte) => filled($parte))->implode(' · ');

                            $series = [];
                            if (filled($item->no_series ?? null)) {
                                $series = is_array($item->no_series)
                                    ? $item->no_series
                                    : preg_split('/[,\n]+/', (string) $item->no_series);
                            } elseif ($item->equipo?->serie) {
                                $series = [$item->equipo->serie];
                            } elseif ($item->producto?->no_serie) {
                                $series = preg_split('/[,\n]+/', (string) $item->producto->no_serie);
                            }

                            $cantidad = max(1, (int) ($item->cantidad ?: 1));

                            return collect(range(0, $cantidad - 1))->map(fn (int $indice): array => [
                                'nombre' => $nombre,
                                'serie' => trim((string) ($series[$indice] ?? '')),
                            ])->all();
                        })->values()->all(),
                ];
            })
            ->values();

        $usuarios = User::query()
            ->where('status', User::STATUS_APPROVED)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (User $usuario): array => [
                'id' => $usuario->id,
                'nombre' => $usuario->name,
            ])
            ->values();

        $envios = PaqueteriaEnvio::query()
            ->with('creador:id,name')
            ->latest('fecha_envio')
            ->latest('id')
            ->limit(100)
            ->get()
            ->map(fn (PaqueteriaEnvio $envio): array => $this->envioJson($envio))
            ->values();

        $resumen = [
            'total' => $envios->count(),
            'costo' => (float) $envios->where('costo_pendiente', false)->sum('costo'),
            'en_camino' => $envios->where('estado', 'en_camino')->count(),
            'por_enviar' => $envios->where('estado', 'por_enviar')->count(),
            'por_completar' => $envios->where('costo_pendiente', true)->count(),
        ];

        $guias = PaqueteriaGuia::query()
            ->orderBy('paqueteria')
            ->orderBy('estado')
            ->orderBy('id')
            ->get(['id', 'paqueteria', 'numero_guia', 'capacidad_kg', 'estado', 'envio_id', 'usada_at'])
            ->map(fn (PaqueteriaGuia $guia): array => [
                'id' => $guia->id,
                'paqueteria' => $guia->paqueteria,
                'numero' => $guia->numero_guia,
                'capacidad' => (float) $guia->capacidad_kg,
                'estado' => $guia->estado,
                'envio_id' => $guia->envio_id,
                'usada_at' => $guia->usada_at?->format('Y-m-d H:i'),
            ])
            ->values();

        $guiasFedex = $guias->filter(fn (array $guia): bool =>
            $guia['paqueteria'] === 'FedEx' && $guia['estado'] === 'disponible'
        )->values();

        $catalogoArticulos = Producto::query()
            ->with(['serialesDisponibles:id,producto_id,no_serie'])
            ->orderBy('tipo_equipo')->orderBy('marca')->orderBy('modelo')->get()
            ->map(function (Producto $producto): array {
                $series = $producto->serialesDisponibles->pluck('no_serie')->filter()->values();
                if ($series->isEmpty() && filled($producto->no_serie)) {
                    $series = collect(preg_split('/[,\n]+/', (string) $producto->no_serie))
                        ->map(fn ($serie) => trim((string) $serie))->filter()->values();
                }
                return [
                    'clave' => 'producto-'.$producto->id,
                    'nombre' => collect([$producto->nombreVisible(), $producto->marca, $producto->modelo])->filter()->implode(' · '),
                    'series' => $series->all(),
                ];
            });

        $catalogoArticulos = $catalogoArticulos->concat(
            Equipo::query()->where('activo', true)->orderBy('tipo')->orderBy('marca')->orderBy('modelo')
                ->get(['id', 'tipo', 'subtipo', 'marca', 'modelo', 'serie'])
                ->map(fn (Equipo $equipo): array => [
                    'clave' => 'equipo-'.$equipo->id,
                    'nombre' => collect([$equipo->subtipo ?: $equipo->tipo, $equipo->marca, $equipo->modelo])->filter()->implode(' · '),
                    'series' => filled($equipo->serie) ? [(string) $equipo->serie] : [],
                ])
        )->sortBy('nombre', SORT_NATURAL | SORT_FLAG_CASE)->values();

        return view('admin.paqueteria.index', compact('remisiones', 'usuarios', 'envios', 'resumen', 'guiasFedex', 'guias', 'catalogoArticulos'));
    }

    public function store(Request $request): JsonResponse
    {
        $this->normalizarArreglosJson($request, ['articulos', 'paquetes', 'responsables']);
        $data = $request->validate([
            'cotizacion_id' => ['nullable', 'integer', 'exists:cotizaciones,id'],
            'remision_folio' => ['nullable', 'string', 'max:80'],
            'cliente' => ['required', 'string', 'max:255'],
            'destino' => ['required', 'string', 'max:255'],
            'codigo_postal' => ['nullable', 'string', 'max:20'],
            'direccion' => ['nullable', 'string', 'max:500'],
            'telefono_receptor' => ['nullable', 'string', 'max:80'],
            'paqueteria' => ['required', 'string', 'max:120'],
            'paqueteria_personalizada' => ['nullable', 'string', 'max:120'],
            'tipo_guia' => ['nullable', 'string', 'in:fisica,virtual,nueva'],
            'guia_id' => ['nullable', 'integer', 'exists:paqueteria_guias,id'],
            'numero_guia' => ['nullable', 'string', 'max:120', 'unique:paqueteria_envios,numero_guia'],
            'capacidad_guia' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
            'fecha_envio' => ['required', 'date'],
            'fecha_entrega_estimada' => ['nullable', 'date', 'after_or_equal:fecha_envio'],
            'costo' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99'],
            'costo_pendiente' => ['required', 'boolean'],
            'peso_real' => ['required', 'numeric', 'min:0'],
            'peso_volumetrico' => ['required', 'numeric', 'min:0'],
            'peso_cobro' => ['required', 'numeric', 'min:0'],
            'paquetes_count' => ['required', 'integer', 'min:0'],
            'articulos' => ['nullable', 'array'],
            'paquetes' => ['nullable', 'array'],
            'responsables' => ['nullable', 'array'],
            'responsables.*' => ['nullable'],
            'notas' => ['nullable', 'string', 'max:5000'],
            'ticket' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:10240'],
        ]);

        if ($data['costo_pendiente']) {
            $data['costo'] = null;
        }

        if (($data['tipo_guia'] ?? null) === 'fisica') {
            if (empty($data['guia_id'])) {
                return response()->json(['message' => 'Selecciona una guía física disponible.'], 422);
            }
            $guia = PaqueteriaGuia::query()
                ->whereKey($data['guia_id'])
                ->where('paqueteria', 'FedEx')
                ->where('estado', 'disponible')
                ->first();
            if (!$guia) {
                return response()->json(['message' => 'La guía física ya no está disponible. Recarga la pantalla y selecciona otra.'], 422);
            }
            $data['numero_guia'] = $guia->numero_guia;
            $data['capacidad_guia'] = $guia->capacidad_kg;
        }

        $data['estado'] = filled($data['numero_guia'] ?? null) ? 'en_camino' : 'por_enviar';
        $data['created_by'] = $request->user()?->id;
        $data['ticket_path'] = $request->file('ticket')?->store('paqueteria/tickets', 'public');
        unset($data['ticket'], $data['guia_id']);

        $envio = DB::transaction(function () use ($data, $request): PaqueteriaEnvio {
            $envio = PaqueteriaEnvio::create($data);
            if (($data['tipo_guia'] ?? null) === 'fisica') {
                PaqueteriaGuia::query()
                    ->whereKey($request->input('guia_id'))
                    ->where('estado', 'disponible')
                    ->update([
                        'estado' => 'usada',
                        'envio_id' => $envio->id,
                        'usada_at' => now(),
                    ]);
            }
            return $envio;
        });

        return response()->json([
            'message' => 'Envío guardado correctamente.',
            'envio' => $this->envioJson($envio->load('creador:id,name')),
        ], 201);
    }

    public function show(PaqueteriaEnvio $paqueteriaEnvio): JsonResponse
    {
        return response()->json(['envio' => $this->envioJson($paqueteriaEnvio->load('creador:id,name'))]);
    }

    public function update(Request $request, PaqueteriaEnvio $paqueteriaEnvio): JsonResponse
    {
        $this->normalizarArreglosJson($request, ['responsables']);
        $request->merge(['fecha_envio' => $paqueteriaEnvio->fecha_envio?->format('Y-m-d')]);
        $data = $request->validate([
            'fecha_entrega_estimada' => ['nullable', 'date', 'after_or_equal:fecha_envio'],
            'costo' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99'],
            'costo_pendiente' => ['required', 'boolean'],
            'notas' => ['nullable', 'string', 'max:5000'],
            'responsables' => ['nullable', 'array'],
            'ticket' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:10240'],
        ]);

        $data['costo'] = $data['costo_pendiente'] ? null : ($data['costo'] ?? $paqueteriaEnvio->costo);
        if ($request->hasFile('ticket')) {
            $data['ticket_path'] = $request->file('ticket')->store('paqueteria/tickets', 'public');
        }
        unset($data['ticket']);
        $paqueteriaEnvio->update($data);

        return response()->json([
            'message' => 'Envío actualizado correctamente.',
            'envio' => $this->envioJson($paqueteriaEnvio->fresh('creador:id,name')),
        ]);
    }

    private function envioJson(PaqueteriaEnvio $envio): array
    {
        return [
            'id' => $envio->id,
            'cotizacion_id' => $envio->cotizacion_id,
            'remision_folio' => $envio->remision_folio,
            'cliente' => $envio->cliente,
            'destino' => $envio->destino,
            'codigo_postal' => $envio->codigo_postal,
            'direccion' => $envio->direccion,
            'telefono_receptor' => $envio->telefono_receptor,
            'paqueteria' => $envio->paqueteriaNombre(),
            'tipo_guia' => $envio->tipo_guia,
            'numero_guia' => $envio->numero_guia,
            'fecha_envio' => $envio->fecha_envio?->format('Y-m-d'),
            'fecha_entrega_estimada' => $envio->fecha_entrega_estimada?->format('Y-m-d'),
            'costo' => $envio->costo !== null ? (float) $envio->costo : null,
            'costo_pendiente' => $envio->costo_pendiente,
            'peso_cobro' => (float) $envio->peso_cobro,
            'paquetes_count' => $envio->paquetes_count,
            'estado' => $envio->estado,
            'articulos' => $envio->articulos ?: [],
            'paquetes' => $envio->paquetes ?: [],
            'responsables' => $envio->responsables ?: [],
            'notas' => $envio->notas,
            'ticket_url' => $envio->ticketUrl(),
            'tracking_url' => $envio->trackingUrl(),
            'creador' => $envio->creador?->name,
        ];
    }

    private function normalizarArreglosJson(Request $request, array $campos): void
    {
        foreach ($campos as $campo) {
            $valor = $request->input($campo);
            if (is_string($valor)) {
                $decodificado = json_decode($valor, true);
                $request->merge([$campo => is_array($decodificado) ? $decodificado : []]);
            }
        }
    }
}
