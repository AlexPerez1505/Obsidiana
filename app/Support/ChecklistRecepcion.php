<?php

namespace App\Support;

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

    /** Texto de un punto a partir de su llave, para mostrarlo después. */
    public static function titulo(string $llave): string
    {
        foreach (static::grupos() as $grupo) {
            if (isset($grupo['puntos'][$llave])) {
                return $grupo['puntos'][$llave]['texto'];
            }
        }

        return $llave;
    }

    /** A qué proceso manda un punto cuando sale mal, si es que manda. */
    public static function mandaA(string $llave): ?string
    {
        foreach (static::grupos() as $grupo) {
            if (isset($grupo['puntos'][$llave])) {
                return $grupo['puntos'][$llave]['manda'];
            }
        }

        return null;
    }

    /**
     * Deja solo los puntos conocidos y con respuesta válida, y recorta la
     * nota. Lo que llega del formulario no se guarda tal cual.
     *
     * @return array<string, array{r: string, nota?: string}>
     */
    public static function limpiar(?array $recibido): array
    {
        $validas = static::llaves();
        $limpio = [];

        foreach ($recibido ?? [] as $llave => $valor) {
            if (! in_array($llave, $validas, true) || ! is_array($valor)) {
                continue;
            }

            $respuesta = $valor['r'] ?? null;

            if (! in_array($respuesta, self::RESPUESTAS, true)) {
                continue;
            }

            $punto = ['r' => $respuesta];
            $nota = trim((string) ($valor['nota'] ?? ''));

            if ($nota !== '') {
                $punto['nota'] = mb_substr($nota, 0, 300);
            }

            $limpio[$llave] = $punto;
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

            $proceso = static::mandaA($llave);

            if (! $proceso) {
                continue;
            }

            $porProceso[$proceso][] = static::titulo($llave);
        }

        return collect($porProceso)
            ->map(fn (array $motivos) => mb_substr(implode('; ', $motivos), 0, 255))
            ->all();
    }
}
