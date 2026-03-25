@props(['title'])

<div class="p-4 bg-gray-50 dark:bg-slate-700/30 border border-gray-200 dark:border-slate-600/30 rounded-xl">
    <div class="flex items-center gap-3 mb-2">
        <x-icon.check class="w-5 h-5 text-emerald-600 dark:text-emerald-300 shrink-0" />
        <h3 class="font-medium text-gray-900 dark:text-white">{{ $title }}</h3>
    </div>
    <p class="text-sm text-gray-600 dark:text-slate-400">{{ $slot }}</p>
</div>
