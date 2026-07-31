@extends('layouts.dashboard')

@section('title', 'Calendario de contenido · Marketing')
@section('page-title', 'Calendario de contenido')
@section('page-sub', 'Plan editorial visual. Sin vinculación a usuarios del sistema.')

@php
    use Carbon\Carbon;

    $year = request('year', now()->year);
    $month = request('month', now()->month);
    $current = Carbon::create($year, $month, 1);
    $prev = $current->copy()->subMonth();
    $next = $current->copy()->addMonth();
    $monthLabel = ucfirst($current->translatedFormat('F Y'));

    $start = $current->copy()->startOfMonth()->startOfWeek(Carbon::MONDAY);
    $end = $current->copy()->endOfMonth()->endOfWeek(Carbon::SUNDAY);
    $days = [];
    $cursor = $start->copy();
    while ($cursor->lte($end)) {
        $days[] = $cursor->copy();
        $cursor->addDay();
    }
    $weeks = array_chunk($days, 7);

    $categories = [
        'educacion' => ['label' => 'Educación', 'color' => '#3b82f6'],
        'equipos' => ['label' => 'Equipos', 'color' => '#f97316'],
        'aplicaciones' => ['label' => 'Aplicaciones', 'color' => '#ec4899'],
        'antes_despues' => ['label' => 'Antes/después congreso', 'color' => '#22c55e'],
        'congreso' => ['label' => 'Congreso', 'color' => '#15803d'],
        'conversion' => ['label' => 'Conversión', 'color' => '#8b5cf6'],
        'fechas' => ['label' => 'Fechas especiales', 'color' => '#06b6d4'],
        'promo' => ['label' => 'Promo', 'color' => '#eab308'],
        'publicacion' => ['label' => 'Publicación', 'color' => '#f43f5e'],
        'revision' => ['label' => 'Revisión', 'color' => '#f59e0b'],
    ];
@endphp

