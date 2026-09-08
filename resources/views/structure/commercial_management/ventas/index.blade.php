@extends('structure.commercial_management.erp')

@section('title', 'Ventas')
@section('page-title', 'Ventas')

@section('erp_content')
    <div class="content-actions">
        <x-ui.view-switch key="ventas" />
        <a href="{{ route('commercial.ventas.create') }}" class="erp-btn">
            <x-gravityui-plus />
            Nueva venta
        </a>
    </div>

    <div class="erp-stats">
        <div class="erp-stat"><span class="ic blue"><x-gravityui-shopping-cart width="22" height="22" /></span><div><div class="n">{{ $total }}</div><div class="l">Ventas</div></div></div>
        <div class="erp-stat"><span class="ic green"><x-gravityui-check width="22" height="22" /></span><div><div class="n">{{ $confirmadas }}</div><div class="l">Confirmadas</div></div></div>
        <div class="erp-stat"><span class="ic amber"><x-gravityui-file-text width="22" height="22" /></span><div><div class="n">{{ $facturadas }}</div><div class="l">Facturadas</div></div></div>
        <div class="erp-stat"><span class="ic slate"><x-gravityui-circle-dollar width="22" height="22" /></span><div><div class="n" style="font-size:19px;">${{ number_format($montoTotal, 2) }}</div><div class="l">Monto total</div></div></div>
    </div>

    <div class="erp-card" data-view-list>
        <div class="erp-table-wrap">
            <table class="erp-table">
                <thead>
                    <tr><th>Folio</th><th>Cliente</th><th>Modalidad</th><th>Total</th><th>Estado</th><th>Fecha</th><th style="text-align:right;">Acciones</th></tr>
                </thead>
                <tbody>
                    @forelse ($ventas as $v)
                        @php
                            $badge = match($v->estado) {
                                'confirmada' => 'ok', 'facturada' => 'info',
                                'cancelada' => 'danger', default => 'neutral',
                            };
                        @endphp
                        <tr>
                            <td class="erp-strong">{{ $v->folio }}</td>
                            <td>{{ $v->customer?->nombre }} {{ $v->customer?->apellido }}</td>
                            <td style="text-transform:capitalize;">{{ $v->modalidad }}@if($v->modalidad === 'financiamiento') · {{ $v->num_meses }}m @endif</td>
                            <td class="erp-strong">${{ number_format($v->total, 2) }}</td>
                            <td><span class="erp-badge {{ $badge }}"><span class="dot"></span>{{ $v->estadoLabel() }}</span></td>
                            <td style="color:var(--muted);">{{ $v->created_at?->format('d/m/Y') }}</td>
                            <td style="text-align:right;">
                                <x-erp.menu>
                                    <a class="erp-menu-item" href="{{ route('commercial.ventas.show', $v) }}">
                                        <x-gravityui-eye />Ver detalle
                                    </a>
                                    <a class="erp-menu-item" href="{{ route('commercial.ventas.edit', $v) }}">
                                        <x-gravityui-pencil />Editar
                                    </a>
                                    <a class="erp-menu-item" href="{{ route('commercial.ventas.pdf', $v) }}" target="_blank">
                                        <x-gravityui-file-text />Descargar PDF
                                    </a>
                                    <a class="erp-menu-item" href="{{ route('commercial.facturas.create', ['venta' => $v->id]) }}">
                                        <x-gravityui-file-dollar />Generar borrador de factura
                                    </a>
                                    <div class="erp-menu-sep"></div>
                                    <form method="POST" action="{{ route('commercial.ventas.destroy', $v) }}" onsubmit="return confirm('¿Eliminar la venta {{ $v->folio }}?');">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="erp-menu-item danger">
                                            <x-gravityui-trash-bin />Eliminar
                                        </button>
                                    </form>
                                </x-erp.menu>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7">
                                <div class="empty-state">
                                    <span class="ico">
                                        <x-gravityui-shopping-cart />
                                    </span>
                                    <h3>Aún no hay ventas</h3>
                                    <p>Registra la primera y aparecerá en esta lista.</p>
                                    <a href="{{ route('commercial.ventas.create') }}" class="erp-btn">Nueva venta</a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @include('partials._paginacion', ['paginator' => $ventas])
    </div>

    {{-- ===================== Vista tarjetas ===================== --}}
    <div class="data-cards" data-view-cards style="display:none;">
        @forelse ($ventas as $v)
            @php
                $badge = match($v->estado) {
                    'confirmada' => 'ok', 'facturada' => 'info',
                    'cancelada' => 'danger', default => 'neutral',
                };
            @endphp
            <article class="data-card">
                <div class="data-card-top">
                    <div style="min-width:0;">
                        <div class="t">{{ $v->folio }}</div>
                        <div class="s">{{ $v->customer?->nombre }} {{ $v->customer?->apellido }}</div>
                    </div>
                    <span class="right erp-badge {{ $badge }}"><span class="dot"></span>{{ $v->estadoLabel() }}</span>
                </div>

                <dl>
                    <div>
                        <dt>Total</dt>
                        <dd>${{ number_format($v->total, 2) }}</dd>
                    </div>
                    <div>
                        <dt>Modalidad</dt>
                        <dd style="text-transform:capitalize;">{{ $v->modalidad }}@if($v->modalidad === 'financiamiento') · {{ $v->num_meses }}m @endif</dd>
                    </div>
                    <div>
                        <dt>Fecha</dt>
                        <dd>{{ $v->created_at?->format('d/m/Y') }}</dd>
                    </div>
                </dl>

                <div class="data-card-foot">
                    <a href="{{ route('commercial.ventas.show', $v) }}" class="tbl-link">Ver</a>
                    <a href="{{ route('commercial.ventas.edit', $v) }}" class="tbl-link">Editar</a>
                    <a href="{{ route('commercial.ventas.pdf', $v) }}" class="tbl-link" target="_blank">PDF</a>
                </div>
            </article>
        @empty
            <div class="erp-card">
                <div class="empty-state">
                    <span class="ico">
                        <x-gravityui-shopping-cart />
                    </span>
                    <h3>Aún no hay ventas</h3>
                    <p>Registra la primera y aparecerá aquí.</p>
                    <a href="{{ route('commercial.ventas.create') }}" class="erp-btn">Nueva venta</a>
                </div>
            </div>
        @endforelse
    </div>
    @include('partials._paginacion', ['paginator' => $ventas])
@endsection
