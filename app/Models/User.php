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
        'payroll_number',
        'avatar',
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
        ];
    }

    /**
     * ¿Es administrador?
     */
    public function isAdmin(): bool
    {
        return (bool) $this->is_admin;
    }

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
        return $this->belongsToMany(Permission::class)->withPivot('level');
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
     * Determina si el usuario tiene un permiso concreto.
     * Los administradores siempre lo tienen.
     */
    public function hasPermission(string $name): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        return $this->permissions()->where('name', $name)->exists();
    }
}
