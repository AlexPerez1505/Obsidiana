<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Trip;
use App\Models\TripExpense;
use App\Models\Vehicle;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TripController extends Controller
{
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
        $data = $request->validate([
            'vehicle_id' => ['nullable', 'exists:vehicles,id'],
            'place'      => ['nullable', 'string', 'max:255'],
        ]);

        $data['user_id'] = $request->user()->id;
        $data['status'] = Trip::STATUS_IN_PROGRESS;
        $data['started_at'] = now();

        if (isset($data['vehicle_id']) && $data['vehicle_id']) {
            $vehicle = Vehicle::find($data['vehicle_id']);
            $data['vehicle_name'] = $vehicle ? "{$vehicle->brand} {$vehicle->model}" : null;
        }

        $trip = Trip::create($data);

        return redirect()->route('admin.trips.show', $trip)->with('status', 'Viaje iniciado correctamente.');
    }

    public function addExpense(Request $request, Trip $trip): JsonResponse
    {
        $this->authorizeTrip($trip);

        $data = $request->validate([
            'type'   => ['required', 'string', 'in:toll,fuel,meal,other'],
            'label'  => ['nullable', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0'],
        ]);

        $iconMap = [
            'toll'  => 'toll',
            'fuel'  => 'fuel',
            'meal'  => 'receipt',
            'other' => 'receipt',
        ];

        $expense = $trip->expenses()->create([
            'type'   => $data['type'],
            'label'  => $data['label'] ?: 'Sin descripción',
            'amount' => $data['amount'],
            'icon'   => $iconMap[$data['type']] ?? 'receipt',
        ]);

        $total = $trip->fresh()->total_computed;

        return response()->json([
            'gasto' => [
                'id'         => $expense->id,
                'type'       => $expense->type,
                'label'      => $expense->label,
                'amount'     => (float) $expense->amount,
                'created_at' => $expense->created_at->toISOString(),
                'time_label' => $expense->created_at->isToday()
                    ? 'Hoy, ' . $expense->created_at->format('g:i A')
                    : $expense->created_at->format('d/m/Y, g:i A'),
            ],
            'total'   => $total,
            'count'   => $trip->expenses()->count(),
        ]);
    }

    public function updateExpense(Request $request, Trip $trip, TripExpense $expense): JsonResponse
    {
        $this->authorizeExpense($trip, $expense);

        $data = $request->validate([
            'type'   => ['required', 'string', 'in:toll,fuel,meal,other'],
            'label'  => ['nullable', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0'],
        ]);

        $iconMap = [
            'toll'  => 'toll',
            'fuel'  => 'fuel',
            'meal'  => 'receipt',
            'other' => 'receipt',
        ];

        $expense->update([
            'type'   => $data['type'],
            'label'  => $data['label'] ?: 'Sin descripción',
            'amount' => $data['amount'],
            'icon'   => $iconMap[$data['type']] ?? 'receipt',
        ]);

        $total = $trip->fresh()->total_computed;

        return response()->json([
            'gasto' => [
                'id'         => $expense->id,
                'type'       => $expense->type,
                'label'      => $expense->label,
                'amount'     => (float) $expense->amount,
                'created_at' => $expense->created_at->toISOString(),
                'time_label' => $expense->created_at->isToday()
                    ? 'Hoy, ' . $expense->created_at->format('g:i A')
                    : $expense->created_at->format('d/m/Y, g:i A'),
            ],
            'total' => $total,
        ]);
    }

    public function destroyExpense(Trip $trip, TripExpense $expense): JsonResponse
    {
        $this->authorizeExpense($trip, $expense);

        $expense->delete();

        $total = $trip->fresh()->total_computed;

        return response()->json([
            'total' => $total,
            'count' => $trip->expenses()->count(),
        ]);
    }

    public function finish(Trip $trip): RedirectResponse
    {
        $this->authorizeTrip($trip);

        $trip->update([
            'status'    => Trip::STATUS_COMPLETED,
            'ended_at'  => now(),
        ]);

        return redirect()->route('admin.viatics.index')->with('status', 'Viaje finalizado. Total: $' . number_format($trip->total, 2));
    }

    private function authorizeTrip(Trip $trip): void
    {
        $user = request()->user();

        abort_unless(
            $user && ($user->isAdmin() || (int) $trip->user_id === (int) $user->id),
            403
        );
    }

    private function authorizeExpense(Trip $trip, TripExpense $expense): void
    {
        $this->authorizeTrip($trip);

        abort_unless((int) $expense->trip_id === (int) $trip->id, 404);
    }
}
