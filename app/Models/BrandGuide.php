<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BrandGuide extends Model
{
    /** @use HasFactory<\Database\Factories\BrandGuideFactory> */
    use HasFactory;

    protected $table = 'guias_de_marca';

    protected $fillable = [
        'colors',
        'fonts',
    ];

    protected function casts(): array
    {
        return [
            'colors' => 'array',
            'fonts' => 'array',
        ];
    }

    /**
     * Datos iniciales por defecto de la guía de marca.
     */
    public static function defaults(): array
    {
        return [
            'colors' => [
                ['name' => 'Azul MediBuy', 'hex' => '#1B5B9E'],
                ['name' => 'Verde MediBuy', 'hex' => '#3E7C38'],
                ['name' => 'Gris neutro', 'hex' => '#B9B9B9'],
            ],
            'fonts' => [
                [
                    'name' => 'Open Sans',
                    'sample' => 'Aa Bb Cc · 0123456789',
                    'usage' => 'DISPLAY · TÍTULOS',
                    'description' => 'Para encabezados, nombres de equipo y números grandes. Pesos 600-800.',
                ],
                [
                    'name' => 'Inter',
                    'sample' => 'Aa Bb Cc · 0123456789',
                    'usage' => 'TEXTO · CUERPO',
                    'description' => 'Para descripciones, copys largos y datos técnicos. Pesos 400-600.',
                ],
            ],
        ];
    }
}
