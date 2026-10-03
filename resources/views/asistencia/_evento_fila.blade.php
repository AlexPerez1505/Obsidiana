@php
    $icono = ['descanso' => 'sun', 'sin_checar' => 'utensils', 'horario_especial' => 'clock'][$e->type] ?? 'calendar';
    $quien = $e->scope === 'todos'
        ? 'Todo el equipo'
        : collect($e->user_ids ?? [])->map(fn ($id) => $empleados->firstWhere('id', $id)?->name)->filter()->implode(', ');
@endphp
<div class="evf">
    <span class="rh-ico-box mute"><x-ui.icon :name="$icono" :size="18" /></span>
    <div class="evf-c">
        <div><b>{{ $e->name }}</b> <span class="rh-chip plain">{{ $e->tipoLabel() }}</span></div>
        <div class="evf-s">
            {{ $e->date_from->translatedFormat('D d M') }}@if (! $e->date_from->isSameDay($e->date_to)) al {{ $e->date_to->translatedFormat('D d M Y') }}@else {{ $e->date_from->format('Y') }}@endif
            @if ($e->type === 'horario_especial') · {{ substr($e->time_in, 0, 5) }}–{{ substr($e->time_out, 0, 5) }}@endif
            · {{ $quien ?: 'Sin personas' }}
        </div>
        @if ($e->note)<div class="evf-s">{{ $e->note }}</div>@endif
    </div>
    <form method="POST" action="{{ route('asistencia.eventos.destroy', $e) }}" data-confirm="Los días ya pasados vuelven a evaluarse." data-confirm-title="Eliminar evento">@csrf @method('DELETE')
        <button class="btn btn--ghost" type="submit" style="padding:5px 10px;">Eliminar</button>
    </form>
</div>
@once
<style>
    .evf { display:flex; gap:12px; align-items:center; padding:13px 18px; border-top:1px solid var(--border); transition:background .15s; }
    .evf:first-child { border-top:0; } .evf:hover { background:var(--surface-2); } .evf form { margin:0; }
    .evf-c { flex:1; min-width:0; } .evf-s { font-size:13px; color:var(--muted); margin-top:3px; }
    @media (max-width: 560px) { .evf { flex-wrap:wrap; } .evf form { width:100%; } .evf form .btn { width:100%; } }
</style>
@endonce
