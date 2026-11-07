<?php

namespace App\Services\Promociones;

use App\Models\Cupon;

/** Lo que dice el motor sobre un cupón aplicado a una compra concreta. */
class ResultadoCupon
{
    /** @param  array<int, string>  $errores */
    public function __construct(
        public readonly ?Cupon $cupon,
        public readonly array $errores,
        public readonly float $subtotalElegible = 0.0,
        public readonly float $descuento = 0.0,
    ) {
    }

    public function valido(): bool
    {
        return $this->cupon !== null && $this->errores === [];
    }

    public function mensaje(): ?string
    {
        return $this->errores[0] ?? null;
    }

    public function toArray(): array
    {
        return [
            'valido' => $this->valido(),
            'codigo' => $this->cupon?->codigo,
            'mensaje' => $this->mensaje(),
            'errores' => $this->errores,
            'subtotal_elegible' => round($this->subtotalElegible, 2),
            'descuento' => round($this->descuento, 2),
            'descripcion' => $this->cupon?->textoDescuento(),
        ];
    }
}
