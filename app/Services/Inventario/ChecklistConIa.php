<?php

namespace App\Services\Inventario;

use App\Models\Brand;
use App\Models\EquipmentModel;
use App\Models\EquipmentType;
use App\Models\Subtype;
use App\Support\ChecklistRecepcion;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * Checklist de recepción hecho a la medida del equipo.
 *
 * El checklist general pregunta por pantallas, tapicería y ruedas a
 * cualquier cosa que llegue; a un endoscopio no le aplica la tapicería ni a
 * una silla el autodiagnóstico. Con tipo, subtipo, marca y modelo, la IA
 * (OpenAI, igual que en empresajureto) arma solo los puntos que sí aplican a
 * ese equipo, cada uno con el proceso al que manda la pieza si sale mal.
 *
 * Se guarda en caché por combinación de catálogo: el mismo modelo siempre
 * recibe el mismo checklist y no se vuelve a pagar la consulta. Sin llave de
 * API, o si la IA falla, se regresa el checklist general.
 */
class ChecklistConIa
{
    private const PROCESOS = ['hojalateria', 'mantenimiento', 'limpieza', 'ninguno'];

    /**
     * @return array{ia: bool, grupos: array, aviso: ?string}
     */
    public function para(int $tipoId, ?int $subtipoId, ?int $marcaId, ?int $modeloId): array
    {
        $equipo = array_filter([
            'Tipo' => EquipmentType::find($tipoId)?->name,
            'Subtipo' => $subtipoId ? Subtype::find($subtipoId)?->name : null,
            'Marca' => $marcaId ? Brand::find($marcaId)?->name : null,
            'Modelo' => $modeloId ? EquipmentModel::find($modeloId)?->name : null,
        ]);

        if (! isset($equipo['Tipo'])) {
            return $this->general('Elige el tipo de equipo para armar su checklist.');
        }

        if (! config('services.openai.api_key')) {
            return $this->general('La IA no está configurada: se muestra el checklist general.');
        }

        $llave = 'checklist-ia:v2:'.implode('-', [$tipoId, $subtipoId ?: 0, $marcaId ?: 0, $modeloId ?: 0]);

        if ($guardado = Cache::get($llave)) {
            return ['ia' => true, 'grupos' => $guardado, 'aviso' => null];
        }

        try {
            $grupos = $this->preguntar($equipo);
        } catch (Throwable $e) {
            Log::warning('[ChecklistConIa] No se pudo armar el checklist', ['equipo' => $equipo, 'error' => $e->getMessage()]);

            return $this->general('La IA no respondió: se muestra el checklist general.');
        }

        if (! $grupos) {
            return $this->general('La IA no regresó puntos: se muestra el checklist general.');
        }

        Cache::forever($llave, $grupos);

        return ['ia' => true, 'grupos' => $grupos, 'aviso' => null];
    }

    private function general(string $aviso): array
    {
        return ['ia' => false, 'grupos' => ChecklistRecepcion::grupos(), 'aviso' => $aviso];
    }

    /** Le pide a OpenAI los puntos y los deja con la misma forma que ChecklistRecepcion::grupos(). */
    private function preguntar(array $equipo): array
    {
        $modelo = (string) config('services.openai.model', 'gpt-5.4');
        $descripcion = collect($equipo)->map(fn ($v, $k) => "$k: $v")->implode("\n");

        $payload = [
            'model' => $modelo,
            'input' => [
                ['role' => 'system', 'content' => $this->instrucciones()],
                ['role' => 'user', 'content' => "Equipo usado que se está recibiendo:\n".$descripcion],
            ],
            'text' => ['format' => [
                'type' => 'json_schema',
                'name' => 'checklist_recepcion',
                'strict' => true,
                'schema' => $this->esquema(),
            ]],
        ];

        // Los gpt-5 razonan: con esfuerzo bajo responde rápido.
        if (str_starts_with($modelo, 'gpt-5')) {
            $payload['reasoning'] = ['effort' => (string) config('services.openai.reasoning_effort', 'low')];
        }

        $respuesta = Http::withToken((string) config('services.openai.api_key'))
            ->timeout(90)
            ->post(rtrim((string) config('services.openai.base_url', 'https://api.openai.com/v1'), '/').'/responses', $payload);

        if (! $respuesta->successful()) {
            throw new \RuntimeException('OpenAI HTTP '.$respuesta->status().': '.mb_substr($respuesta->body(), 0, 300));
        }

        // El texto viene en output_text o dentro de output[].content[].
        $texto = (string) $respuesta->json('output_text', '');

        if ($texto === '') {
            foreach ((array) $respuesta->json('output', []) as $salida) {
                foreach ((array) ($salida['content'] ?? []) as $contenido) {
                    $texto .= $contenido['text'] ?? '';
                }
            }
        }

        return $this->normalizar(json_decode($texto, true)['grupos'] ?? []);
    }

