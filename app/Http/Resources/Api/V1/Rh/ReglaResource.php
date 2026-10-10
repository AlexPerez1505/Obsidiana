<?php

namespace App\Http\Resources\Api\V1\Rh;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Regla de nómina: "cuando pase esto, bonificar o descontar tanto". Trae
 * `resumen`, la regla en una frase tal como la muestra la web.
 *
 * @mixin \App\Models\PayrollRule
 */
class ReglaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'kind' => $this->kind,
            // [{metric, scope: periodo|mes, op, value}] — todas deben cumplirse
            'conditions' => $this->conditions ?? [],
            'repeat_mode' => $this->repeat_mode,
            'repeat_metric' => $this->repeat_metric,
            'repeat_every' => $this->repeat_every,
            'repeat_max' => $this->repeat_max,
            'calc' => $this->calc,
            'amount' => (float) $this->amount,
            'timing' => $this->timing,
            'scope' => $this->scope,
            'user_ids' => $this->user_ids ? array_map('intval', $this->user_ids) : null,
            'active' => (bool) $this->active,
            'sort' => (int) $this->sort,
            'system_key' => $this->system_key,
            'resumen' => $this->resumen(),
            'created_by' => $this->created_by,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
