@props(['id', 'text', 'position' => 'start', 'direction' => 'above'])

<div class="relative" x-data="{ showHint: false }">
    <button
        type="button"
        @mouseenter="showHint = true"
        @mouseleave="showHint = false"
        @focus="showHint = true"
        @blur="showHint = false"
        aria-describedby="{{ $id }}"
        class="text-gray-500 dark:text-slate-400 hover:text-violet-500 dark:hover:text-violet-400 transition cursor-pointer"
    >
        <x-icon.info />
        <span class="sr-only">{{ $text }}</span>
    </button>
    <div
        x-show="showHint"
        x-cloak
        x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="opacity-0 {{ $direction === 'below' ? '-translate-y-1' : 'translate-y-1' }}"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-100"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 {{ $direction === 'below' ? '-translate-y-1' : 'translate-y-1' }}"
        id="{{ $id }}"
        role="tooltip"
        class="absolute z-50 {{ $direction === 'below' ? 'top-full mt-2' : 'bottom-full mb-2' }} {{ $position === 'start' ? 'start-0' : 'end-0' }} w-72 px-3 py-2 text-xs text-white bg-gray-900 dark:bg-slate-700 rounded-xl shadow-lg"
    >
        {{ $text }}
        @if($direction === 'below')
            <div class="absolute bottom-full {{ $position === 'start' ? 'start-3' : 'end-3' }} border-4 border-transparent border-b-gray-900 dark:border-b-slate-700"></div>
        @else
            <div class="absolute top-full {{ $position === 'start' ? 'start-3' : 'end-3' }} border-4 border-transparent border-t-gray-900 dark:border-t-slate-700"></div>
        @endif
    </div>
</div>
