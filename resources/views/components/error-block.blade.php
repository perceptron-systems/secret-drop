@props(['color' => 'red', 'title', 'iconName' => 'icon.exclamation-circle'])

@php
    $iconGradient = match($color) {
        'red' => 'from-red-500/0 to-rose-600',
        'amber' => 'from-amber-500/0 to-orange-600',
        'violet' => 'from-violet-500/0 to-indigo-600',
        default => 'from-slate-500/0 to-slate-600',
    };

    $accentRgb = match($color) {
        'red' => '239, 68, 68',
        'amber' => '245, 158, 11',
        'violet' => '139, 92, 246',
        default => '100, 116, 139',
    };
@endphp

<div {{ $attributes->merge(['class' => 'text-center', 'role' => 'alert']) }}>
    <div class="logo-icon inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-linear-to-br {{ $iconGradient }} mb-6 transition-colors" style="--accent-rgb: {{ $accentRgb }}" aria-hidden="true">
        <x-dynamic-component :component="$iconName" class="w-7 h-7 text-white" />
    </div>
    <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-2 transition-colors">
        {{ $title }}
    </h1>
    {{ $slot }}
</div>
