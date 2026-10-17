<?php

namespace App\Http\Controllers\Garantias;

use App\Http\Controllers\Controller;
use App\Models\Garantia;
use App\Models\GarantiaCobertura;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Coberturas tipo. Cambiarlas NO toca las pólizas ya emitidas: cada
 * garantía guardó su propia copia de los textos al nacer.
 */
class CoberturaController extends Controller
{
    public function index(): View
    {
        return view('structure.garantias.coberturas', [
            'coberturas' => GarantiaCobertura::withCount('garantias')->orderByDesc('activo')->orderBy('nombre')->get(),
            'parametros' => [
                'dias_por_vencer' => config('garantias.dias_por_vencer'),
                'avisos' => config('garantias.avisos_vencimiento'),
                'limite' => config('garantias.limite_aprobacion'),
                'respuesta' => config('garantias.horas_primera_respuesta'),
                'resolucion' => config('garantias.horas_resolucion'),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        GarantiaCobertura::create($this->validar($request));

        return back()->with('status', 'Cobertura creada.');
    }

    public function update(Request $request, GarantiaCobertura $cobertura): RedirectResponse
    {
        $cobertura->update($this->validar($request));

        return back()->with('status', 'Cobertura actualizada. Las pólizas ya emitidas conservan sus condiciones.');
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
