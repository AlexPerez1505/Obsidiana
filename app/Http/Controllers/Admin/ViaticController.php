<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Viatic;
use App\Models\ViaticExpense;
use App\Models\Vehicle;
use App\Services\Administracion\GastosDeViaje;
use App\Services\Administracion\Viaticos;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Pantallas de Viáticos. Alta, edición, fotos y gastos viven en Viaticos
 * y GastosDeViaje, que comparten con la API.
 */
class ViaticController extends Controller
{
    public function __construct(
        private Viaticos $viaticos,
        private GastosDeViaje $gastos,
    ) {
    }

    public function index(Request $request): View
    {
        $viatics = $this->viaticos
            ->visiblesPara($request->user(), $request->get('search'), $request->get('status'))
            ->get();

        return view('admin.viatics.index', [
            'viatics'  => $viatics,
            'filters'  => $request->only(['search', 'status']),
        ]);
    }

    public function create(): View
    {
        $vehicles = Vehicle::orderBy('brand')->get();

        return view('admin.viatics.create', [
            'vehicles' => $vehicles,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate(Viaticos::REGLAS);

        $viatic = $this->viaticos->registrar($data, $request->user(), $request->file('ticket_photos', []));

        return redirect()->route('admin.viatics.show', $viatic)->with('status', 'Viático registrado correctamente.');
    }

    public function edit(Viatic $viatic): View
    {
        $this->authorizeViatic($viatic);

        $vehicles = Vehicle::orderBy('brand')->get();

        return view('admin.viatics.edit', [
            'viatic'  => $viatic,
            'vehicles' => $vehicles,
        ]);
    }

    public function update(Request $request, Viatic $viatic): RedirectResponse
    {
        $this->authorizeViatic($viatic);

        $data = $request->validate(Viaticos::REGLAS + Viaticos::REGLAS_QUITAR_FOTOS);

        $this->viaticos->actualizar($viatic, $data, $request->file('ticket_photos', []));

        if ($request->input('redirect_to') === 'show') {
            return redirect()->route('admin.viatics.show', $viatic)->with('status', 'Fotos actualizadas correctamente.');
        }

        return redirect()->route('admin.viatics.index')->with('status', 'Viático actualizado correctamente.');
    }

    public function destroy(Viatic $viatic): RedirectResponse
    {
        $this->authorizeViatic($viatic);

        $this->viaticos->eliminar($viatic);

        return redirect()->route('admin.viatics.index')->with('status', 'Viático eliminado correctamente.');
    }

    public function show(Viatic $viatic): View
    {
        $this->authorizeViatic($viatic);

        $viatic->load('expenses');

        return view('admin.viatics.show', [
            'viatic' => $viatic,
        ]);
    }

    public function addExpense(Request $request, Viatic $viatic): JsonResponse
    {
        $this->authorizeViatic($viatic);

        $data = $request->validate(GastosDeViaje::REGLAS);

        $expense = $this->gastos->agregar($viatic, $data);

        return response()->json([
            'gasto' => GastosDeViaje::paraPantalla($expense),
            'total'   => $viatic->fresh()->total_computed,
            'count'   => $viatic->expenses()->count(),
        ]);
    }

    public function updateExpense(Request $request, Viatic $viatic, ViaticExpense $expense): JsonResponse
    {
        $this->authorizeExpense($viatic, $expense);

        $data = $request->validate(GastosDeViaje::REGLAS);

        $this->gastos->actualizar($expense, $data);

        return response()->json([
            'gasto' => GastosDeViaje::paraPantalla($expense),
            'total' => $viatic->fresh()->total_computed,
        ]);
    }

    public function destroyExpense(Viatic $viatic, ViaticExpense $expense): JsonResponse
    {
        $this->authorizeExpense($viatic, $expense);

        $expense->delete();

        return response()->json([
            'total' => $viatic->fresh()->total_computed,
            'count' => $viatic->expenses()->count(),
        ]);
    }

    private function authorizeViatic(Viatic $viatic): void
    {
        abort_unless($this->viaticos->puedeVer($viatic, request()->user()), 403);
    }

    private function authorizeExpense(Viatic $viatic, ViaticExpense $expense): void
    {
        $this->authorizeViatic($viatic);

        abort_unless(GastosDeViaje::pertenece($viatic, $expense), 404);
    }
}
