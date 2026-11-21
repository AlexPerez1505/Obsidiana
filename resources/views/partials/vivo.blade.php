{{--
    Tiempo real en todas las pantallas del panel (lo incluye layouts.dashboard).

    Cada 3 s (15 s con la pestaña oculta) pregunta a /_vivo si alguien cambió
    algo (el contador lo mueve App\Support\ObsVivo). Si cambió, trae la misma
    pantalla por detrás y cambia en su lugar solo lo que cambió: sin recargar,
    sin parpadeo y sin mover el scroll.

    No se toca:
      - lo que el usuario esté escribiendo, lo que tenga el foco ni un
        formulario con cambios sin guardar;
      - lo que dibuja JavaScript (mapas, gráficas, fotos elegidas, listas que
        arma la pantalla): si el servidor manda vacío un contenedor que en
        pantalla tiene contenido, se respeta lo de pantalla;
      - lo que la pantalla muestra u oculta (style/hidden) ni las clases de
        estado (active, open, is-on...);
      - un elemento con data-live="off".
    Con una ventana, menú o confirmación abierta se espera a que se cierre.
    Las pantallas de captura (crear/editar) no se actualizan solas.

    API:  ObsVivo.on(fn)    la pantalla se actualiza con su propia función
          ObsVivo.apagar()  esta pantalla no se actualiza sola
          ObsVivo.ahora()   revisar ya
    Después de cada actualización se dispara el evento "obs:vivo" en document.
--}}
@once
<meta name="obs-rev" content="{{ \App\Support\ObsVivo::rev() }}">
<script src="{{ asset('js/vivo.js') }}?v={{ filemtime(public_path('js/vivo.js')) }}" data-url="{{ route('vivo') }}"></script>
@endonce
