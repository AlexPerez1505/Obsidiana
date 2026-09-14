<?php

namespace App\Support;

use App\Models\ClienteSeguimiento;
use App\Models\Cobro;
use App\Models\ComisionPago;
use App\Models\Cotizacion;
use App\Models\Customer;
use App\Models\InventoryMovement;
use App\Models\OrdenSalida;
use App\Models\Task;
use App\Models\User;
use App\Models\Venta;
use App\Models\VentaBitacora;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Línea de tiempo de lo que hizo cada quien.
 *
 * No hay una tabla central de auditoría: cada módulo ya guarda quién y
 * cuándo (created_by, seller_id, registrado_por, entregada_en...). Aquí se
 * juntan esos rastros en una sola lista de eventos, ordenada por hora, para
 * verlos día por día en una sola pantalla.
 *
 * Cada evento es un arreglo con: cuando (Carbon), usuario_id, usuario,
 * modulo, titulo, detalle, url.
 */
class LineaDeTiempo
{
    public const MODULOS = [
        'clientes' => 'Clientes',
        'cotizaciones' => 'Cotizaciones',
        'ventas' => 'Ventas',
        'cobranza' => 'Cobranza',
        'inventario' => 'Entradas de inventario',
        'salidas' => 'Órdenes de salida',
        'seguimientos' => 'Seguimientos',
        'comisiones' => 'Comisiones',
        'tareas' => 'Tareas',
    ];

    private Collection $nombres;

    public function __construct()
    {
        $this->nombres = User::pluck('name', 'id');
    }

    /**
     * @return Collection<int, array> Eventos del rango, del más reciente al más viejo.
     */
    public function eventos(Carbon $desde, Carbon $hasta, ?int $usuarioId = null, ?string $modulo = null): Collection
    {
        $desde = $desde->copy()->startOfDay();
        $hasta = $hasta->copy()->endOfDay();

        $fuentes = [
            'clientes' => fn () => $this->clientes($desde, $hasta),
            'cotizaciones' => fn () => $this->cotizaciones($desde, $hasta),
            'ventas' => fn () => $this->ventas($desde, $hasta),
            'cobranza' => fn () => $this->cobros($desde, $hasta),
            'inventario' => fn () => $this->entradas($desde, $hasta),
            'salidas' => fn () => $this->salidas($desde, $hasta),
            'seguimientos' => fn () => $this->seguimientos($desde, $hasta),
            'comisiones' => fn () => $this->comisiones($desde, $hasta),
            'tareas' => fn () => $this->tareas($desde, $hasta),
        ];

        $eventos = collect();

        foreach ($fuentes as $clave => $fuente) {
            if ($modulo && $modulo !== $clave) {
                continue;
            }

            $eventos = $eventos->merge($fuente());
        }

        if ($usuarioId) {
            $eventos = $eventos->filter(fn ($e) => (int) $e['usuario_id'] === $usuarioId);
        }

        return $eventos->sortByDesc(fn ($e) => $e['cuando']->timestamp)->values();
    }

    // ===================== Fuentes =====================

    private function clientes(Carbon $desde, Carbon $hasta): Collection
    {
        return Customer::whereBetween('created_at', [$desde, $hasta])->get()->map(fn (Customer $c) => $this->evento(
            $c->created_at, $c->asesor_id, 'clientes',
            'Registró '.($c->esProspecto() ? 'al prospecto ' : 'al cliente ').trim($c->nombre.' '.$c->apellido),
            collect([$c->category?->nombre, $c->comoConocio() ? 'conocido en '.$c->comoConocio() : null])->filter()->implode(' · '),
            route('commercial.clientes.show', $c)
        ));
    }

    private function cotizaciones(Carbon $desde, Carbon $hasta): Collection
    {
        return Cotizacion::with(['customer', 'items'])->whereBetween('created_at', [$desde, $hasta])->get()->map(fn (Cotizacion $c) => $this->evento(
            $c->created_at, $c->seller_id, 'cotizaciones',
            'Creó la cotización '.$c->folio.' para '.$this->cliente($c->customer),
            collect([$c->resumenProductos(), '$'.number_format((float) $c->total, 2), ucfirst($c->modalidad)])->filter()->implode(' · '),
            route('commercial.cotizaciones.show', $c)
        ));
    }

