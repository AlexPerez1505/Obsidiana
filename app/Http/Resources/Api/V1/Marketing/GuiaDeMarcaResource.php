<?php

namespace App\Http\Resources\Api\V1\Marketing;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * La guía de marca: paleta (`colors`: name, hex) y tipografías (`fonts`:
 * name, sample, usage, description).
 *
 * @mixin \App\Models\BrandGuide
 */
class GuiaDeMarcaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'colors' => $this->colors ?? [],
            'fonts' => $this->fonts ?? [],
            'updated_at' => $this->updated_at,
        ];
    }
}
