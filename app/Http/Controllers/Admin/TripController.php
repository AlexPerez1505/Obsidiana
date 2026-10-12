<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Trip;
use App\Models\TripExpense;
use App\Services\Administracion\GastosDeViaje;
use App\Services\Administracion\Viajes;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Viajes en curso y sus gastos. La lógica vive en Viajes y GastosDeViaje,
 * que comparten con la API.
 */
class TripController extends Controller
{
    public function __construct(
        private Viajes $viajes,
        private GastosDeViaje $gastos,
    ) {
    }

    public function show(Trip $trip): View
    {
        $this->authorizeTrip($trip);

        $trip->load('expenses');

        return view('admin.trips.show', [
            'trip' => $trip,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate(Viajes::REGLAS_INICIO);

        $trip = $this->viajes->iniciar($data, $request->user());

        return redirect()->route('admin.trips.show', $trip)->with('status', 'Viaje iniciado correctamente.');
    }

    public function addExpense(Request $request, Trip $trip): JsonResponse
    {
        $this->authorizeTrip($trip);

        $data = $request->validate(GastosDeViaje::REGLAS);

        $expense = $this->gastos->agregar($trip, $data);

        return response()->json([
            'gasto' => GastosDeViaje::paraPantalla($expense),
            'total'   => $trip->fresh()->total_computed,
            'count'   => $trip->expenses()->count(),
        ]);
    }

    public function updateExpense(Request $request, Trip $trip, TripExpense $expense): JsonResponse
    {
        $this->authorizeExpense($trip, $expense);

        $data = $request->validate(GastosDeViaje::REGLAS);

        $this->gastos->actualizar($expense, $data);

        return response()->json([
            'gasto' => GastosDeViaje::paraPantalla($expense),
            'total' => $trip->fresh()->total_computed,
        ]);
    }

    public function destroyExpense(Trip $trip, TripExpense $expense): JsonResponse
    {
        $this->authorizeExpense($trip, $expense);

        $expense->delete();

        return response()->json([
            'total' => $trip->fresh()->total_computed,
            'count' => $trip->expenses()->count(),
        ]);
    }

    public function finish(Trip $trip): RedirectResponse
    {
        $this->authorizeTrip($trip);

        $this->viajes->finalizar($trip);

        return redirect()->route('admin.viatics.index')->with('status', 'Viaje finalizado. Total: $' . number_format($trip->total, 2));
    }

    private function authorizeTrip(Trip $trip): void
    {
        abort_unless($this->viajes->puedeVer($trip, request()->user()), 403);
    }

    private function authorizeExpense(Trip $trip, TripExpense $expense): void
    {
        $this->authorizeTrip($trip);

        abort_unless(GastosDeViaje::pertenece($trip, $expense), 404);
    }
}
