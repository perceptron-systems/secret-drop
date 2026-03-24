@props(['href' => null, 'color' => 'violet'])

@php
    $colors = [
        'violet' => 'bg-linear-to-r from-violet-600 to-indigo-600 hover:from-violet-500 hover:to-indigo-500 shadow-violet-500/25',
        'amber' => 'bg-linear-to-r from-amber-600 to-orange-600 hover:from-amber-500 hover:to-orange-500 shadow-amber-500/25',
        'emerald' => 'bg-linear-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 shadow-emerald-500/25',
    ];
    $accentRgbMap = [
        'violet' => '139, 92, 246',
        'amber' => '217, 119, 6',
        'emerald' => '16, 185, 129',
    ];
    $colorClasses = $colors[$color] ?? $colors['violet'];
    $accentRgb = $accentRgbMap[$color] ?? $accentRgbMap['violet'];
    $baseClasses = "btn-glow inline-flex items-center justify-center py-3 px-6 {$colorClasses} text-white font-medium rounded-xl shadow-lg transition-all cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed";
@endphp

@if($href)
    <a href="{{ $href }}" style="--accent-rgb: {{ $accentRgb }}" {{ $attributes->merge(['class' => $baseClasses]) }}>
        {{ $slot }}
    </a>
@else
    <button style="--accent-rgb: {{ $accentRgb }}" {{ $attributes->merge(['class' => $baseClasses]) }}>
        {{ $slot }}
    </button>
@endif
