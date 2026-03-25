@props(['retryRoute', 'retryLabel', 'color' => 'violet', 'iconName' => 'icon.clock'])

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
                <x-dynamic-component :component="$iconName" class="w-7 h-7 text-white" />
            </div>

            <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">{{ __('messages.admin_invalid_link_title') }}</h1>
            <p class="text-gray-600 dark:text-slate-400 mb-6">{{ __('messages.admin_invalid_link_description') }}</p>

            <x-btn-primary :href="$retryRoute" :color="$color" class="w-full">
                {{ $retryLabel }}
            </x-btn-primary>

            <div class="mt-6">
                <a href="{{ route('home') }}" class="text-sm text-gray-500 dark:text-slate-400 hover:text-gray-900 dark:hover:text-white transition">
                    {{ __('messages.admin_back_home') }}
                </a>
            </div>
        </x-card>
    </div>
</div>
