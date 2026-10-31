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
    <div class="evf-acc">
        {{-- Abre el formulario de arriba ya lleno con este evento. --}}
        <button type="button" class="btn btn--ghost" style="padding:5px 10px;" data-ev-editar
                data-url="{{ route('asistencia.eventos.update', $e) }}"
                data-evento="{{ json_encode([
                    'name' => $e->name, 'type' => $e->type,
                    'date_from' => $e->date_from->toDateString(), 'date_to' => $e->date_to->toDateString(),
                    'scope' => $e->scope, 'user_ids' => $e->user_ids ?? [],
                    'time_in' => $e->time_in ? substr($e->time_in, 0, 5) : '', 'time_out' => $e->time_out ? substr($e->time_out, 0, 5) : '',
                    'tolerance_minutes' => $e->tolerance_minutes, 'note' => $e->note,
                ]) }}">Editar</button>
        <form method="POST" action="{{ route('asistencia.eventos.destroy', $e) }}" data-confirm="Los días ya pasados vuelven a evaluarse." data-confirm-title="Eliminar evento">@csrf @method('DELETE')
            <button class="btn btn--ghost" type="submit" style="padding:5px 10px;">Eliminar</button>
        </form>
    </div>
</div>
@once
<style>
    .evf { display:flex; gap:12px; align-items:center; padding:13px 18px; border-top:1px solid var(--border); transition:background .15s; }
    .evf:first-child { border-top:0; } .evf:hover { background:var(--surface-2); } .evf form { margin:0; }
    .evf-c { flex:1; min-width:0; } .evf-s { font-size:13px; color:var(--muted); margin-top:3px; }
    .evf-acc { display:flex; gap:8px; align-items:center; }
    @media (max-width: 560px) { .evf { flex-wrap:wrap; } .evf-acc { width:100%; } .evf-acc > *, .evf-acc form .btn { flex:1; width:100%; } }
</style>
@endonce
