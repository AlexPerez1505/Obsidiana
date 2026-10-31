<?php

namespace App\Support;

use App\Models\PiezaProceso;

/**
 * Checklist de recepción de equipo usado.
 *
 * Vive aquí y no en la vista para que agregar o quitar un punto sea cambiar
 * una línea, sin tocar el formulario ni el controlador. Los puntos están
 * pensados para equipo médico: torres de endoscopia y laparoscopía,
 * monitores, sillas, mesas de cirugía y sus accesorios.
 *
 * Cada punto dice además a qué proceso manda la pieza cuando sale mal. De
 * ahí se propone la ruta: un golpe en la carcasa es hojalatería, que no
 * encienda es mantenimiento, y una pieza sin fallas no pasa por ninguno.
 */
class ChecklistRecepcion
{
    /** Respuestas válidas de cada punto. */
    public const RESPUESTAS = ['si', 'no', 'na'];

    public const ETIQUETAS = [
        'si' => 'Sí',
        'no' => 'No',
        'na' => 'No aplica',
    ];

    /** Estado general con el que se resume la recepción. */
    public const ESTADOS = [
        'excelente' => 'Excelente · como nuevo',
        'bueno' => 'Bueno · detalles menores',
        'regular' => 'Regular · requiere trabajo',
        'malo' => 'Malo · requiere reparación mayor',
    ];

    /**
     * Puntos agrupados. La llave de cada punto es lo que se guarda.
     *
     * 'manda' dice a qué proceso va la pieza si ese punto sale en "No".
     * null significa que la falla se anota pero no manda a ningún proceso
     * (falta un manual, por ejemplo: eso se resuelve pidiéndolo).
     *
     * @return array<string, array{titulo: string, puntos: array<string, array{texto: string, manda: ?string}>}>
     */
    public static function grupos(): array
    {
        return [
            'funcionamiento' => [
                'titulo' => 'Funcionamiento',
                'puntos' => [
                    'enciende' => ['texto' => 'Enciende y arranca correctamente', 'manda' => 'mantenimiento'],
                    'autodiagnostico' => ['texto' => 'Pasa su autodiagnóstico sin errores', 'manda' => 'mantenimiento'],
                    'imagen' => ['texto' => 'La imagen se ve bien (cámara, monitor, pantalla)', 'manda' => 'mantenimiento'],
                    'controles' => ['texto' => 'Botones, perillas y controles responden', 'manda' => 'mantenimiento'],
                    'sin_alarmas' => ['texto' => 'No marca alarmas ni códigos de falla', 'manda' => 'mantenimiento'],
                ],
            ],
            'fisico' => [
                'titulo' => 'Estado físico',
                'puntos' => [
                    'carcasa' => ['texto' => 'Carcasa sin golpes, rayones ni piezas rotas', 'manda' => 'hojalateria'],
                    'pantalla_fisica' => ['texto' => 'Pantalla sin rayones, manchas ni pixeles muertos', 'manda' => 'hojalateria'],
                    'sin_oxido' => ['texto' => 'Sin óxido, corrosión ni fugas', 'manda' => 'hojalateria'],
                    'ruedas' => ['texto' => 'Ruedas y frenos funcionan (sillas, mesas, carros)', 'manda' => 'hojalateria'],
                    'tapiceria' => ['texto' => 'Tapicería sin roturas ni desgaste (sillas, mesas)', 'manda' => 'hojalateria'],
                ],
            ],
            'completo' => [
                'titulo' => 'Está completo',
                'puntos' => [
                    'cables' => ['texto' => 'Cables de poder y de señal completos', 'manda' => null],
                    'accesorios' => ['texto' => 'Accesorios completos según lo acordado', 'manda' => null],
                    'manual' => ['texto' => 'Trae manual o documentación', 'manda' => null],
                    'placa' => ['texto' => 'Conserva su placa de número de serie', 'manda' => null],
                ],
            ],
            'higiene' => [
                'titulo' => 'Higiene y seguridad',
                'puntos' => [
                    'limpio' => ['texto' => 'Llega limpio, sin residuos biológicos', 'manda' => 'limpieza'],
                    'desinfectado' => ['texto' => 'Trae constancia de desinfección', 'manda' => 'limpieza'],
                ],
            ],
        ];
    }

    /** Todas las llaves de punto, aplanadas, para validar lo que llegó. */
    public static function llaves(): array
    {
        return collect(static::grupos())
            ->flatMap(fn (array $g) => array_keys($g['puntos']))
            ->all();
    }

    /**
     * Texto de un punto, para mostrarlo después.
     *
     * Los puntos que armó la IA o que se agregaron a mano no están en
     * grupos(): su texto viaja guardado junto con la respuesta.
     */
    public static function titulo(string $llave, ?array $punto = null): string
    {
        if (! empty($punto['texto'])) {
            return (string) $punto['texto'];
        }

        foreach (static::grupos() as $grupo) {
            if (isset($grupo['puntos'][$llave])) {
                return $grupo['puntos'][$llave]['texto'];
            }
        }

        return $llave;
    }

