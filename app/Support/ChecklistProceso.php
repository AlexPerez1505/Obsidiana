<?php

namespace App\Support;

use App\Models\PiezaProceso;

/**
 * El checklist con el que se cierra un proceso.
 *
 * No es un formulario genérico: se arma para cada pieza con dos partes.
 *
 *  1. Lo que la mandó ahí. Si llegó a hojalatería porque la carcasa venía
 *     golpeada, para salir hay que confirmar que la carcasa quedó. Esos
 *     puntos salen del checklist de recepción, así que el equipo se
 *     verifica contra su propio problema y no contra una lista genérica.
 *
 *  2. Lo propio del proceso. Después de pintar se revisa el acabado;
 *     después de reparar se revisa que encienda y no marque alarmas.
 *
 * Todos los puntos son obligatorios y todos tienen que salir en "sí": si
 * algo sigue mal, la pieza no sale del proceso. De eso se trata.
 */
class ChecklistProceso
{
    /** Puntos fijos de cada proceso, siempre se preguntan. */
    private const PROPIOS = [
        'hojalateria' => [
            'sin_golpes' => 'Ya no tiene golpes ni abolladuras visibles',
            'pintura' => 'La pintura quedó pareja, sin escurrimientos',
            'piezas_completas' => 'Se repusieron las piezas rotas o faltantes',
            'sin_oxido_final' => 'No quedó óxido ni corrosión',
            'ensamblado' => 'Quedó armado y ajustado, sin partes flojas',
        ],
        'mantenimiento' => [
            'enciende_final' => 'Enciende y arranca sin problemas',
            'sin_alarmas_final' => 'No marca alarmas ni códigos de falla',
            'funciones' => 'Se probaron todas sus funciones y responden',
            'imagen_final' => 'La imagen o la lectura sale correcta',
            'seguridad_electrica' => 'Cables y conexiones en buen estado, sin riesgo eléctrico',
        ],
        'limpieza' => [
            'limpio_final' => 'Quedó limpio, sin residuos',
            'desinfectado_final' => 'Se desinfectó con el producto adecuado',
            'sin_dano' => 'La limpieza no dañó etiquetas, pantallas ni acabados',
        ],
    ];

    /**
     * El checklist de esta pieza en este proceso.
     *
     * @return array<string, array{titulo: string, puntos: array<string, string>}>
     */
    public static function para(PiezaProceso $paso): array
    {
        $secciones = [];

        // 1. Lo que la trajo hasta aquí.
        $porCorregir = static::puntosDeOrigen($paso);

        if ($porCorregir) {
            $secciones['origen'] = [
                'titulo' => 'Lo que había que corregir',
                'puntos' => $porCorregir,
            ];
        }

        // 2. Lo que siempre se revisa al salir de este proceso.
        $secciones['proceso'] = [
            'titulo' => 'Revisión de salida · '.$paso->nombre(),
            'puntos' => self::PROPIOS[$paso->proceso] ?? [],
        ];

        return array_filter($secciones, fn (array $s) => $s['puntos'] !== []);
    }

    /**
     * Los puntos del checklist de recepción que mandaron la pieza a este
     * proceso, replanteados como confirmación.
     *
     * @return array<string, string>
     */
    private static function puntosDeOrigen(PiezaProceso $paso): array
    {
        $recepcion = $paso->pieza?->entrada?->checklist_recepcion ?? [];
        $puntos = [];

        foreach ($recepcion as $llave => $respuesta) {
            if (($respuesta['r'] ?? null) !== 'no') {
                continue;
            }

            if (ChecklistRecepcion::mandaA($llave) !== $paso->proceso) {
                continue;
            }

            $texto = ChecklistRecepcion::titulo($llave);
            $nota = $respuesta['nota'] ?? null;

            // Se conserva la llave original con prefijo para no chocar con
            // los puntos propios del proceso.
            $puntos['origen_'.$llave] = $nota
                ? $texto.' — se reportó: '.$nota
                : $texto;
        }

        return $puntos;
    }

    /** Todas las llaves válidas de este paso, para validar lo que llega. */
    public static function llaves(PiezaProceso $paso): array
    {
        return collect(static::para($paso))
            ->flatMap(fn (array $s) => array_keys($s['puntos']))
            ->all();
    }

    /**
     * Limpia lo que mandó el formulario: solo puntos conocidos y respuestas
     * válidas. Aquí no hay "no aplica": o quedó, o no quedó.
     *
     * @return array<string, array{r: string, nota?: string}>
     */
    public static function limpiar(PiezaProceso $paso, ?array $recibido): array
    {
        $validas = static::llaves($paso);
        $limpio = [];

        foreach ($recibido ?? [] as $llave => $valor) {
            if (! in_array($llave, $validas, true) || ! is_array($valor)) {
                continue;
            }

            if (! in_array($valor['r'] ?? null, ['si', 'no'], true)) {
                continue;
            }

            $punto = ['r' => $valor['r']];
            $nota = trim((string) ($valor['nota'] ?? ''));

            if ($nota !== '') {
                $punto['nota'] = mb_substr($nota, 0, 300);
            }

            $limpio[$llave] = $punto;
        }

        return $limpio;
    }

    /**
     * ¿Se puede cerrar este paso con estas respuestas?
     *
     * Devuelve la lista de razones por las que NO. Vacía significa que sí.
     *
     * @return array<int, string>
     */
    public static function razonesParaNoCerrar(PiezaProceso $paso, array $checklist, int $cuantasFotos): array
    {
        $razones = [];
        $todas = static::llaves($paso);

        $sinResponder = array_diff($todas, array_keys($checklist));

        if ($sinResponder) {
            $razones[] = 'Faltan '.count($sinResponder).' punto(s) del checklist por responder.';
        }

        $malas = collect($checklist)->where('r', 'no');

        if ($malas->isNotEmpty()) {
            $razones[] = 'Hay '.$malas->count().' punto(s) que siguen mal. El equipo no puede pasar a stock así.';
        }

        if ($cuantasFotos < 1) {
            $razones[] = 'Falta al menos una foto que demuestre que quedó funcionando.';
        }

        return $razones;
    }

    /** Título legible de un punto, ya sea propio o heredado de recepción. */
    public static function titulo(PiezaProceso $paso, string $llave): string
    {
        foreach (static::para($paso) as $seccion) {
            if (isset($seccion['puntos'][$llave])) {
                return $seccion['puntos'][$llave];
            }
        }

        return $llave;
    }
}
