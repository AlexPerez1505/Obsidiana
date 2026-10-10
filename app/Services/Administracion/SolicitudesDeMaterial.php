<?php

namespace App\Services\Administracion;

use App\Models\MaterialRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rule;

/**
 * Solicitudes de material: quién ve cuáles, el alta con su folio y la
 * revisión del administrador. Lo usan la pantalla de Materiales y la API.
 */
class SolicitudesDeMaterial
{
    public const CATEGORIAS = [
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

    public const ESTADOS = [
        MaterialRequest::STATUS_DRAFT => 'Borrador',
        MaterialRequest::STATUS_PENDING => 'Pendiente',
        MaterialRequest::STATUS_APPROVED => 'Aprobada',
        MaterialRequest::STATUS_REJECTED => 'Rechazada',
        MaterialRequest::STATUS_DELIVERED => 'Entregada',
    ];

    public const URGENCIAS = ['Normal', 'Urgente', 'Programada'];

    public static function reglasAlta(): array
    {
        return [
            'category' => ['required', 'string', 'max:255'],
            'material_name' => ['required', 'string', 'max:255'],
            'quantity' => ['required', 'integer', 'min:1'],
            'unit' => ['required', 'string', 'max:30'],
            'required_date' => ['nullable', 'date'],
            'urgency' => ['required', Rule::in(self::URGENCIAS)],
            'justification' => ['nullable', 'string', 'max:1500'],
        ];
    }

    public static function reglasRevision(): array
    {
        return [
            'decision' => ['required', Rule::in(['approve', 'reject'])],
            'review_notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /** El administrador ve todas; los demás, solo las que pidieron. */
    public function visiblesPara(User $user): Builder
    {
        $query = MaterialRequest::with(['requester', 'reviewer'])
            ->latest('submitted_at')
            ->latest();

        if (! Administrador::pleno($user)) {
            $query->where('requested_by', $user->id);
        }

        return $query;
    }

    public function esVisiblePara(MaterialRequest $solicitud, User $user): bool
    {
        return Administrador::pleno($user) || (int) $solicitud->requested_by === (int) $user->id;
    }

    /**
     * Registra la solicitud ya enviada a revisión.
     *
     * @param  string  $origen  de dónde vino, para distinguirlas después
     */
    public function registrar(array $data, User $quien, string $origen = 'admin_materiales_form'): MaterialRequest
    {
        return MaterialRequest::create([
            'folio' => $this->siguienteFolio(),
            'category' => $data['category'],
            'material_name' => $data['material_name'],
            'quantity' => $data['quantity'],
            'unit' => $data['unit'],
            'required_date' => $data['required_date'] ?? null,
            'urgency' => $data['urgency'],
            'justification' => $data['justification'] ?? null,
            'status' => MaterialRequest::STATUS_PENDING,
            'requested_by' => $quien->id,
            'submitted_at' => now(),
            'metadata' => [
                'source' => $origen,
            ],
        ]);
    }

    /** Aprueba (con la cantidad pedida) o rechaza. Devuelve si se aprobó. */
    public function revisar(MaterialRequest $solicitud, array $data, User $quien): bool
    {
        $aprobada = $data['decision'] === 'approve';

        $solicitud->update([
            'status' => $aprobada ? MaterialRequest::STATUS_APPROVED : MaterialRequest::STATUS_REJECTED,
            'reviewed_by' => $quien->id,
            'reviewed_at' => now(),
            'review_notes' => $data['review_notes'] ?? null,
            'approved_quantity' => $aprobada ? $solicitud->quantity : null,
        ]);

        return $aprobada;
    }

    public static function etiquetaEstado(?string $estado): string
    {
        return self::ESTADOS[$estado] ?? ucfirst((string) $estado);
    }

    private function siguienteFolio(): string
    {
        $ultimo = MaterialRequest::query()
            ->pluck('folio')
            ->map(fn (string $folio): int => (int) preg_replace('/\D+/', '', $folio))
            ->max() ?? 0;

        return 'SOL-'.str_pad((string) ($ultimo + 1), 4, '0', STR_PAD_LEFT);
    }
}
