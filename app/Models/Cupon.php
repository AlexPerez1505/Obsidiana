<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Un cupón de descuento. Las reglas se validan SIEMPRE en el servidor
 * (App\Services\Promociones\MotorDeCupones); el navegador solo manda el código.
 *
 * Estado guardado: activo | pausado. "agotado", "vencido" y "programado"
 * se calculan con estadoReal() para que nunca queden desfasados.
 */
class Cupon extends Model
{
    protected $table = 'mkt_cupones';

    public const TIPO_GENERAL = 'general';
    public const TIPO_EXCLUSIVO = 'exclusivo';

    protected $fillable = [
        'codigo', 'nombre', 'tipo', 'cliente_id', 'origen_cupon_id', 'campana_id',
        'descuento_tipo', 'descuento_valor', 'compra_minima', 'tope_descuento',
        'inicia_en', 'vence_en', 'usos_max', 'usos_por_cliente', 'aplica_a',
        'acumulable', 'solo_primera_compra', 'estado', 'condiciones',
        'creado_por', 'actualizado_por',
    ];

    protected function casts(): array
    {
        return [
            'descuento_valor' => 'decimal:2',
            'compra_minima' => 'decimal:2',
            'tope_descuento' => 'decimal:2',
            'inicia_en' => 'datetime',
            'vence_en' => 'datetime',
            'usos_max' => 'integer',
            'usos_por_cliente' => 'integer',
            'acumulable' => 'boolean',
            'solo_primera_compra' => 'boolean',
        ];
    }

    public static function normalizarCodigo(?string $codigo): string
    {
        return strtoupper(preg_replace('/[^A-Za-z0-9_-]/', '', (string) $codigo) ?? '');
    }

    public function setCodigoAttribute($valor): void
    {
        $this->attributes['codigo'] = self::normalizarCodigo($valor);
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'cliente_id');
    }

    public function origen(): BelongsTo
    {
        return $this->belongsTo(self::class, 'origen_cupon_id');
    }

    public function campana(): BelongsTo
    {
        return $this->belongsTo(Campana::class, 'campana_id');
    }

    public function creador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creado_por');
    }

    public function alcances(): HasMany
    {
        return $this->hasMany(CuponAlcance::class, 'cupon_id');
    }

    public function usos(): HasMany
    {
        return $this->hasMany(CuponUso::class, 'cupon_id');
    }

    public function copias(): HasMany
    {
        return $this->hasMany(self::class, 'origen_cupon_id');
    }

    /** Exclusivo sin cliente: sirve de molde para los códigos por cliente de una campaña. */
    public function esPlantilla(): bool
    {
        return $this->tipo === self::TIPO_EXCLUSIVO && ! $this->cliente_id;
    }

    /** Usos que cuentan contra los límites: consumidos + reservas vigentes. */
    public function usosActivos(?int $clienteId = null): int
    {
        return $this->usos()
            ->when($clienteId, fn ($q) => $q->where('cliente_id', $clienteId))
            ->vigentes()
            ->count();
    }

    public function estadoReal(): string
    {
        if ($this->estado === 'pausado') {
            return 'pausado';
        }
        if ($this->vence_en && $this->vence_en->isPast()) {
            return 'vencido';
        }
        if ($this->usos_max !== null && $this->usosActivos() >= $this->usos_max) {
            return 'agotado';
        }
        if ($this->inicia_en && $this->inicia_en->isFuture()) {
            return 'programado';
        }

        return 'activo';
    }

    public static function etiquetaEstado(string $estado): string
    {
        return [
            'activo' => 'Activo', 'pausado' => 'Pausado', 'agotado' => 'Agotado',
            'vencido' => 'Vencido', 'programado' => 'Programado',
        ][$estado] ?? ucfirst($estado);
    }

    public static function tonoEstado(string $estado): string
    {
        return ['activo' => 'ok', 'pausado' => 'warn', 'agotado' => 'neutral', 'vencido' => 'danger', 'programado' => 'info'][$estado] ?? 'neutral';
    }

    /** "10 %" o "$500.00", con el tope si lo hay. */
    public function textoDescuento(bool $conTope = true): string
    {
        $base = $this->descuento_tipo === 'porcentaje'
            ? rtrim(rtrim(number_format((float) $this->descuento_valor, 2), '0'), '.').' %'
            : '$'.number_format((float) $this->descuento_valor, 2);

        if ($conTope && $this->descuento_tipo === 'porcentaje' && $this->tope_descuento) {
            $base .= ' (máx. $'.number_format((float) $this->tope_descuento, 2).')';
        }

        return $base;
    }

    /** Las condiciones en lenguaje claro, para el cliente y para el vendedor. */
    public function condicionesLegibles(): array
    {
        $c = [];
        $c[] = 'Descuento de '.$this->textoDescuento().($this->aplica_a === 'seleccion' ? ' en los productos participantes.' : ' en tu compra.');
        if ((float) $this->compra_minima > 0) {
            $c[] = 'Compra mínima de $'.number_format((float) $this->compra_minima, 2).' MXN en productos participantes.';
        }
        if ($this->inicia_en && $this->inicia_en->isFuture()) {
            $c[] = 'Válido a partir del '.$this->inicia_en->format('d/m/Y H:i').' h.';
        }
        if ($this->vence_en) {
            $c[] = 'Vence el '.$this->vence_en->format('d/m/Y').' a las '.$this->vence_en->format('H:i').' h.';
        }
        $c[] = $this->usos_por_cliente === 1 ? 'Un uso por cliente.' : "Hasta {$this->usos_por_cliente} usos por cliente.";
        if ($this->solo_primera_compra) {
            $c[] = 'Solo para tu primera compra.';
        }
        $c[] = $this->acumulable ? 'Se puede combinar con otros descuentos.' : 'No se combina con otros descuentos.';
        if ($this->tipo === self::TIPO_EXCLUSIVO) {
            $c[] = 'Cupón personal: solo es válido para la cuenta a la que se envió.';
        }
        if ($this->condiciones) {
            $c[] = $this->condiciones;
        }

        return $c;
    }

    public static function codigoAleatorio(string $prefijo = 'MB', int $largo = 6): string
    {
        // Sin caracteres que se confunden (0/O, 1/I/L).
        $abc = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';
        do {
            $s = '';
            for ($i = 0; $i < $largo; $i++) {
                $s .= $abc[random_int(0, strlen($abc) - 1)];
            }
            $codigo = substr(self::normalizarCodigo($prefijo), 0, 30).'-'.$s;
        } while (self::where('codigo', $codigo)->exists());

        return $codigo;
    }
}
