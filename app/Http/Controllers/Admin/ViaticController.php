<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Viatic;
use App\Models\ViaticExpense;
use App\Models\Vehicle;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ViaticController extends Controller
{
    public function index(Request $request): View
    {
        $query = Viatic::with(['user', 'vehicle'])->latest();
        $user = $request->user();

        if (! $user->isAdmin()) {
            $query->where('user_id', $user->id);
        }

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('place', 'like', "%{$search}%")
                  ->orWhere('vehicle_name', 'like', "%{$search}%")
                  ->orWhereHas('user', fn($u) => $u->where('name', 'like', "%{$search}%"));
            });
        }

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        $viatics = $query->get();

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
        $data = $request->validate([
            'vehicle_id'   => ['nullable', 'exists:vehicles,id'],
            'vehicle_name' => ['nullable', 'string', 'max:100'],
            'place'        => ['nullable', 'string', 'max:255'],
            'tolls'        => ['nullable', 'numeric', 'min:0'],
            'fuel'         => ['nullable', 'numeric', 'min:0'],
            'meals'        => ['nullable', 'numeric', 'min:0'],
            'additional'   => ['nullable', 'numeric', 'min:0'],
            'description'  => ['nullable', 'string', 'max:1000'],
            'expense_date' => ['nullable', 'date'],
        ]);

        $data['user_id'] = $request->user()->id;

        if (isset($data['vehicle_id']) && $data['vehicle_id']) {
            $vehicle = Vehicle::find($data['vehicle_id']);
            $data['vehicle_name'] = $vehicle ? "{$vehicle->brand} {$vehicle->model}" : null;
        }

        // Save initial totals for backward compatibility (optional), but create expense records
        $expenseMap = [
            'toll'   => (float) ($data['tolls']    ?? 0),
            'fuel'   => (float) ($data['fuel']     ?? 0),
            'meal'   => (float) ($data['meals']    ?? 0),
            'other'  => (float) ($data['additional'] ?? 0),
        ];

        $viatic = Viatic::create($data);

        $labels = [
            'toll'  => 'Caseta',
            'fuel'  => 'Gasolina',
            'meal'  => 'Viático',
            'other' => 'Adicional',
        ];

        foreach ($expenseMap as $type => $amount) {
            if ($amount > 0) {
                $viatic->expenses()->create([
                    'type'   => $type,
                    'label'  => $labels[$type],
                    'amount' => $amount,
                    'icon'   => $type === 'toll' ? 'toll' : ($type === 'fuel' ? 'fuel' : 'receipt'),
                ]);
            }
        }

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

        $data = $request->validate([
            'vehicle_id'   => ['nullable', 'exists:vehicles,id'],
            'vehicle_name' => ['nullable', 'string', 'max:100'],
            'place'        => ['nullable', 'string', 'max:255'],
            'tolls'        => ['nullable', 'numeric', 'min:0'],
            'fuel'         => ['nullable', 'numeric', 'min:0'],
            'meals'        => ['nullable', 'numeric', 'min:0'],
            'additional'   => ['nullable', 'numeric', 'min:0'],
            'description'  => ['nullable', 'string', 'max:1000'],
            'expense_date' => ['nullable', 'date'],
        ]);

        if (isset($data['vehicle_id']) && $data['vehicle_id']) {
            $vehicle = Vehicle::find($data['vehicle_id']);
            $data['vehicle_name'] = $vehicle ? "{$vehicle->brand} {$vehicle->model}" : null;
        }

        $viatic->update($data);

        return redirect()->route('admin.viatics.index')->with('status', 'Viático actualizado correctamente.');
    }

    public function destroy(Viatic $viatic): RedirectResponse
    {
        $this->authorizeViatic($viatic);

        $viatic->delete();

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

        $expense = $viatic->expenses()->create([
            'type'   => $data['type'],
            'label'  => $data['label'] ?: 'Sin descripción',
            'amount' => $data['amount'],
            'icon'   => $iconMap[$data['type']] ?? 'receipt',
        ]);

        $total = $viatic->fresh()->total_computed;

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
            'count'   => $viatic->expenses()->count(),
        ]);
    }

    public function updateExpense(Request $request, Viatic $viatic, ViaticExpense $expense): JsonResponse
    {
        $this->authorizeExpense($viatic, $expense);

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

        $total = $viatic->fresh()->total_computed;

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

    public function destroyExpense(Viatic $viatic, ViaticExpense $expense): JsonResponse
    {
        $this->authorizeExpense($viatic, $expense);

        $expense->delete();

        $total = $viatic->fresh()->total_computed;

        return response()->json([
            'total' => $total,
            'count' => $viatic->expenses()->count(),
        ]);
    }

    private function authorizeViatic(Viatic $viatic): void
    {
        $user = request()->user();

        abort_unless(
            $user && ($user->isAdmin() || (int) $viatic->user_id === (int) $user->id),
            403
        );
    }

    private function authorizeExpense(Viatic $viatic, ViaticExpense $expense): void
    {
        $this->authorizeViatic($viatic);

        abort_unless((int) $expense->viatic_id === (int) $viatic->id, 404);
    }
}
