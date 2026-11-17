<?php

namespace App\Services\Almacen;

use App\Models\OrdenSalida;
use App\Models\OrdenSalidaEtapa;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * Checklist de cada etapa de la salida, armado con IA (OpenAI, igual que
 * el de recepción) según el equipo que sale:
 *
 *  - Ingeniería: inspección y pruebas antes de que salga.
 *  - Empaque: emplayado, protección, accesorios y piezas completas.
 *  - Entrega: documentos, estado al entregar, firma y guía.
 *
 * Si no hay llave o la IA no responde, se usa un checklist general.
 * Cada punto: ['clave', 'grupo', 'texto', 'respuesta' => si|no|na|null, 'nota', 'manual'].
 */
class ChecklistDeSalida
{
    public const MAX_PUNTOS = 30;

    public function para(OrdenSalida $orden, string $etapa): array
    {
        $equipo = $this->describirEquipo($orden);

        if (! config('services.openai.api_key') || $equipo === '') {
            return ['ia' => false, 'puntos' => $this->general($etapa), 'aviso' => 'Se usó el checklist general.'];
        }

        $llave = 'checklist-salida:v1:'.$etapa.':'.sha1($equipo);

        try {
            $grupos = Cache::rememberForever($llave, function () use ($etapa, $equipo) {
                $grupos = $this->preguntar($etapa, $equipo);
                if (! $grupos) {
                    throw new \RuntimeException('La IA no regresó puntos.');
                }

                return $grupos;
            });
        } catch (Throwable $e) {
            Log::warning('[ChecklistDeSalida] No se pudo armar con IA', ['etapa' => $etapa, 'error' => $e->getMessage()]);

            return ['ia' => false, 'puntos' => $this->general($etapa), 'aviso' => 'La IA no respondió: se usó el checklist general.'];
        }

        return ['ia' => true, 'puntos' => $this->aPuntos($grupos), 'aviso' => null];
    }

    /** "Torre de endoscopia × 1 (OLYMPUS CV-190, serie 7731)", una por renglón. */
    public function describirEquipo(OrdenSalida $orden): string
    {
        return $orden->items->map(function ($i) {
            $extra = collect([$i->descripcion, $i->no_series ? 'series '.$i->no_series : null])->filter()->implode(', ');

            return trim($i->nombre.' × '.$i->cantidad.($extra ? " ({$extra})" : ''));
        })->filter()->implode("\n");
    }

    /** Puntos agregados a mano ("+ Agregar punto"). */
    public static function puntoManual(string $texto, string $grupo = 'Agregados'): array
    {
        $texto = Str::limit(trim($texto), 200, '');

        return [
            'clave' => 'manual_'.substr(sha1($texto.microtime()), 0, 12),
            'grupo' => $grupo,
            'texto' => $texto,
            'respuesta' => null,
            'nota' => null,
            'manual' => true,
        ];
    }

    private function aPuntos(array $grupos): array
    {
        $puntos = [];
        $usadas = [];

        foreach ($grupos as $g) {
            foreach ($g['puntos'] as $texto) {
                $base = Str::limit(Str::slug($texto, '_'), 50, '') ?: 'punto';
                $clave = $base;
                for ($n = 2; isset($usadas[$clave]); $n++) {
                    $clave = $base.'_'.$n;
                }
                $usadas[$clave] = true;
                $puntos[] = ['clave' => $clave, 'grupo' => $g['titulo'], 'texto' => $texto, 'respuesta' => null, 'nota' => null, 'manual' => false];
            }
        }

        return array_slice($puntos, 0, self::MAX_PUNTOS);
    }

    private function general(string $etapa): array
    {
        $grupos = match ($etapa) {
            OrdenSalidaEtapa::INGENIERIA => [
                ['titulo' => 'Inspección física', 'puntos' => ['El equipo no tiene golpes, grietas ni piezas sueltas', 'Conectores y cables sin daños', 'Pantallas, lentes y superficies sin rayones', 'El número de serie coincide con la venta']],
                ['titulo' => 'Funcionamiento', 'puntos' => ['Enciende y termina su autodiagnóstico sin errores', 'Todas las funciones principales responden', 'No presenta alarmas ni mensajes de falla', 'Se probó con sus accesorios']],
                ['titulo' => 'Limpieza y entrega', 'puntos' => ['El equipo está limpio y desinfectado', 'Trae sus accesorios completos', 'Manual y documentos listos']],
            ],
            OrdenSalidaEtapa::EMPAQUE => [
                ['titulo' => 'Protección', 'puntos' => ['Partes frágiles protegidas (pantallas, lentes, puntas)', 'Equipo emplayado o en caja sin huecos', 'Esquinas y salientes protegidas', 'Caja o tarima rotulada con el folio']],
                ['titulo' => 'Piezas completas', 'puntos' => ['Salen todas las partidas de la orden', 'Accesorios y cables en bolsa identificada', 'Manual y documentos dentro del paquete', 'Etiqueta QR visible en cada pieza']],
            ],
            default => [
                ['titulo' => 'Antes de entregar', 'puntos' => ['Paquetes completos y sin daños al cargar', 'Documentos de la venta listos', 'Dirección y contacto confirmados']],
                ['titulo' => 'Al entregar', 'puntos' => ['El cliente revisó el equipo al recibirlo', 'Se entregaron manual y garantía', 'Se recabó firma y nombre de quien recibe', 'Si fue por paquetería, se registró la guía']],
            ],
        };

        return $this->aPuntos($grupos);
    }

