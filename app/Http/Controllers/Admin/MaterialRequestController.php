<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MaterialRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Validation\Rule;

class MaterialRequestController extends Controller
{
    private const CATEGORIES = [
        'Papeleria',
        'Limpieza',
        'Herramientas',
        'Administracion',
        'Ventas',
        'Logistica y Envios',
        'Almacen',
        'Mantenimiento de Equipo Medico',
        'Servicio Tecnico',
        'Sistemas / TI',
        'Compras',
        'Marketing',
        'Seguridad e Higiene',
        'Mobiliario de Oficina',
        'Uniformes',
        'Publicidad',
        'Capacitacion',
        'Combustible y Transporte',
        'Reparaciones Generales',
        'Hojalateria y Pintura',
        'Otros',
    ];

    private const STATUS_LABELS = [
        MaterialRequest::STATUS_DRAFT => 'Borrador',
        MaterialRequest::STATUS_PENDING => 'Pendiente',
        MaterialRequest::STATUS_APPROVED => 'Aprobada',
        MaterialRequest::STATUS_REJECTED => 'Rechazada',
        MaterialRequest::STATUS_DELIVERED => 'Entregada',
    ];

    public function index(Request $request): View
    {
        $query = MaterialRequest::with(['requester', 'reviewer'])
            ->latest('submitted_at')
            ->latest();

        if (! $request->user()->isAdmin()) {
            $query->where('requested_by', $request->user()->id);
        }

        $requests = $query->get();

        return view('admin.materiales.index', [
            'categories' => self::CATEGORIES,
            'materialRequests' => $requests->map(fn (MaterialRequest $materialRequest): array => $this->row($materialRequest))->all(),
            'pendingCount' => $requests->where('status', MaterialRequest::STATUS_PENDING)->count(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'category' => ['required', 'string', 'max:255'],
            'material_name' => ['required', 'string', 'max:255'],
            'quantity' => ['required', 'integer', 'min:1'],
            'unit' => ['required', 'string', 'max:30'],
            'required_date' => ['nullable', 'date'],
            'urgency' => ['required', Rule::in(['Normal', 'Urgente', 'Programada'])],
            'justification' => ['nullable', 'string', 'max:1500'],
        ]);

        MaterialRequest::create([
            'folio' => $this->nextFolio(),
            'category' => $data['category'],
            'material_name' => $data['material_name'],
            'quantity' => $data['quantity'],
            'unit' => $data['unit'],
            'required_date' => $data['required_date'] ?? null,
            'urgency' => $data['urgency'],
            'justification' => $data['justification'] ?? null,
            'status' => MaterialRequest::STATUS_PENDING,
            'requested_by' => $request->user()->id,
            'submitted_at' => now(),
            'metadata' => [
                'source' => 'admin_materiales_form',
            ],
        ]);

        return redirect()
            ->route('admin.materials.index')
            ->with('status', 'Solicitud enviada a revision.');
    }

    public function review(Request $request, MaterialRequest $materialRequest): RedirectResponse
    {
        abort_unless($request->user()?->isAdmin(), 403);

        $data = $request->validate([
            'decision' => ['required', Rule::in(['approve', 'reject'])],
            'review_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $approved = $data['decision'] === 'approve';

        $materialRequest->update([
            'status' => $approved ? MaterialRequest::STATUS_APPROVED : MaterialRequest::STATUS_REJECTED,
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
            'review_notes' => $data['review_notes'] ?? null,
            'approved_quantity' => $approved ? $materialRequest->quantity : null,
        ]);

        return redirect()
            ->route('admin.materials.index')
            ->with('status', $approved ? 'Solicitud aprobada.' : 'Solicitud rechazada.');
    }

    private function row(MaterialRequest $materialRequest): array
    {
        return [
            'id' => $materialRequest->id,
            'folio' => $materialRequest->folio,
            'category' => $materialRequest->category,
            'material_name' => $materialRequest->material_name,
            'quantity' => $materialRequest->quantity,
            'unit' => $materialRequest->unit,
            'required_date' => $materialRequest->required_date?->format('Y-m-d') ?: 'Sin fecha',
            'urgency' => $materialRequest->urgency,
            'status' => $materialRequest->status,
            'status_label' => self::STATUS_LABELS[$materialRequest->status] ?? ucfirst($materialRequest->status),
            'status_class' => match ($materialRequest->status) {
                MaterialRequest::STATUS_APPROVED,
                MaterialRequest::STATUS_DELIVERED => 'approved',
                MaterialRequest::STATUS_REJECTED => 'rejected',
                default => 'pending',
            },
            'can_review' => $materialRequest->status === MaterialRequest::STATUS_PENDING,
        ];
    }

    private function nextFolio(): string
    {
        $lastNumber = MaterialRequest::query()
            ->pluck('folio')
            ->map(fn (string $folio): int => (int) preg_replace('/\D+/', '', $folio))
            ->max() ?? 0;

        return 'SOL-' . str_pad((string) ($lastNumber + 1), 4, '0', STR_PAD_LEFT);
    }
}