@section('content')
    <style>
        :root {
            --ag-bg: #070c17;
            --ag-surface: #0f1a30;
            --ag-surface-2: #111c36;
            --ag-surface-3: #162444;
            --ag-border: rgba(90, 140, 230, 0.18);
            --ag-text: #e8eef8;
            --ag-muted: #93a4bd;
            --ag-primary: #0a84ff;
        }

        [data-theme="light"] .agenda {
            --ag-bg: #f6f7f9;
            --ag-surface: #ffffff;
            --ag-surface-2: #f7f8fa;
            --ag-surface-3: #eef2f7;
            --ag-border: #e2e8f0;
            --ag-text: #1e293b;
            --ag-muted: #64748b;
        }

        .agenda { color: var(--ag-text); font-size: 14px; }
        .ag-breadcrumb { display: flex; align-items: center; gap: 8px; font-size: 12px; color: var(--ag-muted); margin-bottom: 22px; text-transform: uppercase; letter-spacing: 0.06em; font-weight: 700; }
        .ag-breadcrumb a { color: var(--ag-muted); text-decoration: none; transition: color 0.15s ease; }
        .ag-breadcrumb a:hover { color: var(--ag-primary); }
        .ag-breadcrumb svg { width: 14px; height: 14px; opacity: 0.7; }

        .ag-header { display: flex; align-items: flex-start; justify-content: space-between; gap: 20px; flex-wrap: wrap; margin-bottom: 26px; }
        .ag-pill { display: inline-flex; align-items: center; gap: 6px; padding: 5px 12px; border-radius: 20px; background: rgba(10, 132, 255, 0.10); color: var(--ag-primary); border: 1px solid var(--ag-border); font-size: 11px; font-weight: 800; letter-spacing: 0.07em; text-transform: uppercase; margin-bottom: 12px; }
        .ag-title { font-size: 34px; font-weight: 800; margin: 0 0 8px; letter-spacing: -0.02em; }
        .ag-subtitle { color: var(--ag-muted); font-size: 15px; line-height: 1.6; margin: 0; max-width: 680px; }

        .ag-nav { display: flex; align-items: center; gap: 12px; background: linear-gradient(145deg, rgba(15, 26, 48, 0.95), rgba(17, 28, 54, 0.90)); border: 1px solid var(--ag-border); border-radius: 14px; padding: 8px 12px; }
        .ag-nav__btn, .ag-nav__today { display: flex; align-items: center; justify-content: center; padding: 8px 14px; border-radius: 10px; border: 1px solid var(--ag-border); background: var(--ag-surface-2); color: var(--ag-text); cursor: pointer; font-size: 13px; font-weight: 700; text-decoration: none; transition: background 0.15s ease; }
        .ag-nav__btn:hover, .ag-nav__today:hover { background: var(--ag-surface-3); }
        .ag-nav__btn svg { width: 16px; height: 16px; }
        .ag-nav__month { font-size: 16px; font-weight: 800; min-width: 110px; text-align: center; }

        .ag-filters { display: flex; flex-wrap: wrap; align-items: center; gap: 10px; margin-bottom: 24px; }
        .ag-filter-title { font-size: 12px; font-weight: 700; color: var(--ag-muted); min-width: 80px; text-transform: uppercase; letter-spacing: 0.04em; }
        .ag-chip { display: inline-flex; align-items: center; gap: 6px; padding: 6px 12px; border-radius: 20px; border: 1px solid var(--ag-border); background: var(--ag-surface-2); color: var(--ag-text); font-size: 12px; font-weight: 700; cursor: pointer; transition: transform 0.15s ease; user-select: none; }
        .ag-chip:hover { transform: translateY(-1px); }
        .ag-chip.active { background: var(--ag-surface-3); box-shadow: 0 0 0 1px var(--ag-primary); }
        .ag-chip__dot { width: 8px; height: 8px; border-radius: 50%; }

        .ag-calendar { background: linear-gradient(145deg, rgba(15, 26, 48, 0.95), rgba(17, 28, 54, 0.90)); border: 1px solid var(--ag-border); border-radius: 20px; padding: 20px; box-shadow: 0 8px 26px rgba(0, 0, 0, 0.18); overflow-x: auto; }
        .ag-calendar__grid { display: grid; grid-template-columns: repeat(7, 1fr); min-width: 900px; }
        .ag-day-head { padding: 14px 10px; text-align: center; font-size: 12px; font-weight: 800; color: var(--ag-muted); text-transform: uppercase; letter-spacing: 0.06em; background: var(--ag-surface-3); border-bottom: 1px solid var(--ag-border); }
        .ag-day-head:first-child { border-top-left-radius: 14px; }
        .ag-day-head:last-child { border-top-right-radius: 14px; }

        .ag-day-cell { min-height: 130px; border-right: 1px solid var(--ag-border); border-bottom: 1px solid var(--ag-border); padding: 10px; background: var(--ag-surface); display: flex; flex-direction: column; gap: 8px; transition: background 0.15s ease; cursor: pointer; }
        .ag-day-cell:nth-child(7n) { border-right: none; }
        .ag-day-cell:hover { background: var(--ag-surface-2); }
        .ag-day-cell.out-month { background: rgba(15, 26, 48, 0.55); }
        .ag-day-cell.today { box-shadow: inset 0 0 0 2px var(--ag-primary); }
        .ag-day-cell.today .ag-day__num { background: var(--ag-primary); color: #fff; }
        .ag-day__num { width: 28px; height: 28px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 13px; font-weight: 700; color: var(--ag-muted); margin-bottom: 4px; }
        .ag-day-cell.in-month .ag-day__num { color: var(--ag-text); }
        .ag-events { display: flex; flex-direction: column; gap: 6px; }
        .ag-event { padding: 8px 10px; border-radius: 10px; background: rgba(255, 255, 255, 0.04); border-left: 3px solid var(--event-color, var(--ag-primary)); cursor: pointer; transition: transform 0.12s ease, background 0.12s ease; }
        .ag-event:hover { transform: translateX(2px); background: rgba(255, 255, 255, 0.08); }
        .ag-event__title { font-size: 12px; font-weight: 700; margin: 0 0 3px; line-height: 1.35; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
        .ag-event__meta { display: flex; align-items: center; justify-content: space-between; gap: 6px; }
        .ag-event__time { font-size: 10px; color: var(--ag-muted); font-weight: 700; }
        .ag-event__assign { font-size: 10px; color: var(--ag-muted); font-weight: 700; }
        .ag-day__more { font-size: 10px; color: var(--ag-primary); font-weight: 700; text-align: right; cursor: pointer; }

        .ag-modal { display: none; position: fixed; inset: 0; background: rgba(0, 0, 0, 0.55); align-items: center; justify-content: center; z-index: 100; }
        .ag-modal.active { display: flex; }
        .ag-dialog { background: var(--ag-surface); border: 1px solid var(--ag-border); border-radius: 18px; width: 100%; max-width: 460px; padding: 24px; box-shadow: 0 20px 50px rgba(0, 0, 0, 0.35); }
        .ag-dialog h3 { margin: 0 0 18px; font-size: 20px; font-weight: 800; }
        .ag-field { margin-bottom: 16px; }
        .ag-field label { display: block; font-size: 12px; font-weight: 800; margin-bottom: 6px; text-transform: uppercase; letter-spacing: 0.03em; }
        .ag-field input, .ag-field select, .ag-field textarea { width: 100%; padding: 12px 14px; border: 1px solid var(--ag-border); border-radius: 12px; background: var(--ag-surface-2); color: var(--ag-text); font-family: inherit; font-size: 14px; outline: none; }
        .ag-field input:focus, .ag-field select:focus, .ag-field textarea:focus { border-color: var(--ag-primary); }
        .ag-field textarea { resize: vertical; min-height: 80px; }
        .ag-actions { display: flex; align-items: center; justify-content: flex-end; gap: 12px; margin-top: 22px; }
        .ag-actions button { padding: 12px 20px; border-radius: 12px; border: 1px solid var(--ag-border); background: transparent; color: var(--ag-text); cursor: pointer; font-size: 13px; font-weight: 700; }
        .ag-actions .save { background: linear-gradient(135deg, #0a84ff, #7c3aed); color: #fff; border-color: transparent; }
        .ag-actions .delete { color: var(--ag-rose, #f43f5e); margin-right: auto; }
    </style>

    <div class="agenda">
        <div class="ag-breadcrumb">
            <a href="{{ route('dashboard') }}">Inicio</a>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
            <span>Marketing</span>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
            <span>Calendario</span>
        </div>

        <div class="ag-header">
            <div>
                <div class="ag-pill">Plan editorial · {{ $monthLabel }}</div>
                <h1 class="ag-title">Calendario de contenido</h1>
                <p class="ag-subtitle">Plan visual del mes. Sin vínculo con usuarios del sistema. Clic en un día para agregar o en una tarjeta para editar.</p>
            </div>
            <div class="ag-nav">
                <a class="ag-nav__btn" href="{{ route('marketing.agenda.index', ['year' => $prev->year, 'month' => $prev->month]) }}" aria-label="Mes anterior"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg></a>
                <div class="ag-nav__month">{{ $monthLabel }}</div>
                <a class="ag-nav__btn" href="{{ route('marketing.agenda.index', ['year' => $next->year, 'month' => $next->month]) }}" aria-label="Mes siguiente"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg></a>
                <a class="ag-nav__today" href="{{ route('marketing.agenda.index') }}">Hoy</a>
            </div>
        </div>

        <div class="ag-filters">
            <span class="ag-filter-title">Categorías</span>
            @foreach ($categories as $key => $cat)
                <span class="ag-chip" data-filter="{{ $key }}" onclick="toggleFilter(this)">
                    <span class="ag-chip__dot" style="background: {{ $cat['color'] }}"></span>
                    {{ $cat['label'] }}
                </span>
            @endforeach
        </div>

        <div class="ag-calendar">
            <div class="ag-calendar__grid">
                <div class="ag-day-head">Lun</div>
                <div class="ag-day-head">Mar</div>
                <div class="ag-day-head">Mie</div>
                <div class="ag-day-head">Jue</div>
                <div class="ag-day-head">Vie</div>
                <div class="ag-day-head">Sáb</div>
                <div class="ag-day-head">Dom</div>

                @foreach ($weeks as $week)
                    @foreach ($week as $day)
                        @php
                            $inMonth = $day->month === $current->month;
                            $isToday = $day->isToday();
                            $dateKey = $day->format('Y-m-d');
                        @endphp
                        <div class="ag-day-cell {{ $inMonth ? 'in-month' : 'out-month' }} {{ $isToday ? 'today' : '' }}" data-date="{{ $dateKey }}" onclick="openAgendaDay(this)">
                            <div class="ag-day__num">{{ $day->day }}</div>
                            <div class="ag-events" id="events-{{ $dateKey }}"></div>
                        </div>
                    @endforeach
                @endforeach
            </div>
        </div>
    </div>

    <div class="ag-modal" id="agendaModal" onclick="closeModal(event)">
        <div class="ag-dialog" onclick="event.stopPropagation()">
            <h3 id="modalTitle">Agregar actividad</h3>
            <form id="agendaForm" onsubmit="return false;">
                <input type="hidden" id="eventId">
                <input type="hidden" id="eventDate">

                <div class="ag-field">
                    <label for="eventTitle">Título *</label>
                    <input type="text" id="eventTitle" required placeholder="Ej. Publicación Instagram">
                </div>

                <div class="ag-field">
                    <label for="eventTime">Hora</label>
                    <input type="time" id="eventTime">
                </div>

                <div class="ag-field">
                    <label for="eventCategory">Categoría</label>
                    <select id="eventCategory">
                        @foreach ($categories as $key => $cat)
                            <option value="{{ $key }}">{{ $cat['label'] }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="ag-field">
                    <label for="eventUserId">Asignado a</label>
                    <select id="eventUserId">
                        @foreach ($users as $user)
                            <option value="{{ $user->id }}">{{ $user->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="ag-field">
                    <label for="eventNote">Nota</label>
                    <textarea id="eventNote" placeholder="Detalles opcionales..."></textarea>
                </div>

                <div class="ag-actions">
                    <button type="button" class="delete" id="btnDelete" onclick="deleteEvent()" style="display:none;">Eliminar</button>
                    <button type="button" onclick="closeModal()">Cancelar</button>
                    <button type="button" class="save" onclick="saveEvent()">Guardar</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const STORAGE_KEY = 'agenda_events_v4';
        const categories = @json($categories);
        const users = @json($users);

        function getUserName(userId) {
            const user = users.find(u => u.id == userId);
            return user ? user.name : '';
        }

        function loadEvents() {
            try { return JSON.parse(localStorage.getItem(STORAGE_KEY)) || {}; }
            catch (e) { return {}; }
        }

        function saveEvents(events) { localStorage.setItem(STORAGE_KEY, JSON.stringify(events)); }

        function getActiveFilters() {
            return Array.from(document.querySelectorAll('.ag-chip.active')).map(c => c.dataset.filter);
        }

        function renderEvents() {
            const events = loadEvents();
            const filters = getActiveFilters();

            document.querySelectorAll('.ag-events').forEach(el => el.innerHTML = '');

            Object.keys(events).forEach(date => {
                const container = document.getElementById('events-' + date);
                if (!container) return;

                const visible = events[date].filter(ev => {
                    if (filters.length === 0) return true;
                    return filters.includes(ev.category);
                });

                visible.slice(0, 3).forEach(ev => {
                    const catColor = categories[ev.category]?.color || '#999';

                    const div = document.createElement('div');
                    div.className = 'ag-event';
                    div.style.setProperty('--event-color', catColor);
                    div.dataset.id = ev.id;
                    div.dataset.date = date;
                    const userName = getUserName(ev.userId);
                    div.innerHTML = `
                        <div class="ag-event__title">${ev.title}</div>
                        <div class="ag-event__meta">
                            <span class="ag-event__time">${ev.time || ''}</span>
                            <span class="ag-event__assign">${userName}</span>
                        </div>
                    `;
                    div.onclick = function (e) { e.stopPropagation(); openEditEvent(date, ev.id); };
                    container.appendChild(div);
                });

                if (visible.length > 3) {
                    const more = document.createElement('div');
                    more.className = 'ag-day__more';
                    more.textContent = '+' + (visible.length - 3) + ' más';
                    container.appendChild(more);
                }
            });
        }

        function openAgendaDay(cell) {
            const date = cell.dataset.date;
            document.getElementById('agendaForm').reset();
            document.getElementById('eventId').value = '';
            document.getElementById('eventDate').value = date;
            document.getElementById('modalTitle').textContent = 'Agregar actividad · ' + formatDate(date);
            document.getElementById('btnDelete').style.display = 'none';
            document.getElementById('agendaModal').classList.add('active');
        }

        function openEditEvent(date, id) {
            const events = loadEvents();
            const ev = (events[date] || []).find(e => e.id === id);
            if (!ev) return;

            document.getElementById('eventId').value = ev.id;
            document.getElementById('eventDate').value = date;
            document.getElementById('eventTitle').value = ev.title;
            document.getElementById('eventTime').value = ev.time || '';
            document.getElementById('eventCategory').value = ev.category;
            document.getElementById('eventUserId').value = ev.userId || '';
            document.getElementById('eventNote').value = ev.note || '';
            document.getElementById('modalTitle').textContent = 'Editar actividad · ' + formatDate(date);
            document.getElementById('btnDelete').style.display = 'inline-block';
            document.getElementById('agendaModal').classList.add('active');
        }

        function saveEvent() {
            const date = document.getElementById('eventDate').value;
            const title = document.getElementById('eventTitle').value.trim();
            const time = document.getElementById('eventTime').value;
            const category = document.getElementById('eventCategory').value;
            const userId = document.getElementById('eventUserId').value;
            const note = document.getElementById('eventNote').value.trim();

            if (!title) return;

            const events = loadEvents();
            if (!events[date]) events[date] = [];

            const id = document.getElementById('eventId').value;

            if (id) {
                const idx = events[date].findIndex(e => e.id === id);
                if (idx >= 0) events[date][idx] = { id, title, time, category, userId, note };
            } else {
                events[date].push({ id: Date.now().toString(), title, time, category, userId, note });
            }

            saveEvents(events);
            closeModal();
            renderEvents();
        }

        function deleteEvent() {
            const date = document.getElementById('eventDate').value;
            const id = document.getElementById('eventId').value;
            if (!id) return;

            const events = loadEvents();
            if (events[date]) {
                events[date] = events[date].filter(e => e.id !== id);
                if (events[date].length === 0) delete events[date];
            }

            saveEvents(events);
            closeModal();
            renderEvents();
        }

        function closeModal(e) {
            if (e && e.target !== e.currentTarget) return;
            document.getElementById('agendaModal').classList.remove('active');
        }

        function toggleFilter(chip) { chip.classList.toggle('active'); renderEvents(); }

        function formatDate(dateStr) {
            const [y, m, d] = dateStr.split('-');
            return d + '/' + m + '/' + y;
        }

        document.addEventListener('DOMContentLoaded', renderEvents);
    </script>
@endsection
