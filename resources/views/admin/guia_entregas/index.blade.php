@extends('layouts.dashboard')
@section('title', 'Entregas de guías')
@section('page-title', 'Entregas de guías')
@section('page-sub', 'Consulta y registra entregas vinculadas con Inventario')

@push('head')
<style>
    .ge-page{max-width:100%;padding-bottom:70px}.ge-head{display:flex;justify-content:space-between;align-items:center;gap:15px;margin-bottom:18px}.ge-title{margin:0;color:var(--text);font-size:25px}.ge-muted{margin:5px 0 0;color:var(--muted)}.ge-btn{display:inline-flex;align-items:center;gap:8px;padding:11px 16px;border-radius:8px;background:var(--primary);color:#fff;text-decoration:none;font-weight:800}.ge-toolbar{display:flex;gap:10px;margin-bottom:16px}.ge-search{flex:1;min-height:44px;padding:0 14px;border:1px solid var(--border);border-radius:8px;background:var(--surface);color:var(--text);font:inherit}.ge-submit{border:0;border-radius:8px;padding:0 18px;background:var(--surface-2);color:var(--text);font:inherit;font-weight:800;cursor:pointer}.ge-card{overflow:auto;background:var(--surface);border:1px solid var(--border);border-radius:10px}.ge-table{width:100%;border-collapse:collapse;min-width:850px}.ge-table th,.ge-table td{padding:14px 15px;border-bottom:1px solid var(--border);text-align:left}.ge-table th{color:var(--muted);font-size:11px;text-transform:uppercase;letter-spacing:.04em}.ge-table td{color:var(--text);font-size:13px}.ge-table tr:last-child td{border-bottom:0}.ge-link{color:var(--primary);font-weight:800;text-decoration:none}.ge-badge{display:inline-flex;padding:4px 9px;border-radius:999px;background:#dcfce7;color:#166534;font-size:11px;font-weight:800}.ge-empty{padding:45px;text-align:center;color:var(--muted)}.ge-pag{padding:14px}.ge-series{display:flex;flex-wrap:wrap;gap:4px}.ge-series span{padding:3px 6px;border-radius:5px;background:var(--surface-2);font-family:ui-monospace,monospace;font-size:11px}
</style>
@endpush

@section('content')
<section class="ge-page">
    <div class="ge-head">
        <div><h1 class="ge-title">Entregas de guías</h1><p class="ge-muted">Consulta y gestiona tus entregas vinculadas con equipos del inventario.</p></div>
        @can('entregas_guias.crear')<a class="ge-btn" href="{{ route('admin.guia_entregas.create') }}">＋ Nueva entrega</a>@endcan
    </div>
    <form class="ge-toolbar" method="GET">
        <input class="ge-search" type="search" name="search" value="{{ $search }}" placeholder="Buscar por rastreo, contenido, serie o destinatario…">
        <button class="ge-submit" type="submit">Buscar</button>
    </form>
    <div class="ge-card">
        @if($guias->isEmpty())
            <div class="ge-empty">No hay entregas registradas con esos criterios.</div>
        @else
            <table class="ge-table"><thead><tr><th>Rastreo</th><th>Contenido</th><th>Estado</th><th>Fecha</th><th>Serie</th><th>Destinatario</th><th>Usuario</th><th></th></tr></thead><tbody>
            @foreach($guias as $guia)
                <tr>
                    <td><a class="ge-link" href="{{ route('admin.guia_entregas.show', $guia) }}">{{ $guia->numero_rastreo }}</a></td>
                    <td>{{ Illuminate\Support\Str::limit($guia->contenido ?: '—', 48) }}</td>
                    <td><span class="ge-badge">{{ ucfirst($guia->estado) }}</span></td>
                    <td>{{ $guia->fecha_entrega?->format('Y-m-d') }}</td>
                    <td><div class="ge-series">@forelse($guia->seriales->take(3) as $serial)<span>{{ $serial->no_serie }}</span>@empty—@endforelse @if($guia->seriales->count()>3)<span>+{{ $guia->seriales->count()-3 }}</span>@endif</div></td>
                    <td>{{ $guia->destinatario ?: '—' }}</td><td>{{ $guia->creador?->name ?: '—' }}</td>
                    <td><a class="ge-link" href="{{ route('admin.guia_entregas.show', $guia) }}">Ver</a></td>
                </tr>
            @endforeach
            </tbody></table>
            <div class="ge-pag">{{ $guias->links() }}</div>
        @endif
    </div>
</section>
@endsection
