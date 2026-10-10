<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MaterialRequest;
use App\Services\Administracion\SolicitudesDeMaterial;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Pantalla de Materiales. Las reglas (quién ve qué, folio, revisión)
 * viven en SolicitudesDeMaterial, que comparte con la API.
 */
class MaterialRequestController extends Controller
{
    public function __construct(private SolicitudesDeMaterial $solicitudes)
    {
    }

    public function index(Request $request): View
    {
        $requests = $this->solicitudes->visiblesPara($request->user())->get();

        return view('admin.materiales.index', [
            'categories' => SolicitudesDeMaterial::CATEGORIAS,
            'materialRequests' => $requests->map(fn (MaterialRequest $materialRequest): array => $this->row($materialRequest))->all(),
            'pendingCount' => $requests->where('status', MaterialRequest::STATUS_PENDING)->count(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate(SolicitudesDeMaterial::reglasAlta());

        $this->solicitudes->registrar($data, $request->user());

        return redirect()
            ->route('admin.materials.index')
            ->with('status', 'Solicitud enviada a revision.');
    }

    public function review(Request $request, MaterialRequest $materialRequest): RedirectResponse
    {
        abort_unless($request->user()?->isAdmin(), 403);

        $data = $request->validate(SolicitudesDeMaterial::reglasRevision());

        $approved = $this->solicitudes->revisar($materialRequest, $data, $request->user());

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
            'status_label' => SolicitudesDeMaterial::etiquetaEstado($materialRequest->status),
            'status_class' => match ($materialRequest->status) {
                MaterialRequest::STATUS_APPROVED,
                MaterialRequest::STATUS_DELIVERED => 'approved',
                MaterialRequest::STATUS_REJECTED => 'rejected',
                default => 'pending',
            },
            'can_review' => $materialRequest->status === MaterialRequest::STATUS_PENDING,
        ];
    }
}
