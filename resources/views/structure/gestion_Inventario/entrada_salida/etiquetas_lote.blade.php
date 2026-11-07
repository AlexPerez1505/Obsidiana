<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Imprimir etiquetas · {{ $piezas->count() }}</title>
    <style>
        /* Misma etiqueta que la hoja de una entrada (QR vectorial al tamaño
           real), pero de muchas piezas: se marcan las que se quieren y se
           imprimen todas juntas. */
        * { box-sizing:border-box; }
        body { margin:0; background:#f1f3f6; color:#111; font-family:system-ui, -apple-system, Segoe UI, Roboto, sans-serif; }

        .barra { position:sticky; top:0; z-index:5; display:flex; align-items:center; flex-wrap:wrap; gap:8px 10px;
                 padding:10px 18px; background:#fff; border-bottom:1px solid #e3e6ea; }
        .barra h1 { margin:0; font-size:15px; font-weight:650; flex:1; min-width:180px; }
        .barra .n { color:#6b7280; font-size:13px; }
        .barra button, .barra a, .barra input[type=search] { padding:8px 12px; border:1px solid #d7dbe0; border-radius:8px; background:#fff;
                 color:#111; font-size:13.5px; font-family:inherit; text-decoration:none; cursor:pointer; }
        .barra input[type=search] { cursor:text; min-width:200px; }
        .barra button.primario { background:#007aff; border-color:#007aff; color:#fff; font-weight:600; }
        .barra button.primario:disabled { opacity:.5; cursor:not-allowed; }
        .aviso { margin:0 0 12px; padding:10px 12px; border-radius:8px; background:#fff6db; color:#8a5a00; font-size:13px; }

        .hoja { max-width:960px; margin:0 auto; padding:18px; }
        .grupo { margin-bottom:16px; }
        .grupo-cab { display:flex; align-items:center; gap:8px; margin:0 0 8px; font-size:13px; font-weight:650; }
        .grupo-cab label { display:flex; align-items:center; gap:8px; cursor:pointer; }
        .grupo-cab .n { color:#6b7280; font-weight:500; }
        .rejilla { display:grid; grid-template-columns:repeat(auto-fill, minmax(210px, 1fr)); gap:10px; }

        .sel { position:relative; display:block; cursor:pointer; }
        .sel input { position:absolute; top:8px; right:8px; width:18px; height:18px; accent-color:#007aff; cursor:pointer; z-index:1; }
        .sel.fuera .etiqueta { opacity:.35; }

        .etiqueta { display:flex; align-items:center; gap:11px; padding:11px; background:#fff; border:1px solid #cfd4da; border-radius:8px;
                    break-inside:avoid; page-break-inside:avoid; }
        .sel:not(.fuera) .etiqueta { border-color:#007aff; box-shadow:0 0 0 1px #007aff inset; }
        .etiqueta .qr { width:74px; height:74px; flex:0 0 74px; }
        .etiqueta .qr svg { width:100%; height:100%; display:block; }
        .etiqueta .txt { min-width:0; padding-right:16px; }
        .etiqueta .cod { font-size:14px; font-weight:800; letter-spacing:.03em; font-family:ui-monospace, Consolas, monospace; }
        .etiqueta .eq { margin-top:2px; font-size:11px; line-height:1.3; color:#374151; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden; }
        .etiqueta .em { margin-top:3px; font-size:9px; letter-spacing:.06em; text-transform:uppercase; color:#9ca3af; }
        .vacio { padding:40px; text-align:center; color:#6b7280; background:#fff; border:1px solid #e3e6ea; border-radius:12px; }
        .oculta { display:none !important; }

        @media print {
            body { background:#fff; }
            .barra, .aviso, .grupo-cab, .sel input { display:none !important; }
            .hoja { max-width:none; padding:0; }
            /* Una sola rejilla corrida: las etiquetas no marcadas no salen. */
            .grupo { margin:0; display:contents; }
            .lista { display:grid; grid-template-columns:repeat(3, 1fr); gap:6px; }
            .rejilla { display:contents; }
            .sel.fuera, .sel.oculta { display:none !important; }
            .sel .etiqueta { opacity:1 !important; box-shadow:none !important; border-color:#999 !important; }
            .etiqueta .txt { padding-right:0; }
            @page { margin:10mm; }
        }
    </style>
</head>
<body>

<div class="barra">
    <h1>Imprimir etiquetas</h1>
    <span class="n"><b id="nSel">{{ $piezas->count() }}</b> de {{ $piezas->count() }} marcadas</span>
    @if ($piezas->count() > 8)
        <input type="search" id="buscar" placeholder="Buscar por código o equipo" aria-label="Buscar etiqueta">
    @endif
    <button type="button" id="todas">Marcar todas</button>
    <button type="button" id="ninguna">Quitar todas</button>
    <a href="{{ $volver }}">Volver</a>
    <button type="button" class="primario" id="imprimir">Imprimir</button>
</div>

<div class="hoja">
    @if ($recortado)
        <p class="aviso">Se muestran las primeras {{ \App\Http\Controllers\EtiquetasController::MAXIMO }} piezas. Para imprimir más, hazlo en dos tandas.</p>
    @endif
    @if ($conProductos && ! $vendidas)
        <p class="aviso" style="background:#eef4ff; color:#1d4ed8;">De los productos solo salen las piezas que siguen en inventario (no vendidas).
            <a href="{{ request()->fullUrlWithQuery(['vendidas' => 1]) }}" style="color:inherit; font-weight:600;">Incluir también las vendidas</a></p>
    @endif

    @if ($piezas->isEmpty())
        <p class="vacio">No hay piezas con etiqueta en lo que elegiste.</p>
    @else
        <div class="lista">
            @foreach ($grupos as $nombre => $lista)
                <section class="grupo" data-grupo>
                    <div class="grupo-cab">
                        <label><input type="checkbox" checked data-marcar-grupo> {{ $nombre }}</label>
                        <span class="n">{{ $lista->count() }} pieza(s)</span>
                    </div>
                    <div class="rejilla">
                        @foreach ($lista as $pieza)
                            <label class="sel" data-buscar="{{ mb_strtolower($pieza->codigo.' '.$pieza->no_serie.' '.$nombre) }}">
                                <input type="checkbox" checked data-pieza aria-label="Imprimir {{ $pieza->codigo }}">
                                @include('structure.gestion_Inventario.entrada_salida._etiqueta', ['pieza' => $pieza])
                            </label>
                        @endforeach
                    </div>
                </section>
            @endforeach
        </div>
    @endif
</div>

<script>
(function () {
    const casillas = () => [...document.querySelectorAll('[data-pieza]')];
    const nSel = document.getElementById('nSel'), btn = document.getElementById('imprimir');

    function pintar() {
        let n = 0;
        casillas().forEach(c => { const s = c.closest('.sel'); s.classList.toggle('fuera', !c.checked); if (c.checked && !s.classList.contains('oculta')) n++; });
        document.querySelectorAll('[data-grupo]').forEach(g => {
            const cs = [...g.querySelectorAll('[data-pieza]')], m = cs.filter(c => c.checked).length;
            const t = g.querySelector('[data-marcar-grupo]');
            t.checked = m === cs.length; t.indeterminate = m > 0 && m < cs.length;
        });
        nSel.textContent = n;
        btn.disabled = n === 0;
        btn.textContent = n === 1 ? 'Imprimir 1 etiqueta' : 'Imprimir ' + n + ' etiquetas';
    }

    document.addEventListener('change', e => {
        if (e.target.matches('[data-marcar-grupo]')) {
            e.target.closest('[data-grupo]').querySelectorAll('[data-pieza]').forEach(c => { if (!c.closest('.sel').classList.contains('oculta')) c.checked = e.target.checked; });
        }
        pintar();
    });
    const todas = v => () => { casillas().forEach(c => { if (!c.closest('.sel').classList.contains('oculta')) c.checked = v; }); pintar(); };
    document.getElementById('todas').addEventListener('click', todas(true));
    document.getElementById('ninguna').addEventListener('click', todas(false));

    const buscar = document.getElementById('buscar');
    buscar?.addEventListener('input', () => {
        const q = buscar.value.trim().toLowerCase();
        document.querySelectorAll('.sel').forEach(s => s.classList.toggle('oculta', q !== '' && !s.dataset.buscar.includes(q)));
        document.querySelectorAll('[data-grupo]').forEach(g => g.classList.toggle('oculta', !g.querySelector('.sel:not(.oculta)')));
        pintar();
    });

    btn.addEventListener('click', () => {
        // Lo que esconde la búsqueda no se imprime aunque esté marcado.
        window.print();
    });
    pintar();
})();
</script>
</body>
</html>
