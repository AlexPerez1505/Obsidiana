{{--
    Quién prepara el equipo de la orden. Se usa en la orden de salida y en
    la venta. Recibe $orden, $preparadores (colección de usuarios) y
    $puedeAsignar. A quien se elige le llega el aviso por correo y campana.
--}}
@php
    $asignado = $orden->asignadaA;
    $iniciales = $asignado ? collect(preg_split('/\s+/', trim($asignado->name)))->filter()->take(2)->map(fn ($p) => mb_substr($p, 0, 1))->implode('') : '';
@endphp
<div class="os-asig" id="asignar">
    <div class="os-asig-quien">
        @if ($asignado)
            <span class="os-asig-ini" aria-hidden="true">{{ mb_strtoupper($iniciales) }}</span>
            <div>
                <div class="os-asig-et">Prepara</div>
                <div class="os-asig-nom">{{ $asignado->name }}</div>
                @if ($orden->asignada_en)
                    <div class="os-asig-sub">Asignado{{ $orden->asignadaPor ? ' por '.$orden->asignadaPor->name : '' }} el {{ $orden->asignada_en->format('d/m/Y H:i') }}</div>
                @endif
            </div>
        @else
            <span class="os-asig-ini is-vacio" aria-hidden="true">?</span>
            <div>
                <div class="os-asig-et">Prepara</div>
                <div class="os-asig-nom is-vacio">Nadie asignado todavía</div>
            </div>
        @endif
    </div>

    @if ($puedeAsignar && ! $orden->cerrada())
        <form method="POST" action="{{ route('inventory.salidas.asignar', $orden) }}" class="os-asig-form">
            @csrf
            <label class="sr-only" for="asig-{{ $orden->id }}">Quién prepara {{ $orden->folio }}</label>
            <select name="user_id" id="asig-{{ $orden->id }}" data-no-mayus>
                <option value="">— Sin asignar —</option>
                @foreach ($preparadores as $p)
                    <option value="{{ $p->id }}" @selected($orden->asignada_a === $p->id)>{{ $p->name }}</option>
                @endforeach
            </select>
            <button type="submit" class="btn">{{ $asignado ? 'Cambiar' : 'Asignar' }}</button>
        </form>
        <p class="os-asig-nota">Le llega un aviso en el sistema y a su correo.</p>
    @endif
</div>

@once
    <style>
        .os-asig { display:flex; align-items:center; justify-content:space-between; gap:10px 18px; flex-wrap:wrap;
                   margin-top:16px; padding-top:16px; border-top:1px solid var(--border); }
        .os-asig-quien { display:flex; align-items:center; gap:10px; min-width:0; }
        .os-asig-ini { width:36px; height:36px; flex:0 0 36px; border-radius:50%; display:grid; place-items:center;
                       background:var(--accent, #007aff); color:#fff; font-weight:700; font-size:13px; }
        .os-asig-ini.is-vacio { background:var(--surface-2); color:var(--muted); }
        .os-asig-et { color:var(--muted); font-size:12.5px; }
        .os-asig-nom { font-weight:700; overflow-wrap:anywhere; }
        .os-asig-nom.is-vacio { font-weight:600; color:var(--muted); }
        .os-asig-sub { color:var(--muted); font-size:12px; }
        .os-asig-form { display:flex; gap:8px; align-items:center; flex-wrap:wrap; }
        .os-asig-form select { min-width:200px; max-width:100%; }
        .os-asig-nota { flex-basis:100%; margin:0; text-align:right; color:var(--muted); font-size:12px; }
        .sr-only { position:absolute; width:1px; height:1px; overflow:hidden; clip:rect(0 0 0 0); white-space:nowrap; }
        @media (max-width:640px) {
            .os-asig-form, .os-asig-form select { width:100%; }
            .os-asig-form .btn { width:100%; justify-content:center; }
            .os-asig-nota { text-align:left; }
        }
    </style>
@endonce
