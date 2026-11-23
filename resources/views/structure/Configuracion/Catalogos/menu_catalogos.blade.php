@extends('structure.Configuracion.layout')

@push('head')
    <style>
        .eq-card { position:relative; padding:22px 26px 18px; border:1px solid #20588f; border-radius:18px; background:linear-gradient(145deg,#071a35 0%,#0a1c37 58%,#07172d 100%); box-shadow:0 14px 34px rgba(0,0,0,.22),0 0 20px rgba(0,126,255,.08); overflow:hidden; }
        .eq-card::before { content:''; position:absolute; right:-80px; top:-80px; width:520px; height:180px; border-bottom:2px solid rgba(0,137,255,.58); border-radius:50%; transform:rotate(-12deg); pointer-events:none; }
        .eq-card > * { position:relative; z-index:1; }
        .eq-card .catalog-header { display:grid; grid-template-columns:48px minmax(0,1fr) auto; align-items:center; gap:14px; margin:0; padding:0 0 14px; border-bottom:1px solid rgba(61,104,157,.38); }
        .eq-card .catalog-header-icon { display:flex; align-items:center; justify-content:center; width:48px; height:48px; border:1px solid #087df0; border-radius:13px; background:linear-gradient(145deg,#113e72,#0b274c); color:#47aaff; box-shadow:0 0 0 6px rgba(0,126,255,.08); }
        .eq-card .catalog-header-icon svg { width:27px; height:27px; }
        .eq-card .catalog-header h2 { margin:0; color:#f4f8ff; font-size:24px; font-weight:800; }
        .eq-card .catalog-count { align-self:start; padding:8px 14px; border:1px solid #315779; border-radius:999px; background:#0e2745; color:#b8cee7; font-size:12.5px; }
        .eq-card .eq-intro { margin:-8px 0 18px 62px; color:#a9c2e2; font-size:13.5px; }
        .eq-card .eq-filters { margin-top:16px; }
        .eq-card .eq-search { position:relative; margin-bottom:18px; }
        .eq-card .eq-search label { position:absolute; width:1px; height:1px; overflow:hidden; clip:rect(0 0 0 0); }
        .eq-card .eq-search input { min-height:44px; padding-left:46px; border-color:#1b79c8; border-radius:11px; background:#0e2745; box-shadow:0 0 0 2px rgba(0,126,255,.08); }
        .eq-card .eq-search::before { content:'⌕'; position:absolute; left:16px; top:6px; z-index:2; color:#9fc2e5; font-size:28px; line-height:32px; }
        .eq-card .eq-grid { gap:14px; }
        .eq-card .eq-col { border-color:#1b426d; border-radius:14px; background:linear-gradient(180deg,#102b50,#091a32); box-shadow:inset 0 1px rgba(255,255,255,.03); }
        .eq-card .eq-col-head { padding:13px 16px; border-bottom-color:rgba(54,94,140,.4); }
        .eq-card .eq-col-head h3 { display:flex; align-items:center; gap:8px; color:#f3f7ff; font-size:16px; font-weight:800; }
        .eq-card .eq-col-icon { display:inline-flex; color:#49aaff; }
        .eq-card .eq-col-icon svg { width:20px; height:20px; }
        .eq-card .eq-num { background:#0b203a; border-color:#315779; color:#c3d7ec; }
        .eq-card .eq-list { max-height:320px; padding:7px 9px; }
        .eq-card .eq-item { min-height:45px; border:1px solid transparent; background:rgba(8,24,46,.42); margin-bottom:5px; padding:8px 9px; }
        .eq-card .eq-item:hover { border-color:#1687ed; background:linear-gradient(100deg,#124b80,#112e52); box-shadow:0 0 14px rgba(0,126,255,.18); }
        .eq-card .eq-item-name { color:#f0f5fc; font-weight:700; }
        .eq-card .eq-item-meta { color:#91afd0; }
        .eq-card .eq-add { border-top-color:rgba(54,94,140,.45); color:#29a1ff; font-size:14px; }
        .eq-card .eq-add:hover { background:rgba(0,126,255,.1); }
        [data-theme="light"] .eq-card { background:#fff; border-color:#b9d0e6; box-shadow:0 14px 34px rgba(33,72,111,.12); }
        [data-theme="light"] .eq-card::before { border-bottom-color:rgba(0,126,255,.32); }
        [data-theme="light"] .eq-card .catalog-header { border-bottom-color:#d7e4ef; }
        [data-theme="light"] .eq-card .catalog-header h2, [data-theme="light"] .eq-card .eq-col-head h3 { color:#244765; }
        [data-theme="light"] .eq-card .catalog-count { background:#f1f7fc; border-color:#c7d9e8; color:#55738e; }
        [data-theme="light"] .eq-card .eq-intro { color:#66839d; }
        [data-theme="light"] .eq-card .eq-search input, [data-theme="light"] .eq-card .eq-filters select { background:#fff; color:#294c6c; border-color:#b9cfe1; }
        [data-theme="light"] .eq-card .eq-col { border-color:#c3d6e7; background:#f7fbff; }
        [data-theme="light"] .eq-card .eq-col-head { border-bottom-color:#d7e4ef; }
        [data-theme="light"] .eq-card .eq-num { background:#eef5fb; border-color:#c5d8e8; color:#55738e; }
        [data-theme="light"] .eq-card .eq-item { background:#fff; }
        [data-theme="light"] .eq-card .eq-item:hover { background:#edf7ff; border-color:#70b4e8; }
        [data-theme="light"] .eq-card .eq-item-name { color:#244765; }
        [data-theme="light"] .eq-card .eq-item-meta { color:#66839d; }
        [data-theme="light"] .eq-card .eq-add { border-top-color:#d7e4ef; }
        @media (max-width:900px) { .eq-card .catalog-header { grid-template-columns:48px minmax(0,1fr); } .eq-card .catalog-count { grid-column:2; justify-self:start; } }
    </style>
@endpush

@section('title', 'Catálogos')
@section('page-title', 'Catálogos')

@section('configuracion_content')
    {{-- Las categorías sueltas se retiraron: clientes, congresos y promociones
         se clasifican con el catálogo de equipo (tipo de equipo). --}}

    @include('structure.Configuracion.Catalogos._catalogo_equipo')

    {{-- ===================== Modales de categoría =====================
         Los formularios apuntan a las mismas rutas de siempre. Si el
         navegador no ejecuta el script, los enlaces siguen abriendo la
         pagina completa, que sigue existiendo. --}}

    <dialog class="cfg-modal" id="modalCatCrear" aria-labelledby="modalCatCrearT">
        <form method="POST" action="{{ route('configuracion.categorias.store') }}" class="cfg-modal-box">
            @csrf
            <input type="hidden" name="_modal" value="crear">

            <div class="cfg-modal-head">
                <h3 id="modalCatCrearT">Nueva categoría</h3>
                <button type="button" class="cfg-modal-x" data-cerrar aria-label="Cerrar">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M18 6 6 18M6 6l12 12"/></svg>
                </button>
            </div>

            <div class="cfg-modal-body">
                <label for="catCrearNombre">Nombre</label>
                <input id="catCrearNombre" type="text" name="name" required maxlength="255" autocomplete="off"
                       placeholder="Ingrese el nombre de la categoría"
                       value="{{ old('_modal') === 'crear' ? old('name') : '' }}">
                @if (old('_modal') === 'crear')
                    @error('name')<p class="err">{{ $message }}</p>@enderror
                @endif
            </div>

            <div class="cfg-modal-foot">
                <button type="button" class="btn btn--ghost" data-cerrar>Cancelar</button>
                <button type="submit" class="btn">Guardar categoría</button>
            </div>
        </form>
    </dialog>

    <dialog class="cfg-modal" id="modalCatEditar" aria-labelledby="modalCatEditarT">
        <form method="POST" class="cfg-modal-box" data-form>
            @csrf
            @method('PUT')
            <input type="hidden" name="_modal" value="editar">
            {{-- Guarda a que categoria apunta el formulario, para reabrir el modal si falla la validacion. --}}
            <input type="hidden" name="_url" data-url-field>

            <div class="cfg-modal-head">
                <h3 id="modalCatEditarT">Editar categoría</h3>
                <button type="button" class="cfg-modal-x" data-cerrar aria-label="Cerrar">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M18 6 6 18M6 6l12 12"/></svg>
                </button>
            </div>

            <div class="cfg-modal-body">
                <label for="catEditarNombre">Nombre</label>
                <input id="catEditarNombre" type="text" name="name" required maxlength="255" autocomplete="off"
                       placeholder="Ingrese el nombre de la categoría">
                @if (old('_modal') === 'editar')
                    @error('name')<p class="err">{{ $message }}</p>@enderror
                @endif
            </div>

            <div class="cfg-modal-foot">
                <button type="button" class="btn btn--ghost" data-cerrar>Cancelar</button>
                <button type="submit" class="btn">Guardar cambios</button>
            </div>
        </form>
    </dialog>

    <dialog class="cfg-modal" id="modalCatEliminar" aria-labelledby="modalCatEliminarT">
        <form method="POST" class="cfg-modal-box" data-form>
            @csrf
            @method('DELETE')

            <div class="cfg-modal-head">
                <h3 id="modalCatEliminarT">Eliminar categoría</h3>
                <button type="button" class="cfg-modal-x" data-cerrar aria-label="Cerrar">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M18 6 6 18M6 6l12 12"/></svg>
                </button>
            </div>

            <div class="cfg-modal-body">
                <div class="danger-box" style="margin:0;">
                    <div class="cfg-del">
                        <span class="cfg-del-ico">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/><path d="M10 11v6M14 11v6"/></svg>
                        </span>
                        <div>
                            <div class="cfg-del-name" data-nombre>—</div>
                            <div class="cfg-del-note">Esta acción no se puede deshacer.</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="cfg-modal-foot">
                <button type="button" class="btn btn--ghost" data-cerrar>Cancelar</button>
                <button type="submit" class="btn btn--danger">Eliminar categoría</button>
            </div>
        </form>
    </dialog>

    @if ($errors->any() && old('_modal'))
        <span id="cfgReabrir" data-modal="{{ old('_modal') }}" data-url="{{ old('_url') }}" hidden></span>
    @endif

    <style>
        /* ===== Modales ===== */
        .cfg-modal { padding:0; border:0; background:transparent; max-width:none; max-height:none; overflow:visible; }
        .cfg-modal::backdrop { background:rgba(2,6,23,.5); backdrop-filter:blur(2px); -webkit-backdrop-filter:blur(2px); }
        .cfg-modal[open] { animation:cfgModalIn .16s ease-out; }
        @keyframes cfgModalIn { from { opacity:0; transform:translateY(6px) scale(.985); } }

        .cfg-modal-box { width:min(460px, calc(100vw - 32px)); background:var(--surface);
                         border:1px solid var(--border); border-radius:14px;
                         box-shadow:0 24px 60px rgba(17,24,39,.22); overflow:hidden; }
        [data-theme="dark"] .cfg-modal-box { box-shadow:0 24px 60px rgba(0,0,0,.55); }

        .cfg-modal-head { display:flex; align-items:center; gap:12px; padding:17px 20px;
                          border-bottom:1px solid var(--border); }
        .cfg-modal-head h3 { margin:0; font-size:17px; font-weight:600; letter-spacing:-.01em; color:var(--text); }
        .cfg-modal-x { margin-left:auto; display:inline-flex; align-items:center; justify-content:center;
                       width:32px; height:32px; padding:0; border:0; border-radius:8px;
                       background:none; color:var(--muted); cursor:pointer;
                       transition:background .15s ease, color .15s ease; }
        .cfg-modal-x svg { width:17px; height:17px; }
        .cfg-modal-x:hover { background:var(--surface-2); color:var(--text); }

        .cfg-modal-body { padding:20px; }
        .cfg-modal-body label { display:block; margin-bottom:7px; color:var(--muted); font-size:13.5px; }
        .cfg-modal-body input[type="text"] { width:100%; padding:10px 12px; border:1px solid var(--border);
                                             border-radius:9px; background:var(--surface); color:var(--text);
                                             font-family:inherit; font-size:14px; outline:none; }
        .cfg-modal-body input[type="text"]:focus { border-color:var(--primary); }

        .cfg-modal-foot { display:flex; justify-content:flex-end; align-items:center; gap:10px;
                          padding:15px 20px; border-top:1px solid var(--border); background:var(--surface-2); }

        /* Selects dentro de los modales, con el mismo trato que los inputs */
        .cfg-modal-body select { width:100%; padding:10px 12px; border:1px solid var(--border);
                                 border-radius:9px; background:var(--surface); color:var(--text);
                                 font-family:inherit; font-size:14px; outline:none; cursor:pointer; }
        .cfg-modal-body select:focus { border-color:var(--primary); }
        .cfg-modal-body select:disabled { color:var(--muted); cursor:not-allowed; }
        .cfg-modal-body .eq-mt { margin-top:16px; }
        .cfg-modal-body .eq-nota { margin:10px 0 0; color:var(--muted); font-size:12.5px; line-height:1.5; }

        /* ===== Catálogo de equipo ===== */
        .eq-card { margin-top:22px; }
        .eq-intro { margin:0 0 18px; color:var(--muted); font-size:13.5px; }
        .eq-filters { display:flex; flex-wrap:wrap; gap:12px; align-items:end; margin-bottom:16px; }
        .eq-filters label { display:flex; flex:1 1 180px; flex-direction:column; gap:7px; color:var(--text); font-size:13px; }
        .eq-filter-label { display:flex; align-items:center; gap:7px; font-weight:600; }
        .eq-filter-label svg { width:16px; height:16px; color:#9bb8dd; flex:0 0 auto; }
        .eq-filters select { width:100%; padding:10px 36px 10px 13px; border:1px solid color-mix(in srgb, var(--primary) 22%, var(--border)); border-radius:10px;
                             background:var(--surface); color:var(--text); font:inherit; outline:none; cursor:pointer; transition:border-color .18s, box-shadow .18s; }
        .eq-filters select:hover { border-color:var(--muted); }
        .eq-filters select:focus { border-color:var(--primary); box-shadow:0 0 0 3px color-mix(in srgb, var(--primary) 16%, transparent); }
        .eq-filters select:focus-visible { outline:2px solid var(--primary); outline-offset:2px; }
        .eq-search { display:flex; flex-direction:column; gap:6px; margin-bottom:18px; }
        .eq-search label { color:var(--text); font-size:13px; font-weight:500; }
        .eq-search input { width:100%; box-sizing:border-box; padding:10px 12px; border:1px solid var(--border);
                           border-radius:9px; background:var(--surface); color:var(--text); font:inherit; }
        .eq-search input:focus-visible { outline:2px solid var(--primary); outline-offset:2px; }
        .eq-item[hidden], .eq-vacio[hidden] { display:none; }

        .eq-grid { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:16px; align-items:start; }
        .eq-col { display:flex; flex-direction:column; min-width:0;
                  border:1px solid var(--border); border-radius:12px; background:var(--surface); }

        .eq-col-head { display:flex; align-items:center; gap:10px; padding:14px 16px;
                       border-bottom:1px solid var(--border); }
        .eq-col-head h3 { margin:0; font-size:14.5px; font-weight:600; color:var(--text); }
        .eq-num { margin-left:auto; padding:1px 8px; border-radius:6px; background:var(--surface-2);
                  border:1px solid var(--border); color:var(--muted); font-size:12px; font-weight:500; }

        .eq-list { max-height:320px; overflow-y:auto; padding:6px 8px; }
        .eq-item { display:flex; align-items:center; gap:10px; padding:9px 8px; border-radius:9px; }
        .eq-item:hover { background:var(--surface-2); }
        .eq-item-txt { flex:1; min-width:0; display:flex; flex-direction:column; gap:1px; }
        .eq-item-name { font-size:13.5px; font-weight:500; color:var(--text);
                        overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
        .eq-item-meta { color:var(--muted); font-size:12px;
                        overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }

        /* El menú de tres puntos se encoge un poco dentro de estas listas. */
        .eq-item .congress-menu-trigger { width:28px; height:28px; }
        .eq-item .congress-menu-trigger svg { width:16px; height:16px; }

        .eq-vacio { margin:0; padding:22px 10px; text-align:center; color:var(--muted); font-size:13px; }

        .eq-add { display:flex; align-items:center; justify-content:center; gap:7px; width:100%;
                  padding:11px 12px; border:0; border-top:1px solid var(--border);
                  border-radius:0 0 12px 12px; background:none; color:var(--primary);
                  font-family:inherit; font-size:13px; font-weight:600; cursor:pointer;
                  transition:background .15s ease; }
        .eq-add svg { width:15px; height:15px; }
        .eq-add:hover { background:var(--surface-2); }
        .eq-add:disabled { color:var(--muted); cursor:not-allowed; }
        .eq-add:disabled:hover { background:none; }

        @media (max-width:1200px) { .eq-grid { grid-template-columns:repeat(2,minmax(0,1fr)); } }
        @media (max-width:680px) { .eq-grid { grid-template-columns:1fr; } }
        @media (prefers-reduced-motion:reduce) { .eq-add { transition:none; } }

        .cfg-del { display:flex; align-items:center; gap:14px; }
        .cfg-del-ico { flex:0 0 auto; display:flex; align-items:center; justify-content:center;
                       width:40px; height:40px; border-radius:11px; background:var(--surface); color:var(--danger); }
        .cfg-del-ico svg { width:18px; height:18px; }
        .cfg-del-name { font-size:15px; font-weight:600; color:var(--text); overflow-wrap:anywhere; }
        .cfg-del-note { margin-top:2px; color:var(--muted); font-size:13px; }

        @media (max-width:640px) {
            .cfg-modal-foot { flex-direction:column-reverse; align-items:stretch; }
            .cfg-modal-foot .btn { width:100%; text-align:center; }
        }
        @media (prefers-reduced-motion:reduce) {
            .cfg-modal[open] { animation:none; }
            .cfg-modal-x { transition:none; }
        }
    </style>

    <style>
        #modalTipoCrear .cfg-modal-box { width:min(470px, calc(100vw - 32px)); border:1px solid #1687ed; border-radius:15px; background:linear-gradient(180deg,#0b2344 0%,#07172d 100%); box-shadow:0 0 0 1px rgba(0,126,255,.18),0 24px 70px rgba(0,0,0,.58),0 0 24px rgba(0,126,255,.18); }
        #modalTipoCrear .cfg-modal-head { gap:12px; padding:17px 20px; background:linear-gradient(110deg,#123b6d,#0c2649); border-bottom-color:rgba(65,116,174,.45); }
        #modalTipoCrear .cfg-modal-head h3 { display:flex; align-items:center; gap:12px; color:#f4f8ff; font-size:18px; font-weight:800; }
        #modalTipoCrear .cfg-modal-head h3::before { content:''; display:inline-flex; align-items:center; justify-content:center; width:38px; height:38px; border:1px solid #1591ff; border-radius:11px; background:#0e5595 url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%2362c1ff' stroke-width='1.8' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='m12 3 8 4.5v9L12 21l-8-4.5v-9L12 3Z'/%3E%3Cpath d='m4 7.5 8 4.5 8-4.5M12 12v9'/%3E%3C/svg%3E") center/22px 22px no-repeat; box-shadow:0 0 0 5px rgba(0,126,255,.1); }
        #modalTipoCrear .cfg-modal-x { color:#d8e7f8; }
        #modalTipoCrear .cfg-modal-x:hover { background:rgba(255,255,255,.08); color:#fff; }
        #modalTipoCrear .cfg-modal-body { padding:22px 20px; }
        #modalTipoCrear .cfg-modal-body label { color:#e8f2ff; font-size:13px; font-weight:700; }
        #modalTipoCrear .cfg-modal-body input[type="text"] { padding:11px 12px; border-color:#1687ed; background:#102b4d; color:#f3f8ff; box-shadow:0 0 0 3px rgba(0,126,255,.12); }
        #modalTipoCrear .cfg-modal-body input[type="text"]:focus { border-color:#40b0ff; box-shadow:0 0 0 3px rgba(0,126,255,.2); }
        #modalTipoCrear .cfg-modal-body .eq-mt { display:flex; align-items:flex-start; gap:10px; margin-top:18px; color:#b8cce4; font-weight:400; line-height:1.35; }
        #modalTipoCrear .cfg-modal-body input[type="checkbox"] { appearance:none; width:20px; height:20px; flex:0 0 20px; margin:0; border:1px solid #6693bd; border-radius:5px; background:#102b4d; cursor:pointer; }
        #modalTipoCrear .cfg-modal-body input[type="checkbox"]:checked { border-color:#1687ed; background:#1687ed; box-shadow:0 0 0 3px rgba(0,126,255,.15); }
        #modalTipoCrear .cfg-modal-body input[type="checkbox"]:checked::after { content:'✓'; display:block; color:#fff; font-size:15px; line-height:18px; text-align:center; }
        #modalTipoCrear .cfg-modal-foot { padding:15px 20px; background:rgba(5,17,34,.55); border-top-color:rgba(65,116,174,.4); }
        #modalTipoCrear .cfg-modal-foot .btn { min-height:42px; border-radius:10px; font-weight:700; }
        #modalTipoCrear .cfg-modal-foot .btn:not(.btn--ghost) { background:#1687ed; border-color:#1687ed; }
        [data-theme="light"] #modalTipoCrear .cfg-modal-box { background:#fff; border-color:#1687ed; box-shadow:0 24px 60px rgba(33,72,111,.24),0 0 0 1px rgba(0,126,255,.12); }
        [data-theme="light"] #modalTipoCrear .cfg-modal-head { background:linear-gradient(110deg,#eaf4ff,#f5f9ff); border-bottom-color:#d4e3f0; }
        [data-theme="light"] #modalTipoCrear .cfg-modal-head h3 { color:#244765; }
        [data-theme="light"] #modalTipoCrear .cfg-modal-x { color:#55738e; }
        [data-theme="light"] #modalTipoCrear .cfg-modal-body label { color:#315574; }
        [data-theme="light"] #modalTipoCrear .cfg-modal-body input[type="text"] { background:#fff; color:#244765; border-color:#1687ed; }
        [data-theme="light"] #modalTipoCrear .cfg-modal-body .eq-mt { color:#66839d; }
        [data-theme="light"] #modalTipoCrear .cfg-modal-foot { background:#f5f9fd; border-top-color:#d4e3f0; }
    </style>

    <style>
        /* Modal de nuevo tipo: composicion horizontal con panel ilustrativo. */
        #modalTipoCrear .cfg-modal-box {
            display:grid;
            grid-template-columns:155px minmax(0,1fr);
            grid-template-rows:auto auto auto;
            width:min(800px, calc(100vw - 32px));
            overflow:hidden;
            border:1px solid #28537d;
            border-radius:12px;
            background:linear-gradient(155deg,#0b2344 0%,#08182f 58%,#071326 100%);
            box-shadow:0 0 0 1px rgba(0,126,255,.16),0 24px 70px rgba(0,0,0,.58),0 0 26px rgba(0,126,255,.15);
        }
        #modalTipoCrear .cfg-modal-box::before {
            grid-column:1;
            grid-row:1 / span 3;
            content:'Tipo de equipo\A\AClasifica los equipos de tu\A inventario (endoscopia,\A laparoscopia, etc.)';
            display:flex;
            align-items:flex-end;
            justify-content:center;
            padding:0 12px 17px;
            white-space:pre-wrap;
            text-align:center;
            color:#f3f8ff;
            font-size:11px;
            font-weight:700;
            line-height:1.45;
            border:1px solid rgba(22,135,237,.35);
            border-radius:9px;
            margin:8px;
            background:#102d5d url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 80 80' fill='none' stroke='%2300aaff' stroke-width='2.5' stroke-linejoin='round'%3E%3Cpath d='m40 8 27 16v31L40 71 13 55V24L40 8Z'/%3E%3Cpath d='m13 24 27 16 27-16M40 40v31'/%3E%3C/svg%3E") center 26px/68px 68px no-repeat;
            box-shadow:inset 0 -70px 70px rgba(0,126,255,.28),inset 0 0 24px rgba(0,126,255,.14);
        }
        #modalTipoCrear .cfg-modal-head,
        #modalTipoCrear .cfg-modal-body,
        #modalTipoCrear .cfg-modal-foot { grid-column:2; }
        #modalTipoCrear .cfg-modal-head { padding:17px 20px 13px; background:transparent; border-bottom:0; }
        #modalTipoCrear .cfg-modal-head h3 { gap:0; color:#f4f8ff; font-size:16px; }
        #modalTipoCrear .cfg-modal-head h3::before { display:none; }
        #modalTipoCrear .cfg-modal-head h3::after { content:'Registra un nuevo tipo en el catálogo.'; display:block; margin-top:3px; color:#91abc9; font-size:10px; font-weight:500; }
        #modalTipoCrear .cfg-modal-x { color:#6e94ba; }
        #modalTipoCrear .cfg-modal-body { padding:7px 20px 18px; border-left:1px solid rgba(66,115,166,.28); }
        #modalTipoCrear .cfg-modal-body label { color:#e8f2ff; font-size:11px; }
        #modalTipoCrear .cfg-modal-body input[type="text"] { padding:9px 11px; border-color:#31577d; background:#102b4d; color:#f3f8ff; box-shadow:none; border-radius:8px; }
        #modalTipoCrear .cfg-modal-body input[type="text"]:focus { border-color:#159cff; box-shadow:0 0 0 2px rgba(0,156,255,.18); }
        #modalTipoCrear .cfg-modal-body .eq-mt { display:flex; align-items:flex-start; gap:10px; margin-top:15px; padding-top:14px; border-top:1px solid rgba(66,115,166,.24); color:#d1deee; font-size:11px; line-height:1.3; }
        #modalTipoCrear .cfg-modal-body input[type="checkbox"] { width:16px; height:16px; flex-basis:16px; border-radius:4px; }
        #modalTipoCrear .cfg-modal-foot { justify-content:flex-end; padding:10px 20px 14px; background:transparent; border-top:0; }
        #modalTipoCrear .cfg-modal-foot .btn { min-height:36px; border-radius:8px; font-size:12px; }
        #modalTipoCrear .cfg-modal-foot .btn:not(.btn--ghost) { background:#167cff; border-color:#167cff; box-shadow:0 5px 14px rgba(0,126,255,.2); }
        [data-theme="light"] #modalTipoCrear .cfg-modal-box { background:linear-gradient(155deg,#fff 0%,#f5f9fd 100%); border-color:#8db8dc; box-shadow:0 24px 60px rgba(33,72,111,.24),0 0 0 1px rgba(0,126,255,.12); }
        [data-theme="light"] #modalTipoCrear .cfg-modal-box::before { color:#fff; border-color:#75aee0; background-color:#164d91; }
        [data-theme="light"] #modalTipoCrear .cfg-modal-head { background:transparent; }
        [data-theme="light"] #modalTipoCrear .cfg-modal-head h3 { color:#244765; }
        [data-theme="light"] #modalTipoCrear .cfg-modal-head h3::after { color:#66839d; }
        [data-theme="light"] #modalTipoCrear .cfg-modal-body { border-left-color:#d4e3f0; }
        [data-theme="light"] #modalTipoCrear .cfg-modal-body label { color:#315574; }
        [data-theme="light"] #modalTipoCrear .cfg-modal-body input[type="text"] { background:#fff; color:#244765; border-color:#9bb9d1; }
        [data-theme="light"] #modalTipoCrear .cfg-modal-body .eq-mt { color:#55738e; border-top-color:#d4e3f0; }
        @media (max-width:620px) {
            #modalTipoCrear .cfg-modal-box { display:block; width:min(470px, calc(100vw - 32px)); }
            #modalTipoCrear .cfg-modal-box::before { display:none; }
            #modalTipoCrear .cfg-modal-body { border-left:0; }
        }
    </style>

    <style>
        /* Ajustes de acabado del panel lateral del modal de tipos. */
        #modalTipoCrear .cfg-modal-box::before { display:none; }
        #modalTipoCrear .cfg-modal-side {
            grid-column:1;
            grid-row:1 / span 3;
            display:flex;
            flex-direction:column;
            align-items:center;
            justify-content:flex-start;
            gap:8px;
            margin:8px;
            padding:27px 13px 15px;
            overflow:hidden;
            text-align:center;
            color:#f3f8ff;
            border:1px solid rgba(22,135,237,.35);
            border-radius:9px;
            background:linear-gradient(180deg,#12366e 0%,#123d82 68%,#075dcc 100%);
            box-shadow:inset 0 -42px 45px rgba(0,153,255,.4), inset 0 0 24px rgba(0,126,255,.14);
        }
        #modalTipoCrear .cfg-modal-side::after { content:''; position:absolute; }
        #modalTipoCrear .cfg-modal-side svg { width:68px; height:68px; margin-bottom:8px; color:#00b7ff; filter:drop-shadow(0 0 8px rgba(0,178,255,.65)); }
        #modalTipoCrear .cfg-modal-side strong { font-size:14px; line-height:1.1; }
        #modalTipoCrear .cfg-modal-side span { max-width:130px; color:#d9e8ff; font-size:10px; line-height:1.35; }
        #modalTipoCrear .cfg-modal-head h3 { display:block; flex:1; margin:0; line-height:1.2; }
        #modalTipoCrear .cfg-modal-head h3::after { line-height:1.35; }
        [data-theme="light"] #modalTipoCrear .cfg-modal-side { color:#fff; background:linear-gradient(180deg,#164783 0%,#1451a2 68%,#0871d9 100%); }
        @media (max-width:620px) {
            #modalTipoCrear .cfg-modal-side { display:none; }
            #modalTipoCrear .cfg-modal-head h3 { display:block; }
        }
    </style>

    <style>
        /* Restaurar el estilo compacto del modal de tipo de equipo. */
        #modalTipoCrear .cfg-modal-box { display:block; width:min(470px, calc(100vw - 32px)); overflow:visible; border-radius:15px; }
        #modalTipoCrear .cfg-modal-head { display:flex; grid-column:auto; padding:17px 20px; background:linear-gradient(110deg,#123b6d,#0c2649); border-bottom:1px solid rgba(65,116,174,.45); }
        #modalTipoCrear .cfg-modal-head h3 { display:flex; flex:initial; align-items:center; gap:12px; margin:0; color:#f4f8ff; font-size:18px; line-height:normal; }
        #modalTipoCrear .cfg-modal-head h3::before { display:inline-flex; }
        #modalTipoCrear .cfg-modal-head h3::after { content:none; }
        #modalTipoCrear .cfg-modal-body { grid-column:auto; padding:22px 20px; border-left:0; }
        #modalTipoCrear .cfg-modal-body .eq-mt { margin-top:18px; padding-top:0; border-top:0; }
        #modalTipoCrear .cfg-modal-foot { grid-column:auto; padding:15px 20px; background:rgba(5,17,34,.55); border-top:1px solid rgba(65,116,174,.4); }
        [data-theme="light"] #modalTipoCrear .cfg-modal-head { background:linear-gradient(110deg,#eaf4ff,#f5f9ff); border-bottom-color:#d4e3f0; }
        [data-theme="light"] #modalTipoCrear .cfg-modal-foot { background:#f5f9fd; border-top-color:#d4e3f0; }
        @media (max-width:620px) {
            #modalTipoCrear .cfg-modal-box { display:block; width:min(470px, calc(100vw - 32px)); }
        }
    </style>

    <style>
        /* Modal Nuevo subtipo: formulario compacto con iconos y jerarquia visual. */
        #modalSubtipoCrear .cfg-modal-box { width:min(570px, calc(100vw - 32px)); border:1px solid #1687ed; border-radius:15px; background:linear-gradient(180deg,#0b2344 0%,#07172d 100%); box-shadow:0 0 0 1px rgba(0,126,255,.18),0 24px 70px rgba(0,0,0,.58),0 0 24px rgba(0,126,255,.18); }
        #modalSubtipoCrear .cfg-modal-head { gap:12px; padding:17px 20px; background:linear-gradient(110deg,#123b6d,#0c2649); border-bottom-color:rgba(65,116,174,.45); }
        #modalSubtipoCrear .cfg-modal-head h3 { display:flex; align-items:center; gap:12px; color:#f4f8ff; font-size:18px; font-weight:800; }
        #modalSubtipoCrear .cfg-modal-head h3::before { content:''; display:inline-flex; width:38px; height:38px; border:1px solid #1591ff; border-radius:11px; background:#0e5595 url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%2362c1ff' stroke-width='1.8' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='m4 7 8-4 8 4-8 4-8-4Z'/%3E%3Cpath d='m4 12 8 4 8-4M4 17l8 4 8-4'/%3E%3C/svg%3E") center/22px 22px no-repeat; box-shadow:0 0 0 5px rgba(0,126,255,.1); }
        #modalSubtipoCrear .cfg-modal-body { padding:22px 20px; }
        #modalSubtipoCrear .cfg-modal-body label { color:#e8f2ff; font-size:13px; font-weight:700; }
        #modalSubtipoCrear .cfg-modal-body select,
        #modalSubtipoCrear .cfg-modal-body input[type="text"] { padding:11px 12px 11px 42px; border-color:#1687ed; background-color:#102b4d; color:#f3f8ff; box-shadow:0 0 0 3px rgba(0,126,255,.12); }
        #modalSubtipoCrear .cfg-modal-body select:focus,
        #modalSubtipoCrear .cfg-modal-body input[type="text"]:focus { border-color:#40b0ff; box-shadow:0 0 0 3px rgba(0,126,255,.2); }
        #modalSubtipoCrear .cfg-modal-body select { background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%237eafd8' stroke-width='1.8' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='m4 7 8-4 8 4-8 4-8-4Z'/%3E%3Cpath d='m4 12 8 4 8-4M4 17l8 4 8-4'/%3E%3C/svg%3E"); background-repeat:no-repeat; background-position:12px center; background-size:20px; }
        #modalSubtipoCrear .cfg-modal-body input[type="text"] { background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%237eafd8' stroke-width='1.8' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='m20 13-7 7-9-9V4h7l9 9Z'/%3E%3Ccircle cx='7.5' cy='7.5' r='1'/%3E%3C/svg%3E"); background-repeat:no-repeat; background-position:12px center; background-size:20px; }
        #modalSubtipoCrear .cfg-modal-body .eq-mt { margin-top:20px; }
        #modalSubtipoCrear .cfg-modal-foot { padding:15px 20px; background:rgba(5,17,34,.55); border-top-color:rgba(65,116,174,.4); }
        #modalSubtipoCrear .cfg-modal-foot .btn { min-height:42px; border-radius:10px; font-weight:700; }
        #modalSubtipoCrear .cfg-modal-foot .btn:not(.btn--ghost) { background:#1687ed; border-color:#1687ed; }
        [data-theme="light"] #modalSubtipoCrear .cfg-modal-box { background:#fff; border-color:#1687ed; box-shadow:0 24px 60px rgba(33,72,111,.24),0 0 0 1px rgba(0,126,255,.12); }
        [data-theme="light"] #modalSubtipoCrear .cfg-modal-head { background:linear-gradient(110deg,#eaf4ff,#f5f9ff); border-bottom-color:#d4e3f0; }
        [data-theme="light"] #modalSubtipoCrear .cfg-modal-head h3 { color:#244765; }
        [data-theme="light"] #modalSubtipoCrear .cfg-modal-body label { color:#315574; }
        [data-theme="light"] #modalSubtipoCrear .cfg-modal-body select,
        [data-theme="light"] #modalSubtipoCrear .cfg-modal-body input[type="text"] { background-color:#fff; color:#244765; border-color:#9bb9d1; }
        [data-theme="light"] #modalSubtipoCrear .cfg-modal-foot { background:#f5f9fd; border-top-color:#d4e3f0; }
    </style>

    <style>
        /* Nueva marca y nuevo modelo comparten el mismo lenguaje visual de Nuevo subtipo. */
        #modalMarcaCrear .cfg-modal-box,
        #modalModeloCrear .cfg-modal-box { width:min(570px, calc(100vw - 32px)); border:1px solid #1687ed; border-radius:15px; background:linear-gradient(180deg,#0b2344 0%,#07172d 100%); box-shadow:0 0 0 1px rgba(0,126,255,.18),0 24px 70px rgba(0,0,0,.58),0 0 24px rgba(0,126,255,.18); }
        #modalMarcaCrear .cfg-modal-head,
        #modalModeloCrear .cfg-modal-head { gap:12px; padding:17px 20px; background:linear-gradient(110deg,#123b6d,#0c2649); border-bottom-color:rgba(65,116,174,.45); }
        #modalMarcaCrear .cfg-modal-head h3,
        #modalModeloCrear .cfg-modal-head h3 { display:flex; align-items:center; gap:12px; color:#f4f8ff; font-size:18px; font-weight:800; }
        #modalMarcaCrear .cfg-modal-head h3::before,
        #modalModeloCrear .cfg-modal-head h3::before { content:''; display:inline-flex; width:38px; height:38px; border:1px solid #1591ff; border-radius:11px; background:#0e5595 center/22px 22px no-repeat; box-shadow:0 0 0 5px rgba(0,126,255,.1); }
        #modalMarcaCrear .cfg-modal-head h3::before { background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%2362c1ff' stroke-width='1.8' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='m20 13-7 7-9-9V4h7l9 9Z'/%3E%3Ccircle cx='7.5' cy='7.5' r='1'/%3E%3C/svg%3E"); }
        #modalModeloCrear .cfg-modal-head h3::before { background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%2362c1ff' stroke-width='1.8' stroke-linecap='round' stroke-linejoin='round'%3E%3Crect x='3' y='4' width='18' height='14' rx='2'/%3E%3Cpath d='M8 21h8M12 18v3'/%3E%3C/svg%3E"); }
        #modalMarcaCrear .cfg-modal-body,
        #modalModeloCrear .cfg-modal-body { padding:22px 20px; }
        #modalMarcaCrear .cfg-modal-body label,
        #modalModeloCrear .cfg-modal-body label { color:#e8f2ff; font-size:13px; font-weight:700; }
        #modalMarcaCrear .cfg-modal-body select,
        #modalMarcaCrear .cfg-modal-body input[type="text"],
        #modalModeloCrear .cfg-modal-body select,
        #modalModeloCrear .cfg-modal-body input[type="text"] { padding:11px 12px 11px 42px; border-color:#1687ed; background-color:#102b4d; color:#f3f8ff; box-shadow:0 0 0 3px rgba(0,126,255,.12); }
        #modalMarcaCrear .cfg-modal-body select:focus,
        #modalMarcaCrear .cfg-modal-body input[type="text"]:focus,
        #modalModeloCrear .cfg-modal-body select:focus,
        #modalModeloCrear .cfg-modal-body input[type="text"]:focus { border-color:#40b0ff; box-shadow:0 0 0 3px rgba(0,126,255,.2); }
        #modalMarcaCrear .cfg-modal-body select,
        #modalModeloCrear .cfg-modal-body select { background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%237eafd8' stroke-width='1.8' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='m4 7 8-4 8 4-8 4-8-4Z'/%3E%3Cpath d='m4 12 8 4 8-4M4 17l8 4 8-4'/%3E%3C/svg%3E"); background-repeat:no-repeat; background-position:12px center; background-size:20px; }
        #modalMarcaCrear .cfg-modal-body input[type="text"],
        #modalModeloCrear .cfg-modal-body input[type="text"] { background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%237eafd8' stroke-width='1.8' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='m20 13-7 7-9-9V4h7l9 9Z'/%3E%3Ccircle cx='7.5' cy='7.5' r='1'/%3E%3C/svg%3E"); background-repeat:no-repeat; background-position:12px center; background-size:20px; }
        #modalMarcaCrear .cfg-modal-body .eq-mt,
        #modalModeloCrear .cfg-modal-body .eq-mt { margin-top:20px; }
        #modalMarcaCrear .cfg-modal-foot,
        #modalModeloCrear .cfg-modal-foot { padding:15px 20px; background:rgba(5,17,34,.55); border-top-color:rgba(65,116,174,.4); }
        #modalMarcaCrear .cfg-modal-foot .btn,
        #modalModeloCrear .cfg-modal-foot .btn { min-height:42px; border-radius:10px; font-weight:700; }
        #modalMarcaCrear .cfg-modal-foot .btn:not(.btn--ghost),
        #modalModeloCrear .cfg-modal-foot .btn:not(.btn--ghost) { background:#1687ed; border-color:#1687ed; }
        [data-theme="light"] #modalMarcaCrear .cfg-modal-box,
        [data-theme="light"] #modalModeloCrear .cfg-modal-box { background:#fff; border-color:#1687ed; box-shadow:0 24px 60px rgba(33,72,111,.24),0 0 0 1px rgba(0,126,255,.12); }
        [data-theme="light"] #modalMarcaCrear .cfg-modal-head,
        [data-theme="light"] #modalModeloCrear .cfg-modal-head { background:linear-gradient(110deg,#eaf4ff,#f5f9ff); border-bottom-color:#d4e3f0; }
        [data-theme="light"] #modalMarcaCrear .cfg-modal-head h3,
        [data-theme="light"] #modalModeloCrear .cfg-modal-head h3,
        [data-theme="light"] #modalMarcaCrear .cfg-modal-body label,
        [data-theme="light"] #modalModeloCrear .cfg-modal-body label { color:#315574; }
        [data-theme="light"] #modalMarcaCrear .cfg-modal-body select,
        [data-theme="light"] #modalMarcaCrear .cfg-modal-body input[type="text"],
        [data-theme="light"] #modalModeloCrear .cfg-modal-body select,
        [data-theme="light"] #modalModeloCrear .cfg-modal-body input[type="text"] { background-color:#fff; color:#244765; border-color:#9bb9d1; }
        [data-theme="light"] #modalMarcaCrear .cfg-modal-foot,
        [data-theme="light"] #modalModeloCrear .cfg-modal-foot { background:#f5f9fd; border-top-color:#d4e3f0; }
    </style>

    <style>
        /* Evita que los SVG del catálogo crezcan al cargar la vista y deformen la tarjeta. */
        .eq-card .catalog-header-icon { width:48px !important; height:48px !important; min-width:48px; min-height:48px; overflow:hidden; }
        .eq-card .catalog-header-icon svg { display:block !important; width:27px !important; height:27px !important; max-width:27px !important; max-height:27px !important; flex:0 0 27px !important; }
        .eq-card .eq-filter-label svg { display:block !important; width:20px !important; height:20px !important; max-width:20px !important; max-height:20px !important; flex:0 0 20px !important; }
        .eq-card .eq-col-icon svg { display:block !important; width:20px !important; height:20px !important; max-width:20px !important; max-height:20px !important; flex:0 0 20px !important; }
        .eq-card .eq-add svg { display:block !important; width:18px !important; height:18px !important; max-width:18px !important; max-height:18px !important; }
    </style>

    <script>
        (function () {
            var mCrear = document.getElementById('modalCatCrear');
            var mEditar = document.getElementById('modalCatEditar');
            var mEliminar = document.getElementById('modalCatEliminar');

            function abrir(modal) {
                if (typeof modal.showModal === 'function') { modal.showModal(); }
                else { modal.setAttribute('open', ''); }
            }

            // Cierra el menu de tres puntos que quedo abierto detras del modal.
            function cerrarMenus() {
                document.querySelectorAll('.congress-menu.open').forEach(function (m) {
                    m.classList.remove('open');
                    m.classList.remove('open-up');
                    var t = m.querySelector('.congress-menu-trigger');
                    if (t) t.setAttribute('aria-expanded', 'false');
                });
            }

            // Cierre comun a todos los modales de la pantalla.
            document.querySelectorAll('.cfg-modal').forEach(function (modal) {
                modal.querySelectorAll('[data-cerrar]').forEach(function (b) {
                    b.addEventListener('click', function () { modal.close(); });
                });
                // Clic en el fondo oscuro: el evento apunta al propio <dialog>.
                modal.addEventListener('click', function (e) {
                    if (e.target === modal) modal.close();
                });
            });

            // ---- Disparadores genericos del catalogo de equipo ----
            // data-modal-abrir dice que modal, data-url a donde envia el
            // formulario y data-valores con que llenarlo.
            var marcasPorSubtipo = window.EQ_MARCAS_POR_SUBTIPO || {};

            function llenarMarcas(modal, marcaElegida) {
                var selSub = modal.querySelector('[data-cascada-subtipo]');
                var selMarca = modal.querySelector('[data-cascada-marca]');
                if (!selSub || !selMarca) return;

                var lista = marcasPorSubtipo[selSub.value] || [];
                selMarca.innerHTML = '';
                if (modal.id === 'modalModeloCrear') {
                    modal.querySelector('button[type="submit"]').disabled = !lista.length;
                }

                if (!lista.length) {
                    var vacio = document.createElement('option');
                    vacio.value = '';
                    vacio.textContent = selSub.value ? 'Ese subtipo aún no tiene marcas' : 'Primero selecciona un subtipo';
                    selMarca.appendChild(vacio);
                    selMarca.disabled = true;
                    return;
                }

                selMarca.disabled = false;
                lista.forEach(function (m) {
                    var op = document.createElement('option');
                    op.value = m.id;
                    op.textContent = m.name;
                    selMarca.appendChild(op);
                });

                if (marcaElegida) selMarca.value = marcaElegida;
            }

            document.querySelectorAll('[data-cascada-subtipo]').forEach(function (sel) {
                sel.addEventListener('change', function () {
                    llenarMarcas(sel.closest('.cfg-modal'), null);
                });
            });

            document.querySelectorAll('[data-modal-abrir]').forEach(function (trigger) {
                trigger.addEventListener('click', function (e) {
                    e.preventDefault();

                    var modal = document.getElementById(trigger.dataset.modalAbrir);
                    if (!modal) return;

                    cerrarMenus();

                    var form = modal.querySelector('[data-form]');

                    if (trigger.dataset.url) form.action = trigger.dataset.url;

                    var valores = {};
                    try { valores = JSON.parse(trigger.dataset.valores || '{}'); } catch (err) { valores = {}; }

                    // Primero los campos normales; la marca se llena al final,
                    // cuando el subtipo ya quedo elegido.
                    Object.keys(valores).forEach(function (clave) {
                        if (clave === 'brand_id') return;

                        var destinoTexto = modal.querySelector('[data-texto="' + clave + '"]');
                        if (destinoTexto) { destinoTexto.textContent = valores[clave]; return; }

                        // Un checkbox comparte nombre con su hidden "0": se
                        // marca o desmarca en vez de asignarle valor.
                        var campos = form.querySelectorAll('[name="' + clave + '"]');
                        campos.forEach(function (campo) {
                            if (campo.type === 'checkbox') {
                                campo.checked = valores[clave] === true || valores[clave] === 1 || valores[clave] === '1';
                            } else if (campo.type !== 'hidden' || campos.length === 1) {
                                campo.value = valores[clave];
                            }
                        });
                    });

                    llenarMarcas(modal, valores.brand_id || null);

                    abrir(modal);

                    var primero = form.querySelector('input[type="text"], select');
                    if (primero) primero.focus();
                });
            });

            var btnCrear = document.querySelector('[data-cat-crear]');
            if (btnCrear) {
                btnCrear.addEventListener('click', function (e) {
                    e.preventDefault();
                    var input = document.getElementById('catCrearNombre');
                    input.value = '';
                    abrir(mCrear);
                    input.focus();
                });
            }

            document.querySelectorAll('[data-cat-editar]').forEach(function (a) {
                a.addEventListener('click', function (e) {
                    e.preventDefault();
                    cerrarMenus();
                    var form = mEditar.querySelector('[data-form]');
                    form.action = a.dataset.url;
                    form.querySelector('[data-url-field]').value = a.dataset.url;
                    var input = document.getElementById('catEditarNombre');
                    input.value = a.dataset.nombre || '';
                    abrir(mEditar);
                    input.focus();
                    input.select();
                });
            });

            document.querySelectorAll('[data-cat-eliminar]').forEach(function (a) {
                a.addEventListener('click', function (e) {
                    e.preventDefault();
                    cerrarMenus();
                    var form = mEliminar.querySelector('[data-form]');
                    form.action = a.dataset.url;
                    mEliminar.querySelector('[data-nombre]').textContent = a.dataset.nombre || '—';
                    abrir(mEliminar);
                });
            });

            // Si la validacion fallo, se vuelve a abrir el modal donde ocurrio.
            var reabrir = document.getElementById('cfgReabrir');
            if (reabrir) {
                if (reabrir.dataset.modal === 'crear') {
                    abrir(mCrear);
                    document.getElementById('catCrearNombre').focus();
                } else if (reabrir.dataset.modal === 'editar' && reabrir.dataset.url) {
                    var formE = mEditar.querySelector('[data-form]');
                    formE.action = reabrir.dataset.url;
                    formE.querySelector('[data-url-field]').value = reabrir.dataset.url;
                    var i = document.getElementById('catEditarNombre');
                    i.value = @json(old('name'));
                    abrir(mEditar);
                    i.focus();
                }
            }
        })();
    </script>

    <script>
        (function () {
            // ---- Buscador de categorías ----
            var catBtn = document.getElementById('catalogSearchBtn');
            var catWrap = document.getElementById('catalogSearchWrap');
            var catInput = document.getElementById('catalogSearchInput');
            var catList = document.getElementById('categoryList');
            var catNoResult = document.getElementById('catalogNoResult');
            if (catBtn && catWrap && catInput) {
                var catItems = catList ? catList.querySelectorAll('.category-item') : [];
                catBtn.addEventListener('click', function () {
                    var open = catWrap.classList.toggle('open');
                    catBtn.setAttribute('aria-expanded', open ? 'true' : 'false');
                    if (open) { catInput.focus(); } else { catInput.value = ''; filterCats(''); }
                });
                function filterCats(q) {
                    q = q.trim().toLowerCase();
                    var visible = 0;
                    catItems.forEach(function (it) {
                        var match = it.getAttribute('data-search').indexOf(q) !== -1;
                        it.style.display = match ? '' : 'none';
                        if (match) visible++;
                    });
                    if (catNoResult) catNoResult.style.display = visible === 0 ? 'block' : 'none';
                }
                catInput.addEventListener('input', function () { filterCats(this.value); });
            }

            // ---- Menú de tres puntos (acciones de categoría) ----
            var menus = document.querySelectorAll('.congress-menu');
            function posicionarMenu(menu) {
                var trigger = menu.querySelector('.congress-menu-trigger');
                var dropdown = menu.querySelector('.congress-menu-dropdown');
                var rect = trigger.getBoundingClientRect();
                var altura = dropdown.getBoundingClientRect().height;
                var top = rect.bottom + 4;
                if (top + altura > window.innerHeight && rect.top - altura - 4 >= 0) {
                    top = rect.top - altura - 4;
                }
                menu.style.setProperty('--menu-top', top + 'px');
                menu.style.setProperty('--menu-right', (window.innerWidth - rect.right) + 'px');
            }
            menus.forEach(function (menu) {
                var trigger = menu.querySelector('.congress-menu-trigger');
                trigger.addEventListener('click', function (e) {
                    e.stopPropagation();
                    // Cerrar otros menús abiertos
                    menus.forEach(function (m) {
                        if (m !== menu) {
                            m.classList.remove('open');
                            m.classList.remove('open-up');
                            m.querySelector('.congress-menu-trigger').setAttribute('aria-expanded', 'false');
                        }
                    });
                    // Posición fija para menús dentro de listas con scroll propio,
                    // que si no los recortarían.
                    var open = !menu.classList.contains('open');
                    menu.classList.toggle('open', open);
                    trigger.setAttribute('aria-expanded', open ? 'true' : 'false');
                    if (open && menu.closest('.eq-list')) {
                        posicionarMenu(menu);
                    } else if (open && menu.closest('.category-list')) {
                        var rect = trigger.getBoundingClientRect();
                        var dropdown = menu.querySelector('.congress-menu-dropdown');
                        var altura = dropdown.getBoundingClientRect().height;
                        var top = rect.bottom + 4;
                        if (top + altura > window.innerHeight && rect.top - altura - 4 >= 0) {
                            top = rect.top - altura - 4;
                        }
                        menu.style.setProperty('--menu-top', top + 'px');
                        menu.style.setProperty('--menu-right', (window.innerWidth - rect.right) + 'px');
                    }
                });
            });
            window.addEventListener('scroll', function () {
                menus.forEach(function (menu) {
                    if (menu.classList.contains('open') && menu.closest('.eq-list')) posicionarMenu(menu);
                });
            }, true);
            // Cerrar menú al hacer clic fuera
            document.addEventListener('click', function () {
                menus.forEach(function (m) {
                    m.classList.remove('open');
                    m.classList.remove('open-up');
                    m.querySelector('.congress-menu-trigger').setAttribute('aria-expanded', 'false');
                });
            });
            // Evitar que el clic dentro del menú lo cierre inmediatamente
            document.querySelectorAll('.congress-menu-dropdown').forEach(function (dd) {
                dd.addEventListener('click', function (e) { e.stopPropagation(); });
            });
        })();
    </script>
@endsection
