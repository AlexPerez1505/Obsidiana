@php($type = $type ?? 'scope')

@if ($type === 'probe')
    <svg viewBox="0 0 96 56" fill="none" aria-hidden="true">
        <path d="M13 22h48c11 0 16 7 16 15" stroke="#cbd5e1" stroke-width="2.2" stroke-linecap="round"/>
        <path d="M13 34h45c9 0 13 5 13 12" stroke="#94a3b8" stroke-width="1.8" stroke-linecap="round"/>
        <circle cx="69" cy="22" r="3.5" fill="#8b5cf6"/>
        <circle cx="76" cy="36" r="2.6" fill="#60a5fa"/>
        <path d="M69 22c7 0 12 4 15 10" stroke="#8b5cf6" stroke-width="1.6" stroke-linecap="round"/>
        <path d="M81 20l5-4M83 25l7-1M79 30l5 5" stroke="#111827" stroke-width="1.6" stroke-linecap="round"/>
    </svg>
@elseif ($type === 'fiber')
    <svg viewBox="0 0 96 56" fill="none" aria-hidden="true">
        <path d="M11 26h42c16 0 25-6 31-14" stroke="#cbd5e1" stroke-width="2.2" stroke-linecap="round"/>
        <path d="M15 39h37c16 0 26-5 34-17" stroke="#94a3b8" stroke-width="1.8" stroke-linecap="round"/>
        <path d="M62 20l9 14" stroke="#111827" stroke-width="1.9" stroke-linecap="round"/>
        <circle cx="65" cy="24" r="4" fill="#5eead4"/>
        <path d="M72 25l12 4M72 19l12-5M69 30l5 9" stroke="#64748b" stroke-width="1.5" stroke-linecap="round"/>
    </svg>
@elseif ($type === 'tower')
    <svg viewBox="0 0 96 56" fill="none" aria-hidden="true">
        <path d="M18 15c16 3 28 1 41-6 10-5 18-3 23 6" stroke="#111827" stroke-width="2" stroke-linecap="round"/>
        <path d="M58 12c2 10 1 19-2 28" stroke="#60a5fa" stroke-width="2" stroke-linecap="round"/>
        <path d="M24 26c18-2 31 1 42 10" stroke="#94a3b8" stroke-width="1.8" stroke-linecap="round"/>
        <path d="M35 19c-2 7-2 14 0 22" stroke="#2563eb" stroke-width="1.6" stroke-linecap="round"/>
        <circle cx="74" cy="16" r="4" fill="#111827"/>
        <path d="M72 16l9-5M72 17l11 3" stroke="#111827" stroke-width="1.5" stroke-linecap="round"/>
    </svg>
@elseif ($type === 'control')
    <svg viewBox="0 0 96 56" fill="none" aria-hidden="true">
        <path d="M30 23l16-14c3-2 8-1 10 2l7 10c2 3 1 7-2 9L45 43c-3 3-8 2-10-1l-7-10c-2-3-1-7 2-9z" fill="#111827"/>
        <path d="M36 24l17-13" stroke="#e5e7eb" stroke-width="2" stroke-linecap="round"/>
        <circle cx="45" cy="27" r="5" fill="#f8fafc"/>
        <path d="M58 29c9 0 17 7 19 17" stroke="#111827" stroke-width="2.2" stroke-linecap="round"/>
        <path d="M18 41c6-9 15-13 26-11" stroke="#64748b" stroke-width="1.8" stroke-linecap="round"/>
        <circle cx="74" cy="46" r="3" fill="#111827"/>
    </svg>
@elseif ($type === 'cable')
    <svg viewBox="0 0 96 56" fill="none" aria-hidden="true">
        <path d="M16 10c25 1 31 21 48 28 7 3 11 2 16-2" stroke="#111827" stroke-width="4" stroke-linecap="round"/>
        <path d="M15 19c22 2 29 18 47 26" stroke="#64748b" stroke-width="2" stroke-linecap="round"/>
        <path d="M65 34l15-11 7 10-15 11z" fill="#2563eb"/>
        <path d="M77 22l4-3M85 32l5-2" stroke="#111827" stroke-width="2" stroke-linecap="round"/>
    </svg>
@else
    <svg viewBox="0 0 96 56" fill="none" aria-hidden="true">
        <path d="M31 10c13 0 22 8 22 20 0 10 6 14 14 16" stroke="#111827" stroke-width="2.2" stroke-linecap="round"/>
        <path d="M34 15c8 3 12 8 12 16 0 7 4 12 11 15" stroke="#94a3b8" stroke-width="1.6" stroke-linecap="round"/>
        <rect x="25" y="8" width="15" height="18" rx="5" fill="#f8fafc" stroke="#111827" stroke-width="1.8"/>
        <path d="M28 6l9 23M38 7l-11 18" stroke="#111827" stroke-width="1.4" stroke-linecap="round"/>
        <circle cx="62" cy="45" r="3" fill="#111827"/>
    </svg>
@endif
