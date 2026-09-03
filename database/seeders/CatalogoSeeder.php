<?php

namespace Database\Seeders;

use App\Models\Equipo;
use App\Models\FichaTecnica;
use App\Models\Paquete;
use Illuminate\Database\Seeder;

class CatalogoSeeder extends Seeder
{
    /**
     * Catálogo de prueba de equipo médico (basado en los ejemplos de las capturas).
     */
    public function run(): void
    {
        $equipos = [
            ['tipo' => 'LAPAROSCOPIA', 'modelo' => 'AIM 1588', 'marca' => 'STRYKER', 'precio' => 133000],
            ['tipo' => 'LAPAROSCOPIA', 'modelo' => '1588', 'marca' => 'STRYKER', 'precio' => 25000],
            ['tipo' => 'LAPAROSCOPIA', 'modelo' => '10MM 30°', 'marca' => 'STRYKER', 'precio' => 57000],
            ['tipo' => 'LAPAROSCOPIA', 'modelo' => 'L10', 'marca' => 'STRYKER', 'precio' => 28000],
            ['tipo' => 'LAPAROSCOPIA', 'modelo' => 'VERDE', 'marca' => 'STRYKER', 'precio' => 12000],
            ['tipo' => 'LAPAROSCOPIA', 'modelo' => 'SDC3', 'marca' => 'STRYKER', 'precio' => 25000],
            ['tipo' => 'LAPAROSCOPIA', 'modelo' => '45L', 'marca' => 'STRYKER', 'precio' => 30000],
            ['tipo' => 'TORRE ENDOSCOPIA', 'modelo' => 'CV-190', 'marca' => 'OLYMPUS', 'precio' => 285000],
            ['tipo' => 'VENTILADOR', 'modelo' => 'SAVINA 300', 'marca' => 'DRÄGER', 'precio' => 410000],
            ['tipo' => 'MONITOR SIGNOS VITALES', 'modelo' => 'IntelliVue MX450', 'marca' => 'PHILIPS', 'precio' => 95000],
            ['tipo' => 'ELECTROBISTURÍ', 'modelo' => 'FORCE FX', 'marca' => 'VALLEYLAB', 'precio' => 68000],
            ['tipo' => 'MESA QUIRÚRGICA', 'modelo' => 'MAQUET 1150', 'marca' => 'MAQUET', 'precio' => 320000],
        ];

        $creados = [];
        foreach ($equipos as $data) {
            $creados[$data['modelo']] = Equipo::create($data + ['activo' => true]);
        }

        // Fichas técnicas demo asociadas a algunos equipos.
        FichaTecnica::create([
            'equipo_id' => $creados['AIM 1588']->id,
            'titulo' => 'Ficha técnica — Cámara AIM 1588 STRYKER',
            'contenido' => 'Sistema de cámara 4K con tecnología de imagen avanzada (Advanced Imaging Modalities). Resolución 4K, compatibilidad ICG.',
        ]);

        FichaTecnica::create([
            'equipo_id' => $creados['CV-190']->id,
            'titulo' => 'Ficha técnica — Torre de endoscopía OLYMPUS CV-190',
            'contenido' => 'Procesador EVIS EXERA III con NBI (Narrow Band Imaging). Compatible con videoendoscopios serie 190.',
        ]);

        FichaTecnica::create([
            'equipo_id' => $creados['SAVINA 300']->id,
            'titulo' => 'Ficha técnica — Ventilador DRÄGER Savina 300',
            'contenido' => 'Ventilador de cuidados intensivos con turbina integrada. Modos de ventilación invasiva y no invasiva.',
        ]);

        // Paquete de ejemplo (torre de laparoscopía).
        $paquete = Paquete::create([
            'nombre' => 'Torre de Laparoscopía STRYKER 1588 (completa)',
            'descripcion' => 'Paquete integral: cámara, fuente de luz, insuflador, monitor y ópticas.',
            'precio' => 0, // 0 = suma de sus equipos
            'activo' => true,
        ]);

        $paquete->equipos()->attach([
            $creados['AIM 1588']->id => ['cantidad' => 1],
            $creados['10MM 30°']->id => ['cantidad' => 1],
            $creados['L10']->id => ['cantidad' => 1],
            $creados['SDC3']->id => ['cantidad' => 1],
            $creados['45L']->id => ['cantidad' => 1],
        ]);
    }
}
