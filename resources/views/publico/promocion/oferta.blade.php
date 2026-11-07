@extends('publico.promocion._base')

@section('titulo', $campana->titulo_oferta ?: 'Promoción')

@section('contenido')
    @if (session('pedido_ok'))
        <div class="aviso ok" role="status">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><polyline points="20 6 9 17 4 12"/></svg>
            <div><b>¡Listo! Recibimos tu pedido {{ session('pedido_ok') }}.</b><br>
                Un asesor te contactará para confirmar disponibilidad, envío y forma de pago. Tu descuento queda apartado
                {{ config('promociones.reserva_horas') >= 48 ? 'por '.intdiv(config('promociones.reserva_horas'), 24).' días' : 'por '.config('promociones.reserva_horas').' horas' }}.</div>
        </div>
    @elseif ($pedido)
        <div class="aviso info">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
            <div>Ya registraste el pedido <b>{{ $pedido->folio }}</b> por ${{ number_format((float) $pedido->total, 2) }}. Si haces otro, tu asesor recibirá los dos.</div>
        </div>
    @endif

    @if ($d->es_prueba)
        <div class="aviso warn">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            <div>Enlace de <b>prueba</b>{{ $d->cliente ? '' : ' sin cliente: puedes ver la oferta pero no confirmar un pedido' }}. No cuenta en los resultados de la campaña.</div>
        </div>
    @endif

    @if (! $vigente)
        <div class="aviso err">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
            <div>Esta oferta ya no está disponible{{ $campana->vigencia_fin && $campana->vigencia_fin->isPast() ? ': terminó el '.$campana->vigencia_fin->format('d/m/Y') : '' }}. Escríbenos y con gusto te ayudamos.</div>
        </div>
    @endif

    <div class="card hero">
        @if ($campana->imagenUrl())<img src="{{ $campana->imagenUrl() }}" alt="">@endif
        <div class="pad">
            @if ($nombre)<div class="muted small" style="margin-bottom:4px;">Hola, {{ mb_convert_case(mb_strtolower($nombre), MB_CASE_TITLE) }}</div>@endif
            <h1>{{ $campana->titulo_oferta ?: $campana->nombre }}</h1>
            <p style="margin:0;">{{ $campana->mensaje }}</p>
            @if ($campana->vigencia_fin)
                <p class="muted small" style="margin:10px 0 0;">Oferta válida hasta el {{ $campana->vigencia_fin->format('d/m/Y') }} a las {{ $campana->vigencia_fin->format('H:i') }} h.</p>
            @endif
        </div>
    </div>

    @if ($cupon)
        <div class="card pad">
            <h2><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.6 13.4l-7.2 7.2a2 2 0 0 1-2.8 0L2 12V2h10l8.6 8.6a2 2 0 0 1 0 2.8z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>Tu cupón</h2>
            <span class="codigo">{{ $cupon->codigo }}</span>
            <span class="muted small" style="margin-left:8px;">ya viene aplicado</span>
            <ul class="cond">@foreach ($cupon->condicionesLegibles() as $c)<li>{{ $c }}</li>@endforeach</ul>
        </div>
    @endif

    <form method="POST" action="{{ route('promo.confirmar', $d->token) }}" id="pedido">
        @csrf
        <div class="card pad">
            <h2><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.7 13.4a2 2 0 0 0 2 1.6h9.7a2 2 0 0 0 2-1.6L23 6H6"/></svg>Arma tu pedido</h2>
            @foreach ($productos as $p)
                <div class="prod">
                    @if ($p['imagen'])<img src="{{ $p['imagen'] }}" alt="">@else<span class="ph"></span>@endif
                    <div class="t">
                        <b>{{ $p['nombre'] }}</b>
                        <span class="muted small">{{ $p['modelo'] !== 'PAQUETE' ? $p['modelo'] : 'Paquete' }}{{ $p['marca'] ? ' · '.$p['marca'] : '' }}</span>
                        <div class="precio">{{ $p['precio'] > 0 ? '$'.number_format($p['precio'], 2) : 'Precio a cotizar' }}</div>
                    </div>
                    @if ($p['precio'] > 0 && $vigente)
                        <div class="paso" data-clave="{{ $p['clave'] }}">
                            <button type="button" data-d="-1" aria-label="Menos">−</button>
                            <input type="text" inputmode="numeric" name="cantidades[{{ $p['clave'] }}]" value="{{ old('cantidades.'.$p['clave'], count($productos) === 1 ? 1 : 0) }}" aria-label="Cantidad de {{ $p['nombre'] }}">
                            <button type="button" data-d="1" aria-label="Más">+</button>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>

        <div class="card pad" id="resumen">
            <div class="tot"><span>Subtotal</span><b id="tSub">$0.00</b></div>
            @if ($cupon)<div class="tot desc"><span>Descuento <span class="small">({{ $cupon->codigo }})</span></span><b id="tDesc">−$0.00</b></div>@endif
            @if (config('promociones.oferta.aplica_iva'))<div class="tot"><span>IVA</span><b id="tIva">$0.00</b></div>@endif
            <div class="tot total"><span>Total</span><b id="tTot">$0.00</b></div>
            <p class="muted small" style="margin:6px 0 0;">{{ config('promociones.oferta.aplica_iva') ? 'Incluye IVA.' : 'Precios antes de IVA.' }} El envío lo confirma tu asesor.</p>
            <div id="cuponMsg" class="aviso warn" style="display:none; margin:12px 0 0;"></div>
        </div>

        @if ($vigente && $d->cliente)
            <div class="card pad">
                @if ($errors->any())
                    <div class="aviso err"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg><div>{{ $errors->first() }}</div></div>
                @endif
                <label class="lbl" for="ultimos4">Para confirmar, escribe los últimos 4 dígitos de tu teléfono</label>
                <input class="in" id="ultimos4" name="ultimos4" inputmode="numeric" maxlength="4" autocomplete="off" required placeholder="••••" value="">
                <p class="muted small" style="margin:6px 0 0;">Así nos aseguramos de que el pedido y el cupón son tuyos.</p>
                <label class="check" id="sinCuponBox" style="display:none;"><input type="checkbox" name="sin_cupon" value="1" id="sinCupon"> Confirmar sin el cupón</label>
                <button class="btn" type="submit" id="btnConfirmar" style="margin-top:14px;" disabled>Confirmar pedido</button>
                <p class="muted small" style="margin:10px 0 0; text-align:center;">No se te cobra nada ahora. Un asesor te contactará para cerrar la compra.</p>
            </div>
        @endif
    </form>

    @if ($d->cliente && ! $d->es_prueba)
        <div class="pie"><a href="{{ route('promo.baja', $d->token) }}">Ya no quiero recibir promociones</a></div>
    @endif

