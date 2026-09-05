<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_BANNED = 'banned';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'position',
        'cargo',
        'payroll_number',
        'checador_id',
        'avatar',

        'dashboard_widgets',
        'curp',
        'ine',
        'acta_nacimiento',
        'licencia',
        'domicilio',
        'fecha_ingreso',
        'vacaciones_disponibles',
        'nombre_contacto_emergencia',
        'numero_contacto_emergencia',
        'domicilio_contacto_emergencia',
        'nombre_contacto_emergencia_secundario',
        'numero_contacto_emergencia_secundario',
        'domicilio_contacto_emergencia_secundario',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'verification_code',
        'approval_pin_hash',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'verification_code_expires_at' => 'datetime',
            'verification_code_sent_at' => 'datetime',
            'is_admin' => 'boolean',
            'approved_at' => 'datetime',
            'password' => 'hashed',
            'fecha_ingreso' => 'date',
            'vacaciones_disponibles' => 'integer',
            'dashboard_widgets' => 'array',
        ];
    }

    /**
     * ¿Es administrador? Puede todo, sin revisar permiso por permiso.
     *
     * Cuentan dos cosas: la bandera `is_admin` de la propia cuenta y tener
     * asignado el rol `admin`. Lo segundo hace falta porque la pantalla de
     * roles promete que ese rol "puede todo": sin esto, asignárselo a alguien
     * no le daba nada (el rol no lleva permisos marcados) y quedaba con menos
     * de lo que decía la pantalla.
     */
    public function isAdmin(): bool
    {
        if ($this->is_admin) {
            return true;
        }

        // Memorizado: isAdmin() se consulta en cada revisión de permiso.
        return $this->memoEsAdmin ??= $this->roles()
            ->where('name', self::ROL_ADMIN)
            ->where('is_active', true)
            ->exists();
    }

    /** El rol que no se configura porque siempre puede todo. */
    public const ROL_ADMIN = 'admin';

    private ?bool $memoEsAdmin = null;

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function isBanned(): bool
    {
        return $this->status === self::STATUS_BANNED;
    }

    /**
     * Etiqueta legible del estado.
     */
    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_APPROVED => 'Con acceso',
            self::STATUS_BANNED   => 'Baneado',
            default               => 'Pendiente',
        };
    }

    /**
     * Historial de inicios de sesión del usuario.
     */
    public function loginLogs(): HasMany
    {
        return $this->hasMany(LoginLog::class)->latest('logged_at');
    }

    /**
     * Movimientos administrativos registrados para el empleado.
     */
    public function employeeReports(): HasMany
    {
        return $this->hasMany(EmployeeReport::class)->latest('start_date');
    }

    /**
     * Movimientos administrativos creados por este usuario.
     */
    public function createdEmployeeReports(): HasMany
    {
        return $this->hasMany(EmployeeReport::class, 'created_by')->latest('created_at');
    }

    /**
     * Asistencias registradas del empleado.
     */
    public function attendances(): HasMany
    {
        return $this->hasMany(EmployeeAttendance::class)->latest('attendance_date');
    }

    /**
     * Faltas registradas del empleado.
     */
    public function absences(): HasMany
    {
        return $this->hasMany(EmployeeAbsence::class)->latest('absence_date');
    }

    /**
     * Solicitudes de vacaciones del empleado.
     */
    public function vacationRequests(): HasMany
    {
        return $this->hasMany(EmployeeVacationRequest::class)->latest('start_date');
    }

    /**
     * Solicitudes de permiso del empleado.
     */
    public function permissionRequests(): HasMany
    {
        return $this->hasMany(EmployeePermissionRequest::class)->latest('permission_date');
    }

    /**
     * Incidencias registradas del empleado.
     */
    public function incidents(): HasMany
    {
        return $this->hasMany(EmployeeIncident::class)->latest('incident_date');
    }

    /**
     * Movimientos de inventario registrados por este usuario.
     */
    public function inventoryMovements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class, 'created_by')->latest('movement_date');
    }

    /**
     * Solicitudes de material capturadas por este usuario.
     */
    public function materialRequests(): HasMany
    {
        return $this->hasMany(MaterialRequest::class, 'requested_by')->latest('submitted_at');
    }

    /**
     * Solicitudes de material revisadas por este usuario.
     */
    public function reviewedMaterialRequests(): HasMany
    {
        return $this->hasMany(MaterialRequest::class, 'reviewed_by')->latest('reviewed_at');
    }

    /**
     * Solicitudes de material entregadas por este usuario.
     */
    public function deliveredMaterialRequests(): HasMany
    {
        return $this->hasMany(MaterialRequest::class, 'delivered_by')->latest('delivered_at');
    }

    /**
     * Genera un código de verificación de 6 dígitos, lo guarda hasheado
     * y devuelve el código en texto plano para enviarlo por correo.
     */
    public function generateVerificationCode(int $minutes = 10): string
    {
        $code = (string) random_int(100000, 999999);

        $this->forceFill([
            'verification_code' => Hash::make($code),
            'verification_code_expires_at' => Carbon::now()->addMinutes($minutes),
            'verification_code_sent_at' => Carbon::now(),
        ])->save();

        return $code;
    }

    /**
     * Comprueba si un código es válido y no ha expirado.
     */
    public function verificationCodeMatches(string $code): bool
    {
        if (! $this->verification_code || ! $this->verification_code_expires_at) {
            return false;
        }

        if ($this->verification_code_expires_at->isPast()) {
            return false;
        }

        return Hash::check($code, $this->verification_code);
    }

    /**
     * Marca el correo como verificado y limpia el código.
     */
    public function confirmEmailVerification(): void
    {
        $this->forceFill([
            'email_verified_at' => Carbon::now(),
            'verification_code' => null,
            'verification_code_expires_at' => null,
        ])->save();
    }

    /**
     * Segundos que faltan para poder reenviar el código (throttle de 60s).
     */
    public function secondsUntilResend(int $cooldown = 60): int
    {
        if (! $this->verification_code_sent_at) {
            return 0;
        }

        $elapsed = $this->verification_code_sent_at->diffInSeconds(Carbon::now());

        return (int) max(0, $cooldown - $elapsed);
    }

    /**
     * Permisos asignados al usuario.
     */
    public function permissions(): BelongsToMany
    {
        /*
        | Sin withPivot('level'): esa columna nunca se creó en permission_user,
        | así que pedirla reventaba la consulta en cuanto algo cargaba la
        | relación completa. Un permiso directo se tiene o no se tiene; los
        | matices los da el rol (ver CatalogoPermisos).
        */
        return $this->belongsToMany(Permission::class);
    }

    /**
     * Roles etiqueta asignados al usuario (admin, supervisor, empleado, etc.).
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }

    /**
     * ¿El usuario tiene el rol indicado?
     */
    public function hasRole(string $name): bool
    {
        return $this->roles()->where('name', $name)->exists();
    }

    /**
     * Genera y guarda el hash de un nuevo PIN de aprobación rápida.
     */
    public function setApprovalPin(string $pin): void
    {
        $this->forceFill([
            'approval_pin_hash' => Hash::make($pin),
        ])->save();
    }

    /**
     * Verifica el PIN de aprobación rápida.
     */
    public function checkApprovalPin(string $pin): bool
    {
        if (! $this->approval_pin_hash) {
            return false;
        }

        return Hash::check($pin, $this->approval_pin_hash);
    }

    /**
     * Días de vacaciones ya utilizados (solicitudes aprobadas).
     */
    public function vacacionesUtilizadas(): int
    {
        return (int) $this->vacationRequests()
            ->where('status', 'aprobada')
            ->sum('days_requested');
    }

    /**
     * Permisos utilizados (solicitudes de permiso aprobadas).
     */
    public function permisosUtilizados(): int
    {
        return $this->permissionRequests()
            ->where('status', 'aprobada')
            ->count();
    }

    /**
     * Retardos registrados como incidencia.
     */
    public function retardosCount(): int
    {
        return $this->incidents()
            ->where('incident_type', 'retardo')
            ->count();
    }

    /**
     * Faltas registradas.
     */
    public function faltasCount(): int
    {
        return $this->absences()->count();
    }

    /**
     * Asistencias registradas.
     */
    public function asistenciasCount(): int
    {
        return $this->attendances()->count();
    }

    public function employeeDocuments(): HasMany
    {
        return $this->hasMany(EmployeeDocument::class);
    }

    public function employeeShifts(): HasMany
    {
        return $this->hasMany(EmployeeShift::class);
    }

    /**
     * ¿El usuario puede hacer esto?
     *
     * Un permiso puede llegarle por dos caminos: por el rol que tiene
     * (lo normal) o concedido directo a él (la excepción, para casos
     * puntuales sin inventar un rol nuevo). Basta con uno.
     *
     * El administrador siempre puede todo.
     */
    public function hasPermission(string $name): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        return $this->permisosEfectivos()->contains($name);
    }

    /**
     * Todos los permisos del usuario, vengan de donde vengan.
     *
     * Se resuelve una vez por petición: en una pantalla se pregunta por
     * varios permisos y no tiene caso ir a la base cada vez.
     */
    public function permisosEfectivos(): \Illuminate\Support\Collection
    {
        return $this->memoPermisos ??= $this->permissions()->pluck('name')
            ->merge(
                // Un rol desactivado deja de otorgar: es la forma de quitarle
                // el acceso a todo un grupo sin borrar el rol ni tocar a nadie.
                $this->roles()->where('is_active', true)->with('permissions')->get()
                    ->flatMap(fn (Role $r) => $r->permissions->pluck('name'))
            )
            ->unique()
            ->values();
    }

    /** Cache por petición de permisosEfectivos(). */
    private ?\Illuminate\Support\Collection $memoPermisos = null;

    /** Se olvida lo memorizado cuando cambian los permisos del usuario. */
    public function olvidarPermisos(): void
    {
        $this->memoPermisos = null;
        $this->memoEsAdmin = null;
    }
}
