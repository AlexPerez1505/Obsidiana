<?php

namespace App\Services\Administracion;

use App\Mail\AccountStatusMail;
use App\Models\EmployeeDocument;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

/**
 * Quién entra al sistema: aprobar, desactivar y reactivar cuentas, dar o
 * quitar el admin y completar los datos de RH de un usuario.
 *
 * Lo usan la web (panel de usuarios) y la API, para que las dos apliquen
 * las mismas protecciones y manden los mismos correos.
 */
class CuentasDeUsuario
{
    public const NO_BANEARSE = 'No puedes desactivar tu propia cuenta.';
    public const NO_CAMBIAR_PROPIO_ADMIN = 'No puedes cambiar tu propio rol de administrador.';

    /** Datos de RH que se completan desde el panel de usuarios. */
    public const CAMPOS_RH = [
        'position', 'cargo', 'phone', 'payroll_number', 'checador_id',
        'curp', 'ine', 'acta_nacimiento', 'licencia', 'domicilio',
        'fecha_ingreso', 'vacaciones_disponibles',
        'nombre_contacto_emergencia', 'numero_contacto_emergencia', 'domicilio_contacto_emergencia',
        'nombre_contacto_emergencia_secundario', 'numero_contacto_emergencia_secundario', 'domicilio_contacto_emergencia_secundario',
    ];

    public const REGLAS_RH = [
        'position' => ['nullable', 'string', 'max:255'],
        'cargo' => ['nullable', 'string', 'max:255'],
        'phone' => ['nullable', 'string', 'max:50'],
        'payroll_number' => ['nullable', 'string', 'max:100'],
        'checador_id' => ['nullable', 'string', 'max:100'],
        'curp' => ['nullable', 'string', 'max:18'],
        'ine' => ['nullable', 'string', 'max:255'],
        'acta_nacimiento' => ['nullable', 'string', 'max:255'],
        'licencia' => ['nullable', 'string', 'max:255'],
        'domicilio' => ['nullable', 'string'],
        'fecha_ingreso' => ['nullable', 'date'],
        'vacaciones_disponibles' => ['nullable', 'integer', 'min:0'],
        'nombre_contacto_emergencia' => ['nullable', 'string', 'max:255'],
        'numero_contacto_emergencia' => ['nullable', 'string', 'max:50'],
        'domicilio_contacto_emergencia' => ['nullable', 'string'],
        'nombre_contacto_emergencia_secundario' => ['nullable', 'string', 'max:255'],
        'numero_contacto_emergencia_secundario' => ['nullable', 'string', 'max:50'],
        'domicilio_contacto_emergencia_secundario' => ['nullable', 'string'],
    ];

    public const REGLAS_ROLES = [
        'roles' => ['nullable', 'array'],
        'roles.*' => ['exists:roles,id'],
    ];

    /** Campo del formulario => nombre con el que se guarda el documento. */
    public const DOCUMENTOS = [
        'curp_file' => 'CURP',
        'ine_file' => 'INE',
        'acta_nacimiento_file' => 'Acta de nacimiento',
        'licencia_file' => 'Licencia de manejo',
    ];

    public const REGLAS_DOCUMENTOS = [
        'ine_file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        'acta_nacimiento_file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        'licencia_file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        'curp_file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
    ];

    /** Aprueba una cuenta pendiente para darle acceso. */
    public function aprobar(User $user): void
    {
        $user->forceFill([
            'status' => User::STATUS_APPROVED,
            'banned_reason' => null,
            'approved_at' => now(),
        ])->save();

        $this->notificar($user, 'approved');
    }

    /** Por qué no se puede desactivar esta cuenta (null = sí se puede). */
    public function rechazoAlBanear(User $user, User $quien): ?string
    {
        return $user->id === $quien->id ? self::NO_BANEARSE : null;
    }

    /** Desactiva la cuenta y la saca de todos lados. */
    public function banear(User $user, ?string $motivo = null): void
    {
        $user->forceFill([
            'status' => User::STATUS_BANNED,
            'banned_reason' => $motivo,
        ])->save();

        // Expulsar de todos sus dispositivos, también de las apps y las
        // integraciones que entran por la API con su cuenta.
        DB::table('sessions')->where('user_id', $user->id)->delete();
        $user->tokens()->delete();

        $this->notificar($user, 'banned', $motivo);
    }

    /**
     * Tablas que apuntan al usuario pero que no son historial: se van con él
     * sin que se pierda nada que importe (sus accesos, roles y permisos).
     */
    private const NO_ES_HISTORIAL = ['login_logs', 'permission_user', 'role_user', 'personal_access_tokens', 'sessions'];

