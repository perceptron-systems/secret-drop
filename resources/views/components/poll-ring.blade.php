@props(['color' => 'text-violet-500'])

<svg id="pollRing" class="shrink-0 -rotate-90" width="24" height="24" viewBox="0 0 28 28" aria-hidden="true">
    <circle cx="14" cy="14" r="12" fill="none" stroke="currentColor" class="text-gray-200 dark:text-slate-700" stroke-width="2.5" />
    <circle id="pollRingProgress" cx="14" cy="14" r="12" fill="none" stroke="currentColor" class="{{ $color }}" stroke-width="2.5" stroke-linecap="round" stroke-dasharray="75.4" stroke-dashoffset="75.4" />
</svg>
