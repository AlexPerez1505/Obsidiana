@extends('layouts.dashboard')

@section('title', 'Paquetería')
@section('page-title', 'Paquetería')
@section('page-sub', 'Gestión Administrativa')

@push('head')
<style>
    .paq-page { padding-bottom: 48px; }
    .paq-topbar { display:flex; align-items:center; justify-content:flex-end; gap:16px; margin-bottom:18px; }
    .paq-actions { display:flex; gap:8px; flex-wrap:wrap; }
    .paq-actions .btn { display:inline-flex; align-items:center; gap:7px; }

    .paq-filters { display:flex; align-items:center; gap:7px; flex-wrap:wrap; padding:8px; margin-bottom:28px; background:var(--surface); border:1px solid var(--border); border-radius:12px; }
    .paq-filter { display:inline-flex; align-items:center; gap:8px; border:0; border-radius:9px; padding:9px 12px; background:transparent; color:var(--muted); cursor:pointer; font:inherit; font-size:13px; }
    .paq-filter:hover { background:var(--surface-2); color:var(--text); }
    .paq-filter.active { background:var(--primary); color:#fff; }
    .paq-filter small { opacity:.7; font-size:11px; }
    .paq-dot { width:8px; height:8px; border-radius:50%; background:currentColor; display:inline-block; }

    .paq-hero { margin-bottom:24px; }
    .paq-eyebrow { margin:0 0 7px; color:var(--primary); font-size:13px; font-weight:600; }
    .paq-hero h1 { margin:0; font-size:34px; letter-spacing:-.03em; }
    .paq-total { margin:10px 0 2px; font-size:40px; font-weight:800; letter-spacing:-.04em; }
    .paq-description { margin:0; color:var(--muted); font-size:15px; }

    .paq-tabs { display:flex; gap:4px; width:max-content; margin:25px 0 18px; padding:4px; background:var(--surface-2); border-radius:10px; }
    .paq-tab { border:0; padding:9px 18px; border-radius:7px; background:transparent; color:var(--muted); cursor:pointer; font:inherit; font-size:13px; font-weight:600; }
    .paq-tab.active { background:var(--surface); color:var(--text); box-shadow:0 1px 3px rgba(0,0,0,.12); }

    .paq-stats { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:14px; }
    .paq-stat { min-height:118px; padding:20px; background:var(--surface); border:1px solid var(--border); border-radius:16px; }
    .paq-stat-label { color:var(--muted); font-size:13px; font-weight:600; }
    .paq-stat-value { margin-top:12px; font-size:30px; line-height:1; font-weight:800; }
    .paq-stat-note { margin-top:10px; color:var(--muted); font-size:12px; }
    .paq-stat.blue .paq-stat-value { color:#54a5ff; }
    .paq-stat.amber .paq-stat-value { color:#f5ae3d; }
    .paq-stat.green .paq-stat-value { color:#45c878; }
    .paq-stat.danger .paq-stat-value { color:#ff6b6b; }

    .paq-placeholder { margin-top:18px; padding:22px; background:var(--surface); border:1px solid var(--border); border-radius:16px; color:var(--muted); }
    .paq-placeholder strong { color:var(--text); }
    .paq-envios-panel { margin-top:18px; }
    .paq-envio-card { display:grid; grid-template-columns:minmax(0,1fr) auto; gap:12px; padding:16px 18px; margin-top:10px; background:var(--surface); border:1px solid var(--border); border-radius:14px; }
    .paq-envio-card strong, .paq-envio-card small { display:block; }
    .paq-envio-card small { margin-top:4px; color:var(--muted); }
    .paq-envio-status { align-self:center; padding:6px 10px; border-radius:999px; background:var(--primary-soft); color:var(--primary); font-size:12px; font-weight:700; }
    .paq-guide-admin { display:grid; grid-template-columns:minmax(0,1fr) auto; gap:12px; align-items:center; padding:14px 16px; margin-top:10px; background:var(--surface); border:1px solid var(--border); border-radius:14px; }
    .paq-guide-admin small { display:block; margin-top:4px; color:var(--muted); }
    .paq-guide-admin-status { padding:6px 10px; border-radius:999px; font-size:12px; font-weight:700; }
    .paq-guide-admin-status.available { background:rgba(20,100,45,.45); color:#22d565; }
    .paq-guide-admin-status.used { background:var(--surface-2); color:var(--muted); }
    .paq-detail-grid { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:10px; margin-top:14px; }
    .paq-detail-grid div { padding:12px; border-radius:12px; background:var(--surface); }
    .paq-detail-grid small, .paq-detail-grid strong { display:block; }
    .paq-detail-grid small { color:var(--muted); font-size:11px; }
    .paq-detail-grid strong { margin-top:5px; overflow-wrap:anywhere; }
    .paq-modal[hidden] { display:none; }
    .paq-modal { position:fixed; inset:0; z-index:80; display:flex; align-items:center; justify-content:center; padding:24px; background:rgba(2,6,23,.72); backdrop-filter:blur(5px); }
    .paq-wizard { width:min(760px,100%); max-height:calc(100vh - 48px); overflow:auto; padding:26px; background:var(--surface); border:1px solid var(--border); border-radius:18px; box-shadow:0 24px 70px rgba(0,0,0,.35); }
    .paq-wizard-head { display:flex; align-items:flex-start; justify-content:space-between; gap:16px; margin-bottom:18px; }
    .paq-wizard-step { margin:0 0 5px; color:var(--primary); font-size:14px; font-weight:700; }
    .paq-wizard h2 { margin:0; font-size:26px; letter-spacing:-.03em; }
    .paq-close { border:0; border-radius:9px; padding:9px 14px; background:var(--surface-2); color:var(--text); cursor:pointer; font:inherit; font-weight:600; }
    .paq-progress { display:grid; grid-template-columns:repeat(5,1fr); gap:8px; margin-bottom:20px; }
    .paq-progress i { height:5px; border-radius:5px; background:var(--border); }
    .paq-progress i:first-child { background:var(--primary); }
    .paq-wizard-card { padding:18px; background:var(--surface-2); border:1px solid var(--border); border-radius:14px; }
    .paq-wizard-card + .paq-wizard-card { margin-top:14px; }
    .paq-label { display:block; margin-bottom:8px; color:var(--muted); font-size:13px; font-weight:700; }
    .paq-search { width:100%; box-sizing:border-box; padding:12px 14px; border:1px solid var(--border); border-radius:10px; background:var(--surface); color:var(--text); }
    .paq-remisiones { display:grid; gap:8px; max-height:275px; overflow-y:auto; margin-top:12px; }
    .paq-remision { display:grid; grid-template-columns:115px 1fr auto; align-items:center; gap:12px; width:100%; padding:13px 14px; border:1px solid var(--border); border-radius:12px; background:var(--surface); color:var(--text); text-align:left; cursor:pointer; }
    .paq-remision:hover, .paq-remision.selected { border-color:var(--primary); background:var(--primary-soft); }
    .paq-remision strong, .paq-remision span { display:block; }
    .paq-remision small { color:var(--muted); }
    .paq-remision-arrow { color:var(--muted); font-size:20px; }
    .paq-empty { padding:18px; color:var(--muted); text-align:center; }
    .paq-selected { display:flex; align-items:center; justify-content:space-between; gap:12px; padding:13px 14px; border:1px solid var(--primary); border-radius:12px; background:var(--primary-soft); }
    .paq-selected small { display:block; margin-top:3px; color:var(--muted); }
    .paq-change { border:0; border-radius:999px; padding:8px 12px; background:var(--surface-2); color:var(--text); cursor:pointer; font:inherit; font-weight:600; }
    .paq-manual-banner { display:flex; align-items:center; justify-content:space-between; gap:12px; margin-bottom:14px; padding:14px 16px; border-radius:12px; background:var(--primary-soft); color:var(--primary); font-weight:700; }
    .paq-manual-banner[hidden] { display:none; }
    .paq-auto-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:12px; margin-top:14px; }
    .paq-auto-field { padding:12px; border:1px solid var(--border); border-radius:10px; background:var(--surface); }
    .paq-auto-field.full { grid-column:1/-1; }
    .paq-auto-field small { display:block; margin-bottom:5px; color:var(--muted); font-size:11px; font-weight:700; text-transform:uppercase; }
    .paq-auto-input { width:100%; box-sizing:border-box; border:0; outline:0; padding:0; background:transparent; color:var(--text); font:inherit; }
    .paq-auto-input::placeholder { color:var(--muted); opacity:.85; }
    .paq-items { display:grid; gap:8px; margin-top:12px; }
    .paq-item { display:grid; grid-template-columns:22px minmax(0,1fr) 190px 34px; align-items:center; gap:8px; padding:8px; border:1px solid var(--border); border-radius:9px; background:var(--surface); }
    .paq-item input[type="checkbox"] { width:18px; height:18px; accent-color:var(--primary); }
    .paq-item-input { width:100%; min-width:0; box-sizing:border-box; padding:9px 10px; border:1px solid var(--border); border-radius:8px; background:var(--surface-2); color:var(--text); font:inherit; }
    .paq-item-remove { width:30px; height:30px; border:0; border-radius:50%; background:var(--surface-2); color:var(--muted); cursor:pointer; font-size:18px; }
    .paq-item-remove:hover { color:var(--text); background:var(--border); }
    .paq-add-item { width:max-content; margin-top:10px; border:1px dashed var(--border); border-radius:999px; padding:9px 14px; background:transparent; color:var(--primary); cursor:pointer; font:inherit; font-weight:600; }
    .paq-wizard-foot { display:flex; justify-content:flex-end; margin-top:18px; }
    .paq-wizard-foot.split { justify-content:space-between; }
    .paq-wizard-error { flex:1; align-self:center; margin:0 12px; color:#ff6b6b; font-size:13px; font-weight:700; }
    .paq-wizard-error[hidden] { display:none; }
    .paq-back { border:0; border-radius:999px; padding:10px 16px; background:var(--surface-2); color:var(--text); cursor:pointer; font:inherit; font-weight:700; }
    .paq-back:hover { background:var(--border); }
    .paq-suggestion { display:flex; align-items:center; justify-content:space-between; gap:14px; margin-bottom:14px; padding:14px 16px; border-radius:12px; background:var(--primary-soft); color:var(--primary); font-weight:700; }
    .paq-suggestion[hidden], .paq-step[hidden] { display:none; }
    .paq-couriers { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:10px; }
    .paq-courier { display:flex; align-items:center; gap:10px; min-height:64px; padding:10px 12px; border:1px solid var(--border); border-radius:12px; background:var(--surface); color:var(--text); cursor:pointer; font:inherit; font-weight:700; text-align:left; }
    .paq-courier:hover, .paq-courier.selected { border-color:var(--primary); background:var(--primary-soft); }
    .paq-courier-mark { display:grid; place-items:center; flex:0 0 38px; height:38px; border-radius:10px; background:var(--primary); color:#fff; font-size:12px; }
    .paq-other { border-style:dashed; color:var(--primary); justify-content:center; }
    .paq-tracking { margin-top:14px; padding:16px; border:1px solid var(--border); border-radius:14px; background:var(--surface-2); }
    .paq-tracking[hidden] { display:none; }
    .paq-tracking-input { width:100%; box-sizing:border-box; margin-top:8px; padding:12px 14px; border:1px solid var(--border); border-radius:10px; background:var(--surface); color:var(--text); font:inherit; }
    .paq-tracking-note { margin:8px 0 0; color:var(--muted); font-size:12px; }
    .paq-guide-picker { margin-top:14px; padding:16px; border:1px solid var(--border); border-radius:14px; background:var(--surface-2); }
    .paq-guide-picker[hidden] { display:none; }
    .paq-guide-tabs { display:flex; width:max-content; margin-bottom:14px; padding:4px; border-radius:10px; background:var(--surface); }
    .paq-guide-tab { border:0; border-radius:8px; padding:9px 14px; background:transparent; color:var(--muted); cursor:pointer; font:inherit; font-weight:700; }
    .paq-guide-tab.active { background:var(--primary); color:#fff; }
    .paq-guide-list { display:flex; flex-wrap:wrap; gap:8px; }
    .paq-guide { border:1px solid var(--border); border-radius:999px; padding:10px 14px; background:var(--surface); color:var(--text); cursor:pointer; font:inherit; font-weight:700; }
    .paq-guide:hover, .paq-guide.selected { border-color:var(--primary); background:var(--primary); color:#fff; }
    .paq-virtual-content[hidden] { display:none; }
    .paq-required { display:inline-block; margin-left:5px; padding:4px 7px; border-radius:7px; background:var(--primary-soft); color:var(--primary); font-size:11px; }
    .paq-info-card { margin-top:14px; padding:14px 16px; border-radius:12px; background:var(--primary-soft); color:var(--primary); font-weight:700; line-height:1.45; }
    .paq-package-toggle { display:flex; width:max-content; margin-bottom:16px; padding:4px; border-radius:10px; background:var(--surface); }
    .paq-package-mode { border:0; border-radius:8px; padding:10px 14px; background:transparent; color:var(--muted); cursor:pointer; font:inherit; font-weight:700; }
    .paq-package-mode.active { background:var(--primary); color:#fff; }
    .paq-package { padding:16px; border:1px solid var(--border); border-radius:14px; background:var(--surface); }
    .paq-package + .paq-package { margin-top:10px; }
    .paq-package-head { display:flex; align-items:center; gap:12px; margin-bottom:14px; }
    .paq-package-icon { width:58px; height:28px; border-radius:4px; background:#d6a766; box-shadow:inset 0 0 0 2px rgba(70,40,10,.18); }
    .paq-package-title { font-weight:800; }
    .paq-package-meta { margin-top:4px; color:var(--muted); font-size:13px; }
    .paq-measure-grid { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:10px; }
    .paq-measure label { display:block; margin-bottom:5px; color:var(--muted); font-size:12px; font-weight:700; }
    .paq-measure input { width:100%; box-sizing:border-box; padding:11px 12px; border:1px solid var(--border); border-radius:9px; background:var(--surface-2); color:var(--text); font:inherit; }
    .paq-package-note { margin:12px 0 0; color:var(--muted); font-size:13px; }
    .paq-package-actions { display:flex; gap:10px; flex-wrap:wrap; margin-top:14px; }
    .paq-no-measures-panel { padding:16px; border:1px solid var(--border); border-radius:14px; background:var(--surface); }
    .paq-no-measures-panel .paq-tracking-input { margin:0 0 12px; }
    .paq-measures-summary { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:10px; margin-top:16px; }
    .paq-measure-total { padding:14px; border-radius:12px; background:var(--surface); color:var(--muted); text-align:center; }
    .paq-measure-total strong { display:block; margin-top:5px; color:var(--text); font-size:22px; }
    .paq-measure-total.highlight { background:var(--primary); color:#fff; }
    .paq-measure-total.highlight strong { color:#fff; }
    .paq-calculation { margin-top:14px; padding:12px 14px; border-radius:10px; background:var(--surface); color:var(--muted); }
    .paq-capacity { margin-top:14px; color:var(--text); }
    .paq-capacity-label { margin-bottom:8px; font-weight:700; }
    .paq-capacity-label span { display:inline-block; margin-left:5px; padding:4px 7px; border-radius:7px; background:var(--primary-soft); color:var(--primary); font-size:11px; }
    .paq-capacity-bar { position:relative; height:28px; overflow:hidden; border-radius:999px; background:var(--surface); }
    .paq-capacity-bar i { display:block; height:100%; border-radius:inherit; background:#75cf72; transition:width .2s ease; }
    .paq-capacity-bar strong { position:absolute; inset:0; display:grid; place-items:center; color:var(--text); font-size:13px; }
    .paq-capacity-warning { display:flex; align-items:center; justify-content:space-between; gap:12px; margin-top:14px; padding:14px 16px; border-radius:14px; background:rgba(127,29,29,.28); color:#ff6b6b; font-weight:700; }
    .paq-capacity-success { margin-top:14px; padding:14px 16px; border-radius:14px; background:rgba(20,100,45,.45); color:#22d565; font-weight:700; }
    .paq-capacity-warning[hidden], .paq-capacity-success[hidden], .paq-capacity[hidden] { display:none; }
    .paq-date-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:12px; }
    .paq-date-field { padding:12px; border:1px solid var(--border); border-radius:10px; background:var(--surface); }
    .paq-date-field label, .paq-cost-label { display:block; margin-bottom:7px; color:var(--muted); font-size:13px; font-weight:700; }
    .paq-date-input, .paq-cost-input, .paq-notes { width:100%; box-sizing:border-box; padding:11px 12px; border:1px solid var(--border); border-radius:9px; background:var(--surface-2); color:var(--text); font:inherit; }
    .paq-date-input.auto { border-color:var(--primary); background:var(--primary-soft); }
    .paq-date-note, .paq-cost-note { margin:9px 0 0; color:var(--muted); font-size:13px; }
    .paq-cost-input { margin-bottom:10px; }
    .paq-cost-warning { margin:10px 0 12px; padding:14px 16px; border-radius:12px; background:rgba(126,82,24,.35); color:#ffb53d; font-weight:700; }
    .paq-cost-warning[hidden] { display:none; }
    .paq-check { display:flex; align-items:center; gap:8px; color:var(--muted); font-size:13px; }
    .paq-check input { width:17px; height:17px; accent-color:var(--primary); }
    .paq-people { display:flex; flex-wrap:wrap; gap:8px; }
    .paq-person { border:1px solid var(--border); border-radius:999px; padding:10px 14px; background:var(--surface); color:var(--text); cursor:pointer; font:inherit; font-weight:700; }
    .paq-person.selected { border-color:var(--primary); background:var(--primary); color:#fff; }
    .paq-notes { min-height:90px; resize:vertical; margin-top:8px; }
    .paq-review-summary { padding:22px; border-radius:16px; background:var(--surface); text-align:center; }
    .paq-review-label { color:var(--muted); font-size:13px; }
    .paq-review-total { margin:8px 0 5px; font-size:40px; font-weight:800; }
    .paq-review-sub { color:var(--muted); font-size:13px; }
    .paq-review-route { margin-top:14px; padding:22px; border-radius:16px; background:var(--surface); text-align:center; }
    .paq-review-route-title { color:var(--muted); font-weight:700; }
    .paq-review-route-line { display:flex; align-items:center; gap:10px; margin:24px 0 10px; }
    .paq-review-route-line i { flex:1; height:5px; border-radius:999px; background:var(--primary); }
    .paq-review-route-line b { width:12px; height:12px; border:3px solid var(--primary); border-radius:50%; }
    .paq-review-route-points, .paq-review-route-dates { display:flex; justify-content:space-between; gap:12px; font-size:13px; }
    .paq-review-route-dates { margin-top:22px; color:var(--muted); }
    .paq-review-missing { margin-top:14px; padding:12px 14px; border-radius:12px; background:rgba(127,29,29,.3); color:#ff6b6b; font-weight:700; }
    .paq-review-section-title { margin:16px 0 8px; color:var(--muted); font-size:13px; font-weight:700; }
    .paq-review-package { display:flex; align-items:center; gap:12px; margin-top:8px; padding:13px 14px; border-radius:12px; background:var(--surface); }
    .paq-review-package-main { flex:1; min-width:0; }
    .paq-review-package-main strong, .paq-review-package-main small { display:block; }
    .paq-review-package-main small { margin-top:4px; color:var(--muted); }
    .paq-review-package-weight { text-align:right; font-weight:800; }
    .paq-review-package-weight small { display:block; color:var(--muted); font-weight:400; }
    .paq-review-meta { display:grid; grid-template-columns:repeat(5,minmax(0,1fr)); gap:8px; margin-top:12px; }
    .paq-review-meta div { min-width:0; padding:12px; border-radius:12px; background:var(--surface); }
    .paq-review-meta small, .paq-review-meta strong { display:block; }
    .paq-review-meta small { color:var(--muted); font-size:11px; }
    .paq-review-meta strong { margin-top:5px; overflow-wrap:anywhere; font-size:13px; }
    .paq-review-actions { display:flex; gap:10px; margin-top:14px; }
    .paq-review-actions .btn { flex:1; }
    .paq-save-button { background:#45c878 !important; border-color:#45c878 !important; color:#fff !important; }
    @media (max-width:700px) { .paq-review-meta { grid-template-columns:repeat(2,minmax(0,1fr)); } .paq-review-actions { flex-direction:column; } }
    @media (max-width:700px) { .paq-date-grid { grid-template-columns:1fr; } }
    @media (max-width: 700px) { .paq-measure-grid { grid-template-columns:repeat(2,minmax(0,1fr)); } .paq-measures-summary { grid-template-columns:1fr; } }
    .paq-virtual-content[hidden] { display:none; }
    .paq-required { display:inline-block; margin-left:5px; padding:4px 7px; border-radius:7px; background:var(--primary-soft); color:var(--primary); font-size:11px; }
    .paq-info-card { margin-top:14px; padding:14px 16px; border-radius:12px; background:var(--primary-soft); color:var(--primary); font-weight:700; line-height:1.45; }
    @media (max-width: 700px) { .paq-couriers { grid-template-columns:repeat(2,minmax(0,1fr)); } }
    @media (max-width: 600px) { .paq-modal { padding:10px; } .paq-wizard { max-height:calc(100vh - 20px); padding:18px; } .paq-remision { grid-template-columns:1fr auto; } .paq-remision small { grid-column:1/-1; } .paq-auto-grid { grid-template-columns:1fr; } .paq-auto-field.full { grid-column:auto; } .paq-item { grid-template-columns:22px minmax(0,1fr) 30px; } .paq-item-input[data-item-serial] { grid-column:2; } .paq-item-remove { grid-column:3; grid-row:1 / span 2; } }
    @media (max-width: 850px) { .paq-topbar { align-items:flex-start; flex-direction:column; } .paq-stats { grid-template-columns:repeat(2,minmax(0,1fr)); } }
    @media (max-width: 560px) { .paq-stats { grid-template-columns:1fr; } .paq-total { font-size:34px; } .paq-tabs { width:100%; } .paq-tab { flex:1; } }
</style>
@endpush

@php
    $paqueterias = [
        ['nombre' => 'Todas las paqueterías', 'cantidad' => 0, 'color' => 'currentColor'],
        ['nombre' => 'FedEx', 'cantidad' => 0, 'color' => '#7c3aed'],
        ['nombre' => 'Estafeta', 'cantidad' => 0, 'color' => '#ef4444'],
        ['nombre' => 'DHL', 'cantidad' => 0, 'color' => '#eab308'],
        ['nombre' => 'Paquetexpress', 'cantidad' => 0, 'color' => '#3b82f6'],
        ['nombre' => 'Tres Guerras', 'cantidad' => 0, 'color' => '#22c55e'],
        ['nombre' => 'Castores', 'cantidad' => 0, 'color' => '#a16207'],
        ['nombre' => 'Autobús', 'cantidad' => 0, 'color' => '#0d9488'],
    ];
@endphp
@php
    $enviosConteo = collect($envios)->groupBy('paqueteria')->map->count();
    $enviosConPeso = collect($envios)->sum('peso_cobro');
    $costoPorKilo = $enviosConPeso > 0 && $resumen['costo'] > 0 ? $resumen['costo'] / $enviosConPeso : null;
@endphp

@section('content')
    <div class="paq-page" data-paqueteria-page>
        <div class="paq-topbar">
            <div class="paq-actions">
                <button type="button" class="btn" id="paq-new-shipment" title="Nuevo envío">
                    <span aria-hidden="true">+</span> Nuevo envío
                </button>
            </div>
        </div>

        <div class="paq-filters" role="group" aria-label="Filtrar por paquetería">
            @foreach ($paqueterias as $i => $paqueteria)
                <button type="button" class="paq-filter {{ $i === 0 ? 'active' : '' }}" data-paq-filter="{{ $paqueteria['nombre'] }}">
                    @if ($i > 0)<span class="paq-dot" style="color:{{ $paqueteria['color'] }}"></span>@endif
                    {{ $paqueteria['nombre'] }} <small>{{ $i === 0 ? $resumen['total'] : $enviosConteo->get($paqueteria['nombre'], 0) }}</small>
                </button>
            @endforeach
        </div>

        <section class="paq-hero" aria-labelledby="paq-heading">
            <p class="paq-eyebrow">Envíos y paqueterías · todas las paqueterías</p>
            <h1 id="paq-heading">Envíos</h1>
            <div class="paq-total">${{ number_format($resumen['costo'], 2) }}</div>
            <p class="paq-description">{{ $resumen['total'] ? $resumen['total'].' envío(s) registrados.' : 'Aún no hay envíos registrados.' }}</p>
        </section>

        <div class="paq-tabs" role="tablist" aria-label="Secciones de paquetería">
            <button type="button" class="paq-tab active" role="tab" aria-selected="true" data-paq-tab="resumen">Resumen</button>
            <button type="button" class="paq-tab" role="tab" aria-selected="false" data-paq-tab="envios">Envíos</button>
            <button type="button" class="paq-tab" role="tab" aria-selected="false" data-paq-tab="guias">Guías</button>
        </div>

        <section class="paq-stats" id="paq-resumen-content" aria-label="Resumen de envíos">
            <article class="paq-stat">
                <div class="paq-stat-label">Envíos este mes</div>
                <div class="paq-stat-value">{{ $resumen['total'] }}</div>
                <div class="paq-stat-note">${{ number_format($resumen['costo'], 2) }} registrados</div>
            </article>
            <article class="paq-stat blue">
                <div class="paq-stat-label">En camino</div>
                <div class="paq-stat-value">{{ $resumen['en_camino'] }}</div>
                <div class="paq-stat-note">Envíos con guía</div>
            </article>
            <article class="paq-stat amber">
                <div class="paq-stat-label">Por enviar</div>
                <div class="paq-stat-value">{{ $resumen['por_enviar'] }}</div>
                <div class="paq-stat-note">Sin guía capturada</div>
            </article>
            <article class="paq-stat green">
                <div class="paq-stat-label">Guías físicas</div>
                <div class="paq-stat-value">{{ collect($envios)->where('tipo_guia', 'fisica')->count() }}</div>
                <div class="paq-stat-note">Guías físicas utilizadas</div>
            </article>
            <article class="paq-stat">
                <div class="paq-stat-label">Costo por kilo</div>
                <div class="paq-stat-value">{{ $costoPorKilo !== null ? '$'.number_format($costoPorKilo, 2) : '—' }}</div>
                <div class="paq-stat-note">Costo promedio por kilo</div>
            </article>
            <article class="paq-stat danger">
                <div class="paq-stat-label">Por completar</div>
                <div class="paq-stat-value">{{ $resumen['por_completar'] }}</div>
                <div class="paq-stat-note">Costo pendiente</div>
            </article>
        </section>

        <div class="paq-placeholder" id="paq-resumen-placeholder">
            <strong>Resumen de paquetería</strong>
            <span> — los envíos guardados aparecen abajo.</span>
        </div>

        <section class="paq-envios-panel" id="paq-envios-content" aria-label="Envíos registrados" hidden>
            @forelse ($envios as $envio)
                <article class="paq-envio-card" data-paq-shipment data-courier="{{ $envio['paqueteria'] }}" data-envio-id="{{ $envio['id'] }}">
                    <div>
                        <strong>{{ $envio['paqueteria'] }} · {{ $envio['cliente'] }}</strong>
                        <small>{{ $envio['destino'] }} · {{ $envio['fecha_envio'] }} · {{ $envio['numero_guia'] ?: 'Sin guía' }}</small>
                        <small>{{ $envio['paquetes_count'] }} paquete(s) · {{ number_format($envio['peso_cobro'], 2) }} kg para cobrar · {{ $envio['costo_pendiente'] ? 'Costo pendiente' : '$'.number_format($envio['costo'], 2) }}</small>
                        @if ($envio['tracking_url'])<small><a href="{{ $envio['tracking_url'] }}" target="_blank" rel="noopener">Rastrear guía</a></small>@endif
                    </div>
                    <div><span class="paq-envio-status">{{ $envio['estado'] === 'en_camino' ? 'En camino' : 'Por enviar' }}</span><button type="button" class="paq-change" data-edit-envio="{{ $envio['id'] }}">Editar</button></div>
                </article>
            @empty
                <div class="paq-empty">Todavía no hay envíos guardados.</div>
            @endforelse
        </section>

        <section class="paq-envios-panel" id="paq-guias-content" aria-label="Guías FedEx" hidden>
            <div class="paq-placeholder"><strong>Control de guías</strong><span> — las guías físicas se descuentan automáticamente al usarlas.</span></div>
            @forelse ($guias as $guia)
                <article class="paq-guide-admin">
                    <div><strong>{{ $guia['paqueteria'] }} · {{ $guia['numero'] }}</strong><small>{{ $guia['capacidad'] ? number_format($guia['capacidad'], 2).' kg' : 'Sin capacidad' }}@if ($guia['usada_at']) · Usada el {{ $guia['usada_at'] }}@endif</small></div>
                    <span class="paq-guide-admin-status {{ $guia['estado'] === 'disponible' ? 'available' : 'used' }}">{{ $guia['estado'] === 'disponible' ? 'Disponible' : 'Usada' }}</span>
                </article>
            @empty
                <div class="paq-empty">Todavía no hay guías registradas.</div>
            @endforelse
        </section>
    </div>

    <div class="paq-modal" id="paq-detail-modal" hidden>
        <div class="paq-wizard" role="dialog" aria-modal="true" aria-labelledby="paq-detail-title">
            <div class="paq-wizard-head"><div><p class="paq-wizard-step">Detalle del envío</p><h2 id="paq-detail-title">Envío</h2></div><button type="button" class="paq-close" data-paq-detail-close>Cerrar</button></div>
            <div id="paq-detail-content"></div>
        </div>
    </div>

    <div class="paq-modal" id="paq-wizard-modal" hidden>
        <div class="paq-wizard" role="dialog" aria-modal="true" aria-labelledby="paq-wizard-title">
            <div class="paq-wizard-head">
                <div>
                    <p class="paq-wizard-step">Nuevo envío · Paso 1 de 5</p>
                    <h2 id="paq-wizard-title">¿Qué envías y a quién?</h2>
                </div>
                <button type="button" class="paq-close" data-paq-close>Cancelar</button>
            </div>
            <div class="paq-progress" aria-hidden="true"><i></i><i></i><i></i><i></i><i></i></div>

            <section class="paq-wizard-card" id="paq-remision-picker">
                <label class="paq-label" for="paq-remision-search">Remisión</label>
                <input class="paq-search" id="paq-remision-search" type="search" placeholder="Busca por número, cliente o ciudad..." autocomplete="off">
                <div class="paq-label" style="margin-top:16px; margin-bottom:0;">Últimas remisiones del sistema</div>
                <div class="paq-remisiones" id="paq-remisiones-list"></div>
                <button type="button" class="paq-change" id="paq-no-remision" style="margin-top:12px;">No tiene remisión</button>
            </section>

            <section class="paq-wizard-card" id="paq-remision-data">
                <div class="paq-manual-banner" id="paq-manual-banner" hidden>
                    <span>Envío sin remisión: llena los datos del cliente abajo.</span>
                    <button type="button" class="paq-change" id="paq-use-remision">Usar remisión</button>
                </div>
                <div class="paq-selected" id="paq-selected-remision">
                    <div><strong id="paq-selected-folio">Datos del envío</strong><small id="paq-selected-summary">Selecciona una remisión o captura los datos manualmente</small></div>
                    <button type="button" class="paq-change" id="paq-change-remision">Cambiar</button>
                </div>
                <div class="paq-auto-grid">
                    <label class="paq-auto-field full"><small>Cliente</small><input class="paq-auto-input" id="paq-data-cliente" type="text" placeholder="Nombre del doctor u hospital"></label>
                    <label class="paq-auto-field"><small>Destino (ciudad, estado)</small><input class="paq-auto-input" id="paq-data-destino" type="text" placeholder="Monterrey, Nuevo León"></label>
                    <label class="paq-auto-field"><small>Código postal</small><input class="paq-auto-input" id="paq-data-cp" type="text" inputmode="numeric" placeholder="64000"></label>
                    <label class="paq-auto-field"><small>Dirección</small><input class="paq-auto-input" id="paq-data-direccion" type="text" placeholder="Calle, número, colonia"></label>
                    <label class="paq-auto-field"><small>Teléfono de quien recibe</small><input class="paq-auto-input" id="paq-data-telefono" type="text" inputmode="tel" placeholder="Teléfono"></label>
                </div>
                <div class="paq-label" style="margin-top:16px; margin-bottom:0;">¿Qué va en el paquete? <span id="paq-items-origin"></span></div>
                <div class="paq-items" id="paq-data-items"><div class="paq-empty">Selecciona una remisión para cargar sus artículos.</div></div>
                <button type="button" class="paq-add-item" id="paq-add-item">+ Agregar artículo</button>
            </section>

            <section class="paq-wizard-card paq-step" id="paq-step-2" hidden>
                <div class="paq-suggestion" id="paq-courier-suggestion">
                    <span>💡 Selecciona la paquetería que utilizarás para este envío.</span>
                    <button type="button" class="btn" id="paq-use-suggestion" hidden>Usar sugerencia</button>
                </div>
                <div class="paq-label">Paquetería</div>
                <div class="paq-couriers" id="paq-couriers">
                    <button type="button" class="paq-courier" data-courier="FedEx"><span class="paq-courier-mark">FX</span>FedEx</button>
                    <button type="button" class="paq-courier" data-courier="Estafeta"><span class="paq-courier-mark">ES</span>Estafeta</button>
                    <button type="button" class="paq-courier" data-courier="DHL"><span class="paq-courier-mark">DHL</span>DHL</button>
                    <button type="button" class="paq-courier" data-courier="Paquetexpress"><span class="paq-courier-mark">PX</span>Paquetexpress</button>
                    <button type="button" class="paq-courier" data-courier="Tres Guerras"><span class="paq-courier-mark">3G</span>Tres Guerras</button>
                    <button type="button" class="paq-courier" data-courier="Castores"><span class="paq-courier-mark">CA</span>Castores</button>
                    <button type="button" class="paq-courier" data-courier="Autobús"><span class="paq-courier-mark">BUS</span>Autobús</button>
                    <button type="button" class="paq-courier paq-other" data-courier="Otra">+ Otra paquetería</button>
                </div>
                <div class="paq-tracking" id="paq-other-panel" hidden>
                    <label class="paq-label" for="paq-other-name">Nombre de la paquetería</label>
                    <input class="paq-tracking-input" id="paq-other-name" type="text" placeholder="Ej. Redpack" autocomplete="off">
                </div>
                <div class="paq-tracking" id="paq-tracking-panel" hidden>
                    <label class="paq-label" for="paq-tracking-number">Número de guía (rastreo)</label>
                    <input class="paq-tracking-input" id="paq-tracking-number" type="text" placeholder="Opcional: la puedes poner después" autocomplete="off">
                    <p class="paq-tracking-note">Si la capturas, el envío podrá identificarse con esta guía.</p>
                </div>
                <div class="paq-guide-picker" id="paq-fedex-guides" hidden>
                    <div class="paq-guide-tabs">
                        <button type="button" class="paq-guide-tab active" data-guide-type="fisica">Guía física ({{ $guiasFedex->count() }})</button>
                        <button type="button" class="paq-guide-tab" data-guide-type="virtual">Guía virtual</button>
                    </div>
                    <div class="paq-label">Elige la guía</div>
                    <div class="paq-guide-list" id="paq-fedex-guide-list">
                        @forelse ($guiasFedex as $indice => $guia)
                            <button type="button" class="paq-guide {{ $indice === 0 ? 'selected' : '' }}" data-guide-id="{{ $guia['id'] }}" data-guide="{{ $guia['numero'] }}" data-capacity="{{ $guia['capacidad'] }}">{{ $guia['numero'] }} · {{ rtrim(rtrim(number_format($guia['capacidad'], 2), '0'), '.') }} kg</button>
                        @empty
                            <span class="paq-tracking-note">No hay guías físicas FedEx disponibles.</span>
                        @endforelse
                    </div>
                    <div class="paq-virtual-content" id="paq-fedex-virtual" hidden>
                        <label class="paq-label" for="paq-virtual-guide-number">Número de la guía virtual <span class="paq-required">Obligatorio</span></label>
                        <input class="paq-tracking-input" id="paq-virtual-guide-number" type="text" placeholder="El número que te dio FedEx" autocomplete="off">
                        <div class="paq-info-card">📦 Guía virtual: FedEx ya no da guía en papel. El número de guía y la foto del ticket de envío son el comprobante. El ticket lo subes en “Fechas y total”.</div>
                    </div>
                    <p class="paq-tracking-note" id="paq-guide-note">Selecciona una de tus guías físicas disponibles.</p>
                </div>
            </section>

            <section class="paq-wizard-card paq-step" id="paq-step-3" hidden>
                <div class="paq-package-toggle">
                    <button type="button" class="paq-package-mode active" data-package-mode="individual">Cada producto en su caja</button>
                    <button type="button" class="paq-package-mode" data-package-mode="single">Todo en una sola caja</button>
                </div>
                <div id="paq-measures-content">
                    <div id="paq-packages-list"></div>
                    <div class="paq-package-actions">
                        <button type="button" class="paq-add-item" id="paq-add-package">+ Otro paquete</button>
                        <button type="button" class="paq-change" id="paq-no-measures">No tengo las medidas</button>
                    </div>
                </div>
                <div class="paq-no-measures-panel" id="paq-no-measures-panel" hidden>
                    <label class="paq-label" for="paq-real-weight-input">Peso real total (kg)</label>
                    <input class="paq-tracking-input" id="paq-real-weight-input" type="number" min="0" step="0.01" value="0.0" placeholder="0.0">
                    <button type="button" class="btn" id="paq-no-measures-again">No tengo las medidas</button>
                </div>
                <div class="paq-measures-summary">
                    <div class="paq-measure-total"><span>Peso real</span><strong id="paq-real-weight">0 kg</strong></div>
                    <div class="paq-measure-total"><span>Por volumen</span><strong id="paq-volume-weight">0 kg</strong></div>
                    <div class="paq-measure-total highlight"><span id="paq-charge-label">Para cobrar · 0 paquetes</span><strong id="paq-charge-weight">0 kg</strong></div>
                </div>
                <div class="paq-calculation" id="paq-calculation">Captura las medidas para calcular el peso por volumen.</div>
                <div class="paq-capacity" id="paq-fedex-capacity" hidden>
                    <div class="paq-capacity-label">¿Cabe en la guía <span id="paq-fedex-capacity-guide"></span>?</div>
                    <div class="paq-capacity-bar"><i id="paq-fedex-capacity-fill"></i><strong id="paq-fedex-capacity-text"></strong></div>
                    <div class="paq-capacity-warning" id="paq-fedex-capacity-warning" hidden>
                        <span id="paq-fedex-capacity-warning-text"></span>
                        <button type="button" class="btn" id="paq-join-package">Juntar en una caja</button>
                    </div>
                    <div class="paq-capacity-success" id="paq-fedex-capacity-success" hidden></div>
                </div>
            </section>

            <section class="paq-wizard-card paq-step" id="paq-step-4" hidden>
                <div class="paq-date-grid">
                    <div class="paq-date-field">
                        <label for="paq-shipment-date">Fecha de envío</label>
                        <input class="paq-date-input" id="paq-shipment-date" type="date">
                    </div>
                    <div class="paq-date-field">
                        <label for="paq-delivery-date">Entrega al cliente (estimada) <span class="paq-required">Calculada</span></label>
                        <input class="paq-date-input auto" id="paq-delivery-date" type="date">
                    </div>
                </div>
                <p class="paq-date-note" id="paq-delivery-note">La fecha estimada se calcula según la paquetería seleccionada.</p>
            </section>
            <section class="paq-wizard-card paq-step" id="paq-step-4-cost" hidden>
                <label class="paq-cost-label" for="paq-shipment-cost">¿Cuánto costó el envío?</label>
                <input class="paq-cost-input" id="paq-shipment-cost" type="number" min="0" step="0.01" placeholder="$0.00">
                <p class="paq-cost-note" id="paq-cost-reference">Captura el costo real o marca la opción de costo pendiente.</p>
                <div class="paq-cost-warning" id="paq-cost-warning" hidden></div>
                <label class="paq-check"><input id="paq-cost-pending" type="checkbox"> Todavía no tengo el costo (queda “Por completar”)</label>
            </section>
            <section class="paq-wizard-card paq-step" id="paq-step-4-carrier" hidden>
                <label class="paq-cost-label">¿Quién lo lleva a la paquetería?</label>
                <div class="paq-people" id="paq-people-list"></div>
                <label class="paq-cost-label" for="paq-shipment-notes" style="margin-top:16px">Notas — opcional</label>
                <textarea class="paq-notes" id="paq-shipment-notes" placeholder="Incidencias, instrucciones de entrega..."></textarea>
                <div id="paq-ticket-panel" hidden style="margin-top:16px">
                    <label class="paq-cost-label" for="paq-ticket-file">Ticket de envío FedEx — opcional</label>
                    <input class="paq-tracking-input" id="paq-ticket-file" type="file" accept="image/*,application/pdf">
                    <p class="paq-cost-note" id="paq-ticket-name">El ticket sirve como comprobante de la guía virtual.</p>
                </div>
            </section>
            <section class="paq-step" id="paq-step-5" hidden>
                <div id="paq-review-content"></div>
            </section>

            <div class="paq-wizard-foot split">
                <button type="button" class="paq-back" id="paq-prev-step" hidden>← Atrás</button>
                <div class="paq-wizard-error" id="paq-wizard-error" hidden></div>
                <button type="button" class="btn" id="paq-next-step" disabled>Siguiente →</button>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    const paqueteriaRemisiones = @json($remisiones);
    const paqueteriaUsuarios = @json($usuarios);
    const paqueteriaEnvios = @json($envios);
    const paqueteriaResumen = @json($resumen);
    const paqueteriaGuias = @json($guiasFedex);
    const paqModal = document.getElementById('paq-wizard-modal');
    const paqDetailModal = document.getElementById('paq-detail-modal');
    const paqDetailContent = document.getElementById('paq-detail-content');
    const paqRemisionSearch = document.getElementById('paq-remision-search');
    const paqRemisionesList = document.getElementById('paq-remisiones-list');
    const paqRemisionPicker = document.getElementById('paq-remision-picker');
    const paqRemisionData = document.getElementById('paq-remision-data');
    const paqManualBanner = document.getElementById('paq-manual-banner');
    const paqSelectedRemisionCard = document.getElementById('paq-selected-remision');
    const paqNextStep = document.getElementById('paq-next-step');
    const paqPrevStep = document.getElementById('paq-prev-step');
    const paqStepTwo = document.getElementById('paq-step-2');
    const paqStepThree = document.getElementById('paq-step-3');
    const paqStepFour = document.getElementById('paq-step-4');
    const paqStepFourCost = document.getElementById('paq-step-4-cost');
    const paqStepFourCarrier = document.getElementById('paq-step-4-carrier');
    const paqStepFive = document.getElementById('paq-step-5');
    const paqWizardError = document.getElementById('paq-wizard-error');
    const paqTicketPanel = document.getElementById('paq-ticket-panel');
    const paqTicketFile = document.getElementById('paq-ticket-file');
    const paqTicketName = document.getElementById('paq-ticket-name');
    const paqTrackingPanel = document.getElementById('paq-tracking-panel');
    const paqTrackingNumber = document.getElementById('paq-tracking-number');
    const paqOtherPanel = document.getElementById('paq-other-panel');
    const paqOtherName = document.getElementById('paq-other-name');
    const paqFedexGuides = document.getElementById('paq-fedex-guides');
    const paqFedexGuideList = document.getElementById('paq-fedex-guide-list');
    const paqFedexVirtual = document.getElementById('paq-fedex-virtual');
    const paqVirtualGuideNumber = document.getElementById('paq-virtual-guide-number');
    const paqCourierSuggestion = document.getElementById('paq-courier-suggestion');
    const paqFedexCapacity = document.getElementById('paq-fedex-capacity');
    const paqFedexCapacityGuide = document.getElementById('paq-fedex-capacity-guide');
    const paqFedexCapacityFill = document.getElementById('paq-fedex-capacity-fill');
    const paqFedexCapacityText = document.getElementById('paq-fedex-capacity-text');
    const paqFedexCapacityWarning = document.getElementById('paq-fedex-capacity-warning');
    const paqFedexCapacityWarningText = document.getElementById('paq-fedex-capacity-warning-text');
    const paqFedexCapacitySuccess = document.getElementById('paq-fedex-capacity-success');
    let paqStep = 1;
    let paqCourier = '';
    let paqFedexGuideType = 'fisica';
    let paqPackageMode = 'individual';
    let paqPackages = [];
    let paqSinglePackage = null;
    let paqNoMeasures = false;
    let paqRealWeightWithoutMeasures = 0;
    let paqSelectedRemision = null;
    let paqItems = [];
    let paqResponsibleUsers = [];
    let paqTicket = null;

    function paqActualizarPaso(paso) {
        paqStep = paso;
        const pasoUnoVisible = paso === 1;
        paqRemisionPicker.hidden = !pasoUnoVisible || paqManualBanner.hidden === false;
        paqRemisionData.hidden = !pasoUnoVisible;
        paqStepTwo.hidden = paso !== 2;
        paqStepThree.hidden = paso !== 3;
        paqStepFour.hidden = paso !== 4;
        paqStepFourCost.hidden = paso !== 4;
        paqStepFourCarrier.hidden = paso !== 4;
        paqStepFive.hidden = paso !== 5;
        document.querySelector('.paq-wizard-step').textContent = 'Nuevo envío · Paso ' + paso + ' de 5';
        document.querySelector('.paq-wizard h2').textContent = paso === 1 ? '¿Qué envías y a quién?' : (paso === 2 ? '¿Con qué paquetería?' : (paso === 3 ? 'Medidas y peso' : (paso === 4 ? 'Fechas y total' : 'Revisa y guarda')));
        document.querySelectorAll('.paq-progress i').forEach(function (bar, index) { bar.style.background = index < paso ? 'var(--primary)' : 'var(--border)'; });
        paqPrevStep.hidden = paso === 1;
        paqNextStep.disabled = paso === 1 ? !paqSelectedRemision : (paso === 2 ? !paqPuedeContinuarPasoDos() : false);
        paqNextStep.textContent = paso === 5 ? 'Guardar envío' : 'Siguiente →';
        paqNextStep.classList.toggle('paq-save-button', paso === 5);
    }

    function paqPuedeContinuarPasoDos() {
        return Boolean(paqCourier)
            && (paqCourier !== 'Otra' || paqOtherName.value.trim() !== '')
            && (paqCourier !== 'FedEx' || paqFedexGuideType !== 'fisica' || Boolean(paqFedexGuideList.querySelector('.paq-guide.selected')))
            && (paqCourier !== 'FedEx' || paqFedexGuideType === 'fisica' || paqVirtualGuideNumber.value.trim() !== '');
    }

    function paqNombrePaqueteria() {
        return paqCourier === 'Otra' ? paqOtherName.value.trim() : paqCourier;
    }

    function paqMedidaAnterior(nombre) {
        const texto = String(nombre || '').toLowerCase();
        if (/verde|blanca|gris|ideal eyes|lente/.test(texto)) return { largo: 45, ancho: 15, alto: 10, peso: 0.6, auto: true };
        if (/monitor|pantalla|4k/.test(texto)) return { largo: 80, ancho: 55, alto: 25, peso: 10, auto: true };
        if (/endoscop|laparoscop|c[aá]mara|pinza/.test(texto)) return { largo: 60, ancho: 45, alto: 20, peso: 6, auto: true };
        return { largo: '', ancho: '', alto: '', peso: '', auto: false };
    }

    function paqPrepararPaquetes() {
        const grupos = new Map();
        paqItems.filter(item => item.incluido !== false).forEach(function (item) {
            const clave = item.nombre || 'Producto sin nombre';
            const grupo = grupos.get(clave) || { nombre: clave, cantidad: 0, series: [], ...paqMedidaAnterior(clave), extra: false };
            grupo.cantidad += 1;
            if (item.serie) grupo.series.push(item.serie);
            grupos.set(clave, grupo);
        });
        paqPackages = Array.from(grupos.values());
        paqSinglePackage = paqCajaSugerida();
        paqRenderPackages();
    }

    function paqCajaSugerida() {
        const grande = paqPackages.slice().sort(function (a, b) {
            return ((Number(b.largo) || 0) * (Number(b.ancho) || 0) * (Number(b.alto) || 0)) - ((Number(a.largo) || 0) * (Number(a.ancho) || 0) * (Number(a.alto) || 0));
        })[0] || {};
        return {
            nombre: 'Una caja con todo', cantidad: 1,
            series: paqPackages.reduce(function (series, pack) { return series.concat(pack.series || []); }, []),
            contenido: paqPackages.map(function (pack) { return pack.nombre + (pack.cantidad > 1 ? ' ×' + pack.cantidad : ''); }).join(' · '),
            largo: grande.largo || '', ancho: grande.ancho || '', alto: grande.alto || '',
            peso: Math.round(paqPackages.reduce(function (total, pack) { return total + (Number(pack.peso) || 0) * pack.cantidad; }, 0) * 100) / 100,
            extra: false, single: true
        };
    }

    function paqRenderPackages() {
        const list = document.getElementById('paq-packages-list');
        if (paqPackageMode === 'single') paqSinglePackage = paqSinglePackage || paqCajaSugerida();
        const paquetesParaMostrar = paqPackageMode === 'single' ? (paqSinglePackage ? [paqSinglePackage] : []) : paqPackages;
        list.innerHTML = paquetesParaMostrar.length ? paquetesParaMostrar.map(function (pack, index) { return `<div class="paq-package">
            <div class="paq-package-head"><span class="paq-package-icon"></span><div><div class="paq-package-title">${paqEscape(pack.nombre)} ${paqPackageMode === 'single' ? '' : '<span class="paq-required">× ' + pack.cantidad + ' piezas</span>'}</div><div class="paq-package-meta">${paqPackageMode === 'single' ? paqEscape(pack.contenido || 'Todos los productos') : 'Series: ' + paqEscape(pack.series.join(', ') || 'Sin serie')}</div></div></div>
            <div class="paq-measure-grid">
                <div class="paq-measure"><label>Largo</label><input type="number" min="0" step="0.01" data-measure="largo" data-package="${paqPackageMode === 'single' ? 'single' : index}" value="${paqEscape(pack.largo)}" placeholder="cm"></div>
                <div class="paq-measure"><label>Ancho</label><input type="number" min="0" step="0.01" data-measure="ancho" data-package="${paqPackageMode === 'single' ? 'single' : index}" value="${paqEscape(pack.ancho)}" placeholder="cm"></div>
                <div class="paq-measure"><label>Alto</label><input type="number" min="0" step="0.01" data-measure="alto" data-package="${paqPackageMode === 'single' ? 'single' : index}" value="${paqEscape(pack.alto)}" placeholder="cm"></div>
                <div class="paq-measure"><label>Peso</label><input type="number" min="0" step="0.01" data-measure="peso" data-package="${paqPackageMode === 'single' ? 'single' : index}" value="${paqEscape(pack.peso)}" placeholder="kg"></div>
            </div>
            <p class="paq-package-note">${pack.cantidad > 1 ? 'Son '+pack.cantidad+' piezas iguales, cada una en su caja.' : 'Captura las medidas y el peso de este paquete.'}</p>
        </div>`; }).join('') : '<div class="paq-empty">Agrega al menos un paquete para capturar sus medidas.</div>';
        paquetesParaMostrar.forEach(function (pack, index) {
            if (pack.auto) {
                const meta = list.querySelectorAll('.paq-package-meta')[index];
                if (meta) meta.textContent += ' · Medidas de envíos anteriores';
            }
        });
        if (paqPackages.length > 1) {
            list.querySelectorAll('.paq-package').forEach(function (card, index) {
                const remove = document.createElement('button');
                remove.type = 'button';
                remove.className = 'paq-item-remove';
                remove.dataset.removePackage = index;
                remove.setAttribute('aria-label', 'Quitar paquete');
                remove.textContent = '×';
                card.querySelector('.paq-package-head')?.appendChild(remove);
            });
        }
        paqActualizarTotales();
    }

    function paqActualizarTotales() {
        const paquetesCalculables = paqPackageMode === 'single' ? (paqSinglePackage ? [paqSinglePackage] : []) : paqPackages;
        const real = paqNoMeasures
            ? paqRealWeightWithoutMeasures
            : paquetesCalculables.reduce((total, pack) => total + (Number(pack.peso) || 0) * (paqPackageMode === 'single' ? 1 : pack.cantidad), 0);
        const volumen = paqNoMeasures ? 0 : paquetesCalculables.reduce((total, pack) => total + ((Number(pack.largo) || 0) * (Number(pack.ancho) || 0) * (Number(pack.alto) || 0) / 5000) * (paqPackageMode === 'single' ? 1 : pack.cantidad), 0);
        const paquetes = paqNoMeasures ? (real > 0 ? 1 : 0) : (paqPackageMode === 'single' ? (paqPackages.length ? 1 : 0) : paqPackages.reduce((total, pack) => total + pack.cantidad, 0));
        const cobrar = Math.max(real, volumen);
        document.getElementById('paq-real-weight').textContent = real.toFixed(1) + ' kg';
        document.getElementById('paq-volume-weight').textContent = volumen.toFixed(2) + ' kg';
        document.getElementById('paq-charge-label').textContent = 'Para cobrar · ' + paquetes + ' paquetes';
        document.getElementById('paq-charge-weight').textContent = cobrar.toFixed(1) + ' kg';
        const ejemplo = paqPackages.find(function (pack) { return Number(pack.largo) > 0 && Number(pack.ancho) > 0 && Number(pack.alto) > 0; });
        document.getElementById('paq-calculation').textContent = paqNoMeasures
            ? (real > 0 ? 'Peso capturado manualmente. No se calculará el peso por volumen.' : 'Captura el peso real total para continuar.')
            : ejemplo
            ? '¿Cómo se calcula? ' + ejemplo.largo + ' × ' + ejemplo.ancho + ' × ' + ejemplo.alto + ' ÷ 5,000 = ' + ((Number(ejemplo.largo) * Number(ejemplo.ancho) * Number(ejemplo.alto)) / 5000).toFixed(1) + ' kg por volumen, contra ' + (Number(ejemplo.peso) || 0) + ' kg reales: se cobra el mayor.'
            : 'Captura las medidas para calcular el peso por volumen.';
        paqActualizarCapacidadFedex(cobrar, paquetes);
    }

    function paqMostrarSinMedidas() {
        paqNoMeasures = true;
        document.getElementById('paq-measures-content').hidden = true;
        document.getElementById('paq-no-measures-panel').hidden = false;
        document.getElementById('paq-real-weight-input').value = paqRealWeightWithoutMeasures || '0.0';
        paqActualizarTotales();
    }

    function paqActualizarCapacidadFedex(cobrar, paquetes) {
        const visible = paqCourier === 'FedEx' && paqStep === 3;
        paqFedexCapacity.hidden = !visible;
        if (!visible) return;
        const capacidad = 10;
        const pesoParaCobrar = Math.ceil(cobrar);
        const guia = paqTrackingNumber.value || document.querySelector('.paq-guide.selected')?.dataset.guide || 'Sin guía';
        paqFedexCapacityGuide.textContent = guia + ' · FedEx · servicio por confirmar · ' + capacidad + ' kg';
        paqFedexCapacityFill.style.width = Math.min(100, (pesoParaCobrar / capacidad) * 100) + '%';
        paqFedexCapacityText.textContent = pesoParaCobrar + ' kg de ' + capacidad + ' kg · ' + paquetes + ' paquetes';
        const variosPaquetes = paquetes > 1;
        paqFedexCapacityWarning.hidden = !variosPaquetes;
        paqFedexCapacitySuccess.hidden = variosPaquetes;
        paqFedexCapacityWarningText.textContent = variosPaquetes
            ? 'Cada guía ampara un solo paquete y aquí van ' + paquetes + ' paquetes. Júntalos en una caja o usa ' + paquetes + ' guías.'
            : '';
        paqFedexCapacitySuccess.textContent = !variosPaquetes ? 'Cabe en la guía de ' + capacidad + ' kg: pesa ' + cobrar.toFixed(1) + ' kg para cobrar.' : '';
    }

    function paqEscape(value) {
        return String(value ?? '').replace(/[&<>"']/g, function (character) {
            return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[character];
        });
    }

    function paqFechaWhatsApp(valor) {
        if (!valor) return 'por confirmar';
        const partes = String(valor).split('-').map(Number);
        if (partes.length !== 3 || partes.some(Number.isNaN)) return valor;
        const fecha = new Date(partes[0], partes[1] - 1, partes[2]);
        const dias = ['domingo', 'lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado'];
        const meses = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
        return dias[fecha.getDay()] + ' ' + partes[2] + ' de ' + meses[partes[1] - 1];
    }

    function paqFechaCorta(valor) {
        if (!valor) return 'por confirmar';
        const partes = String(valor).split('-').map(Number);
        if (partes.length !== 3 || partes.some(Number.isNaN)) return valor;
        const meses = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];
        return partes[2] + ' ' + meses[partes[1] - 1] + ' ' + partes[0];
    }

    function paqContenidoWhatsApp() {
        const contenido = paqItems
            .filter(function (item) { return item.incluido !== false && item.nombre && item.nombre.trim(); })
            .map(function (item) {
                const nombre = item.nombre.trim();
                return item.serie && item.serie.trim() ? nombre + ' (serie ' + item.serie.trim() + ')' : nombre;
            });
        return contenido.length ? contenido.join(', ') : 'Por confirmar';
    }

    function paqUrlRastreo(paqueteria, guia) {
        if (!guia) return '';
        if (paqueteria === 'FedEx') return 'https://www.fedex.com/fedextrack/?trknbr=' + encodeURIComponent(guia);
        if (paqueteria === 'DHL') return 'https://www.dhl.com/mx-es/home/rastreo.html?tracking-id=' + encodeURIComponent(guia);
        return '';
    }

    function paqArticulosTexto(remision) {
        const articulos = remision.articulos || [];
        return articulos.length
            ? articulos.map(function (articulo) { return paqEscape(articulo.nombre) + (articulo.cantidad > 1 ? ' ×' + articulo.cantidad : ''); }).join(', ')
            : 'Sin artículos registrados';
    }

    function paqRenderRemisiones() {
        const query = (paqRemisionSearch.value || '').trim().toLowerCase();
        const visibles = paqueteriaRemisiones.filter(function (remision) {
            return [remision.folio, remision.cliente, remision.destino, paqArticulosTexto(remision)].join(' ').toLowerCase().includes(query);
        });

        paqRemisionesList.innerHTML = visibles.length
            ? visibles.map(function (remision) {
                return `<button type="button" class="paq-remision ${paqSelectedRemision?.id === remision.id ? 'selected' : ''}" data-remision-id="${remision.id}">
                    <span><strong>${paqEscape(remision.folio)}</strong><small>${paqEscape(remision.fecha || 'Sin fecha')}</small></span>
                    <span><strong>${paqEscape(remision.cliente || 'Cliente sin nombre')}</strong><small>${paqEscape(remision.destino || 'Sin destino')} · ${paqArticulosTexto(remision)}</small></span>
                    <span class="paq-remision-arrow">›</span>
                </button>`;
            }).join('')
            : '<div class="paq-empty">No se encontraron remisiones.</div>';
    }

    function paqMostrarDatos(remision) {
        paqSelectedRemision = remision;
        paqRemisionPicker.hidden = true;
        paqRemisionData.hidden = false;
        paqManualBanner.hidden = true;
        paqSelectedRemisionCard.hidden = false;
        paqNextStep.disabled = false;

        document.getElementById('paq-selected-folio').textContent = remision.folio ? 'Remisión ' + remision.folio : 'Sin remisión';
        document.getElementById('paq-selected-summary').textContent = [remision.fecha, remision.cliente, remision.destino].filter(Boolean).join(' · ') || 'Captura los datos manualmente';
        document.getElementById('paq-data-cliente').value = remision.cliente || '';
        document.getElementById('paq-data-destino').value = remision.destino || '';
        document.getElementById('paq-data-cp').value = remision.codigo_postal || '';
        document.getElementById('paq-data-direccion').value = remision.direccion || '';
        document.getElementById('paq-data-telefono').value = remision.telefono || '';
        document.getElementById('paq-items-origin').textContent = remision.articulos?.length ? '· De la remisión' : '';
        paqItems = (remision.articulos || []).map(function (articulo) { return { nombre: articulo.nombre || '', serie: articulo.serie || '', incluido: true }; });
        paqRenderItems();
    }

    function paqRenderItems() {
        const container = document.getElementById('paq-data-items');
        container.innerHTML = paqItems.length
            ? paqItems.map(function (item, index) { return `<div class="paq-item">
                    <input type="checkbox" data-item-check="${index}" ${item.incluido !== false ? 'checked' : ''} aria-label="Incluir artículo">
                    <input class="paq-item-input" data-item-name="${index}" value="${paqEscape(item.nombre)}" placeholder="Equipo o producto">
                    <input class="paq-item-input" data-item-serial="${index}" value="${paqEscape(item.serie)}" placeholder="No. de serie">
                    <button type="button" class="paq-item-remove" data-remove-item="${index}" aria-label="Quitar artículo">×</button>
                </div>`; }).join('')
            : '<div class="paq-empty">Agrega los artículos que vas a enviar.</div>';
    }

    function paqFechaLocal(fecha) {
        const date = fecha ? new Date(fecha + 'T12:00:00') : new Date();
        return date.toISOString().slice(0, 10);
    }

    function paqSumarDiasHabiles(fecha, dias) {
        const date = new Date(fecha + 'T12:00:00');
        let restantes = dias;
        while (restantes > 0) {
            date.setDate(date.getDate() + 1);
            if (date.getDay() !== 0 && date.getDay() !== 6) restantes -= 1;
        }
        return date.toISOString().slice(0, 10);
    }

    const paqCostosPromedio = {
        FedEx: { promedio: null, envios: 0, dias: 2 },
        Estafeta: { promedio: 1969, envios: 4, dias: 3 },
        DHL: { promedio: 1126, envios: 1, dias: 2 },
        Paquetexpress: { promedio: 615, envios: 1, dias: 3 },
        'Tres Guerras': { promedio: 4285, envios: 5, dias: 5 },
        Castores: { promedio: 4263, envios: 1, dias: 5 },
        Autobús: { promedio: null, envios: 0, dias: 1 }
    };

    function paqActualizarAdvertenciaCosto() {
        const costo = Number(document.getElementById('paq-shipment-cost').value) || 0;
        const referenciaCosto = paqCostosPromedio[paqCourier];
        const promedio = referenciaCosto?.promedio;
        const referencia = document.getElementById('paq-cost-reference');
        const advertencia = document.getElementById('paq-cost-warning');
        referencia.textContent = promedio
            ? 'Referencia: ' + paqCourier + ' promedia $' + promedio.toLocaleString('es-MX') + ' (' + referenciaCosto.envios + ' envío' + (referenciaCosto.envios === 1 ? '' : 's') + ').'
            : 'Captura el costo real o marca la opción de costo pendiente.';
        if (costo > 0 && promedio && costo > promedio * 1.6) {
            advertencia.hidden = false;
            advertencia.textContent = 'Es ' + Math.round((costo / promedio) * 10) / 10 + ' veces lo normal con ' + paqCourier + '. Revisa que el total esté bien.';
        } else {
            advertencia.hidden = true;
            advertencia.textContent = '';
        }
    }

    function paqPrepararPasoCuatro() {
        const fecha = document.getElementById('paq-shipment-date');
        const entrega = document.getElementById('paq-delivery-date');
        if (!fecha.value) fecha.value = paqFechaLocal();
        const dias = paqCostosPromedio[paqCourier]?.dias || 3;
        entrega.value = paqSumarDiasHabiles(fecha.value, dias);
        document.getElementById('paq-delivery-note').textContent = paqCourier
            ? paqCourier + ' tarda aproximadamente ' + dias + ' día' + (dias === 1 ? '' : 's') + ' hábiles. La fecha es estimada.'
            : 'La fecha estimada se calcula según la paquetería seleccionada.';
        paqActualizarAdvertenciaCosto();
        paqActualizarTicketPanel();
    }

    function paqActualizarTicketPanel() {
        const visible = paqCourier === 'FedEx' && paqFedexGuideType === 'virtual';
        paqTicketPanel.hidden = !visible;
    }

    function paqRenderUsuarios() {
        const list = document.getElementById('paq-people-list');
        const personas = paqueteriaUsuarios.length ? paqueteriaUsuarios : [];
        list.innerHTML = personas.map(function (usuario) {
            return '<button type="button" class="paq-person ' + (paqResponsibleUsers.includes(String(usuario.id)) ? 'selected' : '') + '" data-responsible-user="' + paqEscape(usuario.id) + '">' + paqEscape(usuario.nombre) + '</button>';
        }).join('') + '<button type="button" class="paq-person ' + (paqResponsibleUsers.includes('pickup') ? 'selected' : '') + '" data-responsible-user="pickup">La paquetería recoge</button>';
    }

    function paqValor(id) {
        return document.getElementById(id)?.value || '';
    }

    function paqPaquetesRevision() {
        if (paqNoMeasures) return [{ nombre: 'Paquete', detalle: 'Peso real total capturado manualmente', peso: paqRealWeightWithoutMeasures, cobrar: paqRealWeightWithoutMeasures }];
        const paquetes = paqPackageMode === 'single' ? (paqSinglePackage ? [paqSinglePackage] : []) : paqPackages;
        return paquetes.map(function (pack) {
            const volumen = ((Number(pack.largo) || 0) * (Number(pack.ancho) || 0) * (Number(pack.alto) || 0)) / 5000;
            const porPieza = Math.max(Number(pack.peso) || 0, volumen);
            return {
                nombre: pack.nombre || 'Paquete',
                detalle: [pack.largo && pack.ancho && pack.alto ? pack.largo + '×' + pack.ancho + '×' + pack.alto + ' cm' : '', pack.peso ? pack.peso + ' kg' + (pack.cantidad > 1 && paqPackageMode !== 'single' ? ' c/u' : '') : '', pack.series?.length ? 'Series: ' + pack.series.join(', ') : 'Sin número de serie'].filter(Boolean).join(' · '),
                peso: Number(pack.peso) || 0,
                cobrar: porPieza * (paqPackageMode === 'single' ? 1 : pack.cantidad)
            };
        });
    }

    function paqMostrarError(mensaje) {
        paqWizardError.textContent = mensaje || '';
        paqWizardError.hidden = !mensaje;
    }

    function paqPaquetesGuardar() {
        if (paqNoMeasures) {
            return [{ nombre: 'Paquete', cantidad: 1, peso: paqRealWeightWithoutMeasures, cobrar: paqRealWeightWithoutMeasures, sin_medidas: true }];
        }
        const paquetes = paqPackageMode === 'single' ? (paqSinglePackage ? [paqSinglePackage] : []) : paqPackages;
        return paquetes.map(function (pack) {
            const cantidad = paqPackageMode === 'single' ? 1 : (Number(pack.cantidad) || 1);
            const volumen = ((Number(pack.largo) || 0) * (Number(pack.ancho) || 0) * (Number(pack.alto) || 0)) / 5000;
            const cobrar = Math.max(Number(pack.peso) || 0, volumen) * cantidad;
            return {
                nombre: pack.nombre || 'Paquete', cantidad: cantidad,
                series: pack.series || [], contenido: pack.contenido || '',
                largo: Number(pack.largo) || 0, ancho: Number(pack.ancho) || 0,
                alto: Number(pack.alto) || 0, peso: Number(pack.peso) || 0,
                volumen: volumen, cobrar: cobrar,
            };
        });
    }

    function paqValidarPaso(paso) {
        if (paso === 1) {
            if (!paqValor('paq-data-cliente').trim()) return 'Escribe el cliente o selecciona una remisión.';
            if (!paqValor('paq-data-destino').trim()) return 'Falta el destino.';
            if (!paqItems.some(function (item) { return item.incluido !== false && item.nombre.trim(); })) return 'Agrega al menos un artículo.';
        }
        if (paso === 2 && !paqPuedeContinuarPasoDos()) return paqCourier === 'FedEx' && paqFedexGuideType === 'virtual' ? 'Escribe el número de la guía virtual de FedEx.' : (paqCourier === 'Otra' ? 'Escribe el nombre de la paquetería.' : 'Selecciona una paquetería y una guía.');
        if (paso === 3) {
            if (paqNoMeasures) {
                if (paqRealWeightWithoutMeasures <= 0) return 'Captura el peso real total.';
            } else {
                const paquetes = paqPackageMode === 'single' ? (paqSinglePackage ? [paqSinglePackage] : []) : paqPackages;
                if (!paquetes.length) return 'Agrega al menos un paquete.';
                if (paquetes.some(function (pack) { return Number(pack.largo) <= 0 || Number(pack.ancho) <= 0 || Number(pack.alto) <= 0 || Number(pack.peso) <= 0; })) return 'Completa largo, ancho, alto y peso de cada paquete.';
            }
        }
        if (paso === 4) {
            const fecha = paqValor('paq-shipment-date');
            const entrega = paqValor('paq-delivery-date');
            const pendiente = document.getElementById('paq-cost-pending').checked;
            const costo = Number(paqValor('paq-shipment-cost')) || 0;
            if (!fecha) return 'Falta la fecha de envío.';
            if (entrega && entrega < fecha) return 'La entrega no puede ser antes del envío.';
            if (!pendiente && costo <= 0) return 'Escribe el costo del envío o marca “Todavía no tengo el costo”.';
        }
        return '';
    }

    async function paqGuardarEnvio() {
        const errores = [1, 2, 3, 4].map(paqValidarPaso).find(Boolean);
        if (errores) { paqMostrarError(errores); return; }
        paqMostrarError('Guardando envío…');
        paqNextStep.disabled = true;
        const paquetes = paqPaquetesGuardar();
        const form = new FormData();
        form.append('_token', document.querySelector('meta[name="csrf-token"]')?.content || '');
        if (paqSelectedRemision?.id) form.append('cotizacion_id', paqSelectedRemision.id);
        form.append('remision_folio', paqSelectedRemision?.folio || '');
        form.append('cliente', paqValor('paq-data-cliente'));
        form.append('destino', paqValor('paq-data-destino'));
        form.append('codigo_postal', paqValor('paq-data-cp'));
        form.append('direccion', paqValor('paq-data-direccion'));
        form.append('telefono_receptor', paqValor('paq-data-telefono'));
        form.append('paqueteria', paqNombrePaqueteria());
        form.append('paqueteria_personalizada', paqCourier === 'Otra' ? paqOtherName.value.trim() : '');
        form.append('tipo_guia', paqCourier === 'FedEx' ? paqFedexGuideType : 'nueva');
        form.append('guia_id', paqCourier === 'FedEx' && paqFedexGuideType === 'fisica' ? (paqFedexGuideList.querySelector('.paq-guide.selected')?.dataset.guideId || '') : '');
        form.append('numero_guia', paqFedexGuideType === 'virtual' ? paqVirtualGuideNumber.value.trim() : paqTrackingNumber.value.trim());
        form.append('capacidad_guia', paqCourier === 'FedEx' && paqFedexGuideType === 'fisica' ? 10 : '');
        form.append('fecha_envio', paqValor('paq-shipment-date'));
        form.append('fecha_entrega_estimada', paqValor('paq-delivery-date'));
        form.append('costo', paqValor('paq-shipment-cost'));
        form.append('costo_pendiente', document.getElementById('paq-cost-pending').checked ? '1' : '0');
        form.append('peso_real', document.getElementById('paq-real-weight').textContent.replace(',', '.').replace(' kg', ''));
        form.append('peso_volumetrico', document.getElementById('paq-volume-weight').textContent.replace(',', '.').replace(' kg', ''));
        form.append('peso_cobro', document.getElementById('paq-charge-weight').textContent.replace(',', '.').replace(' kg', ''));
        form.append('paquetes_count', paquetes.length);
        form.append('articulos', JSON.stringify(paqItems.filter(function (item) { return item.incluido !== false && item.nombre.trim(); })));
        form.append('paquetes', JSON.stringify(paquetes));
        form.append('responsables', JSON.stringify(paqResponsibleUsers));
        form.append('notas', paqValor('paq-shipment-notes'));
        if (paqTicket) form.append('ticket', paqTicket);
        try {
            const response = await fetch('{{ route('admin.paqueteria.store') }}', { method: 'POST', body: form, headers: { Accept: 'application/json' }, credentials: 'same-origin' });
            const result = await response.json();
            if (!response.ok) throw new Error(result.message || Object.values(result.errors || {}).flat().join(' ') || 'No se pudo guardar el envío.');
            window.location.reload();
        } catch (error) {
            paqNextStep.disabled = false;
            paqMostrarError(error.message || 'No se pudo guardar el envío.');
        }
    }

    function paqRenderRevision() {
        const destino = paqValor('paq-data-destino') || 'Sin destino';
        const cliente = paqValor('paq-data-cliente') || 'Cliente sin nombre';
        const remision = paqSelectedRemision?.folio ? 'Remisión ' + paqSelectedRemision.folio : 'Sin remisión';
        const costo = document.getElementById('paq-cost-pending').checked ? null : Number(paqValor('paq-shipment-cost')) || 0;
        const fecha = paqValor('paq-shipment-date');
        const entrega = paqValor('paq-delivery-date');
        const guia = paqFedexGuideType === 'virtual' ? paqVirtualGuideNumber.value : paqTrackingNumber.value;
        const paquetes = paqPaquetesRevision();
        const totalCobrar = paquetes.reduce(function (total, pack) { return total + pack.cobrar; }, 0);
        const responsables = paqResponsibleUsers.map(function (id) {
            if (id === 'pickup') return 'La paquetería recoge';
            return paqueteriaUsuarios.find(function (usuario) { return String(usuario.id) === id; })?.nombre;
        }).filter(Boolean).join(', ') || 'Sin responsable seleccionado';
        const estado = guia ? 'En camino' : 'Por enviar';
        const paqueteria = paqNombrePaqueteria() || 'Sin paquetería';
        const ticket = paqTicket ? paqTicket.name : 'No adjunto';
        const trackingUrl = paqUrlRastreo(paqueteria, guia);
        const paquetesHtml = paquetes.length ? paquetes.map(function (pack) {
            return '<div class="paq-review-package"><span class="paq-package-icon"></span><div class="paq-review-package-main"><strong>' + paqEscape(pack.nombre) + '</strong><small>' + paqEscape(pack.detalle) + '</small></div><div class="paq-review-package-weight">' + pack.cobrar.toFixed(2) + ' kg<small>para cobrar</small></div></div>';
        }).join('') : '<div class="paq-empty">Sin paquetes registrados.</div>';
        document.getElementById('paq-review-content').innerHTML =
            '<div class="paq-review-summary"><div class="paq-review-label">Costo del envío</div><div class="paq-review-total">' + (costo === null ? 'Sin costo' : '$' + costo.toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 })) + '</div><div class="paq-review-sub">' + paqEscape(destino) + ' · ' + paqEscape(remision) + '</div></div>' +
            '<div class="paq-review-route"><div class="paq-review-route-title">' + paqEscape(paqueteria) + (guia ? ' · ' + paqEscape(guia) : '') + '</div><div class="paq-review-route-line"><b></b><i></i><b></b></div><div class="paq-review-route-points"><strong>Toluca</strong><strong>' + paqEscape(destino.split(',')[0]) + '</strong></div><div class="paq-review-route-dates"><span>Salió ' + paqEscape(fecha || '—') + '<br>Registrado</span><span>' + estado + '<br>Va en camino</span><span>' + paqEscape(entrega || '—') + '<br>Llegada estimada</span></div></div>' +
            (costo === null ? '<div class="paq-review-missing">Falta: costo. Tócala “Editar” para completarlo.</div>' : '') +
            '<div class="paq-review-section-title">Así va empacado · ' + paquetes.length + ' paquete' + (paquetes.length === 1 ? '' : 's') + ' · ' + totalCobrar.toFixed(2) + ' kg para cobrar</div>' + paquetesHtml +
            '<div class="paq-review-meta"><div><small>Cliente</small><strong>' + paqEscape(cliente) + '</strong></div><div><small>Paquetería</small><strong>' + paqEscape(paqueteria) + '</strong></div><div><small>Guía</small><strong>' + paqEscape(guia || 'Sin guía') + '</strong></div><div><small>Enviado</small><strong>' + paqEscape(fecha || '—') + '</strong></div><div><small>Entrega estimada</small><strong>' + paqEscape(entrega || '—') + '</strong></div><div><small>Lo llevó</small><strong>' + paqEscape(responsables) + '</strong></div></div>' +
            '<div class="paq-review-meta"><div><small>Dirección</small><strong>' + paqEscape(paqValor('paq-data-direccion') || 'Sin dirección') + '</strong></div><div><small>Código postal</small><strong>' + paqEscape(paqValor('paq-data-cp') || 'Sin código postal') + '</strong></div><div><small>Teléfono</small><strong>' + paqEscape(paqValor('paq-data-telefono') || 'Sin teléfono') + '</strong></div><div><small>Ticket FedEx</small><strong>' + paqEscape(ticket) + '</strong></div></div>' +
            (trackingUrl ? '<div class="paq-review-notes"><a href="' + trackingUrl + '" target="_blank" rel="noopener">Abrir rastreo de la guía</a></div>' : '') +
            (paqValor('paq-shipment-notes') ? '<div class="paq-review-notes"><small>Notas</small><p>' + paqEscape(paqValor('paq-shipment-notes')) + '</p></div>' : '') +
            '<div class="paq-review-actions"><button type="button" class="btn" data-review-copy="cliente">Copiar aviso para el cliente</button><button type="button" class="btn" data-review-copy="licenciados">Copiar resumen para los licenciados</button></div>';
    }

    function paqAbrirWizard() {
        paqSelectedRemision = null;
        paqCourier = '';
        paqOtherName.value = '';
        paqResponsibleUsers = [];
        paqPackages = [];
        paqSinglePackage = null;
        paqPackageMode = 'individual';
        paqNoMeasures = false;
        paqRealWeightWithoutMeasures = 0;
        paqTicket = null;
        document.getElementById('paq-shipment-date').value = paqFechaLocal();
        document.getElementById('paq-delivery-date').value = '';
        document.getElementById('paq-shipment-cost').value = '';
        document.getElementById('paq-cost-pending').checked = false;
        document.getElementById('paq-shipment-notes').value = '';
        paqRenderUsuarios();
        document.getElementById('paq-measures-content').hidden = false;
        document.getElementById('paq-no-measures-panel').hidden = true;
        document.getElementById('paq-real-weight-input').value = '0.0';
        document.querySelectorAll('[data-package-mode]').forEach(function (button) { button.classList.toggle('active', button.dataset.packageMode === 'individual'); });
        paqTrackingNumber.value = '';
        paqVirtualGuideNumber.value = '';
        paqTicketFile.value = '';
        paqTicketName.textContent = 'El ticket sirve como comprobante de la guía virtual.';
        paqTrackingPanel.hidden = true;
        paqOtherPanel.hidden = true;
        paqFedexGuides.hidden = true;
        paqFedexGuideType = 'fisica';
        paqFedexGuideList.hidden = false;
        paqFedexVirtual.hidden = true;
        paqFedexGuides.querySelectorAll('[data-guide-type]').forEach(function (tab) { tab.classList.toggle('active', tab.dataset.guideType === 'fisica'); });
        document.querySelectorAll('.paq-guide').forEach(function (button, index) { button.classList.toggle('selected', index === 0); });
        document.querySelectorAll('.paq-courier').forEach(function (button) { button.classList.remove('selected'); });
        paqRemisionSearch.value = '';
        paqRemisionPicker.hidden = false;
        paqRemisionData.hidden = false;
        paqManualBanner.hidden = true;
        paqSelectedRemisionCard.hidden = false;
        paqNextStep.disabled = true;
        paqActualizarPaso(1);
        paqItems = [];
        document.getElementById('paq-selected-folio').textContent = 'Datos del envío';
        document.getElementById('paq-selected-summary').textContent = 'Selecciona una remisión o captura los datos manualmente';
        document.getElementById('paq-data-cliente').value = '';
        document.getElementById('paq-data-destino').value = '';
        document.getElementById('paq-data-cp').value = '';
        document.getElementById('paq-data-direccion').value = '';
        document.getElementById('paq-data-telefono').value = '';
        document.getElementById('paq-items-origin').textContent = '';
        document.getElementById('paq-data-items').innerHTML = '<div class="paq-empty">Selecciona una remisión para cargar sus artículos.</div>';
        paqRenderRemisiones();
        paqModal.hidden = false;
        document.body.style.overflow = 'hidden';
    }

    function paqCerrarWizard() {
        paqModal.hidden = true;
        document.body.style.overflow = '';
    }

    document.getElementById('paq-new-shipment').addEventListener('click', paqAbrirWizard);
    document.querySelector('[data-paq-close]').addEventListener('click', paqCerrarWizard);
    document.getElementById('paq-change-remision').addEventListener('click', function () {
        paqRemisionPicker.hidden = false;
        paqRemisionData.hidden = false;
        paqNextStep.disabled = true;
        paqRenderRemisiones();
    });
    document.getElementById('paq-no-remision').addEventListener('click', function () {
        paqSelectedRemision = { folio: '', fecha: '', cliente: '', destino: '', codigo_postal: '', direccion: '', telefono: '', articulos: [] };
        paqRemisionPicker.hidden = true;
        paqRemisionData.hidden = false;
        paqManualBanner.hidden = false;
        paqSelectedRemisionCard.hidden = true;
        paqNextStep.disabled = false;
        document.getElementById('paq-data-cliente').value = '';
        document.getElementById('paq-data-destino').value = '';
        document.getElementById('paq-data-cp').value = '';
        document.getElementById('paq-data-direccion').value = '';
        document.getElementById('paq-data-telefono').value = '';
        document.getElementById('paq-items-origin').textContent = '';
        paqItems = [];
        paqRenderItems();
    });
    paqNextStep.addEventListener('click', function () {
        paqMostrarError('');
        if (paqStep === 1 && paqSelectedRemision && !paqValidarPaso(1)) {
            paqActualizarPaso(2);
        } else if (paqStep === 2 && !paqValidarPaso(2)) {
            paqPrepararPaquetes();
            paqActualizarPaso(3);
            paqActualizarTotales();
        } else if (paqStep === 3) {
            const error = paqValidarPaso(3);
            if (error) { paqMostrarError(error); return; }
            paqPrepararPasoCuatro();
            paqActualizarPaso(4);
        } else if (paqStep === 4) {
            const error = paqValidarPaso(4);
            if (error) { paqMostrarError(error); return; }
            paqRenderRevision();
            paqActualizarPaso(5);
        } else if (paqStep === 5) {
            paqGuardarEnvio();
        } else {
            paqMostrarError(paqValidarPaso(paqStep));
        }
    });
    paqPrevStep.addEventListener('click', function () {
        if (paqStep === 2) paqActualizarPaso(1);
        if (paqStep === 3) paqActualizarPaso(2);
        if (paqStep === 4) paqActualizarPaso(3);
        if (paqStep === 5) paqActualizarPaso(4);
    });

    document.getElementById('paq-review-content').addEventListener('click', function (event) {
        const button = event.target.closest('[data-review-copy]');
        if (!button) return;
        const destino = paqValor('paq-data-destino') || 'Sin destino';
        const cliente = paqValor('paq-data-cliente').trim() || 'cliente';
        const nombreCliente = cliente.split(/\s+/)[0];
        const paqueteria = paqNombrePaqueteria() || paqCourier || 'la paquetería';
        const remision = paqSelectedRemision?.folio || 'Sin remisión';
        const guia = paqFedexGuideType === 'virtual' ? paqVirtualGuideNumber.value : paqTrackingNumber.value;
        const paquetes = paqPaquetesRevision();
        const peso = paquetes.reduce(function (total, pack) { return total + pack.cobrar; }, 0).toFixed(2);
        const costo = document.getElementById('paq-cost-pending').checked ? 'Sin costo' : '$' + (Number(paqValor('paq-shipment-cost')) || 0).toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        const texto = button.dataset.reviewCopy === 'cliente'
            ? 'Hola ' + nombreCliente + ' 👋\n' +
                'Tu pedido de Grupo MediBuy ya va en camino 📦\n\n' +
                '*Remisión:* ' + remision + '\n' +
                '*Contenido:* ' + paqContenidoWhatsApp() + '\n' +
                '*Paquetería:* ' + paqueteria + '\n' +
                '*Número de guía:* ' + (guia || 'por asignar') + '\n' +
                '*Llega aprox.:* ' + paqFechaWhatsApp(paqValor('paq-delivery-date')) + '\n\n' +
                'Cualquier duda, aquí estamos para ayudarte.\n' +
                '— Grupo MediBuy'
            : (function () {
                const articulos = paqItems
                    .filter(function (item) { return item.incluido !== false && item.nombre && item.nombre.trim(); })
                    .map(function (item) {
                        return '• ' + item.nombre.trim() + (item.serie && item.serie.trim() ? ' · serie ' + item.serie.trim() : '');
                    }).join('\n');
                const origenPaquetes = paqNoMeasures ? [] : (paqPackageMode === 'single' ? (paqSinglePackage ? [paqSinglePackage] : []) : paqPackages);
                const paquetesTexto = origenPaquetes.map(function (pack) {
                    const cantidad = paqPackageMode === 'single' ? 1 : (Number(pack.cantidad) || 1);
                    const medidas = pack.largo && pack.ancho && pack.alto ? pack.largo + '×' + pack.ancho + '×' + pack.alto + ' cm' : 'medidas por confirmar';
                    const pesoPaquete = pack.peso ? pack.peso + ' kg' + (cantidad > 1 && paqPackageMode !== 'single' ? ' c/u' : '') : 'peso por confirmar';
                    return '▫️ ' + (pack.nombre || 'Paquete') + (cantidad > 1 ? ' ×' + cantidad : '') + ': ' + medidas + ', ' + pesoPaquete;
                }).join('\n');
                const responsables = paqResponsibleUsers.map(function (id) {
                    if (id === 'pickup') return 'La paquetería recoge';
                    return paqueteriaUsuarios.find(function (usuario) { return String(usuario.id) === id; })?.nombre;
                }).filter(Boolean).join(', ') || 'Sin responsable';
                const costoLicenciados = document.getElementById('paq-cost-pending').checked
                    ? 'pendiente de capturar'
                    : '$' + (Number(paqValor('paq-shipment-cost')) || 0).toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                return '*Envío a ' + destino + '*\n' +
                    'Remisión ' + remision + ' · ' + cliente + '\n\n' +
                    (articulos || '• Contenido por confirmar') + '\n\n' +
                    'Paquetería: ' + paqueteria + '\n' +
                    'Guía: ' + (guia || 'Sin guía') + '\n' +
                    'Peso para cobrar: ' + peso + ' kg · ' + paquetes.length + ' paquetes\n' +
                    (paquetesTexto ? paquetesTexto + '\n' : '') +
                    'Enviado: ' + paqFechaCorta(paqValor('paq-shipment-date')) + ' · Entrega estimada: ' + paqFechaCorta(paqValor('paq-delivery-date')) + '\n' +
                    'Lo llevó: ' + responsables + '\n\n' +
                    '*Costo del envío: ' + costoLicenciados + '*\n' +
                    'Estado: ' + (guia ? 'En camino' : 'Por enviar') + '\n' +
                    '— Grupo MediBuy';
            })();
        const copiar = navigator.clipboard && navigator.clipboard.writeText
            ? navigator.clipboard.writeText(texto)
            : Promise.reject(new Error('Clipboard no disponible'));
        copiar.then(function () {
            const original = button.textContent;
            button.textContent = button.dataset.reviewCopy === 'cliente' ? 'Copiado: pégalo en WhatsApp' : 'Copiado';
            setTimeout(function () { button.textContent = original; }, 1400);
        }).catch(function () {
            window.prompt('Copia este texto:', texto);
        });
    });
    document.querySelectorAll('[data-package-mode]').forEach(function (button) {
        button.addEventListener('click', function () {
            paqPackageMode = button.dataset.packageMode;
            document.querySelectorAll('[data-package-mode]').forEach(function (item) { item.classList.toggle('active', item === button); });
            if (paqPackageMode === 'single') paqSinglePackage = paqCajaSugerida();
            paqRenderPackages();
            paqActualizarTotales();
        });
    });
    document.getElementById('paq-packages-list').addEventListener('input', function (event) {
        const index = event.target.dataset.package;
        const field = event.target.dataset.measure;
        if (index === undefined || !field) return;
        if (index === 'single') paqSinglePackage[field] = event.target.value;
        else paqPackages[Number(index)][field] = event.target.value;
        paqActualizarTotales();
    });
    document.getElementById('paq-packages-list').addEventListener('click', function (event) {
        const remove = event.target.closest('[data-remove-package]');
        if (!remove) return;
        paqPackages.splice(Number(remove.dataset.removePackage), 1);
        paqRenderPackages();
    });
    document.getElementById('paq-add-package').addEventListener('click', function () {
        paqPackages.push({ nombre: 'Paquete adicional', cantidad: 1, series: [], largo: '', ancho: '', alto: '', peso: '', extra: true });
        paqRenderPackages();
    });
    document.getElementById('paq-no-measures').addEventListener('click', function () {
        paqMostrarSinMedidas();
    });
    document.getElementById('paq-no-measures-again').addEventListener('click', paqMostrarSinMedidas);
    document.getElementById('paq-real-weight-input').addEventListener('input', function (event) {
        paqRealWeightWithoutMeasures = Number(event.target.value) || 0;
        paqActualizarTotales();
    });
    document.getElementById('paq-couriers').addEventListener('click', function (event) {
        const button = event.target.closest('[data-courier]');
        if (!button) return;
        paqCourier = button.dataset.courier;
        const esFedex = paqCourier === 'FedEx';
        const esOtra = paqCourier === 'Otra';
        paqTrackingPanel.hidden = esFedex;
        paqFedexGuides.hidden = !esFedex;
        paqOtherPanel.hidden = !esOtra;
        if (!esOtra) paqOtherName.value = '';
        paqTrackingNumber.value = esFedex
            ? document.querySelector('.paq-guide.selected')?.dataset.guide || ''
            : '';
        if (!esFedex) {
            paqFedexGuideType = 'fisica';
            paqVirtualGuideNumber.value = '';
        }
        paqActualizarTicketPanel();
        document.querySelectorAll('.paq-courier').forEach(function (item) { item.classList.toggle('selected', item === button); });
        paqNextStep.disabled = !paqPuedeContinuarPasoDos();
        paqActualizarTotales();
        paqActualizarAdvertenciaCosto();
    });
    paqFedexGuides.addEventListener('click', function (event) {
        const guide = event.target.closest('[data-guide]');
        if (!guide) return;
        document.querySelectorAll('.paq-guide').forEach(function (button) { button.classList.toggle('selected', button === guide); });
        paqTrackingNumber.value = guide.dataset.guide;
        paqActualizarTotales();
    });
    paqFedexGuides.querySelectorAll('[data-guide-type]').forEach(function (tab) {
        tab.addEventListener('click', function () {
            paqFedexGuides.querySelectorAll('[data-guide-type]').forEach(function (item) { item.classList.toggle('active', item === tab); });
            document.getElementById('paq-guide-note').textContent = tab.dataset.guideType === 'fisica'
                ? 'Selecciona una de tus guías físicas disponibles.'
                : 'Las guías virtuales se capturan manualmente en el número de guía.';
        });
    });
    paqFedexGuides.querySelectorAll('[data-guide-type]').forEach(function (tab) {
        tab.addEventListener('click', function () {
            paqFedexGuideType = tab.dataset.guideType;
            const esFisica = paqFedexGuideType === 'fisica';
            paqFedexGuideList.hidden = !esFisica;
            paqFedexVirtual.hidden = esFisica;
            document.getElementById('paq-guide-note').hidden = !esFisica;
            paqActualizarTicketPanel();
            paqNextStep.disabled = !paqPuedeContinuarPasoDos();
        });
    });
    paqVirtualGuideNumber.addEventListener('input', function () {
        paqNextStep.disabled = !paqPuedeContinuarPasoDos();
    });
    paqTicketFile.addEventListener('change', function () {
        paqTicket = paqTicketFile.files?.[0] || null;
        paqTicketName.textContent = paqTicket ? paqTicket.name : 'El ticket sirve como comprobante de la guía virtual.';
    });
    document.getElementById('paq-join-package').addEventListener('click', function () {
        paqPackageMode = 'single';
        paqSinglePackage = paqCajaSugerida();
        document.querySelectorAll('[data-package-mode]').forEach(function (button) { button.classList.toggle('active', button.dataset.packageMode === 'single'); });
        paqRenderPackages();
        paqActualizarTotales();
    });
    document.getElementById('paq-people-list').addEventListener('click', function (event) {
        const button = event.target.closest('[data-responsible-user]');
        if (!button) return;
        const id = String(button.dataset.responsibleUser);
        paqResponsibleUsers = paqResponsibleUsers.includes(id)
            ? paqResponsibleUsers.filter(function (item) { return item !== id; })
            : paqResponsibleUsers.concat(id);
        paqRenderUsuarios();
    });
    document.getElementById('paq-shipment-date').addEventListener('change', function (event) {
        const dias = paqCostosPromedio[paqCourier]?.dias || 3;
        document.getElementById('paq-delivery-date').value = paqSumarDiasHabiles(event.target.value, dias);
    });
    document.getElementById('paq-cost-pending').addEventListener('change', function (event) {
        document.getElementById('paq-shipment-cost').disabled = event.target.checked;
        if (event.target.checked) document.getElementById('paq-shipment-cost').value = '';
        paqActualizarAdvertenciaCosto();
    });
    document.getElementById('paq-shipment-cost').addEventListener('input', paqActualizarAdvertenciaCosto);
    document.getElementById('paq-use-remision').addEventListener('click', function () {
        paqManualBanner.hidden = true;
        paqSelectedRemisionCard.hidden = false;
        paqRemisionPicker.hidden = false;
        paqNextStep.disabled = true;
        paqRenderRemisiones();
    });
    paqRemisionSearch.addEventListener('input', paqRenderRemisiones);
    paqRemisionesList.addEventListener('click', function (event) {
        const button = event.target.closest('[data-remision-id]');
        if (!button) return;
        const remision = paqueteriaRemisiones.find(function (item) { return String(item.id) === button.dataset.remisionId; });
        if (remision) paqMostrarDatos(remision);
    });
    document.getElementById('paq-add-item').addEventListener('click', function () {
        paqItems.push({ nombre: '', serie: '', incluido: true });
        paqRenderItems();
        const inputs = document.querySelectorAll('[data-item-name]');
        inputs[inputs.length - 1]?.focus();
    });
    document.getElementById('paq-data-items').addEventListener('input', function (event) {
        const nameIndex = event.target.dataset.itemName;
        const serialIndex = event.target.dataset.itemSerial;
        if (nameIndex !== undefined) paqItems[Number(nameIndex)].nombre = event.target.value;
        if (serialIndex !== undefined) paqItems[Number(serialIndex)].serie = event.target.value;
    });
    document.getElementById('paq-data-items').addEventListener('change', function (event) {
        const index = event.target.dataset.itemCheck;
        if (index !== undefined) paqItems[Number(index)].incluido = event.target.checked;
    });
    document.getElementById('paq-data-items').addEventListener('click', function (event) {
        const button = event.target.closest('[data-remove-item]');
        if (!button) return;
        paqItems.splice(Number(button.dataset.removeItem), 1);
        paqRenderItems();
    });
    paqModal.addEventListener('click', function (event) { if (event.target === paqModal) paqCerrarWizard(); });
    document.addEventListener('keydown', function (event) { if (event.key === 'Escape' && !paqModal.hidden) paqCerrarWizard(); });

    document.querySelectorAll('[data-paq-filter]').forEach(function (button) {
        button.addEventListener('click', function () {
            document.querySelectorAll('[data-paq-filter]').forEach(function (item) { item.classList.remove('active'); });
            button.classList.add('active');
            const filtro = button.dataset.paqFilter;
            document.querySelectorAll('[data-paq-shipment]').forEach(function (card) {
                card.hidden = !filtro.startsWith('Todas') && card.dataset.courier !== filtro;
            });
        });
    });

    document.querySelectorAll('[data-edit-envio]').forEach(function (button) {
        button.addEventListener('click', async function () {
            const envio = paqueteriaEnvios.find(function (item) { return String(item.id) === String(button.dataset.editEnvio); });
            if (!envio) return;
            const actual = envio.costo_pendiente ? '' : String(envio.costo ?? '');
            const valor = window.prompt('Costo del envío. Déjalo vacío para marcarlo como pendiente:', actual);
            if (valor === null) return;
            const costo = valor.trim();
            if (costo !== '' && (!Number.isFinite(Number(costo)) || Number(costo) < 0)) {
                window.alert('Escribe un costo válido.');
                return;
            }
            const form = new FormData();
            form.append('_method', 'PUT');
            form.append('_token', document.querySelector('meta[name="csrf-token"]')?.content || '');
            form.append('costo', costo);
            form.append('costo_pendiente', costo === '' ? '1' : '0');
            form.append('notas', envio.notas || '');
            form.append('responsables', JSON.stringify(envio.responsables || []));
            const response = await fetch('{{ url('/admin/paqueteria') }}/' + envio.id, { method: 'POST', body: form, headers: { Accept: 'application/json' }, credentials: 'same-origin' });
            if (!response.ok) {
                const error = await response.json().catch(function () { return {}; });
                window.alert(error.message || 'No se pudo actualizar el envío.');
                return;
            }
            window.location.reload();
        });
    });

    document.querySelectorAll('[data-paq-shipment]').forEach(function (card) {
        card.addEventListener('click', function (event) {
            if (event.target.closest('[data-edit-envio], a, button')) return;
            const envio = paqueteriaEnvios.find(function (item) { return String(item.id) === String(card.dataset.envioId); });
            if (!envio) return;
            const costo = envio.costo_pendiente ? 'Pendiente' : '$' + Number(envio.costo || 0).toLocaleString('es-MX', { minimumFractionDigits: 2 });
            paqDetailContent.innerHTML = '<div class="paq-review-summary"><div class="paq-review-label">' + paqEscape(envio.paqueteria) + '</div><div class="paq-review-total">' + paqEscape(envio.cliente) + '</div><div class="paq-review-sub">' + paqEscape(envio.destino) + '</div></div>' +
                '<div class="paq-detail-grid"><div><small>Guía</small><strong>' + paqEscape(envio.numero_guia || 'Sin guía') + '</strong></div><div><small>Fecha de envío</small><strong>' + paqEscape(envio.fecha_envio || '—') + '</strong></div><div><small>Entrega estimada</small><strong>' + paqEscape(envio.fecha_entrega_estimada || '—') + '</strong></div><div><small>Costo</small><strong>' + costo + '</strong></div><div><small>Dirección</small><strong>' + paqEscape(envio.direccion || 'Sin dirección') + '</strong></div><div><small>Teléfono</small><strong>' + paqEscape(envio.telefono_receptor || 'Sin teléfono') + '</strong></div><div><small>Código postal</small><strong>' + paqEscape(envio.codigo_postal || 'Sin CP') + '</strong></div><div><small>Paquetes</small><strong>' + envio.paquetes_count + '</strong></div><div><small>Peso para cobrar</small><strong>' + Number(envio.peso_cobro || 0).toFixed(2) + ' kg</strong></div></div>' +
                (envio.tracking_url ? '<div class="paq-placeholder"><a href="' + envio.tracking_url + '" target="_blank" rel="noopener">Abrir rastreo de la guía</a></div>' : '') +
                (envio.ticket_url ? '<div class="paq-placeholder"><a href="' + envio.ticket_url + '" target="_blank" rel="noopener">Abrir ticket adjunto</a></div>' : '') +
                (envio.notas ? '<div class="paq-placeholder"><strong>Notas</strong><div>' + paqEscape(envio.notas) + '</div></div>' : '') +
                '<form class="paq-placeholder" data-detail-edit-form data-envio-id="' + envio.id + '"><strong>Editar envío</strong><div class="paq-detail-grid"><label>Entrega estimada<input class="paq-date-input" name="fecha_entrega_estimada" type="date" value="' + paqEscape(envio.fecha_entrega_estimada || '') + '"></label><label>Costo<input class="paq-cost-input" name="costo" type="number" min="0" step="0.01" value="' + (envio.costo_pendiente ? '' : paqEscape(envio.costo || '')) + '"></label></div><label class="paq-check"><input name="costo_pendiente" type="checkbox" ' + (envio.costo_pendiente ? 'checked' : '') + '> Costo pendiente</label><label class="paq-cost-label" style="margin-top:12px">Notas<textarea class="paq-notes" name="notas">' + paqEscape(envio.notas || '') + '</textarea></label><label class="paq-cost-label">Reemplazar ticket<input class="paq-tracking-input" name="ticket" type="file" accept="image/*,application/pdf"></label><button class="btn" type="submit">Guardar cambios</button></form>';
            paqDetailModal.hidden = false;
            document.body.style.overflow = 'hidden';
        });
    });
    document.querySelector('[data-paq-detail-close]').addEventListener('click', function () { paqDetailModal.hidden = true; document.body.style.overflow = ''; });
    paqDetailModal.addEventListener('click', function (event) { if (event.target === paqDetailModal) { paqDetailModal.hidden = true; document.body.style.overflow = ''; } });
    paqDetailContent.addEventListener('submit', async function (event) {
        const formElement = event.target.closest('[data-detail-edit-form]');
        if (!formElement) return;
        event.preventDefault();
        const form = new FormData(formElement);
        form.append('_method', 'PUT');
        form.append('_token', document.querySelector('meta[name="csrf-token"]')?.content || '');
        if (!formElement.querySelector('[name="costo_pendiente"]').checked) form.set('costo_pendiente', '0');
        form.set('responsables', JSON.stringify((paqueteriaEnvios.find(function (item) { return String(item.id) === String(formElement.dataset.envioId); }) || {}).responsables || []));
        const response = await fetch('{{ url('/admin/paqueteria') }}/' + formElement.dataset.envioId, { method: 'POST', body: form, headers: { Accept: 'application/json' }, credentials: 'same-origin' });
        if (!response.ok) { const error = await response.json().catch(function () { return {}; }); window.alert(error.message || 'No se pudo actualizar el envío.'); return; }
        window.location.reload();
    });

    document.querySelectorAll('.paq-tab').forEach(function (button) {
        button.addEventListener('click', function () {
            document.querySelectorAll('.paq-tab').forEach(function (item) {
                item.classList.remove('active');
                item.setAttribute('aria-selected', 'false');
            });
            button.classList.add('active');
            button.setAttribute('aria-selected', 'true');
            const tab = button.dataset.paqTab;
            const resumen = document.getElementById('paq-resumen-content');
            const placeholder = document.getElementById('paq-resumen-placeholder');
            const envios = document.getElementById('paq-envios-content');
            const guias = document.getElementById('paq-guias-content');
            resumen.hidden = tab !== 'resumen';
            placeholder.hidden = tab !== 'resumen';
            envios.hidden = tab !== 'envios';
            guias.hidden = tab !== 'guias';
        });
    });
</script>
@endpush
