<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    /**
     * Solo los clientes que este usuario puede ver.
     *
     * Quien tiene `clientes.ver_todos` (y el administrador, que siempre
     * puede todo) ve el directorio completo. Al resto se le muestran
     * únicamente los clientes que él mismo registró, es decir, los que
     * tienen su id como asesor.
     */
    public function scopeVisiblesPara(Builder $query, User $user): Builder
    {
        if ($user->can('clientes.ver_todos')) {
            return $query;
        }

        return $query->where('asesor_id', $user->id);
    }

    /** ¿Este cliente en particular le aparece a este usuario? */
    public function visiblePara(User $user): bool
    {
        return $user->can('clientes.ver_todos') || (int) $this->asesor_id === (int) $user->id;
    }

    /**
     * Deja un teléfono en puros dígitos y se queda con los últimos 10.
     *
     * Así "722 123 45 67", "(722) 123-4567" y "+52 722 123 4567" cuentan
     * como el mismo número: lo que cambia es el formato o la lada del
     * país, no el cliente.
     */
    public static function telefonoNormalizado(?string $telefono): string
    {
        $digitos = preg_replace('/\D+/', '', (string) $telefono) ?? '';

        return strlen($digitos) > 10 ? substr($digitos, -10) : $digitos;
    }

    /**
     * ¿Ya hay un cliente con este teléfono o este correo?
     *
     * Regresa el primero que coincida y por qué (teléfono o correo), o
     * null si no hay ninguno. Al editar se pasa el id del propio cliente
     * para que no choque consigo mismo.
     *
     * @return array{motivo: string, cliente: Customer}|null
     */
    public static function buscarSimilar(?string $telefono, ?string $correo, ?int $ignorarId = null): ?array
    {
        $digitos = static::telefonoNormalizado($telefono);

        if ($digitos !== '') {
            // Se compara contra el teléfono guardado ya normalizado en SQL,
            // para que también pesque los que se capturaron con otro formato.
            $porTelefono = static::query()
                ->when($ignorarId, fn ($q) => $q->whereKeyNot($ignorarId))
                ->whereRaw(
                    strlen($digitos) >= 10
                        ? "RIGHT(REGEXP_REPLACE(telefono, '[^0-9]', ''), 10) = ?"
                        : "REGEXP_REPLACE(telefono, '[^0-9]', '') = ?",
                    [$digitos]
                )
                ->with(['asesor', 'category', 'congress'])
                ->first();

            if ($porTelefono) {
                return ['motivo' => 'telefono', 'cliente' => $porTelefono];
            }
        }

        $correo = mb_strtolower(trim((string) $correo));

        if ($correo !== '') {
            $porCorreo = static::query()
                ->when($ignorarId, fn ($q) => $q->whereKeyNot($ignorarId))
                ->whereRaw('LOWER(gmail) = ?', [$correo])
                ->with(['asesor', 'category', 'congress'])
                ->first();

            if ($porCorreo) {
                return ['motivo' => 'correo', 'cliente' => $porCorreo];
            }
        }

        return null;
    }

    /**
     * Lo que se le enseña a quien intentó registrar un cliente repetido:
     * los datos del que ya existe, para que sepa de quién se trata. La
     * liga a su ficha solo va si el usuario tiene permiso de verlo.
     */
    public function resumenParaAviso(User $user): array
    {
        return [
            'id' => $this->id,
            'nombre' => trim($this->nombre.' '.$this->apellido),
            'telefono' => $this->telefono,
            'correo' => $this->gmail,
            'direccion' => $this->direccion,
            'categoria' => $this->category?->nombre,
            'conocido' => $this->comoConocio(),
            'asesor' => $this->asesor?->name,
            'activo' => (bool) $this->activo,
            'alta' => $this->created_at?->format('d/m/Y'),
            'url' => $this->visiblePara($user) ? route('commercial.clientes.show', $this) : null,
        ];
    }

    protected $table = 'clientes';

    protected $fillable = [
        'nombre',
        'apellido',
        'telefono',
        'rfc',
        'gmail',
        'direccion',
        'comentarios',
        'congreso_id',
        'como_conocio',
        'categoria_id',
        'recibe_promocion',
        'activo',
        'etapa',
        'asesor_id',
    ];

    /** Un prospecto es alguien interesado que todavía no compra. */
    public const ETAPAS = [
        'cliente' => 'Cliente',
        'prospecto' => 'Prospecto',
    ];

    public function esProspecto(): bool
    {
        return $this->etapa === 'prospecto';
    }

    public function etapaLabel(): string
    {
        return self::ETAPAS[$this->etapa] ?? 'Cliente';
    }

    public function seguimientos(): HasMany
    {
        return $this->hasMany(ClienteSeguimiento::class)->orderByRaw('hecho_en IS NULL DESC')->orderBy('fecha')->orderBy('id');
    }

    /** El siguiente seguimiento que falta por hacer, si hay. */
    public function proximoSeguimiento(): ?ClienteSeguimiento
    {
        return $this->seguimientos->first(fn (ClienteSeguimiento $s) => ! $s->hecho());
    }

    protected $casts = [
        'recibe_promocion' => 'boolean',
        'activo' => 'boolean',
    ];

    public function asesor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'asesor_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'categoria_id');
    }

    public function congress(): BelongsTo
    {
        return $this->belongsTo(Congress::class, 'congreso_id');
    }

    /**
     * Cómo se conoció al cliente, para mostrar en un solo dato: el nombre
     * del congreso si se levantó en uno, o lo que se haya escrito a mano
     * si no. Si no hay ninguno de los dos, no se sabe y se deja vacío.
     */
    public function comoConocio(): ?string
    {
        return $this->congress?->nombre ?: $this->como_conocio ?: null;
    }

    // Ojo: en cotizaciones la llave es customer_id, no cliente_id como en el resto.
    public function cotizaciones(): HasMany
    {
        return $this->hasMany(Cotizacion::class, 'customer_id');
    }

    public function ventas(): HasMany
    {
        return $this->hasMany(Venta::class, 'customer_id');
    }

    /**
     * Lo que este cliente todavía debe de sus ventas.
     *
     * Se apoya en el saldo de cada venta en vez de recalcular la cuenta
     * aquí: esa lógica ya considera el valor a cuenta y los cobros
     * registrados, y duplicarla en dos lugares es como terminan
     * discrepando.
     */
    public function saldoPendiente(): float
    {
        return (float) $this->ventas
            ->where('estado', '!=', 'cancelada')
            ->sum(fn (Venta $v) => $v->saldo());
    }

    public function planPagos(): HasMany
    {
        return $this->hasMany(PlanPago::class, 'cliente_id');
    }

    public function pagos(): HasMany
    {
        return $this->hasMany(Pago::class, 'cliente_id');
    }
}
