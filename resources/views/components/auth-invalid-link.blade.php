@props(['retryRoute', 'retryLabel', 'color' => 'violet', 'iconColor' => 'amber', 'icon' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z'])

<div class="flex-1 flex items-center justify-center p-4 transition-colors">
    <div class="w-full max-w-md">
        <x-card class="p-8 text-center">
            <div class="inline-flex items-center justify-center w-14 h-14 rounded-full bg-{{ $iconColor }}-100 dark:bg-{{ $iconColor }}-500/10 mb-4" aria-hidden="true">
                <svg class="w-7 h-7 text-{{ $iconColor }}-600 dark:text-{{ $iconColor }}-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $icon }}" />
                </svg>
            </div>

            <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">{{ __('messages.admin_invalid_link_title') }}</h1>
            <p class="text-gray-600 dark:text-slate-400 mb-6">{{ __('messages.admin_invalid_link_description') }}</p>

            <x-btn-primary :href="$retryRoute" :color="$color" class="w-full">
                {{ $retryLabel }}
            </x-btn-primary>

            <div class="mt-6">
                <a href="{{ route('home') }}" class="text-sm text-gray-500 dark:text-slate-400 hover:text-{{ $color }}-600 dark:hover:text-{{ $color }}-400 transition">
                    {{ __('messages.admin_back_home') }}
                </a>
            </div>
        </x-card>
    </div>
</div>
