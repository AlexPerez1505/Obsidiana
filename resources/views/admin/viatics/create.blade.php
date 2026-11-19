@extends('layouts.dashboard')

@section('title', 'Nuevo viaje')
@section('page-title', 'Nuevo viaje')
@section('page-sub', 'Registra los gastos y participantes del viaje')

@php($personasSeleccionadas = old('personas_viaje', ''))

@push('head')
<style>
.trip-page{max-width:980px;margin:0 auto;padding:4px 0 36px}.trip-header{display:flex;align-items:center;justify-content:space-between;gap:16px;margin-bottom:24px}.trip-title{margin:0;color:var(--text);font-size:30px}.trip-cancel{border:0;border-radius:999px;background:var(--surface-2);color:var(--text);padding:11px 22px;font:inherit;font-weight:750;text-decoration:none}.trip-progress{display:grid;grid-template-columns:repeat(4,1fr);gap:8px;margin-bottom:28px}.trip-progress span{height:6px;border-radius:99px;background:var(--surface-2)}.trip-progress span.active{background:var(--primary)}.trip-step{display:none}.trip-step.active{display:block}.trip-step-label{margin:0 0 8px;color:var(--muted);font-size:15px}.trip-question{margin:0 0 14px;color:var(--text);font-size:28px}.trip-card{margin-bottom:12px;padding:24px;border-radius:22px;background:var(--surface);border:1px solid var(--border)}.trip-card h3{margin:0 0 16px;color:var(--text);font-size:16px}.trip-fields{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:18px}.trip-field{display:grid;gap:7px}.trip-field.full{grid-column:1/-1}.trip-field label{color:var(--muted);font-size:13px;font-weight:750}.trip-field input,.trip-field select,.trip-field textarea{width:100%;min-height:48px;border:1px solid var(--border);border-radius:12px;background:var(--surface);color:var(--text);padding:0 14px;font:inherit}.trip-field textarea{padding-top:12px;min-height:90px}.trip-chips{display:flex;flex-wrap:wrap;gap:10px}.trip-chip{border:1px solid var(--border);border-radius:999px;background:transparent;color:var(--text);padding:11px 18px;font:inherit;font-weight:700;cursor:pointer}.trip-chip:hover,.trip-chip.selected{border-color:var(--primary);background:var(--primary);color:#fff}.trip-help{color:var(--muted);font-size:12px}.trip-counter{display:flex;align-items:center;justify-content:space-between;gap:16px;margin-top:20px;color:var(--text);font-weight:700}.trip-counter-controls{display:flex;align-items:center;gap:12px}.trip-counter button{width:38px;height:38px;border:0;border-radius:50%;background:var(--surface-2);color:var(--text);font-size:20px;cursor:pointer}.trip-counter strong{font-size:22px}.trip-total{display:flex;align-items:center;justify-content:space-between;margin-top:18px;padding-top:16px;border-top:1px solid var(--border);color:var(--text)}.trip-total strong{font-size:24px}.trip-footer{display:flex;justify-content:space-between;gap:12px;margin-top:22px}.trip-btn{border:1px solid var(--border);border-radius:12px;background:transparent;color:var(--text);padding:12px 22px;font:inherit;font-weight:750;cursor:pointer}.trip-btn.primary{border-color:var(--primary);background:var(--primary);color:#fff}.trip-error{margin-top:14px;color:#ef4444;font-size:13px}.trip-review{display:grid;gap:10px;color:var(--text)}.trip-review div{display:flex;justify-content:space-between;gap:20px;padding-bottom:10px;border-bottom:1px solid var(--border)}.trip-review span{color:var(--muted)}@media(max-width:700px){.trip-header{align-items:flex-start}.trip-title{font-size:24px}.trip-question{font-size:23px}.trip-fields{grid-template-columns:1fr}.trip-field.full{grid-column:auto}.trip-card{padding:18px}.trip-footer{position:sticky;bottom:0;padding:12px 0;background:var(--bg)}}
/* Iconos del paso 2 */
.trip-step[data-step="1"] .trip-card:first-of-type h3:before{content:'';display:inline-block;width:38px;height:38px;margin-right:14px;vertical-align:middle;border-radius:10px;background:rgba(22,131,247,.2) url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%2336a0ff' stroke-width='1.8'%3E%3Cpath d='M5 17h14M6 17V9.5l2-4h8l2 4V17M4 10h16M7 17v2m10-2v2M7.5 13h.01M16.5 13h.01M8 5h8'/%3E%3C/svg%3E") center/23px no-repeat}
.trip-step[data-step="1"] .trip-card:nth-of-type(2):before{content:'';background:rgba(22,131,247,.2) url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%2336a0ff' stroke-width='1.7'%3E%3Cellipse cx='9' cy='7' rx='5' ry='2.5'/%3E%3Cpath d='M4 7v4c0 1.4 2.2 2.5 5 2.5s5-1.1 5-2.5V7M4 11v4c0 1.4 2.2 2.5 5 2.5 1.2 0 2.3-.2 3.3-.7M14 10.5c2.8 0 5-1.1 5-2.5s-2.2-2.5-5-2.5M14 10.5v4c0 1.4 2.2 2.5 5 2.5s5-1.1 5-2.5V8'/%3E%3C/svg%3E") center/24px no-repeat}
</style>
<style>
.trip-page{max-width:1150px;padding:10px 0 42px}.trip-header{justify-content:center;position:relative;margin-bottom:30px}.trip-header .trip-cancel{position:absolute;right:0}.trip-title{font-size:34px;letter-spacing:-.02em}.trip-progress{position:relative;gap:0;margin:0 auto 34px;max-width:820px}.trip-progress:before{content:'';position:absolute;left:12.5%;right:12.5%;top:17px;height:2px;background:#334155;z-index:0}.trip-progress span{position:relative;z-index:1;height:auto;background:transparent!important;text-align:center;color:var(--muted);padding-top:42px;font-size:13px;font-weight:750}.trip-progress span:before{content:counter(step);counter-increment:step;display:grid;place-items:center;width:32px;height:32px;margin:0 auto;border:2px solid #64748b;border-radius:50%;background:#0d1a32;color:#dbeafe;font-weight:800}.trip-progress{counter-reset:step}.trip-progress span.active:before{border-color:#1683f7;background:#1683f7;box-shadow:0 0 0 4px rgba(22,131,247,.18)}.trip-progress span:nth-child(1):after{content:'Datos del viaje'}.trip-progress span:nth-child(2):after{content:'Gastos y viáticos'}.trip-progress span:nth-child(3):after{content:'Confirmación'}.trip-progress span:nth-child(4):after{content:'Finalizado'}.trip-progress span:after{display:block;margin-top:8px}.trip-step-label{display:none}.trip-question{font-size:0;margin:0}.trip-card{padding:24px 26px;border-radius:16px;background:linear-gradient(135deg,#101f3b,#0d1a32);border-color:#24538a;box-shadow:0 8px 24px rgba(0,0,0,.16)}.trip-card h3{font-size:18px}.trip-card h3:before{content:'✦';display:inline-grid;place-items:center;width:34px;height:34px;margin-right:12px;border-radius:10px;background:rgba(22,131,247,.22);color:#55a9ff}.trip-field input,.trip-field select,.trip-field textarea{background:#0d1a32;border-color:#527196}.trip-chip{border-color:#28548b;background:transparent}.trip-chip.selected{box-shadow:0 0 0 2px rgba(22,131,247,.15)}.trip-footer{margin-top:22px}.trip-btn{min-width:116px}.trip-btn.primary{box-shadow:0 7px 16px rgba(22,131,247,.25)}
.trip-step[data-step="1"] .trip-card:has(#tripDescription){display:none}
.trip-step[data-step="1"] .trip-card{border-color:#2467a8;background:linear-gradient(135deg,#10264a,#0c1c38);padding:24px 34px}.trip-step[data-step="1"] .trip-card:first-of-type h3:before{content:'🚗';font-size:20px}.trip-step[data-step="1"] .trip-card:nth-of-type(2){position:relative}.trip-step[data-step="1"] .trip-card:nth-of-type(2):before{content:'◉';display:inline-grid;place-items:center;width:38px;height:38px;margin-bottom:14px;border-radius:10px;background:rgba(22,131,247,.2);color:#36a0ff;font-size:23px}.trip-step[data-step="1"] .trip-card:nth-of-type(2) .trip-counter{margin-top:0}.trip-step[data-step="1"] .trip-card:nth-of-type(2) .trip-counter strong{font-size:20px}.trip-step[data-step="1"] .trip-card:nth-of-type(2) .trip-chips{gap:0;border:1px solid #315985;border-radius:999px;padding:3px;background:#0b1932}.trip-step[data-step="1"] .trip-card:nth-of-type(2) .trip-chips .trip-chip{border:0;border-radius:999px;padding:10px 24px}.trip-step[data-step="1"] .trip-card:nth-of-type(2) .trip-chips .trip-chip.selected{background:#1683f7}.trip-step[data-step="1"] .trip-card:nth-of-type(2) select{min-height:50px;border-color:#6c8fb4}.trip-step[data-step="1"] .trip-chip{font-size:16px;padding:12px 26px}
</style>
<style>
/* Iconos finales del paso 2: SVG alineados con los títulos */
.trip-step[data-step="1"] .trip-card:first-of-type h3:before{content:''!important;display:inline-block;width:38px;height:38px;margin-right:14px;vertical-align:middle;border-radius:10px;background:rgba(22,131,247,.2) url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%2336a0ff' stroke-width='1.8'%3E%3Cpath d='M5 17h14M6 17V9.5l2-4h8l2 4V17M4 10h16M7 17v2m10-2v2M7.5 13h.01M16.5 13h.01M8 5h8'/%3E%3C/svg%3E") center/23px no-repeat!important}
.trip-step[data-step="1"] .trip-card:nth-of-type(2):before{display:none!important;content:none!important}
.trip-step[data-step="1"] .trip-card:nth-of-type(2) .trip-counter>strong{display:flex;align-items:center;gap:14px}
.trip-step[data-step="1"] .trip-card:nth-of-type(2) .trip-counter>strong:before{content:'';display:inline-block;flex:0 0 38px;width:38px;height:38px;border-radius:10px;background:rgba(22,131,247,.2) url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%2336a0ff' stroke-width='1.7'%3E%3Cellipse cx='9' cy='7' rx='5' ry='2.5'/%3E%3Cpath d='M4 7v4c0 1.4 2.2 2.5 5 2.5s5-1.1 5-2.5V7M4 11v4c0 1.4 2.2 2.5 5 2.5 1.2 0 2.3-.2 3.3-.7M14 10.5c2.8 0 5-1.1 5-2.5s-2.2-2.5-5-2.5M14 10.5v4c0 1.4 2.2 2.5 5 2.5s5-1.1 5-2.5V8'/%3E%3C/svg%3E") center/24px no-repeat}
.trip-step[data-step="1"] .trip-card:first-of-type h3:before{background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%2336a0ff' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M19 17h2c.6 0 1-.4 1-1v-3c0-.9-.7-1.7-1.5-1.9C18.7 10.6 16 10 16 10s-1.3-1.4-2.2-2.3c-.5-.4-1.1-.7-1.8-.7H5c-.6 0-1.1.4-1.4.9l-1.4 2.9A3.7 3.7 0 0 0 2 12v4c0 .6.4 1 1 1h2'/%3E%3Ccircle cx='7' cy='17' r='2'/%3E%3Cpath d='M9 17h6'/%3E%3Ccircle cx='17' cy='17' r='2'/%3E%3C/svg%3E")}
.trip-step[data-step="1"] .trip-card:nth-of-type(2) .trip-counter>strong:before{background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%2336a0ff' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M13.744 17.736a6 6 0 1 1-7.48-7.48'/%3E%3Cpath d='M15 6h1v4'/%3E%3Cpath d='m6.134 14.768.866-.5 2 3.464'/%3E%3Ccircle cx='16' cy='8' r='6'/%3E%3C/svg%3E")}
</style>
<style>
/* Iconos Lucide del paso 1 */
.trip-step[data-step="0"] .trip-card h3:before{content:'';display:inline-block;width:38px;height:38px;margin-right:14px;vertical-align:middle;border-radius:10px;background:rgba(22,131,247,.2) center/22px no-repeat}
.trip-step[data-step="0"] .trip-card:nth-of-type(1) h3:before{background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%2336a0ff' stroke-width='2'%3E%3Cpath d='M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z'/%3E%3Cpath d='m3.3 7 8.7 5 8.7-5M12 22V12'/%3E%3C/svg%3E")}
.trip-step[data-step="0"] .trip-card:nth-of-type(3) h3:before{background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%2336a0ff' stroke-width='2'%3E%3Cpath d='M18 21a8 8 0 0 0-16 0'/%3E%3Ccircle cx='10' cy='8' r='5'/%3E%3Cpath d='M22 20c0-3.37-2-6.5-4-8a5 5 0 0 0-.45-8.3'/%3E%3C/svg%3E")}
.trip-step[data-step="0"] .trip-card:nth-of-type(2):before{content:'';display:inline-block;width:38px;height:38px;margin-bottom:10px;border-radius:10px;background:rgba(22,131,247,.2) url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%2336a0ff' stroke-width='2'%3E%3Cpath d='M8 2v3m8-3v3M3 9h18M5 4h14a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2'/%3E%3C/svg%3E") center/22px no-repeat}
.trip-step[data-step="0"] .trip-card:nth-of-type(2):after{content:'Información del viaje';display:block;margin:-48px 0 18px 52px;color:var(--text);font-size:17px;font-weight:800}
.trip-step[data-step="0"] .trip-chip[data-motive]{display:inline-flex;align-items:center;gap:8px}
.trip-step[data-step="0"] .trip-chip[data-motive]:before{content:'';width:17px;height:17px;background:center/17px no-repeat}
.trip-step[data-step="0"] .trip-chip[data-motive="Entrega"]:before{background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2'%3E%3Cpath d='m21 16-4 4-4-4M17 20V4M3 8l4-4 4 4M7 4v16'/%3E%3C/svg%3E")}
.trip-step[data-step="0"] .trip-chip[data-motive="Instalación"]:before{background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2'%3E%3Cpath d='M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3-3M12 8 3.5 16.5a2.12 2.12 0 1 0 3 3L15 11'/%3E%3C/svg%3E")}
.trip-step[data-step="0"] .trip-chip[data-motive="Visita a cliente"]:before{background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2'%3E%3Ccircle cx='12' cy='7' r='4'/%3E%3Cpath d='M5.5 21a6.5 6.5 0 0 1 13 0'/%3E%3C/svg%3E")}
.trip-step[data-step="0"] .trip-chip[data-motive="Congreso / Expo"]:before{background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2'%3E%3Crect x='3' y='4' width='18' height='18' rx='2'/%3E%3Cpath d='M16 2v4M8 2v4M3 10h18'/%3E%3C/svg%3E")}
.trip-step[data-step="0"] .trip-chip[data-motive="Servicio técnico"]:before{background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2'%3E%3Cpath d='M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3-3M12 8 3.5 16.5a2.12 2.12 0 1 0 3 3L15 11'/%3E%3C/svg%3E")}
.trip-step[data-step="0"] .trip-chip[data-motive="Recolección"]:before{background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2'%3E%3Cpath d='M14 18V6a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v11a1 1 0 0 0 1 1h2M15 18H9M19 18h2a1 1 0 0 0 1-1v-4l-4-5h-3M17 18a2 2 0 1 1 4 0M5 18a2 2 0 1 0 4 0'/%3E%3C/svg%3E")}
.trip-step[data-step="0"] .trip-card:nth-of-type(2) label:before{content:'';display:inline-block;width:16px;height:16px;margin-right:7px;vertical-align:-3px;background:center/16px no-repeat}
.trip-step[data-step="0"] .trip-card:nth-of-type(2) .trip-field:nth-child(1) label:before{background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2'%3E%3Cpath d='M20 10c0 5-5.5 10-8 12-2.5-2-8-7-8-12a8 8 0 1 1 16 0'/%3E%3Ccircle cx='12' cy='10' r='3'/%3E%3C/svg%3E")}
.trip-step[data-step="0"] .trip-card:nth-of-type(2) .trip-field:nth-child(2) label:before{background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2'%3E%3Cpath d='m14 5 7-2v14l-7 3-7-3-4 2V5l4-2 7 2v15M7 3v14M14 5v15'/%3E%3C/svg%3E")}
.trip-step[data-step="0"] .trip-card:nth-of-type(2) .trip-field:nth-child(n+3) label:before{background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2'%3E%3Crect x='3' y='4' width='18' height='18' rx='2'/%3E%3Cpath d='M16 2v4M8 2v4M3 10h18'/%3E%3C/svg%3E")}
</style>
<style>
/* Ajuste del encabezado y textos auxiliares del paso 1 */
.trip-step[data-step="0"] .trip-card:nth-of-type(2){position:relative;padding-top:82px}
.trip-step[data-step="0"] .trip-card:nth-of-type(2):before{position:absolute;top:20px;left:24px;margin:0}
.trip-step[data-step="0"] .trip-card:nth-of-type(2):after{position:absolute;top:22px;left:76px;margin:0;white-space:pre-line;line-height:1.45;content:'Información del viaje\A Define el destino y las fechas del viaje.'}
.trip-step[data-step="0"] .trip-card:nth-of-type(2):after{font-size:12px;font-weight:500;color:var(--muted)}
.trip-step[data-step="0"] .trip-card:nth-of-type(2):after:first-line{font-size:17px;font-weight:800;color:var(--text)}
.trip-step[data-step="0"] .trip-chip[data-motive]:before,.trip-step[data-step="0"] .trip-card:nth-of-type(2) label:before{filter:invert(62%) sepia(90%) saturate(1500%) hue-rotate(180deg) brightness(102%) contrast(101%)}
.trip-step[data-step="0"] .trip-card:nth-of-type(3) .trip-chip[data-user]{display:inline-flex;align-items:center;gap:8px}
.trip-step[data-step="0"] .trip-card:nth-of-type(3) .trip-chip[data-user]:before{content:'';width:16px;height:16px;background:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%2336a0ff' stroke-width='2'%3E%3Ccircle cx='12' cy='8' r='4'/%3E%3Cpath d='M4 21a8 8 0 0 1 16 0'/%3E%3C/svg%3E") center/16px no-repeat}
.trip-step[data-step="0"] .trip-card:nth-of-type(3) .trip-counter>span:after{content:'';display:inline-block;width:15px;height:15px;margin-left:8px;vertical-align:-3px;background:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%2336a0ff' stroke-width='2'%3E%3Ccircle cx='12' cy='12' r='10'/%3E%3Cpath d='M12 16v-4M12 8h.01'/%3E%3C/svg%3E") center/15px no-repeat}
</style>
<style>
/* Escala visual del paso 2 según la referencia */
.trip-step[data-step="1"] .trip-card{padding:26px 36px}
.trip-step[data-step="1"] .trip-card h3{display:flex;align-items:center;gap:14px;margin:0 0 18px;font-size:24px;line-height:1.2}
.trip-step[data-step="1"] .trip-card:first-of-type h3:before{width:48px;height:48px;margin:0;background-size:29px}
.trip-step[data-step="1"] .trip-card:nth-of-type(2) .trip-counter{align-items:center;gap:20px}
.trip-step[data-step="1"] .trip-card:nth-of-type(2) .trip-counter>strong{font-size:24px;line-height:1.25}
.trip-step[data-step="1"] .trip-card:nth-of-type(2) .trip-counter>strong:before{flex-basis:48px;width:48px;height:48px;background-size:30px}
.trip-step[data-step="1"] .trip-card:nth-of-type(2) .trip-chips{margin-left:auto}
.trip-step[data-step="1"] .trip-card:nth-of-type(2) select{min-height:54px;font-size:17px}
.trip-step[data-step="1"] .trip-card:first-of-type .trip-chips{gap:14px}
.trip-step[data-step="1"] .trip-card:first-of-type .trip-chip{min-height:48px;padding:0 26px;font-size:17px}
@media(max-width:700px){.trip-step[data-step="1"] .trip-card{padding:22px}.trip-step[data-step="1"] .trip-card:nth-of-type(2) .trip-counter{align-items:flex-start;flex-direction:column}.trip-step[data-step="1"] .trip-card:nth-of-type(2) .trip-chips{margin-left:0}}
</style>
<style>
/* Diseño compacto del paso 3 */
.trip-step[data-step="2"]{gap:10px}
.trip-step[data-step="2"]>.trip-help{margin:0 0 2px;font-size:13px}
.trip-step[data-step="2"] .trip-card{padding:14px 28px;border-color:#2467a8;background:linear-gradient(135deg,#10264a,#0c1c38)}
.trip-step[data-step="2"] .trip-card h3{display:flex;align-items:center;gap:12px;margin:0 0 8px;font-size:18px}
.trip-step[data-step="2"] .trip-card h3:before,.trip-step[data-step="2"] .trip-counter:before{content:'';display:inline-block;flex:0 0 36px;width:36px;height:36px;border-radius:9px;background:rgba(22,131,247,.2) center/22px no-repeat}
.trip-step[data-step="2"] .trip-card:nth-of-type(1) h3:before{background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%2336a0ff' stroke-width='2'%3E%3Cpath d='m13 2-10 12h9l-1 8 10-12h-9l1-8Z'/%3E%3C/svg%3E")}
.trip-step[data-step="2"] .trip-card:nth-of-type(2) h3:before{background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%2336a0ff' stroke-width='2'%3E%3Cpath d='M3 2v7c0 1.1.9 2 2 2h4c1.1 0 2-.9 2-2V2M7 2v20M21 15V2a5 5 0 0 0-5 5v6c0 1.1.9 2 2 2h3M21 15v7'/%3E%3C/svg%3E")}
.trip-step[data-step="2"] .trip-card:nth-of-type(3) .trip-counter:before{background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%2336a0ff' stroke-width='2'%3E%3Cpath d='M3 10V5h18v5M5 10v9m14-9v9M3 19h18M5 10h14'/%3E%3C/svg%3E")}
.trip-step[data-step="2"] .trip-card:nth-of-type(4) .trip-counter:before,.trip-step[data-step="2"] .trip-card:nth-of-type(5) .trip-counter:before{background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%2336a0ff' stroke-width='2'%3E%3Cpath d='M5 17h14M6 17V9.5l2-4h8l2 4V17M4 10h16M7 17v2m10-2v2M7.5 13h.01M16.5 13h.01'/%3E%3C/svg%3E")}
.trip-step[data-step="2"] .trip-card:nth-of-type(6) .trip-counter:before{background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%2336a0ff' stroke-width='2'%3E%3Cpath d='m12 2 9 5-9 5-9-5 9-5Z'/%3E%3Cpath d='m3 12 9 5 9-5M3 17l9 5 9-5'/%3E%3C/svg%3E")}
.trip-step[data-step="2"] .trip-fields{gap:16px}
.trip-step[data-step="2"] .trip-field label{font-size:12px;margin-bottom:4px}
.trip-step[data-step="2"] .trip-field input{min-height:36px;padding:0 12px}
.trip-step[data-step="2"] .trip-card:nth-of-type(2) .trip-fields{align-items:end}
.trip-step[data-step="2"] #calculateMeals{width:100%;min-height:36px;margin:0}
.trip-step[data-step="2"] .trip-counter{display:flex;align-items:center;gap:14px;margin:0}
.trip-step[data-step="2"] .trip-counter>strong{font-size:17px;line-height:1.2}
.trip-step[data-step="2"] .trip-counter>.trip-chips{margin-left:auto}
.trip-step[data-step="2"] .trip-extra-fields{grid-column:1/-1;margin-top:8px}
.trip-step[data-step="2"] .trip-card:nth-of-type(3) .trip-extra-fields{display:grid;grid-template-columns:1fr 1fr}
.trip-step[data-step="2"] .trip-card:nth-of-type(4) .trip-extra-fields,.trip-step[data-step="2"] .trip-card:nth-of-type(5) .trip-extra-fields{display:block}
.trip-step[data-step="2"] .trip-card:nth-of-type(6) .trip-counter{justify-content:space-between}
.trip-step[data-step="2"] .trip-card:nth-of-type(6) .trip-counter .trip-btn{min-height:38px;padding:0 22px}
.trip-step[data-step="2"] .trip-card:last-of-type{padding:16px 28px}
.trip-step[data-step="2"] .trip-total{display:flex;align-items:center;justify-content:space-between;gap:20px}
.trip-step[data-step="2"] .trip-total label{font-size:16px;font-weight:700}
.trip-step[data-step="2"] .trip-total strong{font-size:22px}
@media(max-width:700px){.trip-step[data-step="2"] .trip-card{padding:14px 18px}.trip-step[data-step="2"] .trip-card:nth-of-type(3) .trip-extra-fields{grid-template-columns:1fr}.trip-step[data-step="2"] .trip-counter{align-items:flex-start;flex-wrap:wrap}.trip-step[data-step="2"] .trip-counter>.trip-chips{margin-left:auto}}
</style>
<style>
/* Complementos Lucide para una lectura más clara del formulario */
.trip-cancel{display:inline-flex;align-items:center;gap:8px}
.trip-cancel:before{content:'';width:16px;height:16px;background:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2'%3E%3Cpath d='M18 6 6 18M6 6l12 12'/%3E%3C/svg%3E") center/16px no-repeat}
.trip-step[data-step="1"] .trip-chip[data-vehicle-name]{display:inline-flex;align-items:center;gap:8px}
.trip-step[data-step="1"] .trip-chip[data-vehicle-name]:before{content:'';width:18px;height:18px;background:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2'%3E%3Cpath d='M19 17h2c.6 0 1-.4 1-1v-3c0-.9-.7-1.7-1.5-1.9C18.7 10.6 16 10 16 10s-1.3-1.4-2.2-2.3c-.5-.4-1.1-.7-1.8-.7H5c-.6 0-1.1.4-1.4.9l-1.4 2.9A3.7 3.7 0 0 0 2 12v4c0 .6.4 1 1 1h2M9 17h6'/%3E%3Ccircle cx='7' cy='17' r='2'/%3E%3Ccircle cx='17' cy='17' r='2'/%3E%3C/svg%3E") center/18px no-repeat}
.trip-step[data-step="1"] .trip-field label:before{content:'';display:inline-block;width:15px;height:15px;margin-right:7px;vertical-align:-3px;background:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2'%3E%3Ccircle cx='12' cy='8' r='4'/%3E%3Cpath d='M4 21a8 8 0 0 1 16 0'/%3E%3C/svg%3E") center/15px no-repeat}
.trip-step[data-step="2"] .trip-field label:before{content:'';display:inline-block;width:15px;height:15px;margin-right:7px;vertical-align:-3px;background:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2'%3E%3Ccircle cx='12' cy='12' r='9'/%3E%3Cpath d='M12 7v5l3 2'/%3E%3C/svg%3E") center/15px no-repeat}
.trip-step[data-step="2"] #calculateMeals:before{content:'';display:inline-block;width:17px;height:17px;margin-right:8px;vertical-align:-4px;background:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2'%3E%3Cpath d='M3 2v7c0 1.1.9 2 2 2h4c1.1 0 2-.9 2-2V2M7 2v20M21 15V2a5 5 0 0 0-5 5v6c0 1.1.9 2 2 2h3M21 15v7'/%3E%3C/svg%3E") center/17px no-repeat}
.trip-step[data-step="2"] #addOther:before{content:'';display:inline-block;width:16px;height:16px;margin-right:7px;vertical-align:-3px;background:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2'%3E%3Cpath d='M12 5v14M5 12h14'/%3E%3C/svg%3E") center/16px no-repeat}
.trip-footer #back:before{content:'';display:inline-block;width:16px;height:16px;margin-right:7px;vertical-align:-3px;background:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2'%3E%3Cpath d='M19 12H5M12 19l-7-7 7-7'/%3E%3C/svg%3E") center/16px no-repeat}
.trip-footer #next:after{content:'';display:inline-block;width:17px;height:17px;margin-left:8px;vertical-align:-4px;background:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2'%3E%3Cpath d='M5 12h14M13 5l7 7-7 7'/%3E%3C/svg%3E") center/17px no-repeat}
.trip-step[data-step="0"] .trip-chip.selected:after{content:'';width:15px;height:15px;margin-left:3px;background:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='3'%3E%3Cpath d='m5 12 4 4L19 6'/%3E%3C/svg%3E") center/15px no-repeat}
</style>
<style>
/* Contraste del formulario en modo claro */
:root:not([data-theme="dark"]) .trip-step .trip-card{color:#e8eef8}
:root:not([data-theme="dark"]) .trip-step .trip-card h3,
:root:not([data-theme="dark"]) .trip-step .trip-card strong,
:root:not([data-theme="dark"]) .trip-step .trip-card .trip-counter{color:#f4f7fb}
:root:not([data-theme="dark"]) .trip-step .trip-card .trip-help,
:root:not([data-theme="dark"]) .trip-step .trip-card .trip-field label{color:#b8c8dc}
:root:not([data-theme="dark"]) .trip-step .trip-card .trip-field input,
:root:not([data-theme="dark"]) .trip-step .trip-card .trip-field select,
:root:not([data-theme="dark"]) .trip-step .trip-card .trip-field textarea{background:#fff;color:#26364a;border-color:#c3d0df}
:root:not([data-theme="dark"]) .trip-step .trip-card .trip-chip{color:#e8eef8;border-color:#4775a5}
:root:not([data-theme="dark"]) .trip-step .trip-card .trip-chip:hover,
:root:not([data-theme="dark"]) .trip-step .trip-card .trip-chip.selected{color:#fff;background:#1683f7;border-color:#55adff}
:root:not([data-theme="dark"]) .trip-step .trip-card .trip-btn{color:#e8eef8;border-color:#4775a5}
:root:not([data-theme="dark"]) .trip-step .trip-card .trip-btn:hover{background:rgba(22,131,247,.18)}
:root:not([data-theme="dark"]) .trip-step[data-step="1"] .trip-card:nth-of-type(2) .trip-chips{background:#10284a;border-color:#4775a5}
:root:not([data-theme="dark"]) .trip-footer .trip-btn{color:#25364a}
:root:not([data-theme="dark"]) .trip-footer .trip-btn.primary{color:#fff}
</style>
<style>
/* Modo claro: tarjetas blancas y contenido oscuro */
:root:not([data-theme="dark"]) .trip-step .trip-card{background:#fff!important;border-color:#d7e1ed;color:#243447;box-shadow:0 8px 22px rgba(31,55,85,.08)}
:root:not([data-theme="dark"]) .trip-step .trip-card h3,
:root:not([data-theme="dark"]) .trip-step .trip-card strong,
:root:not([data-theme="dark"]) .trip-step .trip-card .trip-counter{color:#243447}
:root:not([data-theme="dark"]) .trip-step .trip-card .trip-help,
:root:not([data-theme="dark"]) .trip-step .trip-card .trip-field label{color:#63748a}
:root:not([data-theme="dark"]) .trip-step .trip-card h3:before,
:root:not([data-theme="dark"]) .trip-step .trip-card .trip-counter:before,
:root:not([data-theme="dark"]) .trip-step[data-step="1"] .trip-card:nth-of-type(2) .trip-counter>strong:before{background-color:#e8f3ff}
:root:not([data-theme="dark"]) .trip-step .trip-chip{background:#fff;color:#243447;border-color:#a9bdd3}
:root:not([data-theme="dark"]) .trip-step .trip-chip:hover,
:root:not([data-theme="dark"]) .trip-step .trip-chip.selected{background:#1683f7;color:#fff;border-color:#1683f7}
:root:not([data-theme="dark"]) .trip-step .trip-counter button{background:#eef3f8;color:#243447}
:root:not([data-theme="dark"]) .trip-step .trip-counter button:hover{background:#dce9f6}
:root:not([data-theme="dark"]) .trip-step[data-step="1"] .trip-card:nth-of-type(2) .trip-chips{background:#fff;border-color:#a9bdd3}
:root:not([data-theme="dark"]) .trip-progress span:before{background:#fff;color:#243447;border-color:#91a5bc}
:root:not([data-theme="dark"]) .trip-progress span.active:before{background:#1683f7;color:#fff;border-color:#1683f7}
:root:not([data-theme="dark"]) .trip-progress:before{background:#c7d4e2}
:root:not([data-theme="dark"]) .trip-step[data-step="2"] .trip-card .trip-btn{background:#fff;color:#243447;border-color:#9eb5cc}
:root:not([data-theme="dark"]) .trip-step[data-step="2"] .trip-card .trip-btn:hover{background:#edf5fc}
</style>
<style>
/* Corrección de legibilidad de chips en modo claro */
:root:not([data-theme="dark"]) .trip-step .trip-card .trip-chip{color:#243447!important;background:#fff!important;border-color:#8eaccb}
:root:not([data-theme="dark"]) .trip-step .trip-card .trip-chip:hover,
:root:not([data-theme="dark"]) .trip-step .trip-card .trip-chip.selected{color:#fff!important;background:#1683f7!important;border-color:#1683f7!important}
:root:not([data-theme="dark"]) .trip-step .trip-card .trip-chip:before{color:#1683f7}
:root:not([data-theme="dark"]) .trip-step .trip-card .trip-chip:hover:before,
:root:not([data-theme="dark"]) .trip-step .trip-card .trip-chip.selected:before{filter:brightness(0) invert(1)}
</style>
<style>
/* Bordes reforzados y consistencia visual en los cuatro pasos */
.trip-step .trip-card{border-width:1.5px!important}
.trip-step .trip-field input,.trip-step .trip-field select,.trip-step .trip-field textarea{border-width:1.5px!important}
.trip-step .trip-chip{border-width:1.5px!important}
.trip-step .trip-card .trip-btn{border-width:1.5px!important}
:root[data-theme="dark"] .trip-step .trip-card{border-color:#326da8!important}
:root[data-theme="dark"] .trip-step .trip-field input,
:root[data-theme="dark"] .trip-step .trip-field select,
:root[data-theme="dark"] .trip-step .trip-field textarea{border-color:#6388ae!important}
:root:not([data-theme="dark"]) .trip-step .trip-card{border-color:#a9bfd8!important}
:root:not([data-theme="dark"]) .trip-step .trip-field input,
:root:not([data-theme="dark"]) .trip-step .trip-field select,
:root:not([data-theme="dark"]) .trip-step .trip-field textarea{border-color:#9bb5cf!important}
:root:not([data-theme="dark"]) .trip-step .trip-chip{border-color:#83a5c8!important}
:root:not([data-theme="dark"]) .trip-step .trip-card .trip-btn{border-color:#83a5c8!important}
</style>
<style>
  .trip-step[data-step="2"] .trip-extra-fields[hidden]{display:none!important}
</style>
<style>
.trip-step[data-step="3"] .trip-summary-hero{display:grid;gap:8px;text-align:center;padding:26px 24px;background:linear-gradient(135deg,#10264a,#0c1c38)}
.trip-summary-kicker,.trip-summary-label{color:var(--muted);font-size:13px}
.trip-summary-hero strong{font-size:42px;line-height:1.05;color:var(--text)}
.trip-summary-subtitle{margin:0;color:var(--muted);font-size:14px}
.trip-summary-bar{display:flex;height:10px;overflow:hidden;margin-top:12px;border-radius:999px;background:var(--surface-2)}
.trip-summary-bar span{min-width:4px}
.trip-summary-list{display:grid;gap:0}
.trip-summary-row{display:grid;grid-template-columns:minmax(0,1fr) auto;align-items:center;gap:18px;padding:14px 4px;border-bottom:1px solid var(--border)}
.trip-summary-row:last-child{border-bottom:0}
.trip-summary-name{display:flex;align-items:center;gap:10px;color:var(--text);font-weight:750}
.trip-summary-dot{width:10px;height:10px;flex:0 0 10px;border-radius:50%;background:var(--primary)}
.trip-summary-meta{display:block;margin:3px 0 0 20px;color:var(--muted);font-size:12px;font-weight:500}
.trip-summary-amount{text-align:right;color:var(--text);font-weight:800}
.trip-summary-percent{display:block;color:var(--muted);font-size:12px;font-weight:500}
.trip-summary-stats{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:10px}
.trip-summary-stat{padding:14px;border-radius:14px;background:var(--surface-2);color:var(--text)}
.trip-summary-stat strong{display:block;margin-top:5px;font-size:16px}
.trip-summary-money .trip-summary-row strong{color:var(--text)}
.trip-summary-actions{display:grid;gap:10px;margin-top:12px}
.trip-summary-actions .trip-btn{width:100%}
@media(max-width:700px){.trip-summary-hero strong{font-size:34px}.trip-summary-stats{grid-template-columns:repeat(2,minmax(0,1fr))}}
:root:not([data-theme="dark"]) .trip-step[data-step="3"] .trip-summary-hero{background:#fff}
:root:not([data-theme="dark"]) .trip-step[data-step="3"] .trip-summary-stat{background:#f3f7fb}
</style>
<style>
/* Paso 4: resumen final según la referencia visual */
.trip-step[data-step="3"].active{padding:24px 30px 28px;border:1px solid #2878bf;border-radius:18px;background:linear-gradient(145deg,#0d203f,#09172d);box-shadow:0 10px 30px rgba(0,0,0,.18)}
.trip-step[data-step="3"] .trip-step-label{display:block;margin:0;color:#b8cae0;font-size:14px}
.trip-step[data-step="3"] .trip-question{display:block;margin:0 0 18px;font-size:28px;line-height:1.1;color:#f7fbff}
.trip-step[data-step="3"] .trip-card{margin-bottom:12px;padding:22px 26px;border:1px solid #2878bf;background:linear-gradient(145deg,#10294f,#0d1d38);box-shadow:none}
.trip-step[data-step="3"] .trip-summary-hero{padding:20px 26px;border-color:#38a0ff;background:linear-gradient(145deg,#102c55,#0c1e3c)}
.trip-step[data-step="3"] .trip-summary-hero strong{font-size:42px}
.trip-step[data-step="3"] .trip-summary-row{padding:13px 2px;border-color:#385879}
.trip-step[data-step="3"] .trip-summary-stats{grid-template-columns:repeat(5,minmax(0,1fr));margin:0 0 12px}
.trip-step[data-step="3"] .trip-summary-stat{min-height:66px;padding:12px 14px;border:1px solid #2878bf;background:linear-gradient(145deg,#10294f,#0d1d38)}
.trip-step[data-step="3"] .trip-summary-stat strong{font-size:17px}
.trip-step[data-step="3"] .trip-summary-actions{gap:8px;margin:0 0 12px}
.trip-step[data-step="3"] .trip-summary-actions .trip-btn{min-height:48px;border-color:#42678f;background:linear-gradient(145deg,#142b4d,#101d35);font-size:17px}
.trip-step[data-step="3"] .trip-summary-actions .trip-btn:first-child{color:#ffb21c;font-size:18px}
.trip-step[data-step="3"] .trip-summary-actions .trip-btn.primary{border-color:#36a0ff;background:#1683f7;color:#fff;box-shadow:0 8px 20px rgba(22,131,247,.3)}
.trip-step[data-step="3"] .trip-summary-actions .trip-btn:last-child{border:0;background:transparent;color:#36a0ff;box-shadow:none}
.trip-step[data-step="3"] .trip-card:has(#ticket_photos){display:none}
:root:not([data-theme="dark"]) .trip-step[data-step="3"].active{background:#fff;border-color:#86add2;box-shadow:0 8px 24px rgba(68,96,125,.12)}
:root:not([data-theme="dark"]) .trip-step[data-step="3"] .trip-step-label{color:#63748a}
:root:not([data-theme="dark"]) .trip-step[data-step="3"] .trip-question{color:#172b43}
:root:not([data-theme="dark"]) .trip-step[data-step="3"] .trip-card{background:#fff;border-color:#8eaccb}
:root:not([data-theme="dark"]) .trip-step[data-step="3"] .trip-summary-hero{background:#f5f9fd;border-color:#6fa9dc}
:root:not([data-theme="dark"]) .trip-step[data-step="3"] .trip-summary-stat{background:#f5f9fd;border-color:#8eaccb}
:root:not([data-theme="dark"]) .trip-step[data-step="3"] .trip-summary-row{border-color:#c4d2e0}
@media(max-width:900px){.trip-step[data-step="3"] .trip-summary-stats{grid-template-columns:repeat(3,minmax(0,1fr))}}
@media(max-width:600px){.trip-step[data-step="3"].active{padding:20px 16px}.trip-step[data-step="3"] .trip-summary-stats{grid-template-columns:repeat(2,minmax(0,1fr))}.trip-step[data-step="3"] .trip-question{font-size:24px}}
</style>
<style>
.trip-duration{margin:0 24px 16px;font-size:16px}
.trip-step[data-step="2"] .trip-question{display:block;margin:0 0 4px;font-size:28px;line-height:1.1;color:var(--text)}
.trip-step[data-step="2"] #tripSummary{margin:0 0 14px;font-size:16px}
.trip-step[data-step="1"] .trip-chip[data-vehicle-name]{color:#e8eef8!important}
.trip-step[data-step="1"] .trip-chip[data-vehicle-name]:before{filter:brightness(0) invert(1);opacity:1}
.trip-step[data-step="1"] .trip-chip[data-vehicle-name].selected:before,.trip-step[data-step="1"] .trip-chip[data-vehicle-name]:hover:before{filter:brightness(0) invert(1);opacity:1}
:root:not([data-theme="dark"]) .trip-step[data-step="1"] .trip-chip[data-vehicle-name]{color:#243447!important}
:root:not([data-theme="dark"]) .trip-step[data-step="1"] .trip-chip[data-vehicle-name]:before{filter:none;opacity:1}
:root:not([data-theme="dark"]) .trip-step[data-step="1"] .trip-chip[data-vehicle-name].selected:before,:root:not([data-theme="dark"]) .trip-step[data-step="1"] .trip-chip[data-vehicle-name]:hover:before{filter:brightness(0) invert(1)}
/* Controles Sí / No consistentes en todos los pasos */
.trip-step .trip-chips:has(> [data-money]),.trip-step .trip-chips:has(> [data-toggle-expense]){display:inline-flex;align-items:center;gap:0;padding:3px;border:1px solid #46719c;border-radius:999px;background:#08172f}
.trip-step .trip-chips:has(> [data-money]) .trip-chip,.trip-step .trip-chips:has(> [data-toggle-expense]) .trip-chip{min-width:58px;height:38px;padding:0 16px;border:0!important;border-radius:999px;background:transparent;color:#dbeafe;font-size:15px;line-height:38px}
.trip-step .trip-chips:has(> [data-money]) .trip-chip:hover,.trip-step .trip-chips:has(> [data-toggle-expense]) .trip-chip:hover{background:rgba(65,154,255,.2);color:#fff}
.trip-step .trip-chips:has(> [data-money]) .trip-chip.selected,.trip-step .trip-chips:has(> [data-toggle-expense]) .trip-chip.selected{background:#1683f7!important;color:#fff!important;box-shadow:0 2px 7px rgba(22,131,247,.35)}
:root:not([data-theme="dark"]) .trip-step .trip-chips:has(> [data-money]),:root:not([data-theme="dark"]) .trip-step .trip-chips:has(> [data-toggle-expense]){border-color:#86a9cb;background:#eef4fa}
:root:not([data-theme="dark"]) .trip-step .trip-chips:has(> [data-money]) .trip-chip,:root:not([data-theme="dark"]) .trip-step .trip-chips:has(> [data-toggle-expense]) .trip-chip{color:#243447!important;background:transparent!important}
:root:not([data-theme="dark"]) .trip-step .trip-chips:has(> [data-money]) .trip-chip.selected,:root:not([data-theme="dark"]) .trip-step .trip-chips:has(> [data-toggle-expense]) .trip-chip.selected{background:#1683f7!important;color:#fff!important}
</style>
@endpush

@section('content')
<div class="trip-page">
    <div class="trip-header"><a class="trip-cancel" href="{{ route('admin.viatics.index') }}">Cancelar</a><h1 class="trip-title">Nuevo viaje</h1><span></span></div>
    <div class="trip-progress"><span class="active"></span><span></span><span></span><span></span></div>
    <form method="POST" action="{{ route('admin.viatics.store') }}" enctype="multipart/form-data" id="tripForm">
        @csrf
        <input type="hidden" name="vehicle_id" id="vehicleId" value="{{ old('vehicle_id', $vehicles->first()?->id) }}"><input type="hidden" name="vehicle_name" id="vehicleName" value="{{ old('vehicle_name') }}"><input type="hidden" name="personas_viaje" id="travelers" value="{{ $personasSeleccionadas }}"><input type="hidden" name="expense_date" value="{{ old('expense_date', now()->format('Y-m-d')) }}">

        <section class="trip-step active" data-step="0">
            <p class="trip-step-label">Paso 1 de 4</p><h2 class="trip-question">¿A dónde van y quién va?</h2>
            <div class="trip-card"><h3>Motivo del viaje</h3><div class="trip-chips" id="motives">@foreach(['Entrega','Instalación','Visita a cliente','Congreso / Expo','Servicio técnico','Recolección'] as $motivo)<button type="button" class="trip-chip" data-motive="{{ $motivo }}">{{ $motivo }}</button>@endforeach</div><input type="hidden" name="description" id="description" value="{{ old('description') }}"></div>
            <div class="trip-card"><div class="trip-fields"><div class="trip-field"><label for="place">Ciudad</label><select id="place" name="place"><option value="">Selecciona una ciudad</option>@foreach(['Acapulco','Ario','Chapala','Chilpancingo','Córdoba','Guadalajara','León','Monterrey','Oaxaca','Querétaro','San Felipe del Progreso','San Miguel de Allende','Tlaxcala','Toluca','Tuxtla Gutiérrez','Uruapan','Veracruz','Xalapa','Ciudad de México'] as $ciudad)<option value="{{ $ciudad }}" @selected(old('place') === $ciudad)>{{ $ciudad }}</option>@endforeach</select></div><div class="trip-field"><label for="state">Estado</label><select id="state"><option>Se llena solo</option>@foreach(['CDMX','Chiapas','Estado de México','Guanajuato','Guerrero','Jalisco','Michoacán','Nuevo León','Oaxaca','Puebla','Querétaro','Tlaxcala','Veracruz','Otro'] as $estado)<option>{{ $estado }}</option>@endforeach</select></div><div class="trip-field"><label for="fecha_ida">Salida</label><input id="fecha_ida" type="date" name="fecha_ida" value="{{ old('fecha_ida') }}"></div><div class="trip-field"><label for="fecha_regreso">Regreso</label><input id="fecha_regreso" type="date" name="fecha_regreso" value="{{ old('fecha_regreso') }}"></div></div></div>
            <div class="trip-card"><h3>¿Quién viaja del equipo?</h3><div class="trip-chips" id="users">@foreach($users as $user)<button type="button" class="trip-chip {{ str_contains($personasSeleccionadas, $user->name) ? 'selected' : '' }}" data-user="{{ $user->name }}">{{ $user->name }}</button>@endforeach</div><p class="trip-help">Selecciona uno o varios usuarios del sistema.</p><div class="trip-counter"><span>Acompañantes externos <small class="trip-help">(clientes, técnicos)</small></span><div class="trip-counter-controls"><button type="button" data-external="-1">−</button><strong id="externalCount">0</strong><button type="button" data-external="1">+</button></div></div></div>
        </section>

        <section class="trip-step" data-step="1"><p class="trip-step-label">Paso 2 de 4</p><h2 class="trip-question">Vehículo y dinero del viaje</h2><div class="trip-card"><h3>Vehículo</h3><div class="trip-chips" id="vehicles">@foreach(['Crafter','Caddy','BMW','Transporte público'] as $vehiculo)<button type="button" class="trip-chip" data-vehicle-id="" data-vehicle-name="{{ $vehiculo }}">{{ $vehiculo }}</button>@endforeach</div></div><div class="trip-card"><div class="trip-counter"><strong>¿Les dieron dinero para el viaje?</strong><div class="trip-chips"><button type="button" class="trip-chip selected" data-money="no">No</button><button type="button" class="trip-chip" data-money="si">Sí</button></div></div><div class="trip-field" style="margin-top:18px"><label for="payer">¿Quién pagó los gastos?</label><select id="payer"><option value="">Elige</option>@foreach($users as $user)<option value="{{ $user->name }}">{{ $user->name }}</option>@endforeach</select></div></div></section>

        <section class="trip-step" data-step="2"><p class="trip-step-label">Paso 3 de 4</p><h2 class="trip-question">¿Qué gastaron?</h2><p class="trip-help" id="tripSummary">Registra los gastos del viaje</p><div class="trip-card"><h3>Carretera</h3><div class="trip-fields"><div class="trip-field"><label>Casetas (ida y vuelta)</label><input class="expense" type="number" min="0" step="0.01" name="tolls" value="{{ old('tolls', 0) }}" placeholder="$0.00"></div><div class="trip-field"><label>Gasolina</label><input class="expense" type="number" min="0" step="0.01" name="fuel" value="{{ old('fuel', 0) }}" placeholder="$0.00"></div></div></div><div class="trip-card"><h3>Comidas</h3><div class="trip-fields"><div class="trip-field"><label>Total de comidas</label><input class="expense" id="meals" type="number" min="0" step="0.01" name="meals" value="{{ old('meals', 0) }}" placeholder="$0.00"></div><div class="trip-field"><label>&nbsp;</label><button type="button" class="trip-btn" id="calculateMeals">Calcular comida</button></div></div></div><div class="trip-card"><div class="trip-counter"><strong>¿Hubo hospedaje?</strong><div class="trip-chips"><button type="button" class="trip-chip selected" data-toggle-expense="lodging" data-value="no">No</button><button type="button" class="trip-chip" data-toggle-expense="lodging" data-value="si">Sí</button></div></div><div class="trip-fields trip-extra-fields" data-extra-fields="lodging" hidden><div class="trip-field"><label>Noches</label><input type="number" min="0" name="dias_hospedaje" value="{{ old('dias_hospedaje', 0) }}"></div><div class="trip-field"><label>Total hospedaje</label><input class="expense" type="number" min="0" step="0.01" name="lodging" value="{{ old('lodging', 0) }}" placeholder="$0.00"></div></div></div><div class="trip-card"><div class="trip-counter"><strong>¿Pagaron estacionamiento?</strong><div class="trip-chips"><button type="button" class="trip-chip selected" data-toggle-expense="parking" data-value="no">No</button><button type="button" class="trip-chip" data-toggle-expense="parking" data-value="si">Sí</button></div></div><div class="trip-field trip-extra-fields" data-extra-fields="parking" hidden><label>Total estacionamiento</label><input class="expense" type="number" min="0" step="0.01" placeholder="$0.00"></div></div><div class="trip-card"><div class="trip-counter"><strong>¿Usaron taxi o Uber?</strong><div class="trip-chips"><button type="button" class="trip-chip selected" data-toggle-expense="taxi" data-value="no">No</button><button type="button" class="trip-chip" data-toggle-expense="taxi" data-value="si">Sí</button></div></div><div class="trip-field trip-extra-fields" data-extra-fields="taxi" hidden><label>Total de taxis/Uber</label><input class="expense" type="number" min="0" step="0.01" placeholder="$0.00"></div></div><div class="trip-card"><div class="trip-counter"><strong>Otros gastos <span class="trip-help">(material, propinas, etc.)</span></strong><button type="button" class="trip-btn" id="addOther">+ Agregar</button></div><div id="otherRows"></div></div><div class="trip-card"><div class="trip-total"><label><input type="checkbox" id="fixedFund" checked> Fondo fijo de viaje ($500)</label><strong id="total">$500.00</strong></div><p class="trip-help">El total se actualiza conforme agregas gastos.</p></div></section>

        <section class="trip-step" data-step="3">
            <p class="trip-step-label">Paso 4 de 4</p><h2 class="trip-question">Revisa y guarda</h2>
            <div class="trip-card trip-summary-hero"><span class="trip-summary-kicker">Total del viaje</span><strong id="reviewTotal">$0.00</strong><p class="trip-summary-subtitle" id="reviewSubtitle">Sin destino · Sin fechas</p><div class="trip-summary-bar" id="reviewBar"></div></div>
            <div class="trip-card"><div class="trip-summary-list" id="reviewBreakdown"></div></div>
            <div class="trip-summary-stats"><div class="trip-summary-stat"><span class="trip-summary-label">Motivo</span><strong id="reviewMotive">—</strong></div><div class="trip-summary-stat"><span class="trip-summary-label">Duración</span><strong id="reviewDuration">—</strong></div><div class="trip-summary-stat"><span class="trip-summary-label">Viajan</span><strong id="reviewTravelers">—</strong></div><div class="trip-summary-stat"><span class="trip-summary-label">Vehículo</span><strong id="reviewVehicle">—</strong></div><div class="trip-summary-stat"><span class="trip-summary-label">Por persona por día</span><strong id="reviewPerDay">$0.00</strong></div></div>
            <div class="trip-card trip-summary-money"><span class="trip-summary-label">Dinero del viaje</span><div class="trip-summary-list"><div class="trip-summary-row"><div><strong id="reviewMoneyStatus">No se entregó dinero</strong><span class="trip-summary-meta" id="reviewPayer">Sin responsable</span></div><strong id="reviewMoney">$0.00</strong></div><div class="trip-summary-row"><strong>Total gastado</strong><strong id="reviewSpent">$0.00</strong></div><div class="trip-summary-row"><strong id="reviewBalanceLabel">Se le debe al responsable</strong><strong id="reviewBalance">$0.00</strong></div></div></div>
            <div class="trip-summary-actions"><button type="button" class="trip-btn" id="reimburseButton" hidden>Reembolsar <span id="reviewReimburse">$0.00</span></button><button type="button" class="trip-btn primary" id="copyWhatsApp">Copiar para WhatsApp</button><button type="button" class="trip-btn" id="showCopyText">Ver el texto que se copia</button></div>
            <div class="trip-card"><div class="trip-field"><label for="ticket_photos">Fotos de tickets</label><input id="ticket_photos" type="file" name="ticket_photos[]" accept="image/*" multiple></div></div>
        </section>

        <div class="trip-error" id="stepError" hidden></div>
        @if($errors->any())<div class="trip-error">{{ $errors->first() }}</div>@endif
        <div class="trip-footer"><button type="button" class="trip-btn" id="back" hidden>Atrás</button><button type="button" class="trip-btn primary" id="next">Siguiente</button></div>
    </form>
</div>

<script>
(()=>{let step=0,external=0;const steps=[...document.querySelectorAll('[data-step]')],bars=[...document.querySelectorAll('.trip-progress span')],$=id=>document.getElementById(id),money=n=>new Intl.NumberFormat('es-MX',{style:'currency',currency:'MXN'}).format(n||0),users=()=>[...document.querySelectorAll('[data-user].selected')].map(x=>x.dataset.user),sync=()=>$('travelers').value=users().join(', '),total=()=>[...document.querySelectorAll('.expense')].reduce((s,x)=>s+(parseFloat(x.value)||0),0),refresh=()=>{steps.forEach((x,i)=>x.classList.toggle('active',i===step));bars.forEach((x,i)=>x.classList.toggle('active',i<=step));$('back').hidden=step===0;$('next').textContent=step===3?'Guardar viaje':'Siguiente';$('total').textContent=money(total());$('reviewPlace').textContent=$('place').value||'Sin destino';$('reviewDates').textContent=[$('fecha_ida').value,$('fecha_regreso').value].filter(Boolean).join(' → ')||'Sin fechas';$('reviewUsers').textContent=$('travelers').value||'Sin participantes';$('reviewVehicle').textContent=$('vehicleName').value||'Sin vehículo';$('reviewTotal').textContent=money(total())};const error=message=>{const box=$('stepError');box.textContent=message;box.hidden=false};const validate=()=>{if(step===0){if(!$('description').value)return 'Selecciona el motivo del viaje.';if(!$('place').value.trim())return 'Escribe la ciudad del viaje.';if(!$('fecha_ida').value)return 'Selecciona la fecha de salida.';if(!$('fecha_regreso').value)return 'Selecciona la fecha de regreso.';if(!$('travelers').value)return 'Selecciona al menos una persona del equipo.'}if(step===1&&!$('vehicleName').value)return 'Selecciona el vehículo del viaje.';return ''};document.querySelectorAll('[data-motive]').forEach(b=>b.addEventListener('click',()=>{document.querySelectorAll('[data-motive]').forEach(x=>x.classList.remove('selected'));b.classList.add('selected');$('description').value=b.dataset.motive;if($('tripDescription'))$('tripDescription').value=b.dataset.motive;$('stepError').hidden=true}));document.querySelectorAll('[data-user]').forEach(b=>b.addEventListener('click',()=>{b.classList.toggle('selected');sync();$('stepError').hidden=true;refresh()}));document.querySelectorAll('[data-vehicle-id]').forEach(b=>b.addEventListener('click',()=>{document.querySelectorAll('[data-vehicle-id]').forEach(x=>x.classList.remove('selected'));b.classList.add('selected');$('vehicleId').value=b.dataset.vehicleId;$('vehicleName').value=b.dataset.vehicleName;$('stepError').hidden=true;refresh()}));document.querySelectorAll('[data-money]').forEach(b=>b.addEventListener('click',()=>{document.querySelectorAll('[data-money]').forEach(x=>x.classList.remove('selected'));b.classList.add('selected')}));document.querySelectorAll('[data-external]').forEach(b=>b.addEventListener('click',()=>{external=Math.max(0,external+Number(b.dataset.external));$('externalCount').textContent=external}));document.querySelectorAll('.expense').forEach(x=>x.addEventListener('input',refresh));$('tripDescription')?.addEventListener('input',e=>$('description').value=e.target.value);$('next').addEventListener('click',()=>{if(step<3){const message=validate();if(message){error(message);return}$('stepError').hidden=true;step++;refresh()}else $('tripForm').submit()});$('back').addEventListener('click',()=>{if(step>0){step--;refresh()}});refresh()})();
</script>
<script>
const ciudadesEstados={Acapulco:'Guerrero',Ario:'Michoacán',Chapala:'Jalisco',Chilpancingo:'Guerrero',Córdoba:'Veracruz',Guadalajara:'Jalisco',León:'Guanajuato',Monterrey:'Nuevo León',Oaxaca:'Oaxaca',Querétaro:'Querétaro','San Felipe del Progreso':'Estado de México','San Miguel de Allende':'Guanajuato',Tlaxcala:'Tlaxcala',Toluca:'Estado de México','Tuxtla Gutiérrez':'Chiapas',Uruapan:'Michoacán',Veracruz:'Veracruz',Xalapa:'Veracruz','Ciudad de México':'CDMX'};
document.getElementById('place')?.addEventListener('change',function(){document.getElementById('state').value=ciudadesEstados[this.value]||'Otro';document.getElementById('stepError').hidden=true});
const payer=document.getElementById('payer');const selectedTraveler=()=>document.querySelector('[data-user].selected')?.dataset.user;if(payer&&!payer.value&&selectedTraveler()){const option=[...payer.options].find(x=>x.value===selectedTraveler());if(option)payer.value=option.value}document.querySelectorAll('[data-user]').forEach(button=>button.addEventListener('click',()=>{const option=[...payer?.options||[]].find(x=>x.value===selectedTraveler());if(payer&&option&&!payer.value)payer.value=option.value}));
document.querySelectorAll('[data-toggle-expense]').forEach(button=>button.addEventListener('click',()=>{const key=button.dataset.toggleExpense;document.querySelectorAll('[data-toggle-expense="'+key+'"]').forEach(x=>x.classList.remove('selected'));button.classList.add('selected');const fields=document.querySelector('[data-extra-fields="'+key+'"]');if(fields)fields.hidden=button.dataset.value!=='si'}));
document.getElementById('calculateMeals')?.addEventListener('click',()=>{const people=Math.max(1,document.querySelectorAll('[data-user].selected').length);const from=new Date(document.getElementById('fecha_ida').value||new Date()),to=new Date(document.getElementById('fecha_regreso').value||from);const days=Math.max(1,Math.round((to-from)/86400000)+1);document.getElementById('meals').value=people*days*250;document.getElementById('meals').dispatchEvent(new Event('input',{bubbles:true}))});
document.getElementById('addOther')?.addEventListener('click',()=>{const row=document.createElement('div');row.className='trip-fields';row.style.marginTop='14px';row.innerHTML='<div class="trip-field"><label>¿Qué fue?</label><input class="expense" type="text" placeholder="Material, propina... "></div><div class="trip-field"><label>Monto</label><input class="expense other-amount" type="number" min="0" step="0.01" placeholder="$0.00"></div>';document.getElementById('otherRows').appendChild(row);row.querySelector('.other-amount').addEventListener('input',()=>{const total=[...document.querySelectorAll('.expense')].reduce((s,x)=>s+(parseFloat(x.value)||0),0)+(document.getElementById('fixedFund').checked?500:0);document.getElementById('total').textContent=new Intl.NumberFormat('es-MX',{style:'currency',currency:'MXN'}).format(total)});});
document.getElementById('fixedFund')?.addEventListener('change',()=>{const total=[...document.querySelectorAll('.expense')].reduce((s,x)=>s+(parseFloat(x.value)||0),0)+(document.getElementById('fixedFund').checked?500:0);document.getElementById('total').textContent=new Intl.NumberFormat('es-MX',{style:'currency',currency:'MXN'}).format(total)});
const updateTripTotal=()=>{const total=[...document.querySelectorAll('.expense')].reduce((s,x)=>s+(parseFloat(x.value)||0),0)+(document.getElementById('fixedFund')?.checked?500:0);document.getElementById('total').textContent=new Intl.NumberFormat('es-MX',{style:'currency',currency:'MXN'}).format(total)};document.querySelectorAll('.expense').forEach(x=>x.addEventListener('input',updateTripTotal));updateTripTotal();
</script>
<script>
(()=>{
  const byId=id=>document.getElementById(id);
  const money=n=>new Intl.NumberFormat('es-MX',{style:'currency',currency:'MXN'}).format(Number(n)||0);
  const amount=selector=>Number(document.querySelector(selector)?.value)||0;
  const getDays=()=>{const from=new Date(byId('fecha_ida')?.value||''),to=new Date(byId('fecha_regreso')?.value||'');if(Number.isNaN(from.getTime())||Number.isNaN(to.getTime()))return 0;return Math.max(1,Math.round((to-from)/86400000)+1)};
  const updateReview=()=>{
    const tolls=amount('[name="tolls"]'),fuel=amount('[name="fuel"]'),meals=amount('[name="meals"]'),lodging=amount('[data-extra-fields="lodging"] input.expense'),parking=amount('[data-extra-fields="parking"] input.expense'),taxi=amount('[data-extra-fields="taxi"] input.expense');
    const other=[...document.querySelectorAll('#otherRows .other-amount')].reduce((sum,input)=>sum+(Number(input.value)||0),0),fund=byId('fixedFund')?.checked?500:0;
    const rows=[['Casetas',tolls,'#1683f7',''],['Gasolina',fuel,'#f59e0b',''],['Comidas',meals,'#22c55e',`${Math.max(1,document.querySelectorAll('[data-user].selected').length)} personas · ${getDays()||1} días`],['Hospedaje',lodging,'#a855f7',''],['Estacionamiento',parking,'#f97316',''],['Taxi / Uber',taxi,'#14b8a6',''],['Otros gastos',other,'#64748b',''],['Fondo fijo de viaje',fund,'#94a3b8','']].filter(row=>row[1]>0);
    const spent=rows.reduce((sum,row)=>sum+row[1],0), total=spent, people=Math.max(1,document.querySelectorAll('[data-user].selected').length), days=getDays()||1;
    byId('reviewTotal').textContent=money(total);byId('reviewSpent').textContent=money(total);byId('reviewPerDay').textContent=money(total/(people*days));
    const place=byId('place')?.value||'Sin destino',start=byId('fecha_ida')?.value||'',end=byId('fecha_regreso')?.value||'';byId('reviewSubtitle').textContent=`${place} · ${start&&end?start+' → '+end:'Sin fechas'}`;
    byId('reviewMotive').textContent=byId('description')?.value||'Sin motivo';byId('reviewDuration').textContent=`${days} ${days===1?'día':'días'}`;byId('reviewTravelers').textContent=`${people} ${people===1?'persona':'personas'}`;byId('reviewVehicle').textContent=byId('vehicleName')?.value||'Sin vehículo';
    const breakdown=byId('reviewBreakdown'),bar=byId('reviewBar');breakdown.innerHTML='';bar.innerHTML='';rows.forEach(([name,value,color,meta])=>{const percent=total?Math.round(value/total*100):0;const row=document.createElement('div');row.className='trip-summary-row';row.innerHTML=`<div><span class="trip-summary-name"><i class="trip-summary-dot" style="background:${color}"></i>${name}</span>${meta?`<span class="trip-summary-meta">${meta}</span>`:''}</div><strong class="trip-summary-amount">${money(value)}<span class="trip-summary-percent">${percent}%</span></strong>`;breakdown.appendChild(row);const segment=document.createElement('span');segment.style=`flex:${value};background:${color}`;bar.appendChild(segment)});
    const moneyGiven=document.querySelector('[data-money].selected')?.dataset.money==='si',payer=byId('payer')?.value||'Sin responsable';byId('reviewMoneyStatus').textContent=moneyGiven?'Se entregó dinero':'No se entregó dinero';byId('reviewPayer').textContent=moneyGiven?`Pagó ${payer}`:'Sin entrega registrada';byId('reviewMoney').textContent=money(0);byId('reviewBalanceLabel').textContent=moneyGiven?'Saldo pendiente':'Se le debe al responsable';byId('reviewBalance').textContent=money(total);byId('reviewReimburse').textContent=money(total);byId('reimburseButton').hidden=!total;
  };
  document.querySelectorAll('input,select,[data-money],[data-toggle-expense]').forEach(el=>el.addEventListener('input',updateReview));document.querySelectorAll('select,[data-money],[data-toggle-expense],[data-motive],[data-user],[data-vehicle-id]').forEach(el=>el.addEventListener('change',updateReview));document.getElementById('next')?.addEventListener('click',()=>setTimeout(updateReview,0));document.getElementById('back')?.addEventListener('click',()=>setTimeout(updateReview,0));document.getElementById('copyWhatsApp')?.addEventListener('click',()=>{const text=`Viaje a ${byId('place')?.value||'sin destino'}\nTotal: ${byId('reviewTotal')?.textContent||'$0.00'}\n${byId('reviewBreakdown')?.innerText||''}`;navigator.clipboard?.writeText(text);});document.getElementById('showCopyText')?.addEventListener('click',()=>alert(`Resumen para compartir:\n\n${byId('reviewBreakdown')?.innerText||''}`));updateReview();
})();
</script>
<script>
(()=>{
  const start=document.getElementById('fecha_ida'),end=document.getElementById('fecha_regreso');
  if(!start||!end)return;
  let output=document.getElementById('tripDuration');
  if(!output){const card=document.querySelector('.trip-step[data-step="0"] .trip-card:nth-of-type(2)');if(!card)return;output=document.createElement('p');output.id='tripDuration';output.className='trip-help trip-duration';card.after(output)}
  const update=()=>{if(!start.value||!end.value){output.textContent='';return}const from=new Date(`${start.value}T00:00:00`),to=new Date(`${end.value}T00:00:00`),days=Math.max(1,Math.round((to-from)/86400000)+1);output.textContent=days===1?'1 día':`${days} días · ${days-1} ${days-1===1?'noche':'noches'}`};
  start.addEventListener('input',update);end.addEventListener('input',update);start.addEventListener('change',update);end.addEventListener('change',update);update();
})();
</script>
<script>
(()=>{
  const summary=document.getElementById('tripSummary'),start=document.getElementById('fecha_ida'),end=document.getElementById('fecha_regreso');
  if(!summary)return;
  const update=()=>{const people=Math.max(1,document.querySelectorAll('[data-user].selected').length),from=start?.value?new Date(`${start.value}T00:00:00`):null,to=end?.value?new Date(`${end.value}T00:00:00`):null,days=from&&to&&!Number.isNaN(from.getTime())&&!Number.isNaN(to.getTime())?Math.max(1,Math.round((to-from)/86400000)+1):1;summary.textContent=`${people} ${people===1?'persona':'personas'} · ${days} ${days===1?'d\u00eda':'d\u00edas'}`};
  document.querySelectorAll('[data-user]').forEach(button=>button.addEventListener('click',update));start?.addEventListener('change',update);end?.addEventListener('change',update);update();
})();
</script>
<script>
(()=>{
  const button=document.getElementById('copyWhatsApp');if(!button)return;
  const money=n=>new Intl.NumberFormat('es-MX',{style:'currency',currency:'MXN'}).format(Number(n)||0);
  const value=selector=>Number(document.querySelector(selector)?.value)||0;
  const dateText=value=>value?new Intl.DateTimeFormat('es-MX',{day:'numeric',month:'long',year:'numeric'}).format(new Date(`${value}T00:00:00`)):'';
  const days=()=>{const from=document.getElementById('fecha_ida')?.value,to=document.getElementById('fecha_regreso')?.value;if(!from||!to)return 1;return Math.max(1,Math.round((new Date(`${to}T00:00:00`)-new Date(`${from}T00:00:00`))/86400000)+1)};
  const buildText=()=>{const place=document.getElementById('place')?.value||'Sin destino',state=document.getElementById('state')?.value||'',motive=document.getElementById('description')?.value||'Sin motivo',vehicle=document.getElementById('vehicleName')?.value||'Sin vehículo',people=[...document.querySelectorAll('[data-user].selected')].map(x=>x.dataset.user),count=Math.max(1,people.length),duration=days(),tolls=value('[name="tolls"]'),fuel=value('[name="fuel"]'),meals=value('[name="meals"]'),lodging=value('[data-extra-fields="lodging"] input.expense'),parking=value('[data-extra-fields="parking"] input.expense'),taxi=value('[data-extra-fields="taxi"] input.expense'),other=[...document.querySelectorAll('#otherRows .other-amount')].reduce((sum,input)=>sum+(Number(input.value)||0),0),fund=document.getElementById('fixedFund')?.checked?500:0,total=tolls+fuel+meals+lodging+parking+taxi+other+fund,payer=document.getElementById('payer')?.value||people[0]||'Sin responsable';let text=`*Viáticos · ${place}${state&&state!=='Se llena solo'?', '+state:''}*\n${motive} · ${duration} ${duration===1?'día':'días'} · ${vehicle}\nViajan: ${count} ${count===1?'persona':'personas'}${people.length?` (${people.join(', ')})`:''}\n\n`;const rows=[['Casetas',tolls,''],['Gasolina',fuel,''],['Comidas',meals,` (${count} ${count===1?'persona':'personas'} · ${duration} ${duration===1?'día':'días'})`],['Hospedaje',lodging,''],['Estacionamiento',parking,''],['Taxi / Uber',taxi,''],['Otros gastos',other,''],['Fondo fijo de viaje',fund,'']];rows.forEach(([name,amount,meta])=>{if(amount>0)text+=`• ${name}: ${money(amount)}${meta}\n`});text+=`\n*Total gastado: ${money(total)}*\nPor persona por día: ${money(total/(count*duration))}\n\n*Dinero del viaje*\n• No se entregó dinero. Pagó ${payer}.\n*Reembolsar ${money(total)} a ${payer}*\n\n— Grupo MediBuy`;return text};
  button.addEventListener('click',async()=>{const text=buildText();try{await navigator.clipboard.writeText(text)}catch(e){const area=document.createElement('textarea');area.value=text;document.body.appendChild(area);area.select();document.execCommand('copy');area.remove()}button.textContent='Copiado ✓';setTimeout(()=>button.textContent='Copiar para WhatsApp',2200)});
  document.getElementById('showCopyText')?.addEventListener('click',()=>alert(buildText()));
})();
</script>
@endsection
