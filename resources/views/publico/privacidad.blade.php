{{--
    Aviso de privacidad público (sin sesión). Lo pide Meta para publicar la
    app de WhatsApp (URL de la política de privacidad) y sirve también como
    instrucciones para eliminar datos (#eliminar).

    Los datos de la empresa salen de config/medibuy.php (.env del servidor):
    si cambian, se cambian ahí y esta página se actualiza sola.
--}}
@php
    $e = config('medibuy.empresa');
    $c = config('medibuy.contacto');
    $nombre = $e['nombre'] ?: 'Grupo MediBuy';
    $correo = $c['correo'] ?: (config('medibuy.comprobantes.correo') ?: null);
    $web = $e['web'] ?: 'grupomedibuy.com';
    $actualizado = '21 de noviembre de 2026';
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Aviso de privacidad · {{ $nombre }}</title>
    <meta name="description" content="Aviso de privacidad de {{ $nombre }}: qué datos personales tratamos, para qué, y cómo ejercer tus derechos o pedir que se eliminen.">
    <link rel="icon" type="image/png" href="{{ asset('images/favicon.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@500;600;700&display=swap" rel="stylesheet">
    <style>
        :root { --bg:#f7f8fa; --surface:#fff; --text:#1f2937; --muted:#6b7280; --border:#e7e9ee; --primary:#007aff; --soft:#e6f0ff; }
        @media (prefers-color-scheme: dark) {
            :root { --bg:#070c17; --surface:#0f1a30; --text:#e8eef8; --muted:#93a4bd; --border:rgba(90,140,230,.18); --primary:#0a84ff; --soft:rgba(10,132,255,.16); }
        }
        * { box-sizing: border-box; }
        body { margin: 0; background: var(--bg); color: var(--text); font-family: 'Quicksand', system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif; font-size: 16px; line-height: 1.65; -webkit-font-smoothing: antialiased; }
        .pv { max-width: 780px; margin: 0 auto; padding: 40px 18px 64px; }
        .pv-cab { display: flex; align-items: center; gap: 12px; margin-bottom: 26px; }
        .pv-cab img { width: 40px; height: 40px; border-radius: 10px; }
        .pv-cab b { font-size: 17px; }
        .pv-cab span { display: block; font-size: 13px; color: var(--muted); }
        h1 { font-size: clamp(26px, 5vw, 34px); line-height: 1.2; letter-spacing: -.01em; margin: 0 0 8px; }
        .pv-fecha { color: var(--muted); font-size: 14px; margin: 0 0 26px; }
        .pv-indice { background: var(--surface); border: 1px solid var(--border); border-radius: 16px; padding: 16px 20px; margin-bottom: 28px; }
        .pv-indice b { font-size: 13px; text-transform: uppercase; letter-spacing: .06em; color: var(--muted); }
        .pv-indice ol { margin: 8px 0 0; padding-left: 20px; columns: 2; column-gap: 28px; }
        .pv-indice a { color: var(--text); text-decoration: none; }
        .pv-indice a:hover { color: var(--primary); }
        section { background: var(--surface); border: 1px solid var(--border); border-radius: 16px; padding: 22px 24px; margin-bottom: 14px; scroll-margin-top: 16px; }
        h2 { font-size: 19px; margin: 0 0 10px; }
        p { margin: 0 0 10px; }
        ul { margin: 0 0 10px; padding-left: 20px; }
        li { margin: 4px 0; }
        a { color: var(--primary); }
        .pv-caja { background: var(--soft); border-radius: 12px; padding: 14px 16px; margin: 10px 0 0; }
        footer { text-align: center; color: var(--muted); font-size: 13px; margin-top: 28px; }
        @media (max-width: 600px) { .pv-indice ol { columns: 1; } section { padding: 18px; } }
    </style>
</head>
<body>
<main class="pv">
    <div class="pv-cab">
        <img src="{{ asset('images/favicon.png') }}" alt="">
        <div><b>{{ $nombre }}</b><span>{{ $e['giro'] ?: 'Equipo médico' }} · {{ $web }}</span></div>
    </div>

    <h1>Aviso de privacidad</h1>
    <p class="pv-fecha">Última actualización: {{ $actualizado }}</p>

    <nav class="pv-indice" aria-label="Contenido">
        <b>Contenido</b>
        <ol>
            <li><a href="#responsable">Quién es el responsable</a></li>
            <li><a href="#datos">Qué datos tratamos</a></li>
            <li><a href="#finalidades">Para qué los usamos</a></li>
            <li><a href="#whatsapp">Mensajes por WhatsApp</a></li>
            <li><a href="#compartir">Con quién los compartimos</a></li>
            <li><a href="#conservacion">Cuánto tiempo los guardamos</a></li>
            <li><a href="#derechos">Tus derechos (ARCO)</a></li>
            <li><a href="#eliminar">Cómo pedir que se eliminen</a></li>
            <li><a href="#seguridad">Seguridad y cookies</a></li>
            <li><a href="#cambios">Cambios a este aviso</a></li>
        </ol>
    </nav>

    <section id="responsable">
        <h2>1. Quién es el responsable</h2>
        <p><b>{{ $nombre }}</b>@if ($e['ubicacion']), con domicilio en {{ $e['ubicacion'] }},@endif es responsable del tratamiento de tus datos personales, conforme a la Ley Federal de Protección de Datos Personales en Posesión de los Particulares (LFPDPPP) y su reglamento.</p>
        @if ($correo)
            <p>Para cualquier asunto de privacidad, escríbenos a <a href="mailto:{{ $correo }}">{{ $correo }}</a>.</p>
        @endif
    </section>

    <section id="datos">
        <h2>2. Qué datos tratamos</h2>
        <ul>
            <li><b>Identificación y contacto:</b> nombre, teléfono, número de WhatsApp, correo electrónico, institución u hospital y domicilio de entrega.</li>
            <li><b>Datos comerciales y fiscales:</b> RFC, razón social, régimen y domicilio fiscal, para cotizaciones, ventas y facturas.</li>
            <li><b>Conversaciones:</b> los mensajes, fotos, videos, audios, documentos, ubicaciones y contactos que nos envías por WhatsApp, y sus estados de entrega.</li>
            <li><b>Datos del equipo:</b> números de serie, servicios, garantías y evidencias relacionadas con el equipo que adquieres o nos envías.</li>
        </ul>
        <p>No solicitamos datos personales sensibles. Si al pedir un servicio nos compartes información de pacientes, la usamos solo para atender esa solicitud y te pedimos evitar enviarla cuando no sea necesaria.</p>
    </section>

    <section id="finalidades">
        <h2>3. Para qué los usamos</h2>
        <p><b>Finalidades necesarias</b> (sin ellas no podemos atenderte):</p>
        <ul>
            <li>Responder tus solicitudes y mensajes, y darte información de productos y servicios.</li>
            <li>Elaborar cotizaciones, registrar ventas, cobros y facturas.</li>
            <li>Preparar, entregar e instalar equipo; dar mantenimiento, servicio y seguimiento de garantías.</li>
            <li>Avisarte del estado de tus pedidos, servicios, pagos y garantías.</li>
            <li>Cumplir obligaciones legales y fiscales.</li>
        </ul>
        <p><b>Finalidades adicionales:</b> enviarte promociones, ofertas y novedades. Puedes negarte en cualquier momento respondiendo <b>BAJA</b> a cualquier mensaje promocional, tocando el botón “Ya no quiero promociones” o escribiéndonos. Negarte no afecta la atención que te damos.</p>
    </section>

    <section id="whatsapp">
        <h2>4. Mensajes por WhatsApp</h2>
        <p>Usamos la plataforma oficial de WhatsApp Business de Meta Platforms para comunicarnos contigo. Cuando nos escribes, tu mensaje y tu nombre de perfil llegan a nuestro sistema interno, donde el personal autorizado los atiende.</p>
        <ul>
            <li>Solo te escribimos primero con mensajes aprobados por WhatsApp y relacionados con tu relación con nosotros, o si aceptaste recibir promociones.</li>
            <li>Los archivos que nos envías se guardan en nuestro sistema y solo los ve personal con permiso.</li>
            <li>El tratamiento que hace Meta de tu información se rige por las <a href="https://www.whatsapp.com/legal/privacy-policy" target="_blank" rel="noopener">políticas de privacidad de WhatsApp</a>.</li>
        </ul>
    </section>

    <section id="compartir">
        <h2>5. Con quién los compartimos</h2>
        <p>No vendemos ni rentamos tus datos. Solo los compartimos cuando es necesario para las finalidades anteriores:</p>
        <ul>
            <li>Proveedores que nos prestan servicios de tecnología, mensajería (como WhatsApp de Meta), alojamiento, correo y facturación electrónica, que solo pueden usarlos para prestarnos ese servicio.</li>
            <li>Paqueterías, para entregar tu equipo.</li>
            <li>Fabricantes o distribuidores, cuando se requiera para hacer válida una garantía.</li>
            <li>Autoridades, cuando la ley lo exija.</li>
        </ul>
        <p>Estas transferencias no requieren tu consentimiento conforme al artículo 37 de la LFPDPPP.</p>
    </section>

    <section id="conservacion">
        <h2>6. Cuánto tiempo los guardamos</h2>
        <p>Conservamos tus datos mientras exista una relación comercial contigo y después durante los plazos que exigen las leyes fiscales y mercantiles, o mientras siga vigente una garantía. Al terminar, los eliminamos o los hacemos anónimos.</p>
    </section>

    <section id="derechos">
        <h2>7. Tus derechos (ARCO)</h2>
        <p>Puedes <b>acceder</b> a tus datos, pedir que los <b>rectifiquemos</b>, que los <b>cancelemos</b> u <b>oponerte</b> a su uso, así como revocar tu consentimiento o limitar su uso.</p>
        <p>Envía tu solicitud @if ($correo) a <a href="mailto:{{ $correo }}">{{ $correo }}</a> @else por el mismo medio en el que nos contactas @endif con tu nombre, un medio para responderte, una copia de tu identificación y una descripción clara de lo que pides. Te respondemos en un máximo de 20 días hábiles y, si procede, lo aplicamos dentro de los 15 días hábiles siguientes.</p>
    </section>

    <section id="eliminar">
        <h2>8. Cómo pedir que se eliminen tus datos</h2>
        <p>Si quieres que borremos tus datos y tus conversaciones de WhatsApp de nuestros sistemas:</p>
        <ol>
            <li>@if ($correo) Escríbenos a <a href="mailto:{{ $correo }}?subject=Eliminar%20mis%20datos">{{ $correo }}</a> @else Escríbenos @endif con el asunto “Eliminar mis datos”, o envíanos el mensaje <b>“Eliminar mis datos”</b> por WhatsApp desde el número que quieres borrar.</li>
            <li>Indica tu nombre y el teléfono o correo con el que te comunicaste.</li>
            <li>Confirmamos tu identidad y eliminamos tus datos y conversaciones en un plazo máximo de 30 días, salvo los que la ley nos obligue a conservar (por ejemplo, facturas), que solo guardaremos por el tiempo que la ley indique.</li>
        </ol>
        <p class="pv-caja">Te avisaremos por el mismo medio cuando la eliminación esté hecha.</p>
    </section>

    <section id="seguridad">
        <h2>9. Seguridad y cookies</h2>
        <p>Protegemos tus datos con medidas administrativas, técnicas y físicas: acceso solo con usuario y contraseña, permisos por persona, conexiones cifradas (HTTPS) y respaldos.</p>
        <p>Nuestro sistema usa cookies técnicas solo para mantener la sesión del personal que lo opera. Esta página no usa cookies de publicidad ni de rastreo.</p>
    </section>

    <section id="cambios">
        <h2>10. Cambios a este aviso</h2>
        <p>Si cambiamos este aviso, publicaremos la nueva versión en esta misma página con su fecha de actualización.</p>
    </section>

    <footer>© {{ date('Y') }} {{ $nombre }} · {{ $web }}</footer>
</main>
</body>
</html>
