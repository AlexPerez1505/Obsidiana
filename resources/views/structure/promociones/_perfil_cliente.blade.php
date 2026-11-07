{{--
    Promociones en el perfil del cliente: consentimiento, promociones
    recibidas y cupones usados. Se incluye desde customers/ver_cliente.
--}}
@php
    $recibidas = $customer->promocionesRecibidas()->with('campana')->latest('id')->limit(8)->get();
    $usosCupon = $customer->usosDeCupon()->with(['cupon', 'venta', 'cotizacion'])->latest('id')->limit(8)->get();
    $ultimoConsent = $customer->consentimientos()->with('usuario')->first();
    $puede = $customer->puedeRecibirPromociones();
@endphp
<x-ui.card id="promociones">
    <div class="vc-head" style="display:flex; align-items:center; justify-content:space-between; gap:8px;">
        <x-ui.section-title style="margin:0;">Promociones</x-ui.section-title>
        <span class="erp-badge {{ $puede ? 'ok' : ($customer->promocion_revocada_en ? 'danger' : 'neutral') }}" style="display:inline-flex; align-items:center; gap:6px; padding:3px 10px; border-radius:999px; font-size:12px; font-weight:600;">
            {{ $puede ? 'Acepta promociones' : ($customer->promocion_revocada_en ? 'Dado de baja' : 'Sin consentimiento') }}
        </span>
    </div>

    @if ($ultimoConsent)
        <p class="vc-text" style="margin:0 0 10px; font-size:12.5px; color:var(--muted);">
            {{ $ultimoConsent->accion === 'otorgado' ? 'Aceptó' : 'Se dio de baja' }} el {{ $ultimoConsent->created_at->format('d/m/Y') }}
            · {{ $ultimoConsent->fuenteLabel() }}{{ $ultimoConsent->nota ? ' · '.$ultimoConsent->nota : '' }}{{ $ultimoConsent->usuario ? ' · '.$ultimoConsent->usuario->name : '' }}
        </p>
    @endif

    @can('promociones.consentimiento')
        @if ($puede)
            <form method="POST" action="{{ route('promociones.consentimiento.revocar', $customer) }}" style="margin:0 0 12px;"
                  data-confirm="El cliente dejará de recibir promociones por WhatsApp. ¿Continuar?" data-confirm-title="Dar de baja de promociones" data-confirm-ok="Dar de baja">
                @csrf
                <button type="submit" class="btn btn--ghost" style="font-size:13px;">Dar de baja de promociones</button>
            </form>
        @else
            <form method="POST" action="{{ route('promociones.consentimiento.otorgar', $customer) }}" style="display:flex; flex-wrap:wrap; gap:8px; align-items:center; margin:0 0 12px;">
                @csrf
                <select name="medio" required style="padding:7px 9px; border:1px solid var(--border); border-radius:8px; background:var(--surface); color:var(--text); font-size:13px;">
                    <option value="">¿Cómo aceptó?</option>
                    <option value="verbal">De palabra</option>
                    <option value="escrito">Por escrito</option>
                    <option value="formulario">En un formulario</option>
                    <option value="whatsapp">Por WhatsApp</option>
                </select>
                <input name="nota" maxlength="200" placeholder="Nota (opcional)" style="flex:1; min-width:140px; padding:7px 9px; border:1px solid var(--border); border-radius:8px; background:var(--surface); color:var(--text); font-size:13px;">
                <button type="submit" class="btn" style="font-size:13px;">Registrar consentimiento</button>
            </form>
        @endif
    @endcan

    <span class="vc-k">Promociones recibidas</span>
    @forelse ($recibidas as $d)
        <div style="display:flex; justify-content:space-between; gap:10px; padding:7px 0; border-bottom:1px solid var(--border); font-size:13px;">
            <span>
                @can('promociones.ver')<a href="{{ route('promociones.campanas.show', $d->campana) }}">{{ $d->campana?->nombre }}</a>@else{{ $d->campana?->nombre }}@endcan
                <span style="color:var(--muted); font-size:12px;"> · {{ $d->estadoLabel() }}{{ $d->clics ? ' · abrió el enlace' : '' }}</span>
            </span>
            <span style="color:var(--muted); font-size:12px; white-space:nowrap;">{{ ($d->enviado_en ?? $d->created_at)->format('d/m/Y') }}</span>
        </div>
    @empty
        <p class="vc-text vc-empty" style="margin:4px 0 10px;">No ha recibido promociones.</p>
    @endforelse

    <span class="vc-k" style="display:block; margin-top:12px;">Cupones</span>
    @forelse ($usosCupon as $u)
        <div style="display:flex; justify-content:space-between; gap:10px; padding:7px 0; border-bottom:1px solid var(--border); font-size:13px;">
            <span>
                <b style="font-family:ui-monospace,monospace; font-size:12.5px;">{{ $u->cupon?->codigo }}</b>
                <span style="color:var(--muted); font-size:12px;"> · {{ $u->estadoLabel() }}
                    @if ($u->venta) · {{ $u->venta->folio }} @elseif ($u->cotizacion) · {{ $u->cotizacion->folio }} @endif
                </span>
            </span>
            <span style="white-space:nowrap;">-${{ number_format((float) $u->descuento, 2) }}</span>
        </div>
    @empty
        <p class="vc-text vc-empty" style="margin:4px 0 0;">No ha usado cupones.</p>
    @endforelse
</x-ui.card>
