<?php

namespace App\Services\Asistencia;

use App\Models\BiometricEnrollment;
use App\Models\User;

/**
 * Administración de lectores y solicitudes de registro de huella (lo que
 * hace RH; el agente del lector tiene su propia API). Lo comparten la web
 * y la API para que una solicitud nueva siempre reemplace a las pendientes.
 */
class RegistroDeHuellas
{
    public static function reglasDispositivo(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'code' => ['required', 'string', 'max:40', 'alpha_dash', 'unique:biometric_devices,code'],
            'serial_number' => ['nullable', 'string', 'max:120'],
            'location' => ['nullable', 'string', 'max:150'],
        ];
    }

    public static function reglasSolicitud(): array
    {
        return [
            'device_id' => ['required', 'exists:biometric_devices,id'],
            'finger_index' => ['required', 'integer', 'between:0,9'],
        ];
    }

    /** RH pide registrar una huella: el agente del lector elegido la recoge y guía al empleado. */
    public function solicitar(User $user, int $dispositivo, int $dedo, int $solicitadoPor): BiometricEnrollment
    {
        // Una solicitud nueva reemplaza a las pendientes de esa persona
        BiometricEnrollment::where('user_id', $user->id)->whereIn('status', ['pending', 'capturing'])
            ->update(['status' => 'cancelled', 'message' => 'Reemplazada por una solicitud nueva.']);

        return BiometricEnrollment::create([
            'user_id' => $user->id,
            'device_id' => $dispositivo,
            'finger_index' => $dedo,
            'status' => 'pending',
            'message' => 'Esperando al lector',
            'requested_by' => $solicitadoPor,
            'expires_at' => now()->addMinutes(config('asistencia.enrolamiento_minutos', 15)),
        ]);
    }

    /** Solo se cancela lo que sigue en curso; una terminada o vencida se deja como está. */
    public function cancelar(BiometricEnrollment $inscripcion): bool
    {
        if (! $inscripcion->vigente()) {
            return false;
        }

        $inscripcion->update(['status' => 'cancelled', 'message' => 'Cancelada por RH.']);

        return true;
    }
}
