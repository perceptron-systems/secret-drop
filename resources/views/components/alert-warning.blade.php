@props(['label'])

<div {{ $attributes->merge(['class' => 'p-3 bg-amber-50 dark:bg-amber-500/10 border border-amber-200 dark:border-amber-500/20 rounded-xl transition-colors']) }}>
    <p class="text-xs text-amber-700 dark:text-amber-300">
        <strong>{{ $label }}</strong> {{ $slot }}
    </p>
</div>
