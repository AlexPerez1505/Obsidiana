<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
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
        'promocion_autorizada_en',
        'promocion_autorizada_por',
        'promocion_confirmada_en',
        'promocion_revocada_en',
        'activo',
        'asesor_id',
    ];

    protected $casts = [
        'recibe_promocion' => 'boolean',
        'activo' => 'boolean',
        'promocion_autorizada_en' => 'datetime',
        'promocion_confirmada_en' => 'datetime',
        'promocion_revocada_en' => 'datetime',
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

    public function planPagos(): HasMany
    {
        return $this->hasMany(PlanPago::class, 'cliente_id');
    }

    public function pagos(): HasMany
    {
        return $this->hasMany(Pago::class, 'cliente_id');
    }

    public function promocionAutorizadaPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'promocion_autorizada_por');
    }

    public function promoConfirmaciones(): HasMany
    {
        return $this->hasMany(PromoConfirmacion::class, 'cliente_id');
    }

    public function campanaDestinatarios(): HasMany
    {
        return $this->hasMany(CampanaDestinatario::class, 'cliente_id');
    }

    /**
     * El asesor preguntó y el cliente dijo que sí, pero todavía no lo
     * confirma él mismo. Mientras esté aquí, no se le puede mandar
     * ninguna campaña: solo el mensaje de confirmación.
     */
    public function promocionPendienteDeConfirmar(): bool
    {
        return $this->promocion_autorizada_en !== null
            && $this->promocion_confirmada_en === null
            && $this->promocion_revocada_en === null;
    }

    /**
     * La única condición real para poder mandarle una campaña: el
     * cliente lo confirmó él mismo y no lo ha revocado después.
     */
    public function puedeRecibirPromociones(): bool
    {
        return $this->promocion_confirmada_en !== null
            && $this->promocion_revocada_en === null;
    }

    /** Para mostrar en un vistazo en qué va el consentimiento de este cliente. */
    public function estadoPromocion(): string
    {
        if ($this->promocion_revocada_en) {
            return 'revocado';
        }

        if ($this->promocion_confirmada_en) {
            return 'confirmado';
        }

        if ($this->promocion_autorizada_en) {
            return 'pendiente_confirmacion';
        }

        return 'sin_autorizar';
    }
}
