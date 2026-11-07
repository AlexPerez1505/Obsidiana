<?php

namespace App\Http\Controllers\Promociones;

use App\Http\Controllers\Controller;
use App\Models\Cupon;
use App\Models\CuponUso;
use App\Models\EquipmentType;
use App\Models\MktEvento;
use App\Services\Promociones\CatalogoOferta;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Datos de la pantalla de cupones (búsqueda, filtros, orden, página,
 * panel lateral, duplicar y acciones en lote). Las reglas del descuento
 * siguen viviendo en MotorDeCupones; aquí solo se consulta y administra.
 */
class CuponesApiController extends Controller
{
    public const ORDENES = ['recientes', 'codigo', 'vence', 'usos', 'descuento'];

    public function __construct(private readonly CatalogoOferta $catalogo)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $f = $request->validate([
            'q' => ['nullable', 'string', 'max:60'],
            'estado' => ['nullable', 'in:todos,activo,programado,vencido,agotado,pausado'],
            'tipo' => ['nullable', 'in:general,exclusivo,molde'],
            'descuento_tipo' => ['nullable', 'in:porcentaje,monto'],
            'personales' => ['nullable', 'boolean'],
            'con_usos' => ['nullable', 'boolean'],
            'orden' => ['nullable', 'in:'.implode(',', self::ORDENES)],
            'dir' => ['nullable', 'in:asc,desc'],
            'page' => ['nullable', 'integer', 'min:1'],
            'por_pagina' => ['nullable', 'in:12,24,48'],
        ]);

        $base = $this->base($f);
        $conteos = [];
        foreach (['todos', 'activo', 'programado', 'vencido', 'agotado', 'pausado'] as $e) {
            $conteos[$e] = $this->porEstado(clone $base, $e)->count();
        }

        $q = $this->porEstado(clone $base, $f['estado'] ?? 'todos')
            ->with(['cliente:id,nombre,apellido', 'campana:id,nombre'])
            ->withCount([
                'usos as vigentes_count' => fn ($u) => $u->vigentes(),
                'usos as usados_count' => fn ($u) => $u->where('estado', CuponUso::CONSUMIDO),
            ]);

        $dir = ($f['dir'] ?? 'desc') === 'asc' ? 'asc' : 'desc';
        match ($f['orden'] ?? 'recientes') {
            'codigo' => $q->orderBy('codigo', $dir),
            'vence' => $q->orderByRaw('vence_en IS NULL')->orderBy('vence_en', $dir),
            'usos' => $q->orderBy('usados_count', $dir),
            'descuento' => $q->orderBy('descuento_tipo')->orderBy('descuento_valor', $dir),
            default => $q->orderBy('id', $dir),
        };

        $pag = $q->paginate((int) ($f['por_pagina'] ?? 12));

