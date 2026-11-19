@extends('publico.promocion._base')

@section('titulo', 'Dejar de recibir promociones')

@section('contenido')
    <div class="card pad">
        @if ($hecho)
            <div class="aviso ok" style="margin:0;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><polyline points="20 6 9 17 4 12"/></svg>
                <div><b>Listo.</b> Ya no te enviaremos promociones por WhatsApp. Si cambias de opinión, avísale a tu asesor.</div>
            </div>
        @elseif (! $d->cliente)
            <p style="margin:0;">Este enlace es de prueba y no está ligado a ningún cliente.</p>
        @else
            <h1>¿Dejar de recibir promociones?</h1>
            <p class="muted">Ya no te mandaremos ofertas ni cupones por WhatsApp. Seguirás recibiendo los mensajes de tus pedidos y servicios.</p>
            <form method="POST" action="{{ route('promo.baja.confirmar', $d->token) }}" style="margin-top:16px;">
                @csrf
                <button class="btn" type="submit">Sí, ya no quiero promociones</button>
            </form>
            <a class="btn ghost" style="margin-top:10px;" href="{{ route('promo.oferta', $d->token) }}">Volver a la oferta</a>
        @endif
    </div>
@endsection
