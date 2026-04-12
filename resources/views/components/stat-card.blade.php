@props(['value' => null, 'label', 'kpi' => null, 'hint' => null, 'hintId' => null, 'hintPosition' => 'start'])

<x-card class="p-6">
    @if($slot->isNotEmpty())
        {{ $slot }}
    @else
        <div class="text-3xl font-bold text-gray-900 dark:text-white" @if($kpi) data-kpi="{{ $kpi }}" @endif>{{ $value }}</div>
    @endif
    @if($hint)
        <div class="flex items-center gap-1 mt-1">
            <span class="text-sm text-gray-600 dark:text-slate-400">{{ $label }}</span>
            <x-hint-tooltip :id="$hintId ?? 'hint_' . $kpi" :text="$hint" direction="below" :position="$hintPosition" />
        </div>
    @else
        <div class="text-sm text-gray-600 dark:text-slate-400 mt-1">{{ $label }}</div>
    @endif
</x-card>
