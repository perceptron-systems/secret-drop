@props(['description', 'color' => 'violet'])

@php
    $accentRgb = match($color) {
        'amber' => '217, 119, 6',
        'emerald' => '16, 185, 129',
        default => '139, 92, 246',
    };
    $iconGradient = match($color) {
        'amber' => 'from-amber-500/0 to-orange-600',
        'emerald' => 'from-emerald-500/0 to-teal-600',
        default => 'from-violet-500/0 to-indigo-600',
    };
@endphp

<div class="flex-1 flex items-center justify-center p-4 transition-colors" style="--accent-rgb: {{ $accentRgb }}">
    <div class="w-full max-w-md">
        <x-card class="p-8 text-center">
            <div class="logo-icon inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-linear-to-br {{ $iconGradient }} mb-4" aria-hidden="true">
                <x-icon.mail class="w-7 h-7 text-white" />
            </div>

            <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">{{ __('messages.admin_link_sent_title') }}</h1>
            <p class="text-gray-600 dark:text-slate-400 mb-6">{{ $description }}</p>

            <div class="p-4 bg-amber-50 dark:bg-amber-500/10 border border-amber-200 dark:border-amber-500/20 rounded-xl" role="alert">
                <p class="text-sm text-amber-700 dark:text-amber-300">{{ __('messages.admin_link_sent_warning', ['minutes' => config('secrets.magic_link_ttl')]) }}</p>
            </div>

            <div class="mt-6">
                <a href="{{ route('home') }}" class="text-sm text-gray-500 dark:text-slate-400 hover:text-gray-900 dark:hover:text-white transition">
                    {{ __('messages.admin_back_home') }}
                </a>
            </div>
        </x-card>
    </div>
</div>