    private function ventas(Carbon $desde, Carbon $hasta): Collection
    {
        $creadas = Venta::with(['customer', 'items'])->whereBetween('created_at', [$desde, $hasta])->get()->map(fn (Venta $v) => $this->evento(
            $v->created_at, $v->seller_id, 'ventas',
            'Registró la venta '.$v->folio.' para '.$this->cliente($v->customer),
            collect([$v->resumenProductos(), '$'.number_format((float) $v->total, 2), ucfirst($v->modalidad).($v->modalidad === 'financiamiento' ? ' · '.$v->num_meses.' meses' : '')])->filter()->implode(' · '),
            route('commercial.ventas.show', $v)
        ));

        // Lo demás que pasa con una venta ya queda en su bitácora: cambios,
        // cancelaciones, ajustes al plan. Los cobros salen de su tabla.
        $bitacora = VentaBitacora::with('venta')
            ->whereBetween('created_at', [$desde, $hasta])
            ->whereNotIn('tipo', ['cobro_registrado', 'cobro_cancelado'])
            ->get()
            ->filter(fn ($b) => $b->venta)
            ->map(fn (VentaBitacora $b) => $this->evento(
                $b->created_at, $b->user_id, 'ventas',
                $this->tituloBitacora($b),
                $b->descripcion,
                route('commercial.ventas.show', $b->venta)
            ));

        return $creadas->merge($bitacora);
    }

    private function cobros(Carbon $desde, Carbon $hasta): Collection
    {
        return Cobro::with(['venta.customer', 'parcialidad'])->whereBetween('created_at', [$desde, $hasta])->get()
            ->filter(fn ($c) => $c->venta)
            ->map(fn (Cobro $c) => $this->evento(
                $c->created_at, $c->registrado_por, 'cobranza',
                'Cobró $'.number_format((float) $c->monto, 2).' de '.$this->cliente($c->venta->customer),
                collect([$c->venta->folio, $c->metodoLabel(), $c->parcialidad?->nombre, $c->referencia ? 'ref. '.$c->referencia : null])->filter()->implode(' · '),
                route('commercial.ventas.cobros.index', $c->venta)
            ));
    }

    private function entradas(Carbon $desde, Carbon $hasta): Collection
    {
        return InventoryMovement::where('movement_type', InventoryMovement::TYPE_ENTRY)
            ->whereBetween('created_at', [$desde, $hasta])
            ->get()
            ->map(fn (InventoryMovement $m) => $this->evento(
                $m->created_at, $m->created_by, 'inventario',
                'Recibió '.$m->quantity.' pieza(s) de '.($m->item_name ?: 'equipo'),
                collect([$m->folio, $m->condicion ? ucfirst($m->condicion) : null, $m->warehouse])->filter()->implode(' · '),
                route('inventory.movimientos.show', $m)
            ));
    }

    private function salidas(Carbon $desde, Carbon $hasta): Collection
    {
        $ordenes = OrdenSalida::with(['venta.customer', 'items'])
            ->where(function ($q) use ($desde, $hasta) {
                $q->whereBetween('preparada_en', [$desde, $hasta])
                    ->orWhereBetween('entregada_en', [$desde, $hasta])
                    ->orWhereBetween('created_at', [$desde, $hasta]);
            })
            ->get();

        $eventos = collect();

        foreach ($ordenes as $o) {
            $equipo = $o->items->first()?->nombre.($o->items->count() > 1 ? ' +'.($o->items->count() - 1).' más' : '');
            $url = route('inventory.salidas.show', $o);

            if ($o->created_at?->between($desde, $hasta)) {
                $eventos->push($this->evento($o->created_at, $o->created_by, 'salidas',
                    'Se generó la orden de salida '.$o->folio, collect([$o->venta?->folio, $equipo])->filter()->implode(' · '), $url));
            }

            if ($o->preparada_en?->between($desde, $hasta)) {
                $eventos->push($this->evento($o->preparada_en, $o->preparada_por, 'salidas',
                    'Dejó lista la salida '.$o->folio, collect([$equipo, 'para '.$this->cliente($o->venta?->customer)])->filter()->implode(' · '), $url));
            }

            if ($o->entregada_en?->between($desde, $hasta)) {
                $eventos->push($this->evento($o->entregada_en, $o->entregada_por, 'salidas',
                    'Dio salida a '.$o->folio.' · recibió '.$o->recibe_nombre, collect([$equipo, $o->venta?->folio])->filter()->implode(' · '), $url));
            }
        }

        return $eventos;
    }

