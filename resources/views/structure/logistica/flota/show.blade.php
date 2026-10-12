@extends('layouts.dashboard')

@section('title', $vehiculo->plate_number)
@section('page-title', $vehiculo->plate_number)
@section('page-sub', trim($vehiculo->brand.' '.$vehiculo->model.' '.$vehiculo->year))

@section('content')
    @if ($errors->any())
        <x-ui.alert type="err" style="margin-bottom:14px;">@foreach ($errors->all() as $e)<div>{{ $e }}</div>@endforeach</x-ui.alert>
    @endif

    <a href="{{ route('logistica.flota.index') }}" class="fs-volver"><x-ui.icon name="arrow-left" :size="14" /> Flota</a>

    @foreach ($alertas as $a)
        <x-ui.alert type="err" style="margin-bottom:8px;"><x-ui.icon name="alert" :size="15" /> {{ $a }}</x-ui.alert>
    @endforeach

    <div class="fs-resumen">
        <div class="fs-d"><div class="k">Odómetro estimado</div><div class="v">{{ $odometro !== null ? number_format($odometro).' km' : '—' }}</div></div>
        <div class="fs-d"><div class="k">Km en rutas</div><div class="v">{{ number_format($kmTotal, 1) }} km</div></div>
        <div class="fs-d"><div class="k">Rendimiento</div><div class="v">{{ $rendimiento ? $rendimiento.' km/l' : '—' }}</div></div>
        <div class="fs-d"><div class="k">Gasto combustible</div><div class="v">${{ number_format($cargas->sum('monto'), 0) }}</div></div>
    </div>
    <p class="fs-nota">El odómetro se calcula con la última lectura capturada (combustible o servicio) más los km de las rutas posteriores. Captura el odómetro al cargar para mantenerlo exacto; el rendimiento usa cargas de tanque lleno con odómetro.</p>

    <div class="fs-grid">
        {{-- ============ Combustible ============ --}}
        <div class="card" style="padding:0;">
            <div class="fs-cab">Combustible</div>
            <form method="POST" action="{{ route('logistica.flota.combustible.store', $vehiculo) }}" enctype="multipart/form-data" class="fs-form">@csrf
                <div class="fs-fila">
                    <label>Fecha <input type="date" name="fecha" value="{{ old('fecha', today()->toDateString()) }}" max="{{ today()->toDateString() }}" required></label>
                    <label>Litros <input type="number" step="0.01" name="litros" value="{{ old('litros') }}" required></label>
                    <label>Monto $ <input type="number" step="0.01" name="monto" value="{{ old('monto') }}" required></label>
                </div>
                <div class="fs-fila">
                    <label>Odómetro (km) <input type="number" name="odometro_km" value="{{ old('odometro_km') }}"></label>
                    <label>Estación <input type="text" name="estacion" value="{{ old('estacion') }}"></label>
                    <label>Ticket <input type="file" name="ticket" accept="image/*,application/pdf"></label>
                </div>
                <label class="fs-check"><input type="checkbox" name="tanque_lleno" value="1" checked> Tanque lleno</label>
                <button class="btn" type="submit">Registrar carga</button>
            </form>
            <div class="fs-hist">
                @forelse ($cargas as $c)
                    <div class="fs-reg">
                        <div>
                            <b>{{ $c->fecha->format('d/m/Y') }}</b> · {{ rtrim(rtrim(number_format($c->litros, 2), '0'), '.') }} L · ${{ number_format($c->monto, 2) }}
                            <div class="fs-s">{{ $c->odometro_km ? number_format($c->odometro_km).' km · ' : '' }}{{ $c->estacion }} {{ $c->usuario?->name }}@if ($c->ruta_id) · desde ruta @endif @if ($c->ticketUrl())· <a href="{{ $c->ticketUrl() }}" target="_blank">ticket</a>@endif</div>
                        </div>
                        <form method="POST" action="{{ route('logistica.flota.combustible.destroy', $c) }}" data-confirm="Se elimina esta carga." data-confirm-title="Eliminar carga">@csrf @method('DELETE')<button class="fs-x" type="submit" title="Eliminar" aria-label="Eliminar"><x-ui.icon name="x" :size="14" /></button></form>
                    </div>
                @empty
                    <div class="fs-s" style="padding:14px 16px;">Sin cargas registradas.</div>
                @endforelse
            </div>
        </div>

        {{-- ============ Mantenimiento ============ --}}
        <div class="card" style="padding:0;">
            <div class="fs-cab">Mantenimiento y servicios</div>
            <form method="POST" action="{{ route('logistica.flota.mantenimiento.store', $vehiculo) }}" enctype="multipart/form-data" class="fs-form">@csrf
                <div class="fs-fila">
                    <label>Tipo
                        <select name="tipo" required>
                            @foreach (\App\Models\VehiculoMantenimiento::TIPOS as $k => $t)<option value="{{ $k }}" @selected(old('tipo') === $k)>{{ $t }}</option>@endforeach
                        </select>
                    </label>
                    <label>Fecha <input type="date" name="fecha" value="{{ old('fecha', today()->toDateString()) }}" max="{{ today()->toDateString() }}" required></label>
                    <label>Costo $ <input type="number" step="0.01" name="costo" value="{{ old('costo') }}"></label>
                </div>
                <div class="fs-fila">
                    <label>Odómetro (km) <input type="number" name="odometro_km" value="{{ old('odometro_km') }}"></label>
                    <label>Proveedor <input type="text" name="proveedor" value="{{ old('proveedor') }}"></label>
                    <label>Comprobante <input type="file" name="comprobante" accept="image/*,application/pdf"></label>
                </div>
                <div class="fs-fila">
                    <label>Próximo servicio (fecha) <input type="date" name="proximo_fecha" value="{{ old('proximo_fecha') }}"></label>
                    <label>Próximo servicio (km) <input type="number" name="proximo_km" value="{{ old('proximo_km') }}"></label>
                    <span></span>
                </div>
                <label>Descripción <input type="text" name="descripcion" value="{{ old('descripcion') }}" placeholder="Cambio de aceite, frenos…"></label>
                <button class="btn" type="submit">Registrar servicio</button>
            </form>
            <div class="fs-hist">
                @forelse ($mantenimientos as $m)
                    <div class="fs-reg">
                        <div>
                            <b>{{ $m->tipoLabel() }}</b> · {{ $m->fecha->format('d/m/Y') }} · ${{ number_format($m->costo, 2) }}
                            <div class="fs-s">
                                {{ $m->descripcion }} {{ $m->proveedor ? '· '.$m->proveedor : '' }} {{ $m->odometro_km ? '· '.number_format($m->odometro_km).' km' : '' }}
                                @if ($m->proximo_fecha || $m->proximo_km)<br>Próximo: {{ $m->proximo_fecha?->format('d/m/Y') }} {{ $m->proximo_km ? number_format($m->proximo_km).' km' : '' }}@endif
                                @if ($m->comprobanteUrl()) · <a href="{{ $m->comprobanteUrl() }}" target="_blank">comprobante</a>@endif
                            </div>
                        </div>
                        <form method="POST" action="{{ route('logistica.flota.mantenimiento.destroy', $m) }}" data-confirm="Se elimina este registro." data-confirm-title="Eliminar servicio">@csrf @method('DELETE')<button class="fs-x" type="submit" title="Eliminar" aria-label="Eliminar"><x-ui.icon name="x" :size="14" /></button></form>
                    </div>
                @empty
                    <div class="fs-s" style="padding:14px 16px;">Sin servicios registrados.</div>
                @endforelse
            </div>
        </div>
    </div>

    @if ($rutas->count())
        <div class="card" style="padding:0; margin-top:18px;">
            <div class="fs-cab">Últimas rutas</div>
            @foreach ($rutas as $r)
                <div class="fs-reg"><a href="{{ route('logistica.rutas.show', $r) }}"><b>{{ $r->folio }}</b></a><span class="fs-s">{{ $r->iniciada_en?->format('d/m/Y H:i') }} · {{ $r->km_recorridos }} km</span></div>
            @endforeach
        </div>
    @endif

    <style>
        .fs-volver { display:inline-block; margin-bottom:12px; color:var(--primary); font-weight:600; text-decoration:none; font-size:14px; }
        .fs-resumen { display:grid; grid-template-columns:repeat(auto-fit, minmax(170px,1fr)); gap:12px; margin:12px 0 8px; }
        .fs-d { background:var(--surface); border:1px solid var(--border); border-radius:10px; padding:12px 14px; }
        .fs-d .k { font-size:11.5px; color:var(--muted); text-transform:uppercase; letter-spacing:.03em; font-weight:600; }
        .fs-d .v { margin-top:4px; font-size:20px; font-weight:700; }
        .fs-nota { font-size:12px; color:var(--muted); margin:0 0 16px; }
        .fs-grid { display:grid; grid-template-columns:1fr 1fr; gap:18px; align-items:start; }
        .fs-cab { padding:13px 16px; font-weight:700; border-bottom:1px solid var(--border); }
        .fs-form { padding:14px 16px; border-bottom:1px solid var(--border); background:var(--surface-2); }
        .fs-form label { display:block; font-size:12px; font-weight:600; color:var(--muted); margin-top:10px; }
        .fs-form input[type=text], .fs-form input[type=date], .fs-form input[type=number], .fs-form select, .fs-form input[type=file] { width:100%; margin-top:4px; }
        .fs-fila { display:grid; grid-template-columns:repeat(3, 1fr); gap:10px; }
        .fs-fila label { margin-top:10px; }
        .fs-check { display:flex !important; align-items:center; gap:8px; color:var(--text) !important; }
        .fs-check input { width:auto; margin:0; }
        .fs-form .btn { margin-top:12px; }
        .fs-reg { display:flex; justify-content:space-between; gap:10px; padding:11px 16px; border-bottom:1px solid var(--border); font-size:13.5px; }
        .fs-reg:last-child { border-bottom:0; }
        .fs-reg form { margin:0; }
        .fs-s { font-size:12px; color:var(--muted); margin-top:2px; }
        .fs-x { border:0; background:none; color:var(--muted); cursor:pointer; font-size:14px; }
        .fs-x:hover { color:var(--danger); }
        .fs-hist { max-height:420px; overflow:auto; }
        @media (max-width: 960px) { .fs-grid { grid-template-columns:1fr; } .fs-fila { grid-template-columns:1fr; } }
    </style>
@endsection
