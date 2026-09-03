<?php

namespace App\Support;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Cotizacion;
use App\Models\Customer;
use App\Models\EquipmentModel;
use App\Models\EquipmentType;
use App\Models\Factura;
use App\Models\Producto;
use App\Models\Subtype;
use App\Models\Task;
use App\Models\User;
use App\Models\Venta;
use Illuminate\Support\Carbon;

/**
 * Catalogo de tarjetas del tablero.
 *
 * Cada tarjeta declara aqui su nombre, su descripcion, que tan chica o
 * grande puede quedar, y como se calculan sus datos. Agregar una tarjeta
 * nueva es agregar una entrada en catalogo() y su vista en dashboard/widgets.
 *
 * El tamaño se mide en celdas sobre una rejilla de 4 columnas:
 *   w = columnas de ancho (1 a 4)
 *   h = renglones de alto (cada renglon mide --dash-row en la hoja de estilos)
 */
class DashboardWidgets
{
    public const ANCHO_MAX = 4;
    public const ALTO_MAX = 8;

    /** Limites por omision de cualquier tarjeta que no diga otra cosa. */
    private const LIMITES = ['w_min' => 1, 'w_max' => self::ANCHO_MAX, 'h_min' => 2, 'h_max' => self::ALTO_MAX];

    /**
     * Arreglo de fabrica, para quien todavia no personaliza su tablero.
     *
     * @return array<int, array{id: string, w: int, h: int}>
     */
    public static function porOmision(): array
    {
        $ids = ['clientes', 'cotizaciones', 'ventas_mes', 'inventario',
            'ventas_grafica', 'ultimas_cotizaciones', 'ultimos_clientes', 'accesos_rapidos'];

        return array_map(function (string $id) {
            $def = self::definicion($id);

            return ['id' => $id, 'w' => $def['w'], 'h' => $def['h']];
        }, $ids);
    }

    /** Definicion de una tarjeta con sus limites ya completados. */
    public static function definicion(string $id): array
    {
        $def = self::catalogo()[$id] ?? [];

        return array_merge(self::LIMITES, $def);
    }

    /**
     * Definicion de cada tarjeta disponible.
     *
     * @return array<string, array<string, mixed>>
     */
    public static function catalogo(): array
    {
        return [
            'clientes' => [
                'titulo' => 'Clientes',
                'descripcion' => 'Total registrado y cuántos entraron este mes.',
                'grupo' => 'Indicadores',
                'w' => 1, 'h' => 2,
            ],
            'cotizaciones' => [
                'titulo' => 'Cotizaciones',
                'descripcion' => 'Cuántas llevas y por qué monto.',
                'grupo' => 'Indicadores',
                'w' => 1, 'h' => 2,
            ],
            'ventas_mes' => [
                'titulo' => 'Ventas del mes',
                'descripcion' => 'Monto vendido en el mes en curso.',
                'grupo' => 'Indicadores',
                'w' => 1, 'h' => 2,
            ],
            'inventario' => [
                'titulo' => 'Inventario',
                'descripcion' => 'Productos dados de alta y cuáles andan bajos de stock.',
                'grupo' => 'Indicadores',
                'w' => 1, 'h' => 2,
            ],
            'facturas' => [
                'titulo' => 'Facturas por cobrar',
                'descripcion' => 'Facturas que siguen sin pagarse.',
                'grupo' => 'Indicadores',
                'w' => 1, 'h' => 2,
            ],
            'ventas_grafica' => [
                'titulo' => 'Ventas por mes',
                'descripcion' => 'Barras de los últimos seis meses.',
                'grupo' => 'Gráficas',
                'w' => 2, 'h' => 4, 'w_min' => 2, 'h_min' => 3,
            ],
            'ultimas_cotizaciones' => [
                'titulo' => 'Últimas cotizaciones',
                'descripcion' => 'Las cinco más recientes, con su estado.',
                'grupo' => 'Listas',
                'w' => 2, 'h' => 4, 'h_min' => 3,
            ],
            'ultimos_clientes' => [
                'titulo' => 'Últimos clientes',
                'descripcion' => 'Los cinco que se registraron al final.',
                'grupo' => 'Listas',
                'w' => 2, 'h' => 4, 'h_min' => 3,
            ],
            'mis_tareas' => [
                'titulo' => 'Mis tareas',
                'descripcion' => 'Tus pendientes de marketing sin terminar.',
                'grupo' => 'Listas',
                'w' => 2, 'h' => 4, 'h_min' => 3,
            ],
            'catalogo_equipo' => [
                'titulo' => 'Catálogo de equipo',
                'descripcion' => 'Cuántos tipos, subtipos, marcas y modelos hay.',
                'grupo' => 'Listas',
                'w' => 2, 'h' => 3,
            ],
            'accesos_rapidos' => [
                'titulo' => 'Accesos rápidos',
                'descripcion' => 'Botones a lo que más se usa.',
                'grupo' => 'Herramientas',
                'w' => 2, 'h' => 3,
            ],
        ];
    }

