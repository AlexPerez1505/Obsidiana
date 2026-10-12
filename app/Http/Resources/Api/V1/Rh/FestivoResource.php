<?php

namespace App\Http\Resources\Api\V1\Rh;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Un día festivo: ese día no se espera a nadie.
 *
 * @mixin \App\Models\Holiday
 */
class FestivoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'date' => $this->date?->toDateString(),
            'name' => $this->name,
            'created_at' => $this->created_at,
        ];
    }
}