    /** Nombre legible de las tablas que más suelen aparecer en el historial. */
    private const NOMBRES_HISTORIAL = [
        'ventas' => 'ventas', 'cotizaciones' => 'cotizaciones', 'clientes' => 'clientes asignados',
        'cliente_seguimientos' => 'seguimientos de clientes', 'cobros' => 'cobros',
        'inventory_movements' => 'movimientos de inventario', 'productos' => 'productos',
        'producto_seriales' => 'piezas de inventario', 'ordenes_salida' => 'órdenes de salida',
        'pieza_procesos' => 'procesos de piezas', 'pieza_proceso_avances' => 'avances de procesos',
        'attendance_punches' => 'checadas', 'employee_attendances' => 'asistencias',
        'employee_absences' => 'faltas', 'employee_documents' => 'documentos',
        'employee_vacation_requests' => 'vacaciones', 'employee_permission_requests' => 'permisos solicitados',
        'employee_incidents' => 'incidencias', 'employee_shifts' => 'turnos', 'employee_schedules' => 'horarios',
        'payroll_run_lines' => 'nóminas', 'payroll_concepts' => 'conceptos de nómina',
        'comision_pagos' => 'pagos de comisiones', 'viatics' => 'viáticos', 'tasks' => 'tareas',
        'services' => 'servicios', 'service_trackings' => 'seguimientos de servicios',
        'campanas' => 'campañas', 'rutas' => 'rutas', 'garantias' => 'garantías',
        'garantia_reclamaciones' => 'reclamaciones de garantía', 'agenda_events' => 'eventos de agenda',
        'evento_congreso_usuario' => 'congresos', 'vehiculo_combustible' => 'cargas de combustible',
        'venta_bitacora' => 'cambios en ventas', 'garantia_eventos' => 'eventos de garantía',
        'company_events' => 'eventos de la empresa', 'payroll_rules' => 'reglas de nómina',
        'payroll_runs' => 'nóminas cerradas', 'ruta_eventos' => 'eventos de rutas',
        'orden_salida_evidencias' => 'evidencias de salida', 'material_requests' => 'solicitudes de material',
    ];

    /**
     * Por qué no se puede eliminar esta cuenta (null = sí se puede).
     *
     * Solo se elimina una cuenta sin historial: de prueba o que nunca se
     * usó. Borrar a alguien con historial dejaría ventas, entradas o
     * nóminas sin quién las hizo, borraría sus checadas, viáticos y
     * documentos, o la base lo impediría (servicios, campañas). Para quitarle
     * el acceso a alguien con historial está "Quitar acceso".
     */
    public function rechazoAlEliminar(User $user, User $quien): ?string
    {
        if ($user->id === $quien->id) {
            return 'No puedes eliminar tu propia cuenta.';
        }

        if ($user->is_admin) {
            return "{$user->name} es administrador: quítale primero el rol de administrador.";
        }

        $historial = $this->historial($user);

        if ($historial === null) {
            return 'No se pudo revisar el historial de esta cuenta. Usa "Quitar acceso".';
        }

        if ($historial) {
            $lista = collect($historial)->map(fn ($n, $que) => "{$n} {$que}")->take(6)->implode(', ');

            return "No se puede eliminar a {$user->name} porque tiene historial ({$lista}). "
                .'Si ya no debe entrar, usa "Quitar acceso": así no se pierde nada de lo que registró.';
        }

        return null;
    }

    /**
     * Qué tiene registrado el usuario, por tipo: ['ventas' => 3, ...].
     * Se arma desde las llaves foráneas de la base, así un módulo nuevo
     * queda protegido sin tener que acordarse de agregarlo aquí.
     *
     * @return array<string, int>|null  null si no se pudo revisar
     */
    public function historial(User $user): ?array
    {
        try {
            $llaves = DB::select(
                'SELECT TABLE_NAME AS tabla, COLUMN_NAME AS columna FROM information_schema.KEY_COLUMN_USAGE
                 WHERE REFERENCED_TABLE_SCHEMA = DATABASE() AND REFERENCED_TABLE_NAME = ? AND REFERENCED_COLUMN_NAME = ?',
                [$user->getTable(), 'id']
            );
        } catch (\Throwable $e) {
            Log::warning('[CuentasDeUsuario] No se pudo leer el historial', ['error' => $e->getMessage()]);

            return null;
        }

        $cuenta = [];

        foreach ($llaves as $llave) {
            if (in_array($llave->tabla, self::NO_ES_HISTORIAL, true)) {
                continue;
            }

            $n = DB::table($llave->tabla)->where($llave->columna, $user->id)->count();

            if ($n > 0) {
                $nombre = self::NOMBRES_HISTORIAL[$llave->tabla] ?? str_replace('_', ' ', $llave->tabla);
                $cuenta[$nombre] = ($cuenta[$nombre] ?? 0) + $n;
            }
        }

        arsort($cuenta);

        return $cuenta;
    }

