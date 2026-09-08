@extends('structure.commercial_management.erp')

@section('title', 'Cotizaciones')
@section('page-title', 'Cotizaciones')

@section('erp_content')
    <div class="content-actions">
        <x-ui.view-switch key="cotizaciones" />
        <a href="{{ route('commercial.cotizaciones.create') }}" class="erp-btn">
            <x-gravityui-plus />
            Nueva cotización
        </a>
    </div>

    <div class="erp-stats">
        <div class="erp-stat"><span class="ic blue"><x-gravityui-file-text width="22" height="22" /></span><div><div class="n">{{ $total }}</div><div class="l">Cotizaciones</div></div></div>
        <div class="erp-stat"><span class="ic amber"><x-gravityui-pencil width="22" height="22" /></span><div><div class="n">{{ $borradores }}</div><div class="l">Borradores</div></div></div>
        <div class="erp-stat"><span class="ic green"><x-gravityui-check width="22" height="22" /></span><div><div class="n">{{ $aceptadas }}</div><div class="l">Aceptadas</div></div></div>
        <div class="erp-stat"><span class="ic slate"><x-gravityui-circle-dollar width="22" height="22" /></span><div><div class="n" style="font-size:19px;">${{ number_format($montoTotal, 2) }}</div><div class="l">Monto total</div></div></div>
    </div>

    <div class="erp-card" data-view-list>
        <div class="erp-table-wrap">
            <table class="erp-table">
                <thead>
                    <tr><th>Folio</th><th>Cliente</th><th>Modalidad</th><th>Total</th><th>Estado</th><th>Fecha</th><th style="text-align:right;">Acciones</th></tr>
                </thead>
                <tbody>
                    @forelse ($cotizaciones as $cot)
                        @php
                            $badge = match($cot->estado) {
                                'aceptada', 'convertida' => 'ok',
                                'enviada' => 'info',
                                'rechazada' => 'danger',
                                default => 'neutral',
                            };
                        @endphp
                        <tr>
                            <td class="erp-strong">{{ $cot->folio }}</td>
                            <td>{{ $cot->customer?->nombre }} {{ $cot->customer?->apellido }}</td>
                            <td style="text-transform:capitalize;">{{ $cot->modalidad }}@if($cot->modalidad === 'financiamiento') · {{ $cot->num_meses }}m @endif</td>
                            <td class="erp-strong">${{ number_format($cot->total, 2) }}</td>
                            <td><span class="erp-badge {{ $badge }}"><span class="dot"></span>{{ $cot->estadoLabel() }}</span></td>
                            <td style="color:var(--muted);">{{ $cot->created_at?->format('d/m/Y') }}</td>
                            <td style="text-align:right;">
                                <x-erp.menu style="text-align:left;">
                                    <a class="erp-menu-item" href="{{ route('commercial.cotizaciones.show', $cot) }}">
                                        <x-gravityui-eye />Ver detalle
                                    </a>
                                    <a class="erp-menu-item" href="{{ route('commercial.cotizaciones.edit', $cot) }}">
                                        <x-gravityui-pencil />Editar
                                    </a>
                                    <a class="erp-menu-item" href="{{ route('commercial.cotizaciones.pdf', $cot) }}" target="_blank">
                                        <x-gravityui-file-text />Descargar PDF
                                    </a>
                                    <a class="erp-menu-item" href="{{ route('commercial.ventas.create', ['cotizacion' => $cot->id]) }}">
                                        <x-gravityui-shopping-cart />Convertir a venta
                                    </a>
                                    <div class="erp-menu-sep"></div>
                                    <form method="POST" action="{{ route('commercial.cotizaciones.destroy', $cot) }}" onsubmit="return confirm('¿Eliminar la cotización {{ $cot->folio }}?');">
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
                                        <x-gravityui-file-text />
                                    </span>
                                    <h3>Aún no hay cotizaciones</h3>
                                    <p>Crea la primera y aparecerá en esta lista.</p>
                                    <a href="{{ route('commercial.cotizaciones.create') }}" class="erp-btn">Nueva cotización</a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @include('partials._paginacion', ['paginator' => $cotizaciones])
    </div>

    {{-- ===================== Vista tarjetas ===================== --}}
    <div class="data-cards" data-view-cards style="display:none;">
        @forelse ($cotizaciones as $cot)
            @php
                $badge = match($cot->estado) {
                    'aceptada', 'convertida' => 'ok',
                    'enviada' => 'info',
                    'rechazada' => 'danger',
                    default => 'neutral',
                };
            @endphp
            <article class="data-card">
                <div class="data-card-top">
                    <div style="min-width:0;">
                        <div class="t">{{ $cot->folio }}</div>
                        <div class="s">{{ $cot->customer?->nombre }} {{ $cot->customer?->apellido }}</div>
                    </div>
                    <span class="right erp-badge {{ $badge }}"><span class="dot"></span>{{ $cot->estadoLabel() }}</span>
                </div>

                <dl>
                    <div>
                        <dt>Total</dt>
                        <dd>${{ number_format($cot->total, 2) }}</dd>
                    </div>
                    <div>
                        <dt>Modalidad</dt>
                        <dd style="text-transform:capitalize;">{{ $cot->modalidad }}@if($cot->modalidad === 'financiamiento') · {{ $cot->num_meses }}m @endif</dd>
                    </div>
                    <div>
                        <dt>Fecha</dt>
                        <dd>{{ $cot->created_at?->format('d/m/Y') }}</dd>
                    </div>
                </dl>

                <div class="data-card-foot">
                    <a href="{{ route('commercial.cotizaciones.show', $cot) }}" class="tbl-link">Ver</a>
                    <a href="{{ route('commercial.cotizaciones.edit', $cot) }}" class="tbl-link">Editar</a>
                    <a href="{{ route('commercial.cotizaciones.pdf', $cot) }}" class="tbl-link" target="_blank">PDF</a>
                </div>
            </article>
        @empty
            <div class="erp-card">
                <div class="empty-state">
                    <span class="ico">
                        <x-gravityui-file-text />
                    </span>
                    <h3>Aún no hay cotizaciones</h3>
                    <p>Crea la primera y aparecerá aquí.</p>
                    <a href="{{ route('commercial.cotizaciones.create') }}" class="erp-btn">Nueva cotización</a>
                </div>
            </div>
        @endforelse
    </div>
    @include('partials._paginacion', ['paginator' => $cotizaciones])
@endsection
