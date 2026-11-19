<?php

namespace App\Services\Ortografia;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Corrector ortográfico en español de México para todo lo que se escribe.
 *
 * La revisión la hace la IA (la misma cuenta de OpenAI del checklist de
 * recepción): acentos, ñ, b/v, s/c/z, h, letras cambiadas o comidas.
 * No cambia el sentido, el orden de las palabras ni el estilo, y deja en
 * paz marcas, modelos, códigos, folios y números. Respeta las mayúsculas
 * (casi todo en el sistema se guarda en MAYÚSCULAS).
 *
 * Cada texto se corrige una sola vez: la respuesta queda en caché. Sin
 * llave de OpenAI, o si la IA falla, se regresa el texto tal cual: el
 * corrector nunca debe impedir que alguien guarde.
 */
class CorrectorOrtografico
{
    public const MAXIMO = 2000;

    /**
     * @return array{texto: string, cambios: array<int, array{antes: string, despues: string}>, disponible: bool}
     */
    public function corregir(string $texto): array
    {
        $igual = ['texto' => $texto, 'cambios' => [], 'disponible' => true];

        // Nada que revisar: vacío, sin letras o demasiado largo.
        if (mb_strlen($texto) > self::MAXIMO || ! preg_match('/\p{L}{3,}/u', $texto)) {
            return $igual;
        }
        if (! config('services.openai.api_key')) {
            return ['disponible' => false] + $igual;
        }

        $llave = 'ortografia:v1:'.sha1($texto);

        try {
            return Cache::remember($llave, now()->addDays(30), function () use ($texto) {
                // En MAYÚSCULAS la IA se equivoca más con los acentos: se le
                // manda en minúsculas y la respuesta regresa a MAYÚSCULAS.
                $todoMayus = mb_strtoupper($texto) === $texto;
                $r = $this->preguntar($todoMayus ? mb_strtolower($texto) : $texto);
                if ($todoMayus) {
                    $r['corregido'] = mb_strtoupper((string) ($r['corregido'] ?? $texto));
                    $r['cambios'] = collect((array) ($r['cambios'] ?? []))
                        ->map(fn ($c) => ['antes' => mb_strtoupper((string) ($c['antes'] ?? '')), 'despues' => mb_strtoupper((string) ($c['despues'] ?? ''))])
                        ->all();
                }

                return $this->validar($texto, $r);
            });
        } catch (\Throwable $e) {
            Log::warning('[Ortografía] No se pudo revisar', ['error' => mb_substr($e->getMessage(), 0, 300)]);

            return ['disponible' => false] + $igual;
        }
    }

    /** Lo que regresó la IA, con candados para no aceptar algo que no sea una corrección. */
    private function validar(string $original, array $r): array
    {
        $corregido = (string) ($r['corregido'] ?? $original);

        // Una corrección ortográfica no cambia mucho el largo ni convierte
        // el texto en otra cosa. Si la IA se pasó, se queda el original.
        $distinto = mb_strlen($corregido) > mb_strlen($original) * 1.25 + 10
            || mb_strlen($corregido) < mb_strlen($original) * 0.75 - 10
            || trim($corregido) === '';

        // Si todo venía en mayúsculas, se queda en mayúsculas.
        if (! $distinto && mb_strtoupper($original) === $original) {
            $corregido = mb_strtoupper($corregido);
        }

        if ($distinto || $corregido === $original) {
            return ['texto' => $original, 'cambios' => [], 'disponible' => true];
        }

        $cambios = collect((array) ($r['cambios'] ?? []))
            ->map(fn ($c) => ['antes' => (string) ($c['antes'] ?? ''), 'despues' => (string) ($c['despues'] ?? '')])
            ->filter(fn ($c) => $c['antes'] !== '' && $c['antes'] !== $c['despues'])
            ->take(20)->values()->all();

        return ['texto' => $corregido, 'cambios' => $cambios, 'disponible' => true];
    }