    /**
     * Limpia lo que venga guardado o del formulario: descarta ids que ya no
     * existen, recorta tamaños fuera de rango y quita repetidos.
     *
     * @return array<int, array{id: string, w: int, h: int}>
     */
    public static function normalizar(mixed $lista): array
    {
        if (! is_array($lista)) {
            return [];
        }

        $catalogo = self::catalogo();
        $vistos = [];
        $salida = [];

        foreach ($lista as $item) {
            $id = is_array($item) ? ($item['id'] ?? null) : null;

            if (! is_string($id) || ! isset($catalogo[$id]) || isset($vistos[$id])) {
                continue;
            }

            $def = self::definicion($id);

            // Compatibilidad con el formato viejo, que guardaba s / m / g.
            if (! isset($item['w']) && isset($item['size'])) {
                $item['w'] = ['s' => 1, 'm' => 2, 'g' => 4][$item['size']] ?? $def['w'];
            }

            $salida[] = [
                'id' => $id,
                'w' => self::recortar($item['w'] ?? $def['w'], $def['w_min'], $def['w_max']),
                'h' => self::recortar($item['h'] ?? $def['h'], $def['h_min'], $def['h_max']),
            ];

            $vistos[$id] = true;
        }

        return $salida;
    }

    /** Deja el numero dentro del rango; lo que no sea numero cae al minimo. */
    private static function recortar(mixed $valor, int $min, int $max): int
    {
        if (! is_numeric($valor)) {
            return $min;
        }

        return max($min, min($max, (int) $valor));
    }

    /**
     * Tarjetas que le tocan a un usuario, ya normalizadas.
     *
     * @return array<int, array{id: string, w: int, h: int}>
     */
    public static function paraUsuario(User $user): array
    {
        $guardado = self::normalizar($user->dashboard_widgets);

        return $guardado ?: self::porOmision();
    }

    /**
     * Que tanto detalle admite una tarjeta segun lo que ocupa.
     *
     *   1 = apretada, solo el dato principal
     *   2 = hay lugar para un desglose
     *   3 = amplia, cabe ademas una tabla
     */
    public static function nivel(int $w, int $h): int
    {
        $area = $w * $h;

        return match (true) {
            $area >= 8 => 3,
            $area >= 4 => 2,
            default => 1,
        };
    }

    /**
     * Cuantas filas caben en una lista segun el alto.
     *
     * Es una estimacion calibrada contra el alto real de fila (~53px) y el
     * espacio que se lleva el encabezado. Se queda corta a proposito: mas
     * vale que sobre tantito a que la ultima fila salga cortada.
     */
    public static function filasQueCaben(int $h): int
    {
        return max(2, (int) floor(($h - 1) * 1.4));
    }

    /**
     * Calcula los datos de una tarjeta. Se llama solo para las visibles y
     * con su tamaño, asi que las consultas del detalle extra solo corren
     * cuando la tarjeta es lo bastante grande para mostrarlo.
     */
    public static function datos(string $id, User $user, int $w = 1, int $h = 2): array
    {
        $nivel = self::nivel($w, $h);
        $filas = self::filasQueCaben($h);

        $datos = match ($id) {
            'clientes' => self::datosClientes($nivel, $filas),
            'cotizaciones' => self::datosCotizaciones($nivel, $filas),
            'ventas_mes' => self::datosVentasMes($nivel, $filas),
            'inventario' => self::datosInventario($nivel, $filas),
            'facturas' => self::datosFacturas($nivel, $filas),
            'ventas_grafica' => self::datosVentasGrafica($nivel),
            'ultimas_cotizaciones' => self::datosUltimasCotizaciones($filas),
            'ultimos_clientes' => self::datosUltimosClientes($filas),
            'mis_tareas' => self::datosMisTareas($user, $filas),
            'catalogo_equipo' => self::datosCatalogoEquipo($nivel),
            default => [],
        };

        return $datos + ['nivel' => $nivel];
    }

