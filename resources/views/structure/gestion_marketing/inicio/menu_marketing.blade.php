@extends('layouts.dashboard')
@section('title', 'Marketing')

@section('content')
    <div class="card" style="margin-bottom:24px; background:linear-gradient(135deg, var(--surface) 0%, var(--surface-2) 100%);">
        <div style="max-width:780px;">
            <h1 class="section-title" style="margin:0; font-size:34px; line-height:1.15; letter-spacing:-0.02em; font-weight:800;">
                Todo el marketing de MediBuy, <span style="color:var(--green);">en un solo lugar.</span>
            </h1>
            <p class="muted" style="margin:14px 0 0; font-size:15.5px; line-height:1.6; max-width:680px;">
                Guía de marca, calendario de contenido, aprobación de flyers y la biblioteca de
                productos y recursos. Un panel central para que el equipo trabaje rápido,
                consistente y sin errores antes de publicar.
            </p>
        </div>

        <div class="grid stat-row" style="margin-top:28px;">
            <div class="card" style="padding:18px 20px; display:flex; align-items:center; gap:14px;">
                <div style="width:42px; height:42px; border-radius:10px; background:var(--accent-soft); color:var(--accent); display:flex; align-items:center; justify-content:center; font-size:20px; font-weight:800;">0</div>
                <div>
                    <div style="font-size:13px; color:var(--muted); font-weight:500;">Cambios solicitados</div>
                </div>
            </div>
            <div class="card" style="padding:18px 20px; display:flex; align-items:center; gap:14px;">
                <div style="width:42px; height:42px; border-radius:10px; background:var(--primary-soft); color:var(--primary); display:flex; align-items:center; justify-content:center; font-size:20px; font-weight:800;">0</div>
                <div>
                    <div style="font-size:13px; color:var(--muted); font-weight:500;">En revisión</div>
                </div>
            </div>
            <div class="card" style="padding:18px 20px; display:flex; align-items:center; gap:14px;">
                <div style="width:42px; height:42px; border-radius:10px; background:var(--accent-soft); color:var(--accent); display:flex; align-items:center; justify-content:center; font-size:20px; font-weight:800;">0</div>
                <div>
                    <div style="font-size:13px; color:var(--muted); font-weight:500;">Pendiente por tomar</div>
                </div>
            </div>
            <div class="card" style="padding:18px 20px; display:flex; align-items:center; gap:14px;">
                <div style="width:42px; height:42px; border-radius:10px; background:var(--green-soft); color:var(--green); display:flex; align-items:center; justify-content:center; font-size:20px; font-weight:800;">8</div>
                <div>
                    <div style="font-size:13px; color:var(--muted); font-weight:500;">Áreas especializadas</div>
                </div>
            </div>
        </div>
    </div>

    <div>
        <span style="display:inline-block; padding:6px 12px; border-radius:20px; background:var(--surface-2); color:var(--muted); font-size:11px; font-weight:700; letter-spacing:0.06em; text-transform:uppercase; margin-bottom:10px;">Accesos rápidos</span>
        <h2 style="margin:0 0 18px; font-size:22px; font-weight:700;">¿Qué quieres hacer hoy?</h2>

        <div class="grid" style="grid-template-columns:repeat(auto-fit, minmax(260px, 1fr)); gap:18px;">
            <a href="#" style="text-decoration:none; color:inherit;">
                <div class="card" style="padding:20px; display:flex; align-items:center; justify-content:space-between; gap:14px; transition:transform .15s, box-shadow .15s;">
                    <div style="display:flex; align-items:center; gap:14px;">
                        <div style="width:44px; height:44px; border-radius:12px; background:var(--green); color:#fff; display:flex; align-items:center; justify-content:center;">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="22" height="22"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                        </div>
                        <div>
                            <div style="font-size:15.5px; font-weight:700; color:var(--text);">Revisar flyers pendientes</div>
                            <div style="font-size:13px; color:var(--muted); margin-top:2px;">Sin piezas en revisión por ahora.</div>
                        </div>
                    </div>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18" style="color:var(--muted);"><polyline points="9 18 15 12 9 6"/></svg>
                </div>
            </a>

            <a href="#" style="text-decoration:none; color:inherit;">
                <div class="card" style="padding:20px; display:flex; align-items:center; justify-content:space-between; gap:14px; transition:transform .15s, box-shadow .15s;">
                    <div style="display:flex; align-items:center; gap:14px;">
                        <div style="width:44px; height:44px; border-radius:12px; background:var(--green); color:#fff; display:flex; align-items:center; justify-content:center;">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="22" height="22"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                        </div>
                        <div>
                            <div style="font-size:15.5px; font-weight:700; color:var(--text);">Ver calendario de la semana</div>
                            <div style="font-size:13px; color:var(--muted); margin-top:2px;">Qué se publica y en qué red social.</div>
                        </div>
                    </div>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18" style="color:var(--muted);"><polyline points="9 18 15 12 9 6"/></svg>
                </div>
            </a>

            <a href="{{ route('marketing.guia.index') }}" style="text-decoration:none; color:inherit;">
                <div class="card" style="padding:20px; display:flex; align-items:center; justify-content:space-between; gap:14px; transition:transform .15s, box-shadow .15s;">
                    <div style="display:flex; align-items:center; gap:14px;">
                        <div style="width:44px; height:44px; border-radius:12px; background:var(--green); color:#fff; display:flex; align-items:center; justify-content:center;">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="22" height="22"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
                        </div>
                        <div>
                            <div style="font-size:15.5px; font-weight:700; color:var(--text);">Consultar la guía de marca</div>
                            <div style="font-size:13px; color:var(--muted); margin-top:2px;">Colores, tipografías y reglas legales.</div>
                        </div>
                    </div>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18" style="color:var(--muted);"><polyline points="9 18 15 12 9 6"/></svg>
                </div>
            </a>

            <a href="#" style="text-decoration:none; color:inherit;">
                <div class="card" style="padding:20px; display:flex; align-items:center; justify-content:space-between; gap:14px; transition:transform .15s, box-shadow .15s;">
                    <div style="display:flex; align-items:center; gap:14px;">
                        <div style="width:44px; height:44px; border-radius:12px; background:var(--green); color:#fff; display:flex; align-items:center; justify-content:center;">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="22" height="22"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
                        </div>
                        <div>
                            <div style="font-size:15.5px; font-weight:700; color:var(--text);">Abrir biblioteca y catálogos</div>
                            <div style="font-size:13px; color:var(--muted); margin-top:2px;">Productos por área y recursos descargables.</div>
                        </div>
                    </div>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18" style="color:var(--muted);"><polyline points="9 18 15 12 9 6"/></svg>
                </div>
            </a>
        </div>
    </div>
@endsection