<script>
(function () {
    const CSRF = document.querySelector('meta[name="csrf-token"]').content;
    const URL_CALC = @json(route('promo.calcular', $d->token));
    const money = n => '$' + (Number(n) || 0).toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    const $ = id => document.getElementById(id);
    let datos = null, t;

    function cantidades() {
        const c = {};
        document.querySelectorAll('.paso').forEach(p => { c[p.dataset.clave] = Math.max(0, Math.min(99, parseInt(p.querySelector('input').value, 10) || 0)); });
        return c;
    }

    async function calcular() {
        const r = await fetch(URL_CALC, { method: 'POST', headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': CSRF },
                                          body: JSON.stringify({ cantidades: cantidades() }) });
        if (!r.ok) return;
        datos = await r.json();
        $('tSub').textContent = money(datos.subtotal);
        if ($('tDesc')) $('tDesc').textContent = '−' + money(datos.descuento);
        if ($('tIva')) $('tIva').textContent = money(datos.iva);
        $('tTot').textContent = money(datos.total);

        const msg = $('cuponMsg');
        const malo = datos.cupon && !datos.cupon.valido && datos.items.length;
        msg.style.display = malo ? 'flex' : 'none';
        msg.textContent = malo ? datos.cupon.mensaje : '';
        if ($('sinCuponBox')) $('sinCuponBox').style.display = malo ? 'flex' : 'none';
        estadoBoton();
    }

    function estadoBoton() {
        const b = $('btnConfirmar');
        if (!b) return;
        const hay = datos && datos.items.length > 0;
        const malo = datos && datos.cupon && !datos.cupon.valido;
        const ok4 = /^\d{4}$/.test($('ultimos4').value);
        b.disabled = !(hay && ok4 && (!malo || $('sinCupon').checked));
        b.textContent = hay ? 'Confirmar pedido por ' + money(datos.total) : 'Elige al menos un producto';
    }

    document.querySelectorAll('.paso').forEach(p => {
        const inp = p.querySelector('input');
        p.querySelectorAll('button').forEach(b => b.addEventListener('click', () => {
            inp.value = Math.max(0, Math.min(99, (parseInt(inp.value, 10) || 0) + parseInt(b.dataset.d, 10)));
            clearTimeout(t); t = setTimeout(calcular, 150);
        }));
        inp.addEventListener('input', () => { inp.value = inp.value.replace(/\D/g, '').slice(0, 2); clearTimeout(t); t = setTimeout(calcular, 300); });
    });
    $('ultimos4')?.addEventListener('input', e => { e.target.value = e.target.value.replace(/\D/g, '').slice(0, 4); estadoBoton(); });
    $('sinCupon')?.addEventListener('change', estadoBoton);
    $('pedido').addEventListener('submit', () => { const b = $('btnConfirmar'); if (b) { b.disabled = true; b.textContent = 'Enviando…'; } });
    calcular();
})();
</script>
@endsection
