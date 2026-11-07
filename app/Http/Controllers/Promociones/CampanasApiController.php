<?php

namespace App\Http\Controllers\Promociones;

use App\Http\Controllers\Controller;
use App\Models\Campana;
use App\Models\CampanaDestinatario as D;
use App\Models\Venta;
use App\Services\Promociones\Audiencia;
use App\Services\Promociones\CatalogoOferta;
use App\Services\Promociones\MensajeCampana;
use App\Services\Promociones\Resultados;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Datos de la pantalla de campañas: tarjetas, panel lateral, calendario y segmentos. */
class CampanasApiController extends Controller
{
    public const GRUPOS = [
        'todas' => null,
        'borrador' => [Campana::BORRADOR],
        'programada' => [Campana::PROGRAMADA],
        'activas' => [Campana::PROGRAMADA, Campana::ENVIANDO, Campana::PAUSADA],
        'completada' => [Campana::COMPLETADA],
        'cancelada' => [Campana::CANCELADA],
    ];

    public function index(Request $request): JsonResponse
    {
        $f = $request->validate([
            'q' => ['nullable', 'string', 'max:60'],
            'estado' => ['nullable', 'in:'.implode(',', array_keys(self::GRUPOS))],
            'con_cupon' => ['nullable', 'boolean'],
            'con_ventas' => ['nullable', 'boolean'],
            'orden' => ['nullable', 'in:recientes,nombre,envio'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        $q = trim((string) ($f['q'] ?? ''));
        $base = Campana::query()
            ->when($q !== '', fn ($w) => $w->where(fn ($s) => $s->where('nombre', 'like', "%{$q}%")->orWhere('titulo_oferta', 'like', "%{$q}%")->orWhere('mensaje', 'like', "%{$q}%")))
            ->when(! empty($f['con_cupon']), fn ($w) => $w->whereNotNull('cupon_id'))
            ->when(! empty($f['con_ventas']), fn ($w) => $w->whereIn('id', Venta::whereNotNull('campana_id')->where('estado', '!=', 'cancelada')->select('campana_id')));

        $conteos = [];
        foreach (self::GRUPOS as $k => $estados) {
            $conteos[$k] = (clone $base)->when($estados, fn ($w) => $w->whereIn('estado', $estados))->count();
        }

        $estados = self::GRUPOS[$f['estado'] ?? 'todas'] ?? null;
        $lista = (clone $base)->when($estados, fn ($w) => $w->whereIn('estado', $estados))->with(['cupon:id,codigo', 'creador:id,name']);
        match ($f['orden'] ?? 'recientes') {
            'nombre' => $lista->orderBy('nombre'),
            'envio' => $lista->orderByRaw('COALESCE(iniciada_en, programada_para) IS NULL')->orderByDesc(\DB::raw('COALESCE(iniciada_en, programada_para)')),
            default => $lista->orderByDesc('id'),
        };
        $pag = $lista->paginate(12);
        $ids = collect($pag->items())->pluck('id');

        // Agregados de la página en pocas consultas.
        $porEstado = D::whereIn('campana_id', $ids)->where('es_prueba', false)
            ->selectRaw('campana_id, estado, count(*) n, sum(case when clics > 0 then 1 else 0 end) clic')
            ->groupBy('campana_id', 'estado')->get()->groupBy('campana_id');
        $ventas = Venta::whereIn('campana_id', $ids)->where('estado', '!=', 'cancelada')
            ->selectRaw('campana_id, count(*) n, sum(total) t')->groupBy('campana_id')->get()->keyBy('campana_id');

        $data = collect($pag->items())->map(function (Campana $c) use ($porEstado, $ventas) {
            $filas = $porEstado->get($c->id, collect());
            $n = fn (...$e) => (int) $filas->whereIn('estado', $e)->sum('n');
            $total = (int) $filas->sum('n');

            return $this->tarjeta($c) + [
                'destinatarios' => $total ?: null,
                'salieron' => $n(D::ENVIADO, D::ENTREGADO, D::LEIDO, D::SIMULADO, D::FALLO),
                'enviados' => $n(D::ENVIADO, D::ENTREGADO, D::LEIDO),
                'simulados' => $n(D::SIMULADO),
                'pendientes' => $n(D::PENDIENTE, D::ENVIANDO),
                'clics' => (int) $filas->sum('clic'),
                'ventas' => (int) ($ventas[$c->id]->n ?? 0),
                'ingresos' => (float) ($ventas[$c->id]->t ?? 0),
            ];
        });

        return response()->json(['data' => $data, 'total' => $pag->total(), 'pagina' => $pag->currentPage(), 'paginas' => $pag->lastPage(), 'conteos' => $conteos]);
    }

    public function show(Campana $campana, Resultados $resultados, MensajeCampana $mensajes, CatalogoOferta $catalogo): JsonResponse
    {
        $campana->load(['cupon', 'creador:id,name']);

        return response()->json($this->tarjeta($campana) + [
            'mensaje' => $campana->mensaje,
            'vista_previa' => $mensajes->vistaPrevia($campana),
            'resultados' => $resultados->deCampana($campana),
            'cupon' => $campana->cupon ? ['id' => $campana->cupon->id, 'codigo' => $campana->cupon->codigo, 'descuento' => $campana->cupon->textoDescuento(), 'personal' => $campana->cupon_exclusivo] : null,
            'vigencia' => ($campana->vigencia_inicio?->format('d/m/Y H:i') ?? 'Desde el envío').' – '.($campana->vigencia_fin?->format('d/m/Y H:i') ?? 'sin fecha'),
            'productos' => $catalogo->itemsDe($campana)->map(fn ($p) => ['nombre' => $p['nombre'], 'modelo' => $p['modelo'], 'precio' => $p['precio'], 'imagen' => $p['imagen']])->values(),
            'actividad' => $campana->eventos()->with('usuario:id,name')->limit(12)->get()
                ->map(fn ($e) => ['texto' => $e->detalle, 'quien' => $e->usuario?->name ?? 'Sistema / cliente', 'fecha' => $e->created_at->format('d/m/Y H:i')]),
            'puede' => [
                'editar' => $campana->editable(), 'enviar' => $campana->puedeEnviarse(), 'pausar' => $campana->puedePausarse(),
                'reanudar' => $campana->puedeReanudarse(), 'cancelar' => $campana->puedeCancelarse(),
            ],
            'urls' => [
                'detalle' => route('promociones.campanas.show', $campana),
                'editar' => route('promociones.campanas.edit', $campana),
                'duplicar' => route('promociones.campanas.duplicar', $campana),
                'pausar' => route('promociones.campanas.pausar', $campana),
                'reanudar' => route('promociones.campanas.reanudar', $campana),
                'cancelar' => route('promociones.campanas.cancelar', $campana),
            ],
        ]);
    }

    /** Envíos programados y realizados de un mes, para el calendario. */
    public function calendario(Request $request): JsonResponse
    {
        $mes = preg_match('/^\d{4}-\d{2}$/', (string) $request->get('mes')) ? $request->get('mes') : now()->format('Y-m');
        $ini = CarbonImmutable::createFromFormat('Y-m-d', $mes.'-01')->startOfDay();
        $fin = $ini->endOfMonth();

        $eventos = Campana::where(fn ($w) => $w->whereBetween('programada_para', [$ini, $fin])->orWhereBetween('iniciada_en', [$ini, $fin]))
            ->get(['id', 'nombre', 'estado', 'programada_para', 'iniciada_en'])
            ->map(function (Campana $c) {
                $fecha = $c->iniciada_en ?? $c->programada_para;

                return ['id' => $c->id, 'nombre' => $c->nombre, 'estado' => $c->estado, 'estado_label' => $c->estadoLabel(), 'tono' => $c->tonoEstado(),
                    'dia' => $fecha->format('Y-m-d'), 'hora' => $fecha->format('H:i'), 'tipo' => $c->iniciada_en ? 'enviada' : 'programada'];
            });

        return response()->json(['mes' => $mes, 'titulo' => ucfirst($ini->locale('es')->isoFormat('MMMM YYYY')), 'eventos' => $eventos]);
    }

    /** Cuántos clientes reales caen hoy en cada segmento rápido (después de exclusiones). */
    public function segmentos(Audiencia $audiencia): JsonResponse
    {
        $out = [];
        foreach (['todos' => [], 'nuevos' => ['dias' => 30], 'frecuentes' => ['min_compras' => 3], 'inactivos' => ['dias' => 180]] as $seg => $extra) {
            $r = $audiencia->calcular(['modo' => 'segmento', 'segmento' => $seg] + $extra);
            $out[$seg] = $r['elegibles']->count();
        }

        return response()->json($out);
    }

    private function tarjeta(Campana $c): array
    {
        $fecha = $c->iniciada_en ?? $c->programada_para;

        return [
            'id' => $c->id,
            'nombre' => $c->nombre,
            'titulo' => $c->titulo_oferta,
            'estado' => $c->estado,
            'estado_label' => $c->estadoLabel(),
            'tono' => $c->tonoEstado(),
            'imagen' => $c->imagenUrl(),
            'cupon_codigo' => $c->cupon?->codigo,
            'segmento' => Audiencia::describir($c->filtros ?? []),
            'fecha' => $fecha?->format('d/m/Y H:i') ?? $c->created_at->format('d/m/Y'),
            'fecha_label' => $c->iniciada_en ? 'Enviada' : ($c->programada_para ? 'Programada' : 'Creada'),
            'url' => route('promociones.campanas.show', $c),
        ];
    }
}
