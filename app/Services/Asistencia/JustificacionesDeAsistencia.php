<?php

namespace App\Services\Asistencia;

use App\Models\AsistenciaJustificacion;
use App\Models\AttendanceModality;
use App\Models\User;
use App\Notifications\JustificacionRespondida;
use App\Notifications\JustificarAsistencia;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/**
 * RH le pide a alguien que justifique un día sin registro o sin salida; la
 * persona responde desde su notificación (motivo y, si la recuerda, la hora
 * a la que entró o salió); RH la acepta o la rechaza.
 *
 * Aceptar no borra nada: si dio horas, se registran como checadas manuales
 * con el motivo; si no checó nada en todo el día, el día queda justificado
 * como permiso. Así la nómina y el historial lo leen igual que siempre.
 */
class JustificacionesDeAsistencia
{
    public const REGLAS_RESPUESTA = [
        'motivo' => ['required', 'string', 'min:5', 'max:1000'],
        'hora_entrada' => ['nullable', 'date_format:H:i'],
        'hora_salida' => ['nullable', 'date_format:H:i'],
    ];

    public const MENSAJES_RESPUESTA = [
        'motivo.required' => 'Escribe por qué no quedó el registro.',
        'motivo.min' => 'Explica un poco más para que RH lo pueda revisar.',
    ];

    public function __construct(private readonly ServicioDeAsistencia $asistencia) {}

    /**
     * Crea (o reabre, si se había rechazado) la petición y le avisa a la
     * persona por la campana y por correo.
     *
     * @return bool  si el correo salió (la notificación del sistema siempre queda)
     */
    public function pedir(User $user, Carbon $dia, string $problema, string $detalle, User $quien): bool
    {
        $j = AsistenciaJustificacion::firstOrNew(['user_id' => $user->id, 'work_date' => $dia->toDateString()]);

        // Solo se vuelve a pedir lo que RH rechazó
        if ($j->exists && $j->estado !== AsistenciaJustificacion::RECHAZADA) {
            throw ValidationException::withMessages(['justificacion' => $j->estado === AsistenciaJustificacion::ACEPTADA
                ? "Ese día de {$user->name} ya está justificado."
                : "A {$user->name} ya se le pidió justificar este día."]);
        }

        $j->fill([
            'problema' => $problema,
            'detalle' => mb_substr($detalle, 0, 255),
            'solicitada_por' => $quien->id,
            'estado' => AsistenciaJustificacion::PENDIENTE,
            'motivo' => null, 'hora_entrada' => null, 'hora_salida' => null, 'respondida_en' => null,
            'revisada_por' => null, 'revisada_en' => null, 'nota_revision' => null,
        ])->save();

        try {
            $user->notify(new JustificarAsistencia($j));

            return true;
        } catch (\Throwable $e) {
            // La notificación de la campana se guarda antes que el correo.
            Log::warning('[Justificaciones] No salió el correo', ['user_id' => $user->id, 'error' => $e->getMessage()]);

            return false;
        }
    }

    /** La persona responde. Se puede corregir mientras RH no la revise. */
    public function responder(AsistenciaJustificacion $j, array $data): void
    {
        if (! $j->abierta()) {
            throw ValidationException::withMessages(['motivo' => 'Esta justificación ya la revisó RH.']);
        }

        $j->update([
            'motivo' => $data['motivo'],
            'hora_entrada' => $data['hora_entrada'] ?? null,
            'hora_salida' => $data['hora_salida'] ?? null,
            'estado' => AsistenciaJustificacion::RESPONDIDA,
            'respondida_en' => now(),
        ]);

        $j->solicitante?->notify(new JustificacionRespondida(
            $j,
            "{$j->user->name} justificó su {$j->work_date->format('d/m')}",
            mb_substr($j->motivo, 0, 140),
            route('asistencia.checadas', ['fecha' => $j->work_date->toDateString()]).'#persona-'.$j->user_id,
        ));
    }

    /**
     * RH acepta: las horas que dio se registran como checadas manuales; si
     * no checó nada en todo el día y no dio horas, el día queda como permiso.
     */
    public function aceptar(AsistenciaJustificacion $j, User $quien, ?string $nota = null): void
    {
        DB::transaction(function () use ($j, $quien, $nota) {
            $motivo = 'Justificación aceptada: '.mb_substr((string) $j->motivo, 0, 180);
            $dia = $j->work_date->copy();

            foreach (['hora_entrada', 'hora_salida'] as $campo) {
                if ($j->$campo) {
                    $this->asistencia->registrarManual($j->user, $dia->toDateString(), substr($j->$campo, 0, 5), $motivo, $quien->id);
                }
            }

            if (! $j->hora_entrada && ! $j->hora_salida && $j->problema === 'sin_registro') {
                AttendanceModality::create([
                    'user_id' => $j->user_id, 'type' => 'permiso',
                    'date_from' => $dia->toDateString(), 'date_to' => $dia->toDateString(),
                    'requires_fingerprint' => false, 'reason' => $motivo, 'created_by' => $quien->id,
                ]);
                $this->asistencia->recalcularRango($j->user, $dia, $dia);
            }

            $j->update([
                'estado' => AsistenciaJustificacion::ACEPTADA,
                'revisada_por' => $quien->id, 'revisada_en' => now(), 'nota_revision' => $nota,
            ]);
        });

        $this->avisarRevision($j, 'aceptó');
    }

    public function rechazar(AsistenciaJustificacion $j, User $quien, ?string $nota = null): void
    {
        $j->update([
            'estado' => AsistenciaJustificacion::RECHAZADA,
            'revisada_por' => $quien->id, 'revisada_en' => now(), 'nota_revision' => $nota,
        ]);

        $this->avisarRevision($j, 'no aceptó');
    }

    private function avisarRevision(AsistenciaJustificacion $j, string $verbo): void
    {
        $j->user->notify(new JustificacionRespondida(
            $j,
            "RH {$verbo} tu justificación del {$j->work_date->format('d/m')}",
            $j->nota_revision ?: $j->detalle,
            route('asistencia.justificar', $j),
        ));
    }
}
