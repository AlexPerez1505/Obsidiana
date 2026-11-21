{{--
    Autocorrector ortográfico en español de México, en todas las pantallas.

    Al salir de un campo de texto libre (notas, descripciones, nombres,
    direcciones, motivos…), lo escrito se revisa con la IA y se corrige solo:
    acentos, ñ, b/v, s/c/z, h, letras cambiadas. Aparece una notita junto al
    campo con lo que cambió y un botón "Deshacer".

    No se revisan: búsquedas, correos, contraseñas, números, teléfonos,
    series, códigos, folios, RFC, marcas/modelos ni los campos con lista de
    sugerencias del catálogo. Para excluir un campo o una zona:
    data-no-ortografia.

    Si se guarda el formulario mientras una revisión va en camino, se espera
    (máximo unos segundos) a que termine, para que se guarde ya corregido.
--}}
@once
<style>
    .orto-nota { position:fixed; z-index:2147483000; display:flex; align-items:center; gap:8px; max-width:min(420px, calc(100vw - 24px));
                 padding:6px 8px 6px 10px; border-radius:10px; font-size:12.5px; line-height:1.35;
                 background:var(--surface, #fff); color:var(--text, #0f172a); border:1px solid var(--border, #e2e8f0);
                 box-shadow:0 6px 22px rgba(15,23,42,.18); opacity:0; transform:translateY(-4px); transition:opacity .15s, transform .15s; }
    .orto-nota.is-on { opacity:1; transform:none; }
    .orto-nota b { color:var(--green, #15803d); font-weight:700; }
    .orto-nota s { color:var(--muted, #64748b); }
    .orto-nota button { flex:0 0 auto; border:0; border-radius:7px; padding:4px 8px; cursor:pointer; font:inherit; font-weight:600;
                        background:var(--surface-2, #f1f5f9); color:var(--primary, #2563eb); }
    @media (prefers-reduced-motion: reduce) { .orto-nota { transition:none; } }
</style>
<script src="{{ asset('js/ortografia.js') }}?v={{ filemtime(public_path('js/ortografia.js')) }}" data-url="{{ route('ortografia.corregir') }}"></script>
@endonce
