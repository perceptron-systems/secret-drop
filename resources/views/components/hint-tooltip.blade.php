@props(['id', 'text', 'position' => 'start', 'direction' => 'above'])

<div class="shrink-0" x-data="hintTooltip">
    <button
        type="button"
        x-ref="btn"
        data-direction="{{ $direction }}"
        data-position="{{ $position }}"
        @mouseenter="open"
        @mouseleave="close"
        @focus="open"
        @blur="close"
        aria-describedby="{{ $id }}"
        class="text-gray-500 dark:text-slate-400 hover:text-violet-500 dark:hover:text-violet-400 transition cursor-pointer"
    >
        <x-icon.info />
        <span class="sr-only">{{ $text }}</span>
    </button>
    <template x-teleport="body">
        <div
            x-show="show"
            x-cloak
            x-transition:enter="transition ease-out duration-150"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-100"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            :style="tooltipStyle()"
            id="{{ $id }}"
            role="tooltip"
            class="w-72 max-w-[calc(100vw-2rem)] p-3.5 text-xs text-white bg-gray-900 dark:bg-slate-700 rounded-xl shadow-lg whitespace-pre-line"
        >{{ $text }}</div>
    </template>
</div>
