@extends('structure.commercial_management.erp')

@section('title', 'Facturación')
@section('page-title', 'Facturación')

@section('erp_content')
    <div class="content-actions">
        <x-ui.view-switch key="facturas" />
        <a href="{{ route('commercial.facturas.create') }}" class="erp-btn">
            <x-gravityui-plus />
            Nuevo borrador
        </a>
    </div>

    <div class="erp-card" data-view-list>
        <div class="erp-table-wrap">
            <table class="erp-table">
                <thead>
                    <tr><th>Folio</th><th>Cliente</th><th>Venta</th><th>Total</th><th>Estado</th><th>Fecha</th><th style="text-align:right;">Acciones</th></tr>
                </thead>
                <tbody>
                    @forelse ($facturas as $f)
                        @php $badge = match($f->estado) { 'emitida' => 'ok', 'cancelada' => 'danger', default => 'warn' }; @endphp
                        <tr>
                            <td class="erp-strong">{{ $f->folio }}</td>
                            <td>{{ $f->customer?->nombre }} {{ $f->customer?->apellido }}</td>
                            <td style="color:var(--muted);">{{ $f->venta?->folio ?? '—' }}</td>
                            <td class="erp-strong">${{ number_format($f->total, 2) }}</td>
                            <td><span class="erp-badge {{ $badge }}"><span class="dot"></span>{{ $f->estadoLabel() }}</span></td>
                            <td style="color:var(--muted);">{{ $f->created_at?->format('d/m/Y') }}</td>
                            <td style="text-align:right;">
                                <x-erp.menu>
                                    <a class="erp-menu-item" href="{{ route('commercial.facturas.show', $f) }}">
                                        <x-gravityui-eye />Ver detalle
                                    </a>
                                    <a class="erp-menu-item" href="{{ route('commercial.facturas.pdf', $f) }}" target="_blank">
                                        <x-gravityui-file-text />Descargar PDF
                                    </a>
                                    <div class="erp-menu-sep"></div>
                                    <form method="POST" action="{{ route('commercial.facturas.destroy', $f) }}" onsubmit="return confirm('¿Eliminar el borrador {{ $f->folio }}?');">
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
                                    <h3>Aún no hay borradores de factura</h3>
                                    <p>Crea el primero y aparecerá en esta lista.</p>
                                    <a href="{{ route('commercial.facturas.create') }}" class="erp-btn">Nuevo borrador</a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- ===================== Vista tarjetas ===================== --}}
    <div class="data-cards" data-view-cards style="display:none;">
        @forelse ($facturas as $f)
            @php $badge = match($f->estado) { 'emitida' => 'ok', 'cancelada' => 'danger', default => 'warn' }; @endphp
            <article class="data-card">
                <div class="data-card-top">
                    <div style="min-width:0;">
                        <div class="t">{{ $f->folio }}</div>
                        <div class="s">{{ $f->customer?->nombre }} {{ $f->customer?->apellido }}</div>
                    </div>
                    <span class="right erp-badge {{ $badge }}"><span class="dot"></span>{{ $f->estadoLabel() }}</span>
                </div>

                <dl>
                    <div>
                        <dt>Total</dt>
                        <dd>${{ number_format($f->total, 2) }}</dd>
                    </div>
                    <div>
                        <dt>Venta</dt>
                        <dd>{{ $f->venta?->folio ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt>Fecha</dt>
                        <dd>{{ $f->created_at?->format('d/m/Y') }}</dd>
                    </div>
                </dl>

                <div class="data-card-foot">
                    <a href="{{ route('commercial.facturas.show', $f) }}" class="tbl-link">Ver</a>
                    <a href="{{ route('commercial.facturas.pdf', $f) }}" class="tbl-link" target="_blank">PDF</a>
                </div>
            </article>
        @empty
            <div class="erp-card">
                <div class="empty-state">
                    <span class="ico">
                        <x-gravityui-file-text />
                    </span>
                    <h3>Aún no hay borradores de factura</h3>
                    <p>Crea el primero y aparecerá aquí.</p>
                    <a href="{{ route('commercial.facturas.create') }}" class="erp-btn">Nuevo borrador</a>
                </div>
            </div>
        @endforelse
    </div>
@endsection
