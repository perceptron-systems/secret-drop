@props(['title', 'description' => null, 'formAction', 'iconName' => 'icon.lock', 'color' => 'violet'])

@php
    $ringColor = "focus:ring-{$color}-500/50 focus:border-{$color}-500/50";
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
        <x-card class="p-8">
            <div class="text-center mb-8">
                <div class="logo-icon inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-linear-to-br {{ $iconGradient }} mb-4" aria-hidden="true">
                    <x-dynamic-component :component="$iconName" class="w-7 h-7 text-white" />
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
                        @if(!session('pow_required')) autofocus @endif
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

                @if(session('pow_required'))
                    <div id="pow-status" class="p-4 bg-amber-50 dark:bg-amber-500/10 border border-amber-200 dark:border-amber-500/20 rounded-xl">
                        <div class="flex items-center gap-3">
                            <x-spinner />
                            <p class="text-sm text-amber-700 dark:text-amber-300">
                                {{ __('messages.pow_computing') }}
                            </p>
                        </div>
                    </div>
                    <input type="hidden" name="pow_token" value="{{ session('pow_token') }}">
                    <input type="hidden" name="pow_nonce" id="pow-nonce" value="">
                    <script @nonce>
                        document.addEventListener('DOMContentLoaded', function() {
                            window.solvePow('{{ session('pow_challenge') }}', {{ session('pow_difficulty') }}).then(function(nonce) {
                                document.getElementById('pow-nonce').value = nonce;
                                document.getElementById('pow-nonce').closest('form').submit();
                            }).catch(function() {
                                document.getElementById('pow-status').innerHTML = '<p class="text-sm text-red-600 dark:text-red-300">{{ __("messages.pow_failed") }}</p>';
                            });
                        });
                    </script>
                @endif

                <x-btn-primary type="submit" :color="$color" class="w-full">
                    {{ __('messages.admin_send_link') }}
                </x-btn-primary>
            </form>

            <div class="mt-6 text-center">
                <a href="{{ route('home') }}" class="text-sm text-gray-500 dark:text-slate-400 hover:text-gray-900 dark:hover:text-white transition">
                    {{ __('messages.admin_back_home') }}
                </a>
            </div>
        </x-card>
    </div>
</div>