    private function preguntar(string $texto): array
    {
        $modelo = (string) config('services.openai.model', 'gpt-5.4');

        $payload = [
            'model' => $modelo,
            'input' => [
                ['role' => 'system', 'content' => $this->instrucciones()],
                ['role' => 'user', 'content' => $texto],
            ],
            'text' => ['format' => [
                'type' => 'json_schema',
                'name' => 'correccion_ortografica',
                'strict' => true,
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'corregido' => ['type' => 'string'],
                        'cambios' => [
                            'type' => 'array',
                            'items' => [
                                'type' => 'object',
                                'properties' => ['antes' => ['type' => 'string'], 'despues' => ['type' => 'string']],
                                'required' => ['antes', 'despues'],
                                'additionalProperties' => false,
                            ],
                        ],
                    ],
                    'required' => ['corregido', 'cambios'],
                    'additionalProperties' => false,
                ],
            ]],
        ];

        if (str_starts_with($modelo, 'gpt-5')) {
            // Corregir ortografía no requiere razonar: lo más rápido posible.
            $payload['reasoning'] = ['effort' => (string) config('services.openai.ortografia_effort', 'none')];
        }

        $respuesta = Http::withToken((string) config('services.openai.api_key'))
            ->timeout(20)
            ->post(rtrim((string) config('services.openai.base_url', 'https://api.openai.com/v1'), '/').'/responses', $payload);

        if (! $respuesta->successful()) {
            throw new \RuntimeException('OpenAI HTTP '.$respuesta->status().': '.mb_substr($respuesta->body(), 0, 300));
        }

        $salida = (string) $respuesta->json('output_text', '');
        if ($salida === '') {
            foreach ((array) $respuesta->json('output', []) as $o) {
                foreach ((array) ($o['content'] ?? []) as $c) {
                    $salida .= $c['text'] ?? '';
                }
            }
        }

        return (array) json_decode($salida, true);
    }

    private function instrucciones(): string
    {
        return <<<'TXT'
Eres un corrector ortográfico de español de México. Recibes lo que alguien escribió en un campo de un
sistema de una empresa que vende y da mantenimiento a equipo médico (notas, observaciones, descripciones,
nombres de clientes, direcciones, motivos, mensajes).

Corrige SOLO la ortografía: acentos que faltan o sobran, la ñ, confusiones b/v, s/c/z, ll/y, g/j, h que
falta o sobra, letras cambiadas, repetidas o comidas, y palabras pegadas o partidas por error. Usa la
norma del español de México (por ejemplo "laparoscopía", "celular", "computadora").

No hagas nada más:
- No cambies el sentido, el orden de las palabras, el estilo ni el registro; no reescribas ni resumas.
- No toques marcas, modelos, números de serie, códigos, folios, claves, cantidades, fechas, correos,
  ligas ni siglas (OLYMPUS, STORZ, GIF-H190, MB-000123, RFC, CFDI, IVA…).
- Los nombres propios de personas, apellidos y lugares solo se tocan para ponerles el acento que llevan
  normalmente (JOSE → JOSÉ, MARIA → MARÍA, PEREZ → PÉREZ, GONZALEZ → GONZÁLEZ, QUERETARO → QUERÉTARO).
- Revisa TODAS las palabras, también las cortas y los verbos cuyo acento depende del contexto: "ESTA EN
  QUERÉTARO" → "ESTÁ EN QUERÉTARO", "MAS BARATO" → "MÁS BARATO", "EL QUIERE" → "ÉL QUIERE",
  "QUE NECESITA?" → "¿QUÉ NECESITA?"; pero "ESTA TORRE", "MAS" (pero) y "EL EQUIPO" se quedan igual.
- Respeta las mayúsculas tal como vienen: si el texto está todo en MAYÚSCULAS, la corrección va en
  MAYÚSCULAS y con acento (TELÉFONO, AÑO).
- No agregues ni quites signos de puntuación, salvo los de apertura ¿ ¡ cuando la pregunta o
  exclamación es clara.
- Si no hay errores, regresa el texto exactamente igual y "cambios" vacío.

En "corregido" va el texto completo ya corregido; en "cambios", cada palabra que cambiaste (antes y
después), sin repetir.
TXT;
    }
}
