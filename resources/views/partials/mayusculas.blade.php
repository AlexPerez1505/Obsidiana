{{--
    Todo lo que se escribe se guarda en MAYÚSCULAS, con corrector ortográfico.

    Aplica a los campos de texto libre (input de texto/búsqueda y textarea)
    de cualquier pantalla, también los que dibuja el JavaScript después:
      - mientras se escribe, el texto SE VE en mayúsculas (CSS) pero el valor
        sigue como se tecleó, para que el corrector del navegador pueda
        subrayar las palabras mal escritas;
      - al salir del campo y al guardar el formulario, el valor SE CONVIERTE
        a mayúsculas, que es lo que llega al servidor;
      - en el celular el teclado sale en mayúsculas y con autocorrección.

    No se tocan: correos, contraseñas, URLs, usuarios, tokens/llaves, ni
    fecha/número/teléfono, ni selects, casillas o campos ocultos (ahí viajan
    valores internos como rutas de fotos, firmas o claves de catálogo).
    Para excluir un campo o una zona: data-no-mayus.
--}}
@once
<style>
    .obs-mayus { text-transform:uppercase; }
    .obs-mayus::placeholder { text-transform:none; }
</style>
<script src="{{ asset('js/mayusculas.js') }}?v={{ filemtime(public_path('js/mayusculas.js')) }}"></script>
@endonce
