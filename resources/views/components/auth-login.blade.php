@props(['title', 'description' => null, 'formAction', 'icon', 'color' => 'violet'])

@php
    $ringColor = "focus:ring-{$color}-500/50 focus:border-{$color}-500/50";
    $accentRgb = match($color) {
        'amber' => '217, 119, 6',
        'emerald' => '16, 185, 129',
        default => '139, 92, 246',
    };
@endphp

<div class="flex-1 flex items-center justify-center p-4 transition-colors" style="--accent-rgb: {{ $accentRgb }}">
    <div class="w-full max-w-md">
        <x-card class="p-8">
            <div class="text-center mb-8">
                <div class="logo-icon inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-linear-to-br from-{{ $color }}-500 to-{{ $color === 'violet' ? 'indigo' : 'orange' }}-600 shadow-lg shadow-{{ $color }}-500/25 mb-4" aria-hidden="true">
                    <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        {!! $icon !!}
                    </svg>
                </div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $title }}</h1>
                @if($description)
                    <p class="mt-2 text-gray-600 dark:text-slate-400">{{ $description }}</p>
                @endif
            </div>

            <form action="{{ $formAction }}" method="POST" class="space-y-6" autocomplete="off">
                @csrf

                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-2">
                        {{ __('messages.your_email') }}
                    </label>
                    <input
                        id="email"
                        name="email"
                        type="email"
                        required
                        aria-required="true"
                        @if(!session('captcha_required')) autofocus @endif
                        autocomplete="off"
                        value="{{ old('email') }}"
                        placeholder="{{ __('messages.admin_email_placeholder') }}"
                        @error('email') aria-describedby="email-error" aria-invalid="true" @enderror
                        class="w-full px-4 py-3 bg-gray-50 dark:bg-slate-900/50 border border-gray-300 dark:border-slate-600/50 rounded-xl text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-slate-400 focus:outline-none focus:ring-2 {{ $ringColor }} transition"
                    >
                    @error('email')
                        <p id="email-error" class="mt-2 text-sm text-red-600 dark:text-red-300" role="alert">{{ $message }}</p>
                    @enderror
                </div>

                @if(session('captcha_required'))
                    <div class="p-4 bg-amber-50 dark:bg-amber-500/10 border border-amber-200 dark:border-amber-500/20 rounded-xl">
                        <p class="text-sm text-amber-700 dark:text-amber-300 mb-3">
                            {{ __('messages.rate_limit_exceeded') }}
                        </p>
                        <label for="captcha_answer" class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-2">
                            {{ __('messages.captcha_label') }}
                        </label>
                        <div class="flex items-center gap-3">
                            <div class="px-4 py-2 bg-white dark:bg-slate-800 border border-gray-300 dark:border-slate-600 rounded-lg font-mono text-lg text-gray-900 dark:text-white">
                                {{ session('captcha_challenge') }} = ?
                            </div>
                            <input
                                id="captcha_answer"
                                name="captcha_answer"
                                type="number"
                                required
                                aria-required="true"
                                autofocus
                                placeholder="{{ __('messages.captcha_placeholder') }}"
                                @error('captcha') aria-describedby="captcha-error" aria-invalid="true" @enderror
                                class="flex-1 px-4 py-2 bg-gray-50 dark:bg-slate-900/50 border border-gray-300 dark:border-slate-600/50 rounded-lg text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-slate-400 focus:outline-none focus:ring-2 {{ $ringColor }} transition"
                            >
                        </div>
                        <input type="hidden" name="captcha_token" value="{{ session('captcha_token') }}">
                        @error('captcha')
                            <p id="captcha-error" class="mt-2 text-sm text-red-600 dark:text-red-300" role="alert">{{ $message }}</p>
                        @enderror
                    </div>
                @endif

                <x-btn-primary type="submit" :color="$color" class="w-full">
                    {{ __('messages.admin_send_link') }}
                </x-btn-primary>
            </form>

            <div class="mt-6 text-center">
                <a href="{{ route('home') }}" class="text-sm text-gray-500 dark:text-slate-400 hover:text-{{ $color }}-600 dark:hover:text-{{ $color }}-400 transition">
                    {{ __('messages.admin_back_home') }}
                </a>
            </div>
        </x-card>
    </div>
</div>
