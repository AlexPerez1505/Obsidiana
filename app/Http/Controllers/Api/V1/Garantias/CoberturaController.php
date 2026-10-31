<?php

namespace App\Http\Controllers\Api\V1\Garantias;

use App\Http\Controllers\Controller;
use App\Models\Garantia;
use App\Models\GarantiaCobertura;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Pólizas y coberturas: las condiciones que se copian a cada garantía
 * nueva. Editarlas no toca las pólizas ya emitidas.
 *
 * @tags Garantías
 */
class CoberturaController extends Controller
{
    /**
     * Coberturas con su conteo de garantías y los parámetros del módulo.
     */
    public function index(): JsonResponse
    {
        return response()->json([
            'data' => GarantiaCobertura::withCount('garantias')->orderByDesc('activo')->orderBy('nombre')->get()
                ->map(fn (GarantiaCobertura $c) => [
                    'id' => $c->id,
                    'nombre' => $c->nombre,
                    'tipo' => $c->tipo,
                    'tipo_label' => $c->tipoLabel(),
                    'meses' => $c->meses,
                    'aplica_a' => $c->aplica_a,
                    'aplica_label' => $c->aplicaLabel(),
                    'cubre' => $c->cubre,
                    'exclusiones' => $c->exclusiones,
                    'condiciones' => $c->condiciones,
                    'activo' => (bool) $c->activo,
                    'garantias' => $c->garantias_count,
                ])->values(),
            'parametros' => [
                'dias_por_vencer' => config('garantias.dias_por_vencer'),
                'avisos' => config('garantias.avisos_vencimiento'),
                'limite' => config('garantias.limite_aprobacion'),
                'respuesta' => config('garantias.horas_primera_respuesta'),
                'resolucion' => config('garantias.horas_resolucion'),
            ],
        ]);
    }

    /**
     * Nueva cobertura.
     */
    public function store(Request $request): JsonResponse
    {
        $cobertura = GarantiaCobertura::create($this->validar($request));

        return response()->json([
            'data' => ['id' => $cobertura->id, 'nombre' => $cobertura->nombre, 'tipo' => $cobertura->tipo],
            'message' => 'Cobertura creada.',
        ], 201);
    }

    /**
     * Editar cobertura (las pólizas emitidas conservan sus condiciones).
     */
    public function update(Request $request, GarantiaCobertura $cobertura): JsonResponse
    {
        $cobertura->update($this->validar($request));

        return response()->json(['message' => 'Cobertura actualizada. Las pólizas ya emitidas conservan sus condiciones.']);
    }

    private function validar(Request $request): array
    {
        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:160'],
            'tipo' => ['required', Rule::in(array_keys(Garantia::TIPOS))],
            'meses' => ['required', 'integer', 'min:1', 'max:120'],
            'aplica_a' => ['nullable', Rule::in(['nuevo', 'usado'])],
            'cubre' => ['nullable', 'string', 'max:5000'],
            'exclusiones' => ['nullable', 'string', 'max:5000'],
            'condiciones' => ['nullable', 'string', 'max:5000'],
            'activo' => ['nullable', 'boolean'],
        ]);
        $data['activo'] = (bool) ($data['activo'] ?? false);

        return $data;
    }
}