    private function preguntar(string $etapa, string $equipo): array
    {
        $modelo = (string) config('services.openai.model', 'gpt-5.4');

        $payload = [
            'model' => $modelo,
            'input' => [
                ['role' => 'system', 'content' => $this->instrucciones($etapa)],
                ['role' => 'user', 'content' => "Equipo que sale en esta venta:\n".$equipo],
            ],
            'text' => ['format' => [
                'type' => 'json_schema',
                'name' => 'checklist_salida',
                'strict' => true,
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'grupos' => [
                            'type' => 'array',
                            'items' => [
                                'type' => 'object',
                                'properties' => [
                                    'titulo' => ['type' => 'string'],
                                    'puntos' => ['type' => 'array', 'items' => ['type' => 'string']],
                                ],
                                'required' => ['titulo', 'puntos'],
                                'additionalProperties' => false,
                            ],
                        ],
                    ],
                    'required' => ['grupos'],
                    'additionalProperties' => false,
                ],
            ]],
        ];

        if (str_starts_with($modelo, 'gpt-5')) {
            $payload['reasoning'] = ['effort' => (string) config('services.openai.reasoning_effort', 'low')];
        }

        $respuesta = Http::withToken((string) config('services.openai.api_key'))
            ->timeout(90)
            ->post(rtrim((string) config('services.openai.base_url', 'https://api.openai.com/v1'), '/').'/responses', $payload);

        if (! $respuesta->successful()) {
            throw new \RuntimeException('OpenAI HTTP '.$respuesta->status().': '.mb_substr($respuesta->body(), 0, 300));
        }

        $texto = (string) $respuesta->json('output_text', '');
        if ($texto === '') {
            foreach ((array) $respuesta->json('output', []) as $salida) {
                foreach ((array) ($salida['content'] ?? []) as $contenido) {
                    $texto .= $contenido['text'] ?? '';
                }
            }
        }

        return collect(json_decode($texto, true)['grupos'] ?? [])
            ->map(fn ($g) => [
                'titulo' => trim((string) ($g['titulo'] ?? '')),
                'puntos' => collect($g['puntos'] ?? [])->map(fn ($p) => Str::limit(trim((string) $p), 200, ''))->filter()->values()->all(),
            ])
            ->filter(fn ($g) => $g['titulo'] !== '' && $g['puntos'])
            ->values()->all();
    }

    private function instrucciones(string $etapa): string
    {
        $comun = <<<'TXT'
Trabajas en un almacén de equipo médico (nuevo y seminuevo) en México: torres de endoscopia y laparoscopía,
monitores, mesas de cirugía, electrocirugía, ultrasonido, sus accesorios, etc. Se vendió el equipo indicado y
está por salir. Cada punto se contesta Sí / No / No aplica: redáctalo en positivo, corto y concreto, de modo
que "Sí" signifique que está bien. Solo puntos que de verdad apliquen a ESE equipo; si conoces el modelo, usa
sus partes reales (puertos, conectores, cabezales, baterías, lentes, mangueras). Pocos grupos claros y entre 8 y
20 puntos en total.
TXT;

        return $comun."\n\n".match ($etapa) {
            OrdenSalidaEtapa::INGENIERIA => 'Etapa: INGENIERÍA. El ingeniero biomédico inspecciona y prueba el equipo antes de que salga: estado físico, '
                .'que encienda y pase su autodiagnóstico, pruebas de funcionamiento propias del equipo (imagen, luz, presión, '
                .'succión, energía, alarmas, controles), seguridad eléctrica, limpieza/desinfección y que esté completo con sus accesorios. '
                .'Grupos sugeridos: Inspección física, Pruebas de funcionamiento, Seguridad, Completo y limpio.',
            OrdenSalidaEtapa::EMPAQUE => 'Etapa: EMPLAYADO Y EMPAQUE. Quien empaca protege y empaqueta el equipo y confirma con qué piezas sale: '
                .'protección de partes frágiles de ESE equipo, emplayado, caja/tarima, acolchonado, accesorios y cables identificados, '
                .'manuales, etiquetas, rotulado con folio. No incluyas pruebas de funcionamiento. '
                .'Grupos sugeridos: Protección, Piezas y accesorios completos, Rotulado.',
            default => 'Etapa: ENTREGA. Quien entrega (en persona, en ruta o por paquetería) revisa la carga, los documentos y la entrega: '
                .'paquetes completos y sin daño, documentos (factura/remisión, manual, garantía), dirección y contacto, '
                .'revisión del cliente al recibir, firma y nombre, guía de paquetería si aplica. No incluyas pruebas técnicas. '
                .'Grupos sugeridos: Antes de salir, Al entregar.',
        };
    }
}
