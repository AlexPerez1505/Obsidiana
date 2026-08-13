@extends('layouts.dashboard')

@section('title', 'Equipos')
@section('page-title', 'Equipos')
@section('page-sub', 'Gestion de Inventario > Equipos')

@push('head')
<style>
    .equipment-page { display: grid; gap: 18px; }
    .equipment-head {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 16px;
        flex-wrap: wrap;
    }
    .equipment-head p { margin: 0; color: #718096; font-size: 14px; font-weight: 600; }
    .equipment-create {
        min-height: 38px;
        padding: 0 14px;
        border-radius: 4px;
        background: #158be8;
        color: #fff;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        text-decoration: none;
        font-size: 12px;
        font-weight: 900;
        box-shadow: 0 7px 16px rgba(21, 139, 232, .22);
        white-space: nowrap;
    }
    .equipment-create:hover { background: #0879d0; }
    .equipment-filters {
        display: flex;
        gap: 10px;
        align-items: center;
        flex-wrap: wrap;
    }
    .equipment-filters input,
    .equipment-filters select {
        min-height: 40px;
        padding: 0 14px;
        border: 1px solid var(--border);
        border-radius: 10px;
        background: var(--surface);
        color: var(--text);
        font: inherit;
        font-size: 13px;
        outline: none;
        transition: border .15s, box-shadow .15s;
    }
    .equipment-filters input:focus,
    .equipment-filters select:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(0,122,255,.12); }
    .equipment-filters input { min-width: 260px; }
    .equipment-table-panel {
        overflow: hidden;
        border: 1px solid #a8c5ff;
        border-radius: 5px;
        background: #fff;
    }
    .equipment-table-wrap { overflow-x: auto; }
    .equipment-table {
        width: 100%;
        min-width: 940px;
        border-collapse: collapse;
        color: #202938;
        font-size: 13px;
    }
    .equipment-table th {
        padding: 17px 16px;
        background: #d8e2ff;
        color: #111827;
        font-size: 12px;
        font-weight: 900;
        text-align: left;
        border-bottom: 1px solid #a8c5ff;
    }
    .equipment-table td {
        height: 64px;
        padding: 11px 16px;
        border-bottom: 1px solid #a8c5ff;
        background: #fff;
        vertical-align: middle;
        font-weight: 600;
    }
    .state-pill {
        min-width: 70px;
        min-height: 24px;
        padding: 0 10px;
        border-radius: 999px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 12px;
        font-weight: 800;
        line-height: 1;
        white-space: nowrap;
    }
    .state-pill.green { color: #16a329; border: 1px solid #22c943; background: #f7fff8; }
    .state-pill.blue { color: #1689ff; border: 1px solid #1689ff; background: #f5fbff; }
    .state-pill.red { color: #ff3131; border: 1px solid #ff4b4b; background: #fff8f8; }
    .action-dots { position: relative; display: inline-block; }
    .dots-btn {
        width: 32px; height: 32px; border: 0; border-radius: 50%;
        background: transparent; color: #111827; font-size: 1.35rem; line-height: 1;
        cursor: pointer; display: inline-flex; align-items: center; justify-content: center;
    }
    .dots-btn:hover { background: #eef4ff; color: #0879d0; }
    .action-menu {
        display: none; position: absolute; right: 0; top: calc(100% + 6px);
        background: #fff; border: 1px solid #e2e8f0; border-radius: 12px;
        box-shadow: 0 10px 28px rgba(17,24,39,.12); z-index: 20;
        min-width: 170px; padding: 6px; overflow: hidden; flex-direction: column;
    }
    .action-dots.open .action-menu { display: flex; }
    .action-menu a, .action-menu button {
        display: flex; align-items: center; gap: 8px; width: 100%; padding: 9px 12px;
        border: none; background: none; border-radius: 8px;
        color: #111827; font-size: 0.85rem; font-weight: 700;
        text-align: left; text-decoration: none; cursor: pointer; font-family: inherit;
    }
    .action-menu a:hover, .action-menu button:hover { background: #eef4ff; color: #0879d0; }
    .action-menu button.danger, .action-menu form button { color: #ef4444; }
    .action-menu svg { width: 16px; height: 16px; flex: 0 0 auto; }

    .qr-modal { display:none; position:fixed; inset:0; background:rgba(0,0,0,.5); z-index:130; align-items:center; justify-content:center; backdrop-filter: blur(5px); }
    .qr-modal.show { display:flex; }
    .qr-modal__panel { position:relative; background:#fff; border-radius:20px; padding:26px 28px; width:min(380px, calc(100% - 40px)); box-shadow:0 24px 60px rgba(0,0,0,.25); text-align:center; }
    .qr-modal__close { position:absolute; top:12px; right:14px; width:32px; height:32px; border:none; background:#f3f4f6; color:#6b7280; font-size:1.4rem; border-radius:50%; cursor:pointer; }
    .qr-modal__close:hover { background:#e5e7eb; }
    .qr-modal__panel h3 { margin:0 0 4px; font-size:1.1rem; color:#111827; }
    .qr-modal__subtitle { margin:0 0 18px; color:#6b7280; font-size:.85rem; }
    .qr-modal__image { min-height:200px; display:grid; place-items:center; border:1px dashed #e5e7eb; border-radius:14px; margin-bottom:18px; background:#f9fafb; padding:18px; }
    .qr-modal__image img { max-width:100%; max-height:260px; display:block; }
    .qr-modal__actions { display:flex; gap:10px; justify-content:center; flex-wrap:wrap; }
    .qr-modal__actions button { padding:10px 16px; border:none; border-radius:10px; font-size:.9rem; font-weight:700; cursor:pointer; }
    .qr-modal__actions .btn-primary { background:#3b82f6; color:#fff; }
    .qr-modal__actions .btn-primary:hover { background:#2563eb; }
    .qr-modal__actions .btn-ghost { background:#f3f4f6; color:#374151; }
    .qr-modal__actions .btn-ghost:hover { background:#e5e7eb; }
    .qr-modal__actions .btn-success { background:#22c55e; color:#fff; }
    .qr-modal__actions .btn-success:hover { background:#16a34a; }
    .equipment-foot {
        min-height: 40px;
        padding: 0 16px;
        background: #d7e9ff;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 14px;
        color: #1689ff;
        font-size: 12px;
        font-weight: 700;
    }
    :root[data-theme="dark"] .equipment-table-panel,
    :root[data-theme="dark"] .equipment-filters input,
    :root[data-theme="dark"] .equipment-filters select,
    :root[data-theme="dark"] .equipment-table td { background: var(--surface); color: var(--text); border-color: var(--border); }
    :root[data-theme="dark"] .equipment-table th { background: rgba(10, 132, 255, .18); color: var(--text); border-color: var(--border); }
</style>
@endpush

@section('content')
    <div class="equipment-page">
        <div class="equipment-head">
            <div>
                <h2 class="page-title">Equipos</h2>
                <p>Administración de los equipos registrados en el inventario.</p>
            </div>
            <a href="{{ route('inventory.equipos.create') }}" class="equipment-create">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 5v14M5 12h14"/></svg>
                Agregar equipo
            </a>
        </div>

        <form class="equipment-filters" id="equipmentFilterForm" method="GET" action="{{ route('inventory.equipos.index') }}">
            <input type="text" name="search" id="equipmentSearch" value="{{ $filters['search'] ?? '' }}" placeholder="Buscar equipo, serie, marca o modelo..." style="min-width:260px;">
            <select name="estado" id="equipmentEstado">
                <option value="">Todos los estados</option>
                @foreach($estados as $estado)
                    <option value="{{ $estado }}" @selected(($filters['estado'] ?? '') === $estado)>{{ $estado }}</option>
                @endforeach
            </select>
            <a href="{{ route('inventory.equipos.index') }}" class="btn btn--ghost" style="min-height:40px; text-decoration:none;">Limpiar</a>
        </form>

        @include('structure.gestion_Inventario.equipos._table')
    </div>

    {{-- Modal del código QR --}}
    <div class="qr-modal" id="qrModal">
        <div class="qr-modal__panel">
            <button type="button" class="qr-modal__close" onclick="closeQrModal()">&times;</button>
            <h3>Generar código QR</h3>
            <p class="qr-modal__subtitle" id="qrModalSubtitle">Serie: —</p>
            <div class="qr-modal__image">
                <img id="qrImage" src="" alt="Código QR del equipo">
            </div>
            <div class="qr-modal__actions">
                <button class="btn-ghost" onclick="closeQrModal()">Cerrar</button>
                <button class="btn-primary" onclick="printQr()">Imprimir</button>
                <button class="btn-success" onclick="downloadQr()">Descargar</button>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var form = document.getElementById('equipmentFilterForm');
        var searchInput = document.getElementById('equipmentSearch');
        var searchTimeout;

        function updateTable(html) {
            var parser = new DOMParser();
            var doc = parser.parseFromString(html, 'text/html');
            var newPanel = doc.getElementById('equipmentTablePanel');
            var currentPanel = document.getElementById('equipmentTablePanel');
            if (newPanel && currentPanel) {
                currentPanel.outerHTML = newPanel.outerHTML;
            }
        }

        function fetchEquipos() {
            var url = form.getAttribute('action') + '?' + new URLSearchParams(new FormData(form)).toString();
            var currentPanel = document.getElementById('equipmentTablePanel');
            if (currentPanel) currentPanel.style.opacity = '0.6';

            fetch(url, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin'
            })
            .then(function (r) { return r.text(); })
            .then(function (html) {
                updateTable(html);
                if (window.history.replaceState) {
                    window.history.replaceState({}, '', url);
                }
            })
            .catch(function (err) {
                console.error('Error al buscar equipos:', err);
            })
            .finally(function () {
                var currentPanel = document.getElementById('equipmentTablePanel');
                if (currentPanel) currentPanel.style.opacity = '1';
            });
        }

        if (searchInput) {
            searchInput.addEventListener('input', function () {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(fetchEquipos, 300);
            });
        }

        form.querySelectorAll('select').forEach(function (select) {
            select.addEventListener('change', fetchEquipos);
        });

        var tablePanel = document.getElementById('equipmentTablePanel');
        if (tablePanel) {
            tablePanel.addEventListener('click', function (e) {
                var btn = e.target.closest('.dots-btn');
                if (!btn) return;
                e.stopPropagation();
                var dots = btn.closest('[data-action-dots]');
                var isOpen = dots.classList.contains('open');
                document.querySelectorAll('[data-action-dots].open').forEach(function (d) {
                    d.classList.remove('open');
                });
                if (!isOpen) dots.classList.add('open');
            });
        }

        document.addEventListener('click', function () {
            document.querySelectorAll('[data-action-dots].open').forEach(function (d) {
                d.classList.remove('open');
            });
        });

        window.openQrModal = function (equipoId, serie) {
            var modal = document.getElementById('qrModal');
            var img = document.getElementById('qrImage');
            var subtitle = document.getElementById('qrModalSubtitle');
            img.src = '{{ route('inventory.equipos.qrImage', ['equipo' => '__ID__']) }}'.replace('__ID__', equipoId) + '?t=' + Date.now();
            subtitle.textContent = 'Serie: ' + serie;
            modal.classList.add('show');
        };

        window.closeQrModal = function () {
            document.getElementById('qrModal').classList.remove('show');
        };

        window.printQr = function () {
            var img = document.getElementById('qrImage').src;
            var w = window.open(img, '_blank', 'width=400,height=400');
            if (w) w.print();
        };

        window.downloadQr = function () {
            var img = document.getElementById('qrImage');
            var a = document.createElement('a');
            a.href = img.src;
            a.download = 'qr-equipo-' + document.getElementById('qrModalSubtitle').textContent.replace(/[^a-zA-Z0-9-]/g, '') + '.png';
            document.body.appendChild(a);
            a.click();
            a.remove();
        };

        document.getElementById('qrModal').addEventListener('click', function (e) {
            if (e.target === this) closeQrModal();
        });
    });
</script>
@endpush
