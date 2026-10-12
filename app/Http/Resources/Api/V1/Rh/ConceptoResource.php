<?php

namespace App\Http\Resources\Api\V1\Rh;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Bono o descuento propio de una persona: fijo cada quincena
 * (`cada_periodo`, con vigencia opcional) o de una sola quincena (`unica`).
 *
 * @mixin \App\Models\PayrollConcept
 */
class ConceptoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'kind' => $this->kind,
            'name' => $this->name,
            'amount' => (float) $this->amount,
            'recurrence' => $this->recurrence,
            'period_start' => $this->period_start?->toDateString(),
            'valid_from' => $this->valid_from?->toDateString(),
            'valid_until' => $this->valid_until?->toDateString(),
            'note' => $this->note,
            'vigencia' => $this->vigenciaTexto(),
            'created_by' => $this->created_by,
            'created_at' => $this->created_at,
        ];
    }
}
