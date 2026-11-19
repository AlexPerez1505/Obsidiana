@extends('structure.commercial_management.erp')

@section('title', 'WhatsApp · Promociones')
@section('page-title', 'Marketing y promociones')

@section('erp_content')
@include('structure.promociones._estilos')
<div class="mk mkt" data-no-mayus>
    @include('structure.promociones._shell', ['tab' => 'whatsapp'])

    <div class="mkt-grid">
        <div class="mkt-col">
            <section class="mkt-card">
                <div class="mkt-card-h"><h3>Estado de la integración</h3>
                    <span class="erp-badge {{ $estado->listoParaEnviar() ? 'ok' : 'warn' }}"><span class="dot"></span>{{ $estado->listoParaEnviar() ? 'Lista para enviar' : 'Modo de pruebas' }}</span>
                </div>
                <div class="mkt-card-b">
                    <p class="ayuda" style="margin-bottom:10px;">Se configura en el archivo <span class="mkt-codigo">.env</span> del servidor (nunca en la base). Después de cambiarlo en producción: <span class="mkt-codigo">php artisan config:cache</span>.</p>
                    <ul class="mkt-list">
                        @foreach ($estado->revision() as $r)
                            <li>
                                <span><span class="mkt-codigo">{{ $r['clave'] }}</span><div class="ayuda" style="margin-top:4px;">{{ $r['texto'] }}</div></span>
                                <span class="v"><span class="erp-badge {{ $r['ok'] ? 'ok' : 'neutral' }}"><span class="dot"></span>{{ $r['ok'] ? 'Listo' : 'Falta' }}</span></span>
                            </li>
                        @endforeach
                        <li><span><span class="mkt-codigo">PROMO_SOLO_NUMEROS_PRUEBA</span><div class="ayuda" style="margin-top:4px;">Mientras esté encendido, las campañas solo llegan de verdad a los números de prueba; al resto se les marca “simulado”.</div></span>
                            <span class="v"><span class="erp-badge {{ config('promociones.solo_numeros_prueba') ? 'warn' : 'ok' }}"><span class="dot"></span>{{ config('promociones.solo_numeros_prueba') ? 'Encendido' : 'Apagado' }}</span></span></li>
                    </ul>
                </div>
            </section>

            <section class="mkt-card">
                <div class="mkt-card-h"><h3>Plantilla que hay que aprobar en Meta</h3></div>
                <div class="mkt-card-b" style="font-size:13.5px; line-height:1.55;">
                    <p style="margin-top:0;">En <b>WhatsApp Manager → Plantillas de mensajes</b> crea una plantilla de categoría <b>Marketing</b>, idioma <b>Español (MEX)</b>, llamada <span class="mkt-codigo">{{ config('promociones.whatsapp.plantilla') }}</span>:</p>
                    <ul class="mkt-list">
                        <li><span class="k">Encabezado</span><span class="v">{{ config('promociones.whatsapp.plantilla_con_imagen') ? 'Imagen' : 'Ninguno' }}</span></li>
                        <li><span class="k">Cuerpo</span><span class="v" style="font-weight:400; max-width:420px;">{{ config('promociones.whatsapp.plantilla_texto') }}</span></li>
                        <li><span class="k">Variables</span><span class="v" style="font-weight:400;">@{{1}} nombre · @{{2}} oferta · @{{3}} código · @{{4}} vencimiento</span></li>
                        <li><span class="k">Botón 1 (Visitar sitio web, URL dinámica)</span><span class="v" style="font-weight:400;">“{{ config('promociones.whatsapp.plantilla_boton_texto') }}” → <span class="mkt-codigo">{{ $ofertaUrl }}</span></span></li>
                        @if (config('promociones.whatsapp.plantilla_boton_baja'))
                            <li><span class="k">Botón 2 (Respuesta rápida)</span><span class="v" style="font-weight:400;">“Ya no quiero promociones”</span></li>
                        @endif
                    </ul>
                    <p class="ayuda" style="margin-top:10px;">Si cambias el texto aprobado, copia el texto nuevo en <span class="mkt-codigo">WHATSAPP_PLANTILLA_TEXTO</span> para que la vista previa coincida.</p>
                </div>
            </section>

            <section class="mkt-card">
                <div class="mkt-card-h"><h3>Webhook (entregas, lecturas y bajas)</h3></div>
                <div class="mkt-card-b" style="font-size:13.5px;">
                    <ul class="mkt-list">
                        <li><span class="k">URL de devolución de llamada</span><span class="v"><span class="mkt-codigo">{{ $webhookUrl }}</span></span></li>
                        <li><span class="k">Token de verificación</span><span class="v">el valor de <span class="mkt-codigo">WHATSAPP_WEBHOOK_VERIFY_TOKEN</span></span></li>
                        <li><span class="k">Campo a suscribir</span><span class="v"><span class="mkt-codigo">messages</span></span></li>
                        <li><span class="k">Último aviso recibido</span><span class="v">{{ $ultimoAviso ? \Illuminate\Support\Carbon::parse($ultimoAviso)->format('d/m/Y H:i') : 'Ninguno todavía' }}</span></li>
                    </ul>
                    <p class="ayuda" style="margin-top:10px;">Cada aviso se valida con la firma <span class="mkt-codigo">X-Hub-Signature-256</span> y el secreto de la app; los repetidos se ignoran. Sin webhook, “entregado” y “leído” quedan en cero.</p>
                </div>
            </section>
        </div>

        <div class="mkt-col">
            <section class="mkt-card">
                <div class="mkt-card-h"><h3>Plantillas en la cuenta</h3></div>
                <div class="mkt-card-b">
                    @if (! $estado->real())
                        <p class="ayuda">Disponible cuando <span class="mkt-codigo">WHATSAPP_DRIVER=meta</span>.</p>
                    @elseif ($plantillas === null)
                        <p class="ayuda">No se pudieron consultar (revisa el token y WHATSAPP_BUSINESS_ACCOUNT_ID).</p>
                    @elseif (empty($plantillas))
                        <p class="ayuda">La cuenta no tiene plantillas.</p>
                    @else
                        <ul class="mkt-list">
                            @foreach ($plantillas as $p)
                                <li><span>{{ $p['name'] }} <span class="mkt-muted">· {{ $p['language'] ?? '' }} · {{ strtolower($p['category'] ?? '') }}</span></span>
                                    <span class="v"><span class="erp-badge {{ ($p['status'] ?? '') === 'APPROVED' ? 'ok' : 'warn' }}"><span class="dot"></span>{{ $p['status'] ?? '' }}</span></span></li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </section>
            <section class="mkt-card">
                <div class="mkt-card-h"><h3>Reglas de envío</h3></div>
                <div class="mkt-card-b">
                    <ul class="mkt-list">
                        <li><span class="k">Frecuencia</span><span class="v">1 promoción cada {{ config('promociones.frecuencia_horas') }} h por cliente</span></li>
                        <li><span class="k">Ritmo de la cola</span><span class="v">{{ config('promociones.mensajes_por_minuto') }} mensajes/min</span></li>
                        <li><span class="k">Reserva de cupón</span><span class="v">{{ config('promociones.reserva_horas') }} h en cotización</span></li>
                        <li><span class="k">Números de prueba</span><span class="v">{{ count(config('promociones.numeros_prueba')) }}</span></li>
                        <li><span class="k">Versión de la API</span><span class="v">{{ config('promociones.whatsapp.api_version') }}</span></li>
                    </ul>
                    <p class="ayuda" style="margin-top:10px;">La cola necesita un proceso corriendo (<span class="mkt-codigo">php artisan queue:work</span>) y el programador (<span class="mkt-codigo">php artisan schedule:run</span> cada minuto) para las campañas programadas.</p>
                </div>
            </section>
        </div>
    </div>
</div>
@endsection
