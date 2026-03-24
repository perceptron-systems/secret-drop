@props(['title'])

<div class="flex items-center gap-4 mb-8">
    <a href="{{ route('home') }}" class="p-2 -ms-2 text-gray-500 hover:text-gray-700 dark:text-slate-400 dark:hover:text-slate-300 transition-colors" aria-label="{{ __('messages.a11y_back') }}">
        <svg class="w-5 h-5 rtl:rotate-180" aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
        </svg>
    </a>
    <h1 class="text-2xl font-bold text-gray-900 dark:text-white tracking-tight transition-colors">
        {{ $title }}
    </h1>
</div>
