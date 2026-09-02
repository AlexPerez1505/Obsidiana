{{-- Estilos de las pantallas de detalle de cotización y venta. --}}
@once
<style>
    .doc-grid { display:grid; grid-template-columns:minmax(0,1.6fr) minmax(0,1fr); gap:18px; align-items:start; }
    @media (max-width:1000px) { .doc-grid { grid-template-columns:1fr; } }
    .doc-col { display:flex; flex-direction:column; gap:18px; min-width:0; }

    .doc-head { display:flex; align-items:center; gap:10px;
                padding-bottom:14px; margin-bottom:16px; border-bottom:1px solid var(--border); }
    .doc-head h3 { margin:0; font-size:15px; font-weight:600; }
    .doc-head .der { margin-left:auto; color:var(--muted); font-size:12.5px; }

    /* ===================== Tabla de equipo ===================== */
    .doc-tabla-wrap { overflow-x:auto; }
    .doc-tabla { width:100%; border-collapse:collapse; font-size:13.5px; }
    .doc-tabla th { padding:0 12px 10px; border-bottom:1px solid var(--border);
                    color:var(--muted); font-size:11.5px; font-weight:600;
                    letter-spacing:.03em; text-transform:uppercase; text-align:left; }
    .doc-tabla td { padding:12px; border-bottom:1px solid var(--border); vertical-align:middle; }
    .doc-tabla tr:last-child td { border-bottom:0; }
    .doc-tabla .c { text-align:center; }
    .doc-tabla .r { text-align:right; }

    .doc-img { display:flex; align-items:center; justify-content:center; width:44px; height:44px;
               border:1px solid var(--border); border-radius:9px; background:var(--surface-2);
               color:var(--muted); overflow:hidden; }
    .doc-img img { width:100%; height:100%; object-fit:cover; }
    .doc-img svg { width:19px; height:19px; }

    .doc-nom { font-weight:600; color:var(--text); }
    .doc-det { margin-top:1px; color:var(--muted); font-size:12px; }
    .doc-regalo { color:var(--green); font-size:12.5px; font-weight:600; }

    /* ===================== Resumen de montos ===================== */
    .doc-tot { display:flex; justify-content:space-between; align-items:baseline; gap:16px; padding:8px 0; }
    .doc-tot + .doc-tot { border-top:1px solid var(--border); }
    .doc-tot .e { color:var(--muted); font-size:13.5px; }
    .doc-tot .v { font-size:13.5px; font-weight:600; }
    .doc-tot.total { margin-top:6px; padding-top:14px; border-top:1.5px solid var(--text); }
    .doc-tot.total .e { color:var(--text); font-size:13px; font-weight:600;
                        letter-spacing:.04em; text-transform:uppercase; }
    .doc-tot.total .v { font-size:22px; font-weight:700; letter-spacing:-.02em; }
    .doc-tot.aparte .v { color:var(--green); font-size:15px; font-weight:700; }

    /* ===================== Filas de pagos ===================== */
    .doc-pago { display:flex; align-items:center; gap:12px; padding:11px 0; }
    .doc-pago + .doc-pago { border-top:1px solid var(--border); }
    .doc-pago .txt { flex:1; min-width:0; }
    .doc-pago .t { font-size:13.5px; font-weight:600; }
    .doc-pago .s { margin-top:1px; color:var(--muted); font-size:12px; }
    .doc-pago .m { font-size:13.5px; font-weight:700; white-space:nowrap; }

    .doc-chip { padding:2px 9px; border-radius:999px; font-size:11.5px; font-weight:600; white-space:nowrap; }
    .doc-chip.es-pagado { background:var(--green-soft); color:var(--green); }
    .doc-chip.es-parcial { background:var(--accent-soft); color:var(--accent); }
    .doc-chip.es-vencido { background:var(--danger-soft); color:var(--danger); }
    .doc-chip.es-pendiente { background:var(--surface-2); color:var(--muted); }

    /* ===================== Datos sueltos ===================== */
    .doc-par { display:flex; justify-content:space-between; gap:14px; padding:7px 0; font-size:13.5px; }
    .doc-par + .doc-par { border-top:1px solid var(--border); }
    .doc-par .k { color:var(--muted); }
    .doc-par .v { font-weight:600; text-align:right; overflow-wrap:anywhere; }

    .doc-nota { padding:11px 13px; border:1px solid var(--border); border-left:3px solid var(--primary);
                border-radius:9px; background:var(--surface-2); font-size:13.5px; }
</style>
@endonce