        return response()->json([
            'data' => collect($pag->items())->map(fn (Cupon $c) => $this->fila($c))->values(),
            'total' => $pag->total(),
            'pagina' => $pag->currentPage(),
            'paginas' => $pag->lastPage(),
            'conteos' => $conteos,
        ]);
    }

    public function show(Cupon $cupon): JsonResponse
    {
        $cupon->load(['cliente', 'campana', 'alcances', 'origen', 'creador'])
            ->loadCount(['usos as vigentes_count' => fn ($u) => $u->vigentes(), 'usos as usados_count' => fn ($u) => $u->where('estado', CuponUso::CONSUMIDO)]);

        $usos = $cupon->usos()->with(['cliente:id,nombre,apellido', 'venta:id,folio,total,estado', 'cotizacion:id,folio'])->latest('id')->limit(25)->get();
        $consumidos = $cupon->usos()->where('mkt_cupon_usos.estado', CuponUso::CONSUMIDO);

        $claves = $cupon->alcances->map(fn ($a) => $a->tipo.'-'.$a->ref_id)->all();
        $items = $this->catalogo->resolver(array_values(array_filter($claves, fn ($k) => ! str_starts_with($k, 'tipo_equipo-'))));
        $tipos = EquipmentType::whereIn('id', $cupon->alcances->where('tipo', 'tipo_equipo')->pluck('ref_id'))->get(['id', 'name']);

        return response()->json($this->fila($cupon) + [
            'condiciones' => $cupon->condicionesLegibles(),
            'alcance' => $cupon->aplica_a === 'seleccion'
                ? $items->map(fn ($i) => $i['nombre'].($i['modelo'] && $i['modelo'] !== 'PAQUETE' ? ' '.$i['modelo'] : ''))->merge($tipos->map(fn ($t) => 'Tipo: '.$t->name))->values()
                : ['Todos los productos y paquetes'],
            'resultados' => [
                'usados' => (clone $consumidos)->count(),
                'reservados' => $cupon->usos()->where('estado', CuponUso::RESERVADO)->where('expira_en', '>', now())->count(),
                'descontado' => (float) (clone $consumidos)->sum('descuento'),
                'ventas' => (float) (clone $consumidos)->join('ventas', 'ventas.id', '=', 'mkt_cupon_usos.venta_id')->where('ventas.estado', '!=', 'cancelada')->sum('ventas.total'),
                'copias' => $cupon->esPlantilla() ? $cupon->copias()->count() : null,
            ],
            'usos_lista' => $usos->map(fn (CuponUso $u) => [
                'cliente' => trim(($u->cliente?->nombre ?? '').' '.($u->cliente?->apellido ?? '')),
                'documento' => $u->venta?->folio ?? $u->cotizacion?->folio,
                'url' => $u->venta ? route('commercial.ventas.show', $u->venta) : ($u->cotizacion ? route('commercial.cotizaciones.show', $u->cotizacion) : null),
                'descuento' => (float) $u->descuento,
                'estado' => $u->estadoLabel(),
                'tono' => $u->tono(),
                'motivo' => $u->motivo_cierre,
                'fecha' => ($u->consumido_en ?? $u->reservado_en ?? $u->created_at)->format('d/m/Y H:i'),
            ]),
            'actividad' => MktEvento::where('cupon_id', $cupon->id)->with('usuario:id,name')->latest('id')->limit(12)->get()
                ->map(fn ($e) => ['texto' => $e->detalle, 'quien' => $e->usuario?->name ?? 'Sistema', 'fecha' => $e->created_at->format('d/m/Y H:i')])
                ->push(['texto' => 'Creado', 'quien' => $cupon->creador?->name ?? 'Sistema', 'fecha' => $cupon->created_at->format('d/m/Y H:i')]),
            // Lo que necesita el asistente para editar.
            'form' => [
                'codigo' => $cupon->codigo, 'nombre' => $cupon->nombre, 'tipo' => $cupon->tipo,
                'cliente' => $cupon->cliente ? ['id' => $cupon->cliente->id, 'nombre' => trim($cupon->cliente->nombre.' '.$cupon->cliente->apellido)] : null,
                'descuento_tipo' => $cupon->descuento_tipo, 'descuento_valor' => (float) $cupon->descuento_valor,
                'tope_descuento' => $cupon->tope_descuento !== null ? (float) $cupon->tope_descuento : null,
                'compra_minima' => (float) $cupon->compra_minima,
                'inicia_en' => $cupon->inicia_en?->format('Y-m-d\TH:i'), 'vence_en' => $cupon->vence_en?->format('Y-m-d\TH:i'),
                'usos_max' => $cupon->usos_max, 'usos_por_cliente' => $cupon->usos_por_cliente,
                'aplica_a' => $cupon->aplica_a, 'acumulable' => $cupon->acumulable, 'solo_primera_compra' => $cupon->solo_primera_compra,
                'estado' => $cupon->estado, 'condiciones' => $cupon->condiciones,
                'items' => $items->map(fn ($i) => ['clave' => $i['clave'], 'nombre' => trim($i['nombre'].' '.($i['modelo'] !== 'PAQUETE' ? $i['modelo'] : ''))])->values(),
                'tipos' => $tipos->pluck('id')->values(),
                'con_usos' => $cupon->usos()->exists(),
            ],
        ]);
    }

    public function duplicar(Cupon $cupon): JsonResponse
    {
        $copia = DB::transaction(function () use ($cupon) {
            $c = $cupon->replicate(['codigo', 'campana_id', 'origen_cupon_id', 'creado_por', 'actualizado_por']);
            $c->codigo = Cupon::codigoAleatorio(explode('-', $cupon->codigo)[0]);
            $c->nombre = mb_substr('Copia de '.$cupon->nombre, 0, 255);
            // Nace pausada para revisarla antes de que alguien la use.
            $c->estado = 'pausado';
            $c->creado_por = auth()->id();
            $c->save();
            foreach ($cupon->alcances as $a) {
                $c->alcances()->create(['tipo' => $a->tipo, 'ref_id' => $a->ref_id]);
            }
            MktEvento::registrar('cupon_duplicado', 'Duplicó el cupón '.$cupon->codigo.' como '.$c->codigo, null, $c);

            return $c;
        });

        return response()->json(['id' => $copia->id, 'codigo' => $copia->codigo, 'mensaje' => "Copia creada como {$copia->codigo} (en pausa para revisarla)."]);
    }

    public function lote(Request $request): JsonResponse
    {
        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1', 'max:100'],
            'ids.*' => ['integer'],
            'accion' => ['required', 'in:pausar,activar'],
        ]);
        $nuevo = $data['accion'] === 'pausar' ? 'pausado' : 'activo';

        $cupones = Cupon::whereIn('id', $data['ids'])->where('estado', '!=', $nuevo)->get();
        foreach ($cupones as $c) {
            $c->update(['estado' => $nuevo, 'actualizado_por' => auth()->id()]);
            MktEvento::registrar('cupon_'.$nuevo, ($nuevo === 'pausado' ? 'Pausó' : 'Reactivó').' el cupón '.$c->codigo.' (en lote)', null, $c);
        }

        $omitidos = count($data['ids']) - $cupones->count();

        return response()->json([
            'cambiados' => $cupones->count(),
            'mensaje' => $cupones->count().' cupón(es) '.($nuevo === 'pausado' ? 'pausados' : 'activados').($omitidos ? " · {$omitidos} ya estaban así" : '').'.',
        ]);
    }

    // ------------------------------------------------------------------

    public static function fila(Cupon $c): array
    {
        $vigentes = (int) ($c->vigentes_count ?? $c->usosActivos());
        $estado = self::estadoCon($c, $vigentes);
        $vence = $c->vence_en;

        return [
            'id' => $c->id,
            'codigo' => $c->codigo,
            'nombre' => $c->nombre,
            'tipo' => $c->esPlantilla() ? 'molde' : $c->tipo,
            'tipo_label' => $c->esPlantilla() ? 'Molde personal' : ($c->tipo === 'exclusivo' ? 'Exclusivo' : 'General'),
            'cliente' => $c->cliente ? trim($c->cliente->nombre.' '.$c->cliente->apellido) : null,
            'campana' => $c->campana ? ['id' => $c->campana->id, 'nombre' => $c->campana->nombre] : null,
            'personal' => (bool) $c->origen_cupon_id,
            'descuento' => $c->textoDescuento(false),
            'descuento_tipo' => $c->descuento_tipo,
            'tope' => $c->tope_descuento ? '$'.number_format((float) $c->tope_descuento, 0) : null,
            'minimo' => (float) $c->compra_minima > 0 ? '$'.number_format((float) $c->compra_minima, 0) : null,
            'estado' => $estado,
            'estado_label' => Cupon::etiquetaEstado($estado),
            'tono' => Cupon::tonoEstado($estado),
            'inicia' => $c->inicia_en?->format('d/m/Y'),
            'vence' => $vence?->format('d/m/Y'),
            'vence_rel' => $vence ? ($vence->isPast() ? 'Venció '.$vence->diffForHumans() : 'Vence '.$vence->diffForHumans()) : 'Sin vencimiento',
            'usados' => (int) ($c->usados_count ?? 0),
            'vigentes' => $vigentes,
            'usos_max' => $c->usos_max,
            'pct' => $c->usos_max ? min(100, round($vigentes / $c->usos_max * 100)) : null,
            'pausado' => $c->estado === 'pausado',
        ];
    }

    public static function estadoCon(Cupon $c, int $vigentes): string
    {
        return match (true) {
            $c->estado === 'pausado' => 'pausado',
            $c->vence_en && $c->vence_en->isPast() => 'vencido',
            $c->usos_max !== null && $vigentes >= $c->usos_max => 'agotado',
            $c->inicia_en && $c->inicia_en->isFuture() => 'programado',
            default => 'activo',
        };
    }

    private function base(array $f): Builder
    {
        $q = trim((string) ($f['q'] ?? ''));

        return Cupon::query()
            ->when(empty($f['personales']), fn ($w) => $w->whereNull('origen_cupon_id'))
            ->when($q !== '', fn ($w) => $w->where(fn ($s) => $s->where('codigo', 'like', "%{$q}%")->orWhere('nombre', 'like', "%{$q}%")))
            ->when(($f['tipo'] ?? null) === 'general', fn ($w) => $w->where('tipo', 'general'))
            ->when(($f['tipo'] ?? null) === 'exclusivo', fn ($w) => $w->where('tipo', 'exclusivo')->whereNotNull('cliente_id'))
            ->when(($f['tipo'] ?? null) === 'molde', fn ($w) => $w->where('tipo', 'exclusivo')->whereNull('cliente_id'))
            ->when($f['descuento_tipo'] ?? null, fn ($w, $t) => $w->where('descuento_tipo', $t))
            ->when(! empty($f['con_usos']), fn ($w) => $w->whereHas('usos', fn ($u) => $u->where('estado', CuponUso::CONSUMIDO)));
    }

    private function porEstado(Builder $q, string $estado): Builder
    {
        $vig = "(select count(*) from mkt_cupon_usos u where u.cupon_id = mkt_cupones.id and (u.estado = 'consumido' or (u.estado = 'reservado' and u.expira_en > ?)))";
        $ahora = now();
        $noVencido = fn ($w) => $w->whereNull('vence_en')->orWhere('vence_en', '>=', $ahora);

        return match ($estado) {
            'pausado' => $q->where('estado', 'pausado'),
            'vencido' => $q->where('estado', '!=', 'pausado')->where('vence_en', '<', $ahora),
            'agotado' => $q->where('estado', '!=', 'pausado')->where($noVencido)->whereNotNull('usos_max')->whereRaw("{$vig} >= usos_max", [$ahora]),
            'programado' => $q->where('estado', 'activo')->where($noVencido)->where('inicia_en', '>', $ahora)
                ->where(fn ($w) => $w->whereNull('usos_max')->orWhereRaw("{$vig} < usos_max", [$ahora])),
            'activo' => $q->where('estado', 'activo')->where($noVencido)->where(fn ($w) => $w->whereNull('inicia_en')->orWhere('inicia_en', '<=', $ahora))
                ->where(fn ($w) => $w->whereNull('usos_max')->orWhereRaw("{$vig} < usos_max", [$ahora])),
            default => $q,
        };
    }
}