    /**
     * Grupo de un punto: el que trae guardado o, en checklists de antes de
     * que se guardara, el del checklist general.
     */
    public static function grupoDe(string $llave, ?array $punto = null): string
    {
        if (! empty($punto['grupo'])) {
            return (string) $punto['grupo'];
        }

        foreach (static::grupos() as $grupo) {
            if (isset($grupo['puntos'][$llave])) {
                return $grupo['titulo'];
            }
        }

        return 'Otros';
    }

    /** A qué proceso manda un punto cuando sale mal, si es que manda. */
    public static function mandaA(string $llave, ?array $punto = null): ?string
    {
        if ($punto !== null && array_key_exists('manda', $punto)) {
            return $punto['manda'] ?: null;
        }

        foreach (static::grupos() as $grupo) {
            if (isset($grupo['puntos'][$llave])) {
                return $grupo['puntos'][$llave]['manda'];
            }
        }

        return null;
    }

    /** Cuántos puntos puede traer un checklist (los de la IA más los agregados a mano). */
    public const MAX_PUNTOS = 80;

    /**
     * Deja solo los puntos con respuesta válida, y recorta textos y notas.
     * Lo que llega del formulario no se guarda tal cual.
     *
     * Cada punto se guarda con su texto, su grupo y a qué proceso manda: el
     * checklist ya no es fijo (lo arma la IA según el equipo y se le pueden
     * agregar puntos a mano), así que la llave sola ya no basta para saber
     * qué decía.
     *
     * @return array<string, array{r: string, texto: string, grupo: ?string, manda: ?string, nota?: string}>
     */
    public static function limpiar(?array $recibido): array
    {
        $procesos = array_keys(PiezaProceso::PROCESOS);
        $limpio = [];

        foreach ($recibido ?? [] as $llave => $valor) {
            $llave = (string) $llave;

            if (! preg_match('/^[a-z0-9_]{1,60}$/', $llave) || ! is_array($valor)) {
                continue;
            }

            $respuesta = $valor['r'] ?? null;

            if (! in_array($respuesta, self::RESPUESTAS, true)) {
                continue;
            }

            $texto = trim((string) ($valor['texto'] ?? '')) ?: static::titulo($llave);

            // Un punto sin texto propio y que tampoco es de los de siempre no
            // dice nada: no se guarda.
            if ($texto === $llave) {
                continue;
            }

            $manda = $valor['manda'] ?? static::mandaA($llave);

            $punto = [
                'r' => $respuesta,
                'texto' => mb_substr($texto, 0, 200),
                'grupo' => mb_substr(trim((string) ($valor['grupo'] ?? '')), 0, 60) ?: null,
                'manda' => in_array($manda, $procesos, true) ? $manda : null,
            ];

            $nota = trim((string) ($valor['nota'] ?? ''));

            if ($nota !== '') {
                $punto['nota'] = mb_substr($nota, 0, 300);
            }

            $limpio[$llave] = $punto;

            if (count($limpio) >= self::MAX_PUNTOS) {
                break;
            }
        }

        return $limpio;
    }

    /** Cuántos puntos salieron mal: es el resumen que interesa de un vistazo. */
    public static function cuantosMal(array $checklist): int
    {
        return collect($checklist)->where('r', 'no')->count();
    }

    /**
     * Qué procesos propone el checklist, y por qué.
     *
     * Se lee lo que salió en "No" y se agrupa por el proceso al que manda
     * cada punto. El motivo se conserva para que después se vea de dónde
     * salió cada paso de la ruta.
     *
     * @return array<string, string>  proceso => motivo
     */
    public static function procesosSugeridos(array $checklist): array
    {
        $porProceso = [];

        foreach ($checklist as $llave => $punto) {
            if (($punto['r'] ?? null) !== 'no') {
                continue;
            }

            $proceso = static::mandaA($llave, $punto);

            if (! $proceso) {
                continue;
            }

            $porProceso[$proceso][] = static::motivoDe($llave, $punto);
        }

        return collect($porProceso)
            ->map(fn (array $motivos) => mb_substr(implode('; ', $motivos), 0, 255))
            ->all();
    }

    /**
     * Texto con el que un punto en "No" justifica un proceso.
     *
     * Es la nota que escribió quien recibió la pieza ("parte trasera
     * rayada"), no el título del punto: el título está en positivo
     * ("Carcasa sin golpes") y leído como motivo dice lo contrario de lo
     * que pasó. Si no dejaron nota, se avisa qué punto salió en "No".
     *
     * @param  array{r: string, nota?: string}  $punto
     */
    public static function motivoDe(string $llave, array $punto): string
    {
        $nota = trim((string) ($punto['nota'] ?? ''));

        if ($nota !== '') {
            return mb_strtoupper(mb_substr($nota, 0, 1)).mb_substr($nota, 1);
        }

        return 'No cumple: '.mb_strtolower(static::titulo($llave, $punto));
    }
}
