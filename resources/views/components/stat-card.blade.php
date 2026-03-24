@props(['value', 'label', 'kpi' => null])

<x-card class="p-6">
    <div class="text-3xl font-bold text-gray-900 dark:text-white" @if($kpi) data-kpi="{{ $kpi }}" @endif>{{ $value }}</div>
    <div class="text-sm text-gray-600 dark:text-slate-400 mt-1">{{ $label }}</div>
</x-card>