    private function instrucciones(): string
    {
        return <<<'TXT'
Eres el jefe de recepción de un almacén de equipo médico usado en México (torres de endoscopia y
laparoscopía, monitores, mesas y sillas de cirugía, electrocirugía, ultrasonido, sus accesorios, etc.).

Arma el checklist de recepción para el equipo que se te indica: lo que quien lo recibe revisa A SIMPLE
VISTA al llegar para decir si llegó bien. Cada punto se contesta Sí / No / No aplica, así que redáctalo en
positivo ("La lente no tiene rayones"), de forma que "Sí" signifique que está bien.

- En la recepción el equipo NO se enciende ni se prueba: las pruebas de funcionamiento (encender,
  autodiagnóstico, imagen, alarmas, controles, presión, succión, etc.) las hace después mantenimiento.
  No incluyas ningún punto que requiera conectarlo, encenderlo u operarlo, ni un grupo de funcionamiento.
- Solo puntos que de verdad apliquen a ESE equipo según su tipo, subtipo, marca y modelo. No preguntes
  por tapicería a un monitor. Si conoces el modelo, usa sus partes reales (puertos, conectores,
  cabezales, baterías, mangueras, lentes, etc.) para revisar que estén, completas y sin daños.
- Agrúpalos en pocos grupos claros (por ejemplo Estado físico, Está completo, Higiene y seguridad),
  entre 8 y 20 puntos en total, cortos y concretos.
- En "manda" pon a qué proceso va la pieza si ese punto sale en "No": "mantenimiento" para daños en
  partes eléctricas o electrónicas (conectores, cables pelados, pantallas rotas), "hojalateria" para golpes, carcasa, pintura, óxido, ruedas o tapicería,
  "limpieza" para suciedad o residuos, y "ninguno" cuando la falla solo se anota (falta un manual, un
  accesorio o la placa de serie).
TXT;
    }

    private function esquema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'grupos' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'titulo' => ['type' => 'string'],
                            'puntos' => [
                                'type' => 'array',
                                'items' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'texto' => ['type' => 'string'],
                                        'manda' => ['type' => 'string', 'enum' => self::PROCESOS],
                                    ],
                                    'required' => ['texto', 'manda'],
                                    'additionalProperties' => false,
                                ],
                            ],
                        ],
                        'required' => ['titulo', 'puntos'],
                        'additionalProperties' => false,
                    ],
                ],
            ],
            'required' => ['grupos'],
            'additionalProperties' => false,
        ];
    }

    /**
     * Llaves estables a partir del texto, para que la respuesta del
     * formulario sepa a qué punto corresponde.
     */
    private function normalizar(array $grupos): array
    {
        $resultado = [];
        $usadas = [];

        foreach ($grupos as $g) {
            $titulo = trim((string) ($g['titulo'] ?? ''));
            $puntos = [];

            foreach ((array) ($g['puntos'] ?? []) as $p) {
                $texto = trim((string) ($p['texto'] ?? ''));

                if ($texto === '') {
                    continue;
                }

                $base = Str::limit(Str::slug($texto, '_'), 50, '') ?: 'punto';
                $llave = $base;
                for ($n = 2; isset($usadas[$llave]); $n++) {
                    $llave = $base.'_'.$n;
                }
                $usadas[$llave] = true;

                $manda = $p['manda'] ?? 'ninguno';
                $puntos[$llave] = ['texto' => mb_substr($texto, 0, 200), 'manda' => $manda === 'ninguno' ? null : $manda];
            }

            if ($titulo !== '' && $puntos) {
                $resultado[Str::slug($titulo, '_') ?: 'grupo_'.count($resultado)] = ['titulo' => $titulo, 'puntos' => $puntos];
            }
        }

        return $resultado;
    }
}
