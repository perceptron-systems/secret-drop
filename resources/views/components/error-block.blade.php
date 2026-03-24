@props(['color' => 'red', 'title', 'icon'])

<div {{ $attributes->merge(['class' => 'text-center', 'role' => 'alert']) }}>
    <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-{{ $color }}-100 dark:bg-{{ $color }}-500/10 mb-6 transition-colors" aria-hidden="true">
        <svg class="w-7 h-7 text-{{ $color }}-500 dark:text-{{ $color }}-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $icon }}" />
        </svg>
    </div>
    <h1 class="text-xl font-bold text-gray-900 dark:text-white mb-2 transition-colors">
        {{ $title }}
    </h1>
    {{ $slot }}
</div>
