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
