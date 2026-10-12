<?php

namespace App\Http\Resources\Api\V1\Administracion;

use App\Http\Resources\Api\V1\UsuarioResource;
use Illuminate\Http\Request;

/**
 * Un usuario como lo ve el panel de administración: su estado de acceso,
 * sus roles y sus datos de RH. Sin contraseña, códigos ni PIN, y sin
 * sueldo ni comisión (esos tienen su propio permiso en RH y Comisiones).
 *
 * @mixin \App\Models\User
 */
class UsuarioPanelResource extends UsuarioResource
{
    public function toArray(Request $request): array
    {
        return parent::toArray($request) + [
            'position' => $this->position,
            'payroll_number' => $this->payroll_number,
            'checador_id' => $this->checador_id,
            'status' => $this->status,
            'status_label' => $this->statusLabel(),
            // La bandera de la cuenta; es_administrador también cuenta el rol admin.
            'is_admin' => (bool) $this->is_admin,
            'es_administrador' => $this->isAdmin(),
            'banned_reason' => $this->banned_reason,
            'approved_at' => $this->approved_at?->toIso8601String(),
            'email_verified_at' => $this->email_verified_at?->toIso8601String(),
            'tiene_firma' => (bool) $this->firma_path,

            'curp' => $this->curp,
            'ine' => $this->ine,
            'acta_nacimiento' => $this->acta_nacimiento,
            'licencia' => $this->licencia,
            'domicilio' => $this->domicilio,
            'fecha_ingreso' => $this->fecha_ingreso?->format('Y-m-d'),
            'vacaciones_disponibles' => $this->vacaciones_disponibles,
            'nombre_contacto_emergencia' => $this->nombre_contacto_emergencia,
            'numero_contacto_emergencia' => $this->numero_contacto_emergencia,
            'domicilio_contacto_emergencia' => $this->domicilio_contacto_emergencia,
            'nombre_contacto_emergencia_secundario' => $this->nombre_contacto_emergencia_secundario,
            'numero_contacto_emergencia_secundario' => $this->numero_contacto_emergencia_secundario,
            'domicilio_contacto_emergencia_secundario' => $this->domicilio_contacto_emergencia_secundario,

            'roles' => $this->whenLoaded('roles', fn () => $this->roles->map(fn ($r) => [
                'id' => $r->id,
                'name' => $r->name,
                'label' => $r->label,
                'is_active' => (bool) $r->is_active,
            ])->values()),
            'login_logs_count' => $this->whenCounted('loginLogs'),
            // Sesiones abiertas en el navegador ahora mismo (las de la app
            // salen en /auth/sesiones del propio usuario).
            'sesiones_activas' => $this->when(isset($this->resource->sesiones_activas), fn () => (int) $this->resource->sesiones_activas),
            'documentos' => DocumentoEmpleadoResource::collection($this->whenLoaded('employeeDocuments')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