    /** Elimina una cuenta sin historial (revisar antes con rechazoAlEliminar). */
    public function eliminar(User $user): void
    {
        $disco = Storage::disk(config('filesystems.fotos_disk', 'public'));
        $archivos = array_filter([$user->avatar]);

        DB::transaction(function () use ($user) {
            DB::table('sessions')->where('user_id', $user->id)->delete();
            $user->tokens()->delete();
            $user->delete();
        });

        $disco->delete($archivos);
    }

    /** Reactiva una cuenta baneada (le devuelve el acceso). */
    public function desbanear(User $user): void
    {
        $user->forceFill([
            'status' => User::STATUS_APPROVED,
            'banned_reason' => null,
            'approved_at' => $user->approved_at ?? now(),
        ])->save();

        $this->notificar($user, 'reactivated');
    }

    /**
     * Nadie se quita (ni se da) el admin a sí mismo: evita quedarse sin
     * administradores por error.
     */
    public function rechazoAlCambiarAdmin(User $user, User $quien): ?string
    {
        return $user->id === $quien->id ? self::NO_CAMBIAR_PROPIO_ADMIN : null;
    }

    public function alternarAdmin(User $user): void
    {
        $user->forceFill(['is_admin' => ! $user->is_admin])->save();
    }

    /**
     * Guarda los datos de RH que vengan. Solo toca las llaves presentes:
     * la web manda siempre el formulario completo, la API puede mandar
     * solo lo que cambia.
     */
    public function actualizarPerfilRh(User $user, array $datos): void
    {
        $user->forceFill(Arr::only($datos, self::CAMPOS_RH))->save();
    }

    /**
     * Deja al usuario exactamente con estos roles.
     *
     * Con $quien (la API), el rol de administrador queda protegido: solo
     * lo da o lo quita un administrador con acceso total, y nadie se lo
     * cambia a sí mismo (igual que la bandera de admin). Sin esto, quien
     * solo puede editar usuarios podría hacerse administrador marcando el
     * rol.
     */
    public function sincronizarRoles(User $user, array $ids, ?User $quien = null): void
    {
        $ids = collect($ids)->map(fn ($id) => (int) $id)->unique()->values();

        if ($quien) {
            $this->protegerRolAdmin($user, $ids->all(), $quien);
        }

        $user->roles()->sync($ids->all());
        $user->olvidarPermisos();
    }

    /**
     * Guarda o reemplaza el archivo de un documento del empleado (INE,
     * CURP, acta de nacimiento, licencia…). Si ya hay uno con el mismo
     * nombre, se borra el archivo anterior.
     */
    public function guardarDocumento(User $user, string $nombre, UploadedFile $archivo): EmployeeDocument
    {
        $documento = EmployeeDocument::where('user_id', $user->id)
            ->where('name', $nombre)
            ->first();

        if ($documento && $documento->file_path) {
            Storage::disk('public')->delete($documento->file_path);
        }

        $ruta = $archivo->store("employee_documents/{$user->id}", 'public');

        return EmployeeDocument::updateOrCreate(
            ['user_id' => $user->id, 'name' => $nombre],
            [
                'file_path' => $ruta,
                'file_type' => $archivo->getClientMimeType(),
                'file_size' => $archivo->getSize(),
            ]
        );
    }

    private function protegerRolAdmin(User $user, array $ids, User $quien): void
    {
        $rolAdmin = Role::where('name', User::ROL_ADMIN)->value('id');

        if (! $rolAdmin) {
            return;
        }

        $loTiene = $user->roles()->whereKey($rolAdmin)->exists();
        $loTendra = in_array((int) $rolAdmin, $ids, true);

        if ($loTiene === $loTendra) {
            return;
        }

        if ($user->id === $quien->id) {
            throw ValidationException::withMessages(['roles' => self::NO_CAMBIAR_PROPIO_ADMIN]);
        }

        if (! Administrador::pleno($quien)) {
            throw ValidationException::withMessages([
                'roles' => 'Solo un administrador puede dar o quitar el rol de administrador.',
            ]);
        }
    }

    /** Envía el correo de estado sin romper si el SMTP falla. */
    private function notificar(User $user, string $tipo, ?string $motivo = null): void
    {
        try {
            Mail::to($user->email)->send(new AccountStatusMail($tipo, $user->name, $motivo));
        } catch (\Throwable $e) {
            Log::error("No se pudo enviar el correo de estado ({$tipo}) a {$user->email}: ".$e->getMessage());
        }
    }
}