    // ===================== Cálculos =====================

    private static function datosClientes(int $nivel, int $filas): array
    {
        $datos = [
            'total' => Customer::count(),
            'nuevos' => Customer::where('created_at', '>=', now()->startOfMonth())->count(),
            'inactivos' => Customer::where('activo', false)->count(),
        ];

        if ($nivel >= 2) {
            $datos['activos'] = Customer::where('activo', true)->count();
            $datos['con_promocion'] = Customer::where('recibe_promocion', true)->count();
        }

        if ($nivel >= 3) {
            // Desglose por categoria, para que la tarjeta amplia diga algo mas.
            $datos['tabla'] = Customer::selectRaw('categoria_id, COUNT(*) as total')
                ->groupBy('categoria_id')
                ->orderByDesc('total')
                ->limit($filas)
                ->get()
                ->map(fn ($fila) => [
                    'etiqueta' => Category::find($fila->categoria_id)?->nombre ?? 'Sin categoría',
                    'valor' => $fila->total,
                ])
                ->all();
        }

        return $datos;
    }

    private static function datosCotizaciones(int $nivel, int $filas): array
    {
        $datos = [
            'total' => Cotizacion::count(),
            'monto' => (float) Cotizacion::sum('total'),
            'mes' => Cotizacion::where('created_at', '>=', now()->startOfMonth())->count(),
        ];

        if ($nivel >= 2) {
            $datos['promedio'] = $datos['total'] > 0 ? $datos['monto'] / $datos['total'] : 0.0;
        }

        if ($nivel >= 3) {
            $datos['tabla'] = Cotizacion::selectRaw('estado, COUNT(*) as total, SUM(total) as monto')
                ->groupBy('estado')
                ->orderByDesc('total')
                ->limit($filas)
                ->get()
                ->map(fn ($fila) => [
                    'etiqueta' => ucfirst($fila->estado ?: 'Sin estado'),
                    'valor' => $fila->total,
                    'extra' => '$' . number_format((float) $fila->monto, 2),
                ])
                ->all();
        }

        return $datos;
    }

    private static function datosVentasMes(int $nivel, int $filas): array
    {
        $desde = now()->startOfMonth();

        $datos = [
            'monto' => (float) Venta::where('created_at', '>=', $desde)->sum('total'),
            'cantidad' => Venta::where('created_at', '>=', $desde)->count(),
            'monto_total' => (float) Venta::sum('total'),
        ];

        if ($nivel >= 2) {
            $inicioAnterior = (clone $desde)->subMonth();
            $anterior = (float) Venta::whereBetween('created_at', [$inicioAnterior, (clone $inicioAnterior)->endOfMonth()])->sum('total');

            $datos['mes_anterior'] = $anterior;
            $datos['variacion'] = $anterior > 0 ? (($datos['monto'] - $anterior) / $anterior) * 100 : null;
        }

        if ($nivel >= 3) {
            $datos['tabla'] = Venta::with('customer')
                ->where('created_at', '>=', $desde)
                ->orderByDesc('total')
                ->limit($filas)
                ->get()
                ->map(fn ($venta) => [
                    'etiqueta' => trim(($venta->customer->nombre ?? '') . ' ' . ($venta->customer->apellido ?? '')) ?: ($venta->folio ?: 'Venta #' . $venta->id),
                    'valor' => '$' . number_format((float) $venta->total, 2),
                ])
                ->all();
        }

        return $datos;
    }

