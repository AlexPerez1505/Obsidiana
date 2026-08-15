@extends('layouts.dashboard')
@section('title', 'Paquetes')
@section('page-title', 'Paquetes')
@section('page-sub', 'Agrupaciones de productos listas para usar')

@push('head')
<style>
    .packages-page { display: grid; gap: 22px; }
    .packages-head {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 16px;
        flex-wrap: wrap;
    }
    .packages-head h2 { margin: 0 0 4px; font-size: 1.35rem; font-weight: 900; color: var(--text); }
    .packages-head p { margin: 0; color: var(--muted); font-size: 0.92rem; }
    .packages-create {
        padding: 10px 16px;
        border-radius: 10px;
        background: var(--primary);
        color: #fff;
        text-decoration: none;
        font-size: 0.88rem;
        font-weight: 800;
        display: inline-flex;
        align-items: center;
        gap: 7px;
        transition: filter .15s;
    }
    .packages-create:hover { filter: brightness(.95); }
    .packages-create svg { width: 16px; height: 16px; }

    .packages-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
        gap: 18px;
    }
    .package-card {
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: 16px;
        padding: 20px;
        box-shadow: var(--shadow);
        display: flex;
        flex-direction: column;
        gap: 14px;
        transition: transform .12s, box-shadow .12s;
    }
    .package-card:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(0,0,0,.08); }

    .package-header { display: flex; align-items: flex-start; justify-content: space-between; gap: 10px; }
    .package-title { margin: 0; font-size: 1.05rem; font-weight: 900; color: var(--text); }
    .package-count {
        padding: 4px 10px;
        border-radius: 999px;
        background: rgba(21, 139, 232, .12);
        color: #158be8;
        font-size: 0.75rem;
        font-weight: 900;
        white-space: nowrap;
    }

    .package-products {
        list-style: none;
        margin: 0;
        padding: 0;
        display: flex;
        flex-direction: column;
        gap: 8px;
    }
    .package-product {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        padding: 8px 10px;
        border-radius: 8px;
        background: var(--surface-2);
        font-size: 0.82rem;
        color: var(--text);
    }
    .package-product span:first-child { font-weight: 700; }
    .package-product span:last-child { color: var(--muted); font-weight: 800; }

    .package-footer {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        padding-top: 12px;
        border-top: 1px solid var(--border);
    }
    .package-total { font-size: 0.9rem; color: var(--muted); }
    .package-total strong { color: var(--text); font-size: 1.05rem; }
    .package-actions { display: flex; gap: 8px; }
    .package-actions a, .package-actions button {
        padding: 7px 12px;
        border-radius: 8px;
        font-size: 0.82rem;
        font-weight: 800;
        text-decoration: none;
        cursor: pointer;
        border: 1px solid transparent;
        display: inline-flex;
        align-items: center;
        gap: 5px;
    }
    .package-actions a { background: var(--surface-2); color: var(--text); border-color: var(--border); }
    .package-actions a:hover { background: var(--border); }
    .package-actions button { background: #fee2e2; color: #991b1b; border-color: #fecaca; }
    .package-actions button:hover { background: #fecaca; }

    .packages-empty {
        grid-column: 1 / -1;
        text-align: center;
        padding: 56px 24px;
        color: var(--muted);
    }
    .packages-empty svg {
        width: 64px;
        height: 64px;
        margin-bottom: 14px;
        color: var(--border);
    }
    .packages-empty h3 { margin: 0 0 6px; color: var(--text); font-size: 1.1rem; }
    .packages-empty p { margin: 0 0 18px; }
</style>
@endpush

@section('content')
    <div class="packages-page">
        <div class="packages-head">
            <div>
                <h2>Paquetes</h2>
                <p>Agrupaciones de productos que se usan juntos frecuentemente.</p>
            </div>
            <a href="{{ route('inventory.paquetes.create') }}" class="packages-create">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 5v14M5 12h14"/></svg>
                Agregar paquete
            </a>
        </div>

        <div class="packages-grid">
            @forelse($paquetes as $paquete)
                @php
                    $totalProductos = $paquete->productos->sum('pivot.cantidad');
                    $totalPrecio = $paquete->productos->sum(fn ($p) => $p->precio * $p->pivot->cantidad);
                @endphp
                <div class="package-card">
                    <div class="package-header">
                        <h3 class="package-title">{{ $paquete->nombre }}</h3>
                        <span class="package-count">{{ $totalProductos }} producto{{ $totalProductos === 1 ? '' : 's' }}</span>
                    </div>

                    <ul class="package-products">
                        @foreach($paquete->productos as $producto)
                            <li class="package-product">
                                <span>{{ $producto->tipo_equipo }} {{ $producto->marca }} {{ $producto->modelo }}</span>
                                <span>x{{ $producto->pivot->cantidad }}</span>
                            </li>
                        @endforeach
                    </ul>

                    <div class="package-footer">
                        <span class="package-total">Total: <strong>${{ number_format($totalPrecio, 2) }}</strong></span>
                        <div class="package-actions">
                            <a href="{{ route('inventory.paquetes.edit', $paquete) }}">Editar</a>
                            <form method="POST" action="{{ route('inventory.paquetes.destroy', $paquete) }}" onsubmit="return confirm('¿Eliminar este paquete?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit">Eliminar</button>
                            </form>
                        </div>
                    </div>
                </div>
            @empty
                <div class="packages-empty">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path></svg>
                    <h3>Sin paquetes registrados</h3>
                    <p>Crea tu primer paquete para agrupar productos que se usan juntos.</p>
                    <a href="{{ route('inventory.paquetes.create') }}" class="packages-create">Agregar paquete</a>
                </div>
            @endforelse
        </div>
    </div>
@endsection