    private function seguimientos(Carbon $desde, Carbon $hasta): Collection
    {
        $eventos = collect();

        $lista = ClienteSeguimiento::with('customer')
            ->where(function ($q) use ($desde, $hasta) {
                $q->whereBetween('created_at', [$desde, $hasta])->orWhereBetween('hecho_en', [$desde, $hasta]);
            })
            ->get()
            ->filter(fn ($s) => $s->customer);

        foreach ($lista as $s) {
            $url = route('commercial.clientes.show', $s->customer).'#seguimientos';
            $cliente = $this->cliente($s->customer);

            if ($s->created_at?->between($desde, $hasta)) {
                $eventos->push($this->evento($s->created_at, $s->created_by ?? $s->user_id, 'seguimientos',
                    'Programó: '.mb_strtolower($s->tipoLabel()).' a '.$cliente.' el '.$s->fecha?->format('d/m/Y'), $s->nota, $url));
            }

            if ($s->hecho_en?->between($desde, $hasta)) {
                $eventos->push($this->evento($s->hecho_en, $s->hecho_por ?? $s->user_id, 'seguimientos',
                    'Hizo seguimiento a '.$cliente.': '.mb_strtolower($s->tipoLabel()), $s->resultado ?: $s->nota, $url));
            }
        }

        return $eventos;
    }

    private function comisiones(Carbon $desde, Carbon $hasta): Collection
    {
        return ComisionPago::whereBetween('created_at', [$desde, $hasta])->get()->map(fn (ComisionPago $p) => $this->evento(
            $p->created_at, $p->registrado_por, 'comisiones',
            'Pagó $'.number_format((float) $p->monto, 2).' de comisión a '.($this->nombres[$p->user_id] ?? 'asesor'),
            collect(['Periodo '.$p->periodo, $p->nota])->filter()->implode(' · '),
            route('commercial.comisiones.index', ['periodo' => $p->periodo])
        ));
    }

    private function tareas(Carbon $desde, Carbon $hasta): Collection
    {
        return Task::whereBetween('created_at', [$desde, $hasta])->get()->map(fn (Task $t) => $this->evento(
            $t->created_at, $t->created_by ?? $t->user_id, 'tareas',
            'Creó la tarea: '.$t->title,
            $t->user_id && $t->user_id !== $t->created_by ? 'Asignada a '.($this->nombres[$t->user_id] ?? '—') : null,
            null
        ));
    }

    // ===================== Ayudas =====================

    private function evento(?Carbon $cuando, ?int $usuarioId, string $modulo, string $titulo, ?string $detalle, ?string $url): array
    {
        return [
            'cuando' => $cuando ?? now(),
            'usuario_id' => $usuarioId,
            'usuario' => $usuarioId ? ($this->nombres[$usuarioId] ?? 'Usuario eliminado') : 'Sistema',
            'modulo' => $modulo,
            'modulo_label' => self::MODULOS[$modulo] ?? ucfirst($modulo),
            'titulo' => $titulo,
            'detalle' => $detalle ?: null,
            'url' => $url,
        ];
    }

    private function cliente(?Customer $c): string
    {
        return $c ? (trim($c->nombre.' '.$c->apellido) ?: 'cliente') : 'cliente';
    }

    private function tituloBitacora(VentaBitacora $b): string
    {
        $folio = $b->venta?->folio ?? 'venta';

        return match ($b->tipo) {
            'venta_cancelada' => 'Canceló la venta '.$folio,
            'items_editados' => 'Editó la venta '.$folio,
            'plan_rebalanceado' => 'Ajustó el plan de pagos de '.$folio,
            'fechas_recorridas' => 'Recorrió las fechas de pago de '.$folio,
            'parcialidad_agregada' => 'Agregó una parcialidad a '.$folio,
            'parcialidad_editada' => 'Editó una parcialidad de '.$folio,
            'parcialidad_eliminada' => 'Eliminó una parcialidad de '.$folio,
            'excedente_absorbido' => 'Aplicó excedente cobrado en '.$folio,
            default => ucfirst(str_replace('_', ' ', $b->tipo)).' · '.$folio,
        };
    }
}
