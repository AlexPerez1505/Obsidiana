@php($type = $type ?? 'tower')

@if ($type === 'monitor')
    <svg viewBox="0 0 72 72" fill="none" aria-hidden="true">
        <rect x="16" y="10" width="40" height="28" rx="4" fill="#f8fafc" stroke="#64748b" stroke-width="2"/>
        <path d="M20 31c7-13 12 8 18-4 5-10 8 3 14-5" stroke="#2563eb" stroke-width="1.6" stroke-linecap="round"/>
        <path d="M30 38h12l3 9H27l3-9z" fill="#cbd5e1" stroke="#64748b" stroke-width="1.5"/>
        <rect x="23" y="48" width="26" height="5" rx="2" fill="#111827"/>
    </svg>
@elseif ($type === 'stack')
    <svg viewBox="0 0 72 72" fill="none" aria-hidden="true">
        <rect x="26" y="6" width="20" height="13" rx="2" fill="#111827"/>
        <rect x="23" y="20" width="26" height="12" rx="2" fill="#f8fafc" stroke="#64748b" stroke-width="1.6"/>
        <rect x="24" y="33" width="24" height="12" rx="2" fill="#111827"/>
        <rect x="22" y="46" width="28" height="14" rx="2" fill="#f8fafc" stroke="#64748b" stroke-width="1.6"/>
        <path d="M30 62h12M33 60v6M39 60v6" stroke="#111827" stroke-width="2" stroke-linecap="round"/>
        <circle cx="43" cy="26" r="1.8" fill="#22c55e"/>
        <circle cx="43" cy="52" r="1.8" fill="#2563eb"/>
    </svg>
@elseif ($type === 'scope')
    <svg viewBox="0 0 72 72" fill="none" aria-hidden="true">
        <rect x="27" y="10" width="17" height="36" rx="3" fill="#e5e7eb" stroke="#64748b" stroke-width="1.8"/>
        <path d="M31 7h10M30 19h12M30 30h12M30 42h12" stroke="#111827" stroke-width="1.5" stroke-linecap="round"/>
        <path d="M44 24c10 2 13 10 9 18-3 6-1 11 5 15" stroke="#111827" stroke-width="2" stroke-linecap="round"/>
        <path d="M25 18c-9 6-9 17-1 25" stroke="#94a3b8" stroke-width="2" stroke-linecap="round"/>
        <circle cx="58" cy="57" r="3" fill="#111827"/>
    </svg>
@elseif ($type === 'cart')
    <svg viewBox="0 0 72 72" fill="none" aria-hidden="true">
        <rect x="25" y="7" width="22" height="10" rx="2" fill="#111827"/>
        <rect x="23" y="19" width="26" height="12" rx="2" fill="#f8fafc" stroke="#64748b" stroke-width="1.6"/>
        <rect x="24" y="33" width="24" height="12" rx="2" fill="#111827"/>
        <rect x="22" y="47" width="28" height="10" rx="2" fill="#e5e7eb" stroke="#64748b" stroke-width="1.5"/>
        <path d="M20 58h32M25 58l-3 7M47 58l3 7" stroke="#111827" stroke-width="2" stroke-linecap="round"/>
        <circle cx="22" cy="66" r="2.5" fill="#111827"/>
        <circle cx="50" cy="66" r="2.5" fill="#111827"/>
    </svg>
@elseif ($type === 'unit')
    <svg viewBox="0 0 72 72" fill="none" aria-hidden="true">
        <rect x="20" y="8" width="30" height="48" rx="4" fill="#f8fafc" stroke="#64748b" stroke-width="1.8"/>
        <rect x="25" y="13" width="20" height="14" rx="2" fill="#111827"/>
        <path d="M27 35h17M27 42h17M27 49h12" stroke="#94a3b8" stroke-width="2" stroke-linecap="round"/>
        <path d="M50 26c7 2 9 7 6 13-2 5-1 9 3 13" stroke="#111827" stroke-width="1.8" stroke-linecap="round"/>
        <path d="M26 58h18M29 56v7M42 56v7" stroke="#111827" stroke-width="2" stroke-linecap="round"/>
    </svg>
@else
    <svg viewBox="0 0 72 72" fill="none" aria-hidden="true">
        <rect x="24" y="7" width="24" height="12" rx="2" fill="#111827"/>
        <rect x="22" y="21" width="28" height="14" rx="2" fill="#f8fafc" stroke="#64748b" stroke-width="1.7"/>
        <rect x="23" y="37" width="26" height="18" rx="3" fill="#e5e7eb" stroke="#64748b" stroke-width="1.7"/>
        <path d="M28 27h16M30 44h12M30 49h12" stroke="#2563eb" stroke-width="1.8" stroke-linecap="round"/>
        <path d="M28 57h16M31 55v8M41 55v8" stroke="#111827" stroke-width="2" stroke-linecap="round"/>
        <circle cx="46" cy="44" r="2" fill="#22c55e"/>
    </svg>
@endif
