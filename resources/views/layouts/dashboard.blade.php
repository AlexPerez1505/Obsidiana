<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @auth<meta name="acceso-firma" content="{{ auth()->user()->firmaAcceso() }}">@endauth
    <title>@yield('title', 'Panel') · {{ config('app.name') }}</title>
    <link rel="icon" type="image/png" href="{{ asset('images/favicon.png') }}">
    <link rel="manifest" href="{{ asset('manifest.webmanifest') }}">
    <meta name="theme-color" content="#007aff">
    <link rel="apple-touch-icon" href="{{ asset('images/pwa-192.png') }}">

    @stack('head')

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    {{-- Aplica el tema guardado antes de pintar (evita parpadeo) --}}
    <script>
        (function () {
            try {
                var t = localStorage.getItem('theme');
                if (!t) { t = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light'; }
                document.documentElement.setAttribute('data-theme', t);
            } catch (e) {}
        })();
    </script>

    <link rel="stylesheet" href="{{ asset('css/panel.css') }}?v={{ filemtime(public_path('css/panel.css')) }}">
</head>
<body>
@php
    $h = now()->hour;
    $saludo = $h < 12 ? 'Buenos días' : ($h < 19 ? 'Buenas tardes' : 'Buenas noches');
    $u = auth()->user();
    $initials = collect(explode(' ', trim($u->name)))->filter()->take(2)->map(fn($p) => mb_substr($p, 0, 1))->implode('');
    $primerNombre = collect(explode(' ', trim($u->name)))->filter()->first() ?: $u->name;

    // Respaldo del programador de tareas: los recordatorios de seguimiento
    // se disparan desde aquí como máximo una vez cada 10 minutos.
    app(\App\Services\Seguimientos::class)->recordarSiToca();

    $notifs = $u->unreadNotifications()->latest()->limit(8)->get();
    $notifsTotal = $u->unreadNotifications()->count();
@endphp
<div class="app" id="app">
    <div class="overlay" id="overlay"></div>

    {{-- ===================== SIDEBAR ===================== --}}
    <aside class="sidebar">
        <div class="brand">
            <div class="brand-logo">
                {{-- Icono del logo (public/images/logo-icon.png). Si no existe, usa el ícono por defecto. --}}
                <img src="{{ asset('images/logo-icon-web.png') }}" alt="{{ config('app.name') }}"
                     onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                <svg style="display:none" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="20" height="20"><path d="M3 7l9-4 9 4-9 4-9-4z"/><path d="M3 7v10l9 4 9-4V7"/></svg>
            </div>
            <div class="brand-text">
                <div class="brand-name">{{ config('app.name') }}</div>
                <div class="brand-sub">Plataforma empresarial</div>
            </div>
        </div>

        <button class="collapse-btn" id="collapse-btn" type="button" aria-label="Contraer menú" title="Contraer menú">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
        </button>

        <nav class="nav">
            @php
                // El rol Mantenimiento solo ve su tablero y Órdenes de servicio.
                $soloMantenimiento = auth()->user()->hasRole('mantenimiento') && ! auth()->user()->isAdmin();
            @endphp
            @unless ($soloMantenimiento)
                <a class="nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}" data-tip="Dashboard">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/></svg>
                    <span class="nav-label">Dashboard</span>
                </a>
            @endunless
            @if (auth()->user()->isAdmin() || auth()->user()->hasRole('mantenimiento'))
                <a class="nav-item {{ request()->routeIs('mantenimiento.*') ? 'active' : '' }}" href="{{ route('mantenimiento.dashboard') }}" data-tip="Mantenimiento">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>
                    <span class="nav-label">Mantenimiento</span>
                </a>
            @endif
            @unless ($soloMantenimiento)
            <div class="nav-section">Operación</div>
            <div class="nav-group {{ request()->routeIs('commercial.*') && ! request()->routeIs('commercial.comisiones.*') ? 'open' : '' }}">
                <a class="nav-item nav-toggle" href="#" data-tip="Gestión Comercial">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="8" r="3.5"/><path d="M2 20c0-3.5 3-5.5 7-5.5s7 2 7 5.5"/><path d="M17 5a3 3 0 0 1 0 6"/><path d="M20 20c0-2.5-1.3-4.2-3.5-5"/></svg>
                    <span class="nav-label">Gestión Comercial</span>
                    <svg class="nav-chev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><polyline points="6 9 12 15 18 9"/></svg>
                </a>
                <div class="submenu">
                    @can('clientes.ver')
                    <a class="nav-item nav-sub {{ request()->routeIs('commercial.clientes.index') ? 'active' : '' }}" href="{{ route('commercial.clientes.index') }}" data-tip="Clientes">
                        <svg class="nav-bullet" viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="12" r="6"/></svg>
                        <span class="nav-label">Clientes</span>
                    </a>
                    @endcan
                    @can('cotizaciones.ver')
                    <a class="nav-item nav-sub {{ request()->routeIs('commercial.cotizaciones.*') ? 'active' : '' }}" href="{{ route('commercial.cotizaciones.index') }}" data-tip="Cotizaciones">
                        <svg class="nav-bullet" viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="12" r="6"/></svg>
                        <span class="nav-label">Cotizaciones</span>
                    </a>
                    @endcan
                    @can('tienda.ver')
                    <a class="nav-item nav-sub {{ request()->routeIs('commercial.solicitudes-web.*') ? 'active' : '' }}" href="{{ route('commercial.solicitudes-web.index') }}" data-tip="Solicitudes web">
                        <svg class="nav-bullet" viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="12" r="6"/></svg>
                        <span class="nav-label">Solicitudes web</span>
                    </a>
                    @endcan
                    @can('ventas.ver')
                    <a class="nav-item nav-sub {{ request()->routeIs('commercial.ventas.*') && ! request()->routeIs('commercial.ventas.rapida.*') ? 'active' : '' }}" href="{{ route('commercial.ventas.index') }}" data-tip="Ventas">
                        <svg class="nav-bullet" viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="12" r="6"/></svg>
                        <span class="nav-label">Ventas</span>
                    </a>
                    @endcan
                    @can('ventas.crear')
                        <a class="nav-item nav-sub {{ request()->routeIs('commercial.ventas.rapida.*') ? 'active' : '' }}" href="{{ route('commercial.ventas.rapida.index') }}" data-tip="Venta rápida">
                            <svg class="nav-bullet" viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="12" r="6"/></svg>
                            <span class="nav-label">Venta rápida</span>
                        </a>
                    @endcan
                    @can('cobranza.ver')
                    <a class="nav-item nav-sub {{ request()->routeIs('commercial.cobranza.*') ? 'active' : '' }}" href="{{ route('commercial.cobranza.index') }}" data-tip="Cobranza">
                        <svg class="nav-bullet" viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="12" r="6"/></svg>
                        <span class="nav-label">Cobranza</span>
                    </a>
                    @endcan
                    @can('facturacion.ver')
                    <a class="nav-item nav-sub {{ request()->routeIs('commercial.facturas.*') ? 'active' : '' }}" href="{{ route('commercial.facturas.index') }}" data-tip="Facturación">
                        <svg class="nav-bullet" viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="12" r="6"/></svg>
                        <span class="nav-label">Facturación</span>
                    </a>
                    @endcan
                    <a class="nav-item nav-sub" href="#" data-tip="Promociones">
                        <svg class="nav-bullet" viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="12" r="6"/></svg>
                        <span class="nav-label">Promociones</span>
                    </a>
                </div>
            </div>
            <div class="nav-group {{ (request()->routeIs('inventory.*') && ! request()->routeIs('inventory.congresos.*')) || request()->routeIs('configuracion.catalogos.*') ? 'open' : '' }}">
                <a class="nav-item nav-toggle" href="#" data-tip="Gestión de Inventario">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg>
                    <span class="nav-label">Gestión de Inventario</span>
                    <svg class="nav-chev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><polyline points="6 9 12 15 18 9"/></svg>
                </a>
                <div class="submenu">
                    @can('inventario.ver')
                    <a class="nav-item nav-sub {{ request()->routeIs('inventory.movimientos.*') ? 'active' : '' }}" href="{{ route('inventory.movimientos.index') }}" data-tip="Entrada / Salida">
                        <svg class="nav-bullet" viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="12" r="6"/></svg>
                        <span class="nav-label">Entrada / Salida</span>
                    </a>
                    @endcan
                    @can('salidas.ver')
                        <a class="nav-item nav-sub {{ request()->routeIs('inventory.salidas.*') ? 'active' : '' }}" href="{{ route('inventory.salidas.index') }}" data-tip="Órdenes de salida">
                            <svg class="nav-bullet" viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="12" r="6"/></svg>
                            <span class="nav-label">Órdenes de salida</span>
                        </a>
                    @endcan
                    @can('procesos.ver')
                    <a class="nav-item nav-sub {{ request()->routeIs('inventory.procesos.*') ? 'active' : '' }}" href="{{ route('inventory.procesos.index') }}" data-tip="Procesos">
                        <svg class="nav-bullet" viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="12" r="6"/></svg>
                        <span class="nav-label">Procesos</span>
                    </a>
                    @endcan
                    @can('inventario.consulta_escaneo')
                    <a class="nav-item nav-sub {{ request()->routeIs('inventory.consulta.*') ? 'active' : '' }}" href="{{ route('inventory.consulta.index') }}" data-tip="Consulta por escaneo">
                        <svg class="nav-bullet" viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="12" r="6"/></svg>
                        <span class="nav-label">Consulta por escaneo</span>
                    </a>
                    @endcan
                    @can('inventario.ver')
                    <a class="nav-item nav-sub {{ request()->routeIs('inventory.productos.*') ? 'active' : '' }}" href="{{ route('inventory.productos.index') }}" data-tip="Productos">
                        <svg class="nav-bullet" viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="12" r="6"/></svg>
                        <span class="nav-label">Productos</span>
                    </a>
                    @endcan
                    @can('inventario.ver')
                    <a class="nav-item nav-sub {{ request()->routeIs('inventory.fichas.*') ? 'active' : '' }}" href="{{ route('inventory.fichas.index') }}" data-tip="Fichas técnicas">
                        <svg class="nav-bullet" viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="12" r="6"/></svg>
                        <span class="nav-label">Fichas técnicas</span>
                    </a>
                    @endcan
                    @can('inventario.catalogo')
                    <a class="nav-item nav-sub {{ request()->routeIs('configuracion.catalogos.*') ? 'active' : '' }}" href="{{ route('configuracion.catalogos.index') }}" data-tip="Catálogo">
                        <svg class="nav-bullet" viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="12" r="6"/></svg>
                        <span class="nav-label">Catálogo</span>
                    </a>
                    @endcan
                </div>
            </div>
            @canany(['rutas.ver', 'rutas.mapa', 'rutas.chofer', 'rutas.incidencias', 'rutas.flota', 'rutas.analytics'])
            <div class="nav-group {{ request()->routeIs('logistica.*') ? 'open' : '' }}">
                <a class="nav-item nav-toggle" href="#" data-tip="Logística">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
                    <span class="nav-label">Logística</span>
                    <svg class="nav-chev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><polyline points="6 9 12 15 18 9"/></svg>
                </a>
                <div class="submenu">
                    @can('rutas.mapa')
                    <a class="nav-item nav-sub {{ request()->routeIs('logistica.mapa') ? 'active' : '' }}" href="{{ route('logistica.mapa') }}" data-tip="Mapa en vivo">
                        <svg class="nav-bullet" viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="12" r="6"/></svg>
                        <span class="nav-label">Mapa en vivo</span>
                    </a>
                    @endcan
                    @can('rutas.ver')
                    <a class="nav-item nav-sub {{ request()->routeIs('logistica.rutas.*') ? 'active' : '' }}" href="{{ route('logistica.rutas.index') }}" data-tip="Rutas">
                        <svg class="nav-bullet" viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="12" r="6"/></svg>
                        <span class="nav-label">Rutas</span>
                    </a>
                    @endcan
                    @can('rutas.incidencias')
                    <a class="nav-item nav-sub {{ request()->routeIs('logistica.incidencias.*') ? 'active' : '' }}" href="{{ route('logistica.incidencias.index') }}" data-tip="Incidencias">
                        <svg class="nav-bullet" viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="12" r="6"/></svg>
                        <span class="nav-label">Incidencias</span>
                    </a>
                    @endcan
                    @can('rutas.flota')
                    <a class="nav-item nav-sub {{ request()->routeIs('logistica.flota.*') ? 'active' : '' }}" href="{{ route('logistica.flota.index') }}" data-tip="Flota">
                        <svg class="nav-bullet" viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="12" r="6"/></svg>
                        <span class="nav-label">Flota y mantenimiento</span>
                    </a>
                    @endcan
                    @can('rutas.analytics')
                    <a class="nav-item nav-sub {{ request()->routeIs('logistica.analytics') ? 'active' : '' }}" href="{{ route('logistica.analytics') }}" data-tip="Indicadores">
                        <svg class="nav-bullet" viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="12" r="6"/></svg>
                        <span class="nav-label">Indicadores</span>
                    </a>
                    @endcan
                    @can('rutas.chofer')
                    <a class="nav-item nav-sub {{ request()->routeIs('logistica.chofer.*') ? 'active' : '' }}" href="{{ route('logistica.chofer.index') }}" data-tip="Mis rutas">
                        <svg class="nav-bullet" viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="12" r="6"/></svg>
                        <span class="nav-label">Mis rutas (chofer)</span>
                    </a>
                    @endcan
                </div>
            </div>
            @endcanany
            @canany(['asistencia.ver', 'asistencia.biometria', 'asistencia.nomina', 'comisiones.ver'])
            <div class="nav-group {{ request()->routeIs('asistencia.*', 'nomina.*', 'commercial.comisiones.*') ? 'open' : '' }}">
                <a class="nav-item nav-toggle" href="#" data-tip="Recursos Humanos">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 11c0-1.1.9-2 2-2m-6 4a6 6 0 0 1 12 0M6 9a8 8 0 0 1 16 0M4 14c0-4.4 3.6-8 8-8"/><path d="M9 20c1 .6 2 1 3 1M12 17v1"/></svg>
                    <span class="nav-label">Asistencia (RH)</span>
                    <svg class="nav-chev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><polyline points="6 9 12 15 18 9"/></svg>
                </a>
                <div class="submenu">
                    @can('asistencia.ver')
                    <a class="nav-item nav-sub {{ request()->routeIs('asistencia.panel', 'asistencia.empleado') ? 'active' : '' }}" href="{{ route('asistencia.panel') }}" data-tip="Panel">
                        <svg class="nav-bullet" viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="12" r="6"/></svg>
                        <span class="nav-label">Panel de asistencia</span>
                    </a>
                    <a class="nav-item nav-sub {{ request()->routeIs('asistencia.checadas') ? 'active' : '' }}" href="{{ route('asistencia.checadas') }}" data-tip="Checadas">
                        <svg class="nav-bullet" viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="12" r="6"/></svg>
                        <span class="nav-label">Checadas</span>
                    </a>
                    <a class="nav-item nav-sub {{ request()->routeIs('asistencia.modalidades') ? 'active' : '' }}" href="{{ route('asistencia.modalidades') }}" data-tip="Incidencias del día">
                        <svg class="nav-bullet" viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="12" r="6"/></svg>
                        <span class="nav-label">Incidencias del día</span>
                    </a>
                    @endcan
                    @can('asistencia.gestionar')
                    <a class="nav-item nav-sub {{ request()->routeIs('asistencia.horarios') ? 'active' : '' }}" href="{{ route('asistencia.horarios') }}" data-tip="Horarios">
                        <svg class="nav-bullet" viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="12" r="6"/></svg>
                        <span class="nav-label">Horarios y festivos</span>
                    </a>
                    <a class="nav-item nav-sub {{ request()->routeIs('asistencia.eventos') ? 'active' : '' }}" href="{{ route('asistencia.eventos') }}" data-tip="Eventos de empresa">
                        <svg class="nav-bullet" viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="12" r="6"/></svg>
                        <span class="nav-label">Eventos de empresa</span>
                    </a>
                    @endcan
                    @can('asistencia.nomina')
                    <a class="nav-item nav-sub {{ request()->routeIs('nomina.*') ? 'active' : '' }}" href="{{ route('nomina.index') }}" data-tip="Nómina">
                        <svg class="nav-bullet" viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="12" r="6"/></svg>
                        <span class="nav-label">Nómina</span>
                    </a>
                    @endcan
                    {{-- Comisiones de los vendedores: se pagan con RH, junto a la nómina. --}}
                    @can('comisiones.ver')
                    <a class="nav-item nav-sub {{ request()->routeIs('commercial.comisiones.*') ? 'active' : '' }}" href="{{ route('commercial.comisiones.index') }}" data-tip="Comisiones">
                        <svg class="nav-bullet" viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="12" r="6"/></svg>
                        <span class="nav-label">Comisiones</span>
                    </a>
                    @endcan
                    @can('asistencia.biometria')
                    <a class="nav-item nav-sub {{ request()->routeIs('asistencia.biometria.*') ? 'active' : '' }}" href="{{ route('asistencia.biometria.index') }}" data-tip="Lectores y huellas">
                        <svg class="nav-bullet" viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="12" r="6"/></svg>
                        <span class="nav-label">Lectores y huellas</span>
                    </a>
                    @endcan
                </div>
            </div>
            @endcanany
            @endunless
            <div class="nav-group {{ request()->routeIs('gestion.servicios.*') ? 'open' : '' }}">
                <a class="nav-item nav-toggle" href="#" data-tip="Gestión de Servicios">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>
                    <span class="nav-label">Gestión de Servicios</span>
                    <svg class="nav-chev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><polyline points="6 9 12 15 18 9"/></svg>
                </a>
                <div class="submenu">
                    @can('servicios.ver')
                    <a class="nav-item nav-sub {{ request()->routeIs('gestion.servicios.historial') ? 'active' : '' }}" href="{{ route('gestion.servicios.historial') }}" data-tip="Historial de Servicios">
                        <svg class="nav-bullet" viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="12" r="6"/></svg>
                        <span class="nav-label">Historial de Servicios</span>
                    </a>
                    <a class="nav-item nav-sub {{ request()->routeIs('gestion.servicios.refacciones.*') ? 'active' : '' }}" href="{{ route('gestion.servicios.refacciones.index') }}" data-tip="Refacciones">
                        <svg class="nav-bullet" viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="12" r="6"/></svg>
                        <span class="nav-label">Refacciones</span>
                    </a>
                    <a class="nav-item nav-sub {{ request()->routeIs('gestion.servicios.garantia.*') ? 'active' : '' }}" href="{{ route('gestion.servicios.garantia.index') }}" data-tip="Cartas de Garantía">
                        <svg class="nav-bullet" viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="12" r="6"/></svg>
                        <span class="nav-label">Cartas de Garantía</span>
                    </a>
                    <a class="nav-item nav-sub {{ request()->routeIs('gestion.servicios.mantenimiento.*') ? 'active' : '' }}" href="{{ route('gestion.servicios.mantenimiento.index') }}" data-tip="Mantenimiento">
                        <svg class="nav-bullet" viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="12" r="6"/></svg>
                        <span class="nav-label">Mantenimiento</span>
                    </a>
                    @endcan
                    @can('servicios.crear')
                    <a class="nav-item nav-sub {{ request()->routeIs('gestion.servicios.historial.nueva_orden') ? 'active' : '' }}" href="{{ route('gestion.servicios.historial.nueva_orden') }}" data-tip="Nueva Orden">
                        <svg class="nav-bullet" viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="12" r="6"/></svg>
                        <span class="nav-label">Nueva Orden</span>
                    </a>
                    <a class="nav-item nav-sub {{ request()->routeIs('gestion.servicios.registro') ? 'active' : '' }}" href="{{ route('gestion.servicios.registro') }}" data-tip="Registro Externo">
                        <svg class="nav-bullet" viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="12" r="6"/></svg>
                        <span class="nav-label">Registro Externo</span>
                    </a>
                    @endcan
                    @can('servicios.ver')
                    <a class="nav-item nav-sub {{ request()->routeIs('gestion.servicios.area_endoscopia') ? 'active' : '' }}" href="{{ route('gestion.servicios.area_endoscopia') }}" data-tip="Área de Endoscopia">
                        <svg class="nav-bullet" viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="12" r="6"/></svg>
                        <span class="nav-label">Área de Endoscopia</span>
                    </a>
                    <a class="nav-item nav-sub {{ request()->routeIs('gestion.servicios.area') ? 'active' : '' }}" href="{{ route('gestion.servicios.area') }}" data-tip="Área General">
                        <svg class="nav-bullet" viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="12" r="6"/></svg>
                        <span class="nav-label">Área General</span>
                    </a>
                    <a class="nav-item nav-sub {{ request()->routeIs('gestion.servicios.externo') ? 'active' : '' }}" href="{{ route('gestion.servicios.externo') }}" data-tip="Mantenimiento Externo">
                        <svg class="nav-bullet" viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="12" r="6"/></svg>
                        <span class="nav-label">Externo</span>
                    </a>
                    <a class="nav-item nav-sub {{ request()->routeIs('gestion.servicios.servicio_externo.interno') ? 'active' : '' }}" href="{{ route('gestion.servicios.servicio_externo.interno') }}" data-tip="Servicio Externo Interno">
                        <svg class="nav-bullet" viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="12" r="6"/></svg>
                        <span class="nav-label">Servicio externo interno</span>
                    </a>
                    <a class="nav-item nav-sub {{ request()->routeIs('gestion.servicios.servicio_externo.externo') ? 'active' : '' }}" href="{{ route('gestion.servicios.servicio_externo.externo') }}" data-tip="Servicio Externo Externo">
                        <svg class="nav-bullet" viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="12" r="6"/></svg>
                        <span class="nav-label">Servicio externo externo</span>
                    </a>
                    @endcan
                </div>
            </div>
            @unless ($soloMantenimiento)
            @can('garantias.ver')
            <div class="nav-group {{ request()->routeIs('garantias.*') ? 'open' : '' }}">
                <a class="nav-item nav-toggle" href="#" data-tip="Garantías">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="M9 12l2 2 4-4"/></svg>
                    <span class="nav-label">Garantías</span>
                    <svg class="nav-chev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><polyline points="6 9 12 15 18 9"/></svg>
                </a>
                <div class="submenu">
                    <a class="nav-item nav-sub {{ request()->routeIs('garantias.tablero') ? 'active' : '' }}" href="{{ route('garantias.tablero') }}" data-tip="Centro de garantías">
                        <svg class="nav-bullet" viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="12" r="6"/></svg>
                        <span class="nav-label">Centro de garantías</span>
                    </a>
                    <a class="nav-item nav-sub {{ request()->routeIs('garantias.reclamaciones.*') ? 'active' : '' }}" href="{{ route('garantias.reclamaciones.index') }}" data-tip="Reclamaciones">
                        <svg class="nav-bullet" viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="12" r="6"/></svg>
                        <span class="nav-label">Reclamaciones</span>
                    </a>
                    <a class="nav-item nav-sub {{ request()->routeIs('garantias.index', 'garantias.show', 'garantias.create', 'garantias.serie') ? 'active' : '' }}" href="{{ route('garantias.index') }}" data-tip="Garantías por equipo">
                        <svg class="nav-bullet" viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="12" r="6"/></svg>
                        <span class="nav-label">Garantías por equipo</span>
                    </a>
                    <a class="nav-item nav-sub {{ request()->routeIs('garantias.reportes') ? 'active' : '' }}" href="{{ route('garantias.reportes') }}" data-tip="Reportes">
                        <svg class="nav-bullet" viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="12" r="6"/></svg>
                        <span class="nav-label">Reportes</span>
                    </a>
                    @can('garantias.configurar')
                    <a class="nav-item nav-sub {{ request()->routeIs('garantias.coberturas.*') ? 'active' : '' }}" href="{{ route('garantias.coberturas.index') }}" data-tip="Pólizas y coberturas">
                        <svg class="nav-bullet" viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="12" r="6"/></svg>
                        <span class="nav-label">Pólizas y coberturas</span>
                    </a>
                    @endcan
                </div>
            </div>
            @endcan
            {{-- Agenda: un solo módulo para la agenda personal y la de la empresa
                 (los antiguos "Calendarios" redirigen aquí). --}}
            @can('agenda.ver')
            <div class="nav-group {{ request()->routeIs('calendarios.*', 'agenda.*') ? 'open' : '' }}">
                <a class="nav-item nav-toggle" href="#" data-tip="Agenda">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="4" width="18" height="17" rx="2"></rect>
                        <path d="M16 2v4M8 2v4M3 10h18"></path>
                        <path d="M8 14h.01M12 14h.01M16 14h.01M8 18h.01M12 18h.01"></path>
                    </svg>
                    <span class="nav-label">Agenda</span>
                    <svg class="nav-chev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><polyline points="6 9 12 15 18 9"></polyline></svg>
                </a>
                <div class="submenu">
                    <a class="nav-item nav-sub {{ request()->routeIs('agenda.*') && request('ambito') !== 'empresa' ? 'active' : '' }}" href="{{ route('agenda.index', ['ambito' => 'mia']) }}" data-tip="Mi agenda">
                        <svg class="nav-bullet" viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="12" r="6"/></svg>
                        <span class="nav-label">Mi agenda</span>
                    </a>
                    <a class="nav-item nav-sub {{ request()->routeIs('agenda.*') && request('ambito') === 'empresa' ? 'active' : '' }}" href="{{ route('agenda.index', ['ambito' => 'empresa']) }}" data-tip="Agenda de la empresa">
                        <svg class="nav-bullet" viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="12" r="6"/></svg>
                        <span class="nav-label">Agenda de la empresa</span>
                    </a>
                </div>
            </div>
            @endcan
            <div class="nav-section">Administración</div>
            <div class="nav-group {{ request()->routeIs('admin.*', 'inventory.congresos.*') ? 'open' : '' }}">
                <a class="nav-item nav-toggle" href="#" data-tip="Gestión Administrativa">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 2 7 12 12 22 7 12 2"/><polyline points="2 17 12 22 22 17"/><polyline points="2 12 12 17 22 12"/></svg>
                    <span class="nav-label">Gestión Administrativa</span>
                    <svg class="nav-chev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><polyline points="6 9 12 15 18 9"/></svg>
                </a>
                <div class="submenu">
                    {{-- Congresos: eventos de la empresa (quién va, equipo enviado, ventas de congreso). --}}
                    @can('congresos.ver')
                    <a class="nav-item nav-sub {{ request()->routeIs('inventory.congresos.*') ? 'active' : '' }}" href="{{ route('inventory.congresos.index') }}" data-tip="Congresos">
                        <svg class="nav-bullet" viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="12" r="6"/></svg>
                        <span class="nav-label">Congresos</span>
                    </a>
                    @endcan
                    @can('actividad.ver')
                        <a class="nav-item nav-sub {{ request()->routeIs('admin.actividad.*') ? 'active' : '' }}" href="{{ route('admin.actividad.index') }}" data-tip="Actividad">
                            <svg class="nav-bullet" viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="12" r="6"/></svg>
                            <span class="nav-label">Actividad</span>
                        </a>
                    @endcan
                    @can('usuarios.ver')
                    <a class="nav-item nav-sub {{ request()->routeIs('admin.users.*') ? 'active' : '' }}" href="{{ route('admin.users.index') }}" data-tip="Usuarios">
                        <svg class="nav-bullet" viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="12" r="6"/></svg>
                        <span class="nav-label">Usuarios</span>
                    </a>
                    @endcan
                    @if(auth()->user()?->isAdmin())
                    <a class="nav-item nav-sub {{ request()->routeIs('admin.vehicles.*') ? 'active' : '' }}" href="{{ route('admin.vehicles.index') }}" data-tip="Vehículos">
                        <svg class="nav-bullet" viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="12" r="6"/></svg>
                        <span class="nav-label">Vehículos</span>
                    </a>
                    @endif
                    @can('viaticos.ver')
                    <a class="nav-item nav-sub {{ request()->routeIs('admin.viatics.*') ? 'active' : '' }}" href="{{ route('admin.viatics.index') }}" data-tip="Viáticos">
                        <svg class="nav-bullet" viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="12" r="6"/></svg>
                        <span class="nav-label">Viáticos</span>
                    </a>
                    @endcan
                    <a class="nav-item nav-sub {{ request()->routeIs('admin.paqueteria.*') ? 'active' : '' }}" href="{{ route('admin.paqueteria.index') }}" data-tip="Paquetería">
                        <svg class="nav-bullet" viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="12" r="6"/></svg>
                        <span class="nav-label">Paquetería</span>
                    </a>
                    @can('entregas_guias.ver')
                    <a class="nav-item nav-sub {{ request()->routeIs('admin.guia_entregas.*') ? 'active' : '' }}" href="{{ route('admin.guia_entregas.index') }}" data-tip="Entregas de guías">
                        <svg class="nav-bullet" viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="12" r="6"/></svg>
                        <span class="nav-label">Entregas de guías</span>
                    </a>
                    @endcan
                    <a class="nav-item nav-sub {{ request()->routeIs('admin.materials.*') ? 'active' : '' }}" href="{{ route('admin.materials.index') }}" data-tip="Materiales">
                        <svg class="nav-bullet" viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="12" r="6"/></svg>
                        <span class="nav-label">Materiales</span>
                    </a>
                    <a class="nav-item nav-sub {{ request()->routeIs('admin.reports.*') ? 'active' : '' }}" href="{{ route('admin.reports.index') }}" data-tip="Reportes">
                        <svg class="nav-bullet" viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="12" r="6"/></svg>
                        <span class="nav-label">Reportes</span>
                    </a>
                    {{--
                        La pantalla antigua de permisos (admin.permissions.*) editaba a mano
                        las filas de la tabla. Ya no se enlaza: el catálogo vive en código
                        (CatalogoPermisos) y renombrar una fila ahí rompía el Gate en silencio.
                        Lo que se reparte ahora es en Configuración → Roles y permisos.
                    --}}
                </div>
            </div>
            <div class="nav-group {{ request()->routeIs('marketing.*', 'promociones.*', 'mensajeria.*') ? 'open' : '' }}">
                <a class="nav-item nav-toggle" href="#" data-tip="Gestión de Marketing">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/></svg>
                    <span class="nav-label">Gestión de Marketing</span>
                    <svg class="nav-chev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><polyline points="6 9 12 15 18 9"/></svg>
                </a>
                <div class="submenu">
                    <a class="nav-item nav-sub {{ request()->routeIs('marketing.inicio') ? 'active' : '' }}" href="{{ route('marketing.inicio') }}" data-tip="Inicio">
                        <svg class="nav-bullet" viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="12" r="6"/></svg>
                        <span class="nav-label">Inicio</span>
                    </a>
                    @can('marketing.ver')
                    <a class="nav-item nav-sub {{ request()->routeIs('marketing.guia_de_marca.index') ? 'active' : '' }}" href="{{ route('marketing.guia_de_marca.index') }}" data-tip="Guía de marca">
                        <svg class="nav-bullet" viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="12" r="6"/></svg>
                        <span class="nav-label">Guía de marca</span>
                    </a>
                    @endcan
                    @can('marketing.ver')
                    <a class="nav-item nav-sub {{ request()->routeIs('marketing.calendario.index') ? 'active' : '' }}" href="{{ route('marketing.calendario.index') }}" data-tip="Calendario">
                        <svg class="nav-bullet" viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="12" r="6"/></svg>
                        <span class="nav-label">Calendario</span>
                    </a>
                    @endcan
                    @can('marketing.aprobar')
                    <a class="nav-item nav-sub {{ request()->routeIs('marketing.aprobacion_flyers.index') ? 'active' : '' }}" href="{{ route('marketing.aprobacion_flyers.index') }}" data-tip="Aprobación de flyers">
                        <svg class="nav-bullet" viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="12" r="6"/></svg>
                        <span class="nav-label">Aprobación de flyers</span>
                    </a>
                    @endcan
                    @can('marketing.ver')
                    <a class="nav-item nav-sub {{ request()->routeIs('marketing.biblioteca_catalogo.index') ? 'active' : '' }}" href="{{ route('marketing.biblioteca_catalogo.index') }}" data-tip="Biblioteca & catálogo">
                        <svg class="nav-bullet" viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="12" r="6"/></svg>
                        <span class="nav-label">Biblioteca & catálogo</span>
                    </a>
                    @endcan
                    @can('mensajeria.ver')
                    <a class="nav-item nav-sub {{ request()->routeIs('mensajeria.*') ? 'active' : '' }}" href="{{ route('mensajeria.index') }}" data-tip="Mensajería">
                        <svg class="nav-bullet" viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="12" r="6"/></svg>
                        <span class="nav-label">Mensajería</span>
                        <span class="nav-count" id="navMsjNoLeidos" style="display:none"></span>
                    </a>
                    @endcan
                    @can('promociones.ver')
                    <a class="nav-item nav-sub {{ request()->routeIs('promociones.*') ? 'active' : '' }}" href="{{ route('promociones.campanas.index') }}" data-tip="Promociones y cupones">
                        <svg class="nav-bullet" viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="12" r="6"/></svg>
                        <span class="nav-label">Promociones y cupones</span>
                    </a>
                    @endcan
                    <div class="submenu-label">Datos</div>
                    @can('marketing.ver')
                    <a class="nav-item nav-sub {{ request()->routeIs('marketing.tareas.index') ? 'active' : '' }}" href="{{ route('marketing.tareas.index') }}" data-tip="Tareas">
                        <svg class="nav-bullet" viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="12" r="6"/></svg>
                        <span class="nav-label">Tareas</span>
                        <span class="nav-count">6</span>
                    </a>
                    @endcan
                </div>
            </div>
            <div class="nav-section">Sistema</div>
            <div class="nav-group {{ request()->routeIs('configuracion.roles.*', 'configuracion.api.*') ? 'open' : '' }}">
                <a class="nav-item nav-toggle" href="#" data-tip="Configuración">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
                    <span class="nav-label">Configuración</span>
                    <svg class="nav-chev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><polyline points="6 9 12 15 18 9"/></svg>
                </a>
                <div class="submenu">
                    @can('roles.gestionar')
                        <a class="nav-item nav-sub {{ request()->routeIs('configuracion.roles.*') ? 'active' : '' }}" href="{{ route('configuracion.roles.index') }}" data-tip="Roles y permisos">
                            <svg class="nav-bullet" viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="12" r="6"/></svg>
                            <span class="nav-label">Roles y permisos</span>
                        </a>
                    @endcan
                    @can('api.llaves')
                        <a class="nav-item nav-sub {{ request()->routeIs('configuracion.api.*') ? 'active' : '' }}" href="{{ route('configuracion.api.index') }}" data-tip="API e integraciones">
                            <svg class="nav-bullet" viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="12" r="6"/></svg>
                            <span class="nav-label">API e integraciones</span>
                        </a>
                    @endcan
                </div>
            </div>
            @endunless
        </nav>

    </aside>

    {{-- ===================== MAIN ===================== --}}
    <div class="main">
        <header class="topbar">
            <button class="hamburger" id="hamburger" type="button" aria-label="Menú">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="22" height="22"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
            </button>
            <div class="topbar-titulo">
                {{-- Dentro de una seccion se muestra su nombre; en el inicio, el saludo.
                     En el telefono el saludo va con el primer nombre: el nombre
                     completo partia la cabecera en cinco renglones. --}}
                <h1 class="page-title">@hasSection('page-title')@yield('page-title')@else<span id="greet-word">{{ $saludo }}</span>, <span class="solo-ancho">{{ $u->name }}</span><span class="solo-angosto">{{ $primerNombre }}</span>@endif</h1>
                @hasSection('page-title')
                    @hasSection('page-sub')<p class="page-sub">@yield('page-sub')</p>@endif
                @else
                    <p class="page-sub">@yield('page-sub', 'Resumen general de tu cuenta')</p>
                @endif
            </div>
            <div class="topbar-spacer"></div>

            <a class="icon-btn" href="{{ route('calendarios.mio') }}" aria-label="Mi calendario" title="Mi calendario">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="17" rx="2"/><path d="M16 2v4M8 2v4M3 10h18M8 14h.01M12 14h.01M16 14h.01M8 18h.01M12 18h.01"/></svg>
            </a>

            {{-- Tema --}}
            <button class="icon-btn" id="theme-toggle" type="button" aria-label="Cambiar tema">
                <svg class="ico-moon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8z"/></svg>
                <svg class="ico-sun" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="4.5"/><path d="M12 2v2M12 20v2M2 12h2M20 12h2M5 5l1.5 1.5M17.5 17.5L19 19M19 5l-1.5 1.5M6.5 17.5L5 19"/></svg>
            </button>

            {{-- Notificaciones --}}
            <div class="dd" id="dd-notif">
                <button class="icon-btn" type="button" aria-label="Notificaciones{{ $notifsTotal ? ' ('.$notifsTotal.' sin leer)' : '' }}" data-dd="dd-notif">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8a6 6 0 1 0-12 0c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.7 21a2 2 0 0 1-3.4 0"/></svg>
                    @if ($notifsTotal)
                        <span class="notif-num">{{ $notifsTotal > 9 ? '9+' : $notifsTotal }}</span>
                    @endif
                </button>
                <div class="dd-panel">
                    <div class="dd-head" style="display:flex; align-items:center; gap:8px;">
                        <b style="flex:1;">Notificaciones</b>
                        @if ($notifsTotal)
                            <form method="POST" action="{{ route('notificaciones.leerTodas') }}">
                                @csrf
                                <button type="submit" class="notif-todas">Marcar todas leídas</button>
                            </form>
                        @endif
                    </div>
                    @if ($notifs->isEmpty())
                        <div class="dd-empty">No tienes notificaciones nuevas.</div>
                    @else
                        <div class="dd-notif">
                            @foreach ($notifs as $n)
                                <a class="dd-item" href="{{ route('notificaciones.abrir', $n->id) }}">
                                    <span class="di-ico">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
                                    </span>
                                    <span style="min-width:0;">
                                        <b style="display:block; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">{{ $n->data['titulo'] ?? 'Notificación' }}</b>
                                        <small style="display:block; color:var(--muted); font-size:12px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">{{ $n->data['texto'] ?? '' }}</small>
                                        <small style="display:block; color:var(--muted); font-size:11px;">{{ $n->created_at?->diffForHumans() }}</small>
                                    </span>
                                </a>
                            @endforeach
                        </div>
                        @if ($notifsTotal > $notifs->count())
                            <div class="dd-empty">Y {{ $notifsTotal - $notifs->count() }} más sin leer.</div>
                        @endif
                    @endif
                </div>
            </div>
            <style>
                .notif-num { position:absolute; top:6px; right:6px; min-width:17px; height:17px; padding:0 4px; border-radius:9px; background:var(--danger); color:#fff; font-size:10.5px; font-weight:800; display:flex; align-items:center; justify-content:center; line-height:1; }
                .notif-todas { border:none; background:transparent; color:var(--primary); font-size:12px; font-weight:600; cursor:pointer; font-family:inherit; padding:0; }
                .notif-todas:hover { text-decoration:underline; }
            </style>

            {{-- Usuario --}}
            <div class="dd" id="dd-user">
                <button class="user-btn" type="button" data-dd="dd-user">
                    <span class="avatar" style="position:relative;overflow:hidden;">
                        {{ Str::upper($initials) }}
                        @if ($u->avatarUrl())
                            <img src="{{ $u->avatarUrl() }}" alt="" style="position:absolute;inset:0;width:100%;height:100%;object-fit:cover;object-position:{{ $u->avatarFoco() }};" onerror="this.remove()">
                        @endif
                    </span>
                    <span class="user-name">{{ $u->name }}</span>
                    <svg class="chev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><polyline points="6 9 12 15 18 9"/></svg>
                </button>
                <div class="dd-panel">
                    <div class="dd-head"><b>Acciones rápidas</b><small>Tu cuenta y herramientas</small></div>
                    <a class="dd-item" href="{{ route('profile.edit') }}">
                        <span class="di-ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18"><path d="M11 4H4v16h16v-7"/><path d="M18.5 2.5a2.1 2.1 0 0 1 3 3L12 15l-4 1 1-4z"/></svg></span>
                        <span><b>Editar perfil</b><small>Actualiza tu información personal</small></span>
                    </a>
                    {{-- El tablero dejo de ser la pantalla de cuenta, asi que
                         la seguridad de la sesion se entra por aqui. --}}
                    <a class="dd-item" href="{{ route('account') }}">
                        <span class="di-ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18"><path d="M12 2 4 5v6c0 5 3.5 8 8 11 4.5-3 8-6 8-11V5l-8-3z"/></svg></span>
                        <span><b>Mi cuenta</b><small>Sesiones activas e historial de accesos</small></span>
                    </a>
                    @if ($u->isAdmin())
                        <a class="dd-item" href="{{ route('admin.users.index') }}">
                            <span class="di-ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18"><rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/></svg></span>
                            <span><b>Panel de usuarios</b><small>Administra las cuentas</small></span>
                        </a>
                    @endif
                    <div class="dd-sep"></div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button class="dd-item danger" type="submit">
                            <span class="di-ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg></span>
                            <span><b>Cerrar sesión</b><small>Cerrar sesión en tu cuenta actual</small></span>
                        </button>
                    </form>
                </div>
            </div>
        </header>

        <main class="content">
            @yield('content')
        </main>
        {{-- Tiempo real: la pantalla se actualiza sola con los cambios de otros. --}}
        @include('partials.vivo')
        {{-- Todo lo que se escribe se guarda en MAYÚSCULAS, con corrector. --}}
        @include('partials.mayusculas')
        {{-- Autocorrector ortográfico (español de México) al salir de cada campo. --}}
        @include('partials.ortografia')
    </div>
</div>

{{-- Toast global de confirmaciones --}}
<div class="toast" id="appToast" role="status" aria-live="polite">
    <span class="toast-ico" id="appToastIco"></span>
    <span id="appToastMsg"></span>
</div>

<div class="cm-overlay" id="cmOverlay" hidden>
    <div class="cm-box" role="dialog" aria-modal="true" aria-labelledby="cmTitle" aria-describedby="cmMsg">
        <div class="cm-ico" id="cmIco"></div>
        <h3 class="cm-title" id="cmTitle">¿Estás seguro?</h3>
        <p class="cm-msg" id="cmMsg"></p>
        <div class="cm-actions">
            <button type="button" class="cm-btn cm-cancel" id="cmCancel">Cancelar</button>
            <button type="button" class="cm-btn cm-ok" id="cmOk">Confirmar</button>
        </div>
    </div>
</div>
@if (session('status'))
    <span id="appFlash" data-msg="{{ session('status') }}" data-type="ok" hidden></span>
@endif
@if ($errors->any())
    <span id="appFlashErr" data-msg="{{ $errors->first() }}" hidden></span>
@endif

<script src="{{ asset('js/panel.js') }}?v={{ filemtime(public_path('js/panel.js')) }}"></script>

<script>
    // Registro del service worker (instalación como app y página sin conexión)
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', function () { navigator.serviceWorker.register('/sw.js').catch(function () {}); });
    }
</script>
@stack('scripts')
    {{-- Auto-refresco de permisos: si cambia el acceso del usuario (permisos,
         rol admin o estatus), la pagina se recarga sola sin dar refresh. --}}
    @auth
    <script>
    (function () {
        var meta = document.querySelector('meta[name="acceso-firma"]');
        if (!meta) return;
        var firmaInicial = meta.getAttribute('content');
        var url = @json(route('api.mi_acceso'));
        var recargando = false;
        function recargar() {
            if (recargando) return;
            // Candado anti-bucle: nunca recargar dos veces en menos de 8s.
            try {
                var ultimo = parseInt(sessionStorage.getItem('acceso_reload_at') || '0', 10);
                if (Date.now() - ultimo < 8000) return;
                sessionStorage.setItem('acceso_reload_at', String(Date.now()));
            } catch (e) {}
            recargando = true;
            window.location.reload();
        }
        var enVuelo = false;
        function revisar() {
            if (enVuelo || recargando) return;      // no encimar peticiones
            enVuelo = true;
            fetch(url, { headers: { 'Accept': 'application/json' }, credentials: 'same-origin', cache: 'no-store' })
                .then(function (r) {
                    if (r.status === 401 || r.status === 419) { recargar(); return null; }
                    if (!r.ok) return null;
                    return r.json();
                })
                .then(function (d) {
                    if (d && d.firma && d.firma !== firmaInicial) { recargar(); }
                })
                .catch(function () { /* red intermitente: se reintenta en el proximo ciclo */ })
                .finally(function () { enVuelo = false; });
        }
        // Cambiar permisos/rol/estatus es una escritura, y toda escritura sube
        // el contador de /_vivo: se revisa solo cuando ObsVivo avisa que hubo
        // un cambio (casi al instante) y, de respaldo, cada 60 s con la
        // pestaña visible. Antes era una petición con sesión cada 3 s por
        // pestaña, que cargaba de más el hosting.
        document.addEventListener('obs:cambio', revisar);
        setInterval(function () { if (!document.hidden) revisar(); }, 60000);
        document.addEventListener('visibilitychange', function () {
            if (document.visibilityState === 'visible') revisar();
        });
    })();
    </script>
    @endauth
</body>
</html>