    private static function datosInventario(int $nivel, int $filas): array
    {
        $datos = [
            'total' => Producto::count(),
            'sin_stock' => Producto::where('stock', '<=', 0)->count(),
            'valor' => (float) Producto::sum('precio'),
        ];

        if ($nivel >= 2) {
            $datos['unidades'] = (int) Producto::sum('stock');
        }

        if ($nivel >= 3) {
            $datos['tabla'] = Producto::orderBy('stock')
                ->limit($filas)
                ->get()
                ->map(fn ($p) => [
                    'etiqueta' => trim(($p->marca ?? '') . ' ' . ($p->modelo ?? '')) ?: ($p->tipo_equipo ?: 'Producto #' . $p->id),
                    'valor' => $p->stock . ' pz',
                    'alerta' => $p->stock <= 0,
                ])
                ->all();
        }

        return $datos;
    }

    private static function datosFacturas(int $nivel, int $filas): array
    {
        // El nombre del estado pagado cambia entre modulos; se cuenta lo que
        // no esta marcado como pagado.
        $noPagadas = ['pagada', 'pagado', 'cancelada'];

        $datos = [
            'total' => Factura::count(),
            'pendientes' => Factura::whereNotIn('estado', $noPagadas)->count(),
        ];

        if ($nivel >= 2) {
            $datos['monto_pendiente'] = (float) Factura::whereNotIn('estado', $noPagadas)->sum('total');
        }

        if ($nivel >= 3) {
            $datos['tabla'] = Factura::with('customer')
                ->whereNotIn('estado', $noPagadas)
                ->latest()
                ->limit($filas)
                ->get()
                ->map(fn ($f) => [
                    'etiqueta' => $f->folio ?: 'Factura #' . $f->id,
                    'valor' => '$' . number_format((float) $f->total, 2),
                    'extra' => ucfirst($f->estado ?: '—'),
                ])
                ->all();
        }

        return $datos;
    }

    private static function datosVentasGrafica(int $nivel): array
    {
        $meses = collect(range(5, 0))->map(function (int $atras) {
            $inicio = now()->startOfMonth()->subMonths($atras);
            $fin = (clone $inicio)->endOfMonth();

            return [
                'etiqueta' => ucfirst($inicio->locale('es')->isoFormat('MMM')),
                'monto' => (float) Venta::whereBetween('created_at', [$inicio, $fin])->sum('total'),
            ];
        })->all();

        $maximo = collect($meses)->max('monto') ?: 0;

        $datos = ['meses' => $meses, 'maximo' => $maximo];

        if ($nivel >= 3) {
            // Grande, además de las barras se listan las cifras del periodo.
            $datos['total_periodo'] = array_sum(array_column($meses, 'monto'));
            $datos['tabla'] = array_map(fn ($mes) => [
                'etiqueta' => $mes['etiqueta'],
                'valor' => '$' . number_format($mes['monto'], 2),
            ], array_reverse($meses));
        }

        return $datos;
    }

    // En las listas, cuantas filas se traen depende del alto de la tarjeta.

    private static function datosUltimasCotizaciones(int $filas): array
    {
        return [
            'filas' => Cotizacion::with('customer')->latest()->limit($filas)->get(),
        ];
    }

    private static function datosUltimosClientes(int $filas): array
    {
        return [
            'filas' => Customer::with('asesor')->latest()->limit($filas)->get(),
        ];
    }

    private static function datosMisTareas(User $user, int $filas): array
    {
        return [
            'filas' => Task::where('user_id', $user->id)
                ->whereNotIn('status', ['completado', 'completada', 'terminado'])
                ->latest()
                ->limit($filas)
                ->get(),
        ];
    }

    private static function datosCatalogoEquipo(int $nivel): array
    {
        $datos = [
            'tipos' => EquipmentType::count(),
            'subtipos' => Subtype::count(),
            'marcas' => Brand::count(),
            'modelos' => EquipmentModel::count(),
        ];

        if ($nivel >= 3) {
            $datos['tabla'] = EquipmentType::withCount('subtypes')
                ->orderByDesc('subtypes_count')
                ->limit(8)
                ->get()
                ->map(fn ($t) => [
                    'etiqueta' => $t->name,
                    'valor' => $t->subtypes_count . ' subtipos',
                ])
                ->all();
        }

        return $datos;
    }
}
