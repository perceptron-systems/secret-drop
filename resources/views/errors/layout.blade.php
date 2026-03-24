@extends('layouts.app')

@section('content')
<div class="flex-1 flex items-center justify-center p-4 transition-colors">
    <div class="w-full max-w-md">
        <div class="card-accent bg-white/80 dark:bg-slate-800/50 backdrop-blur-xl border border-gray-200 dark:border-slate-700/50 rounded-2xl shadow-2xl overflow-hidden transition-colors">
            <div class="p-8 lg:p-12 text-center">
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-{{ $color ?? 'gray' }}-100 dark:bg-{{ $color ?? 'gray' }}-500/10 mb-6 transition-colors" aria-hidden="true">
                    @yield('icon')
                </div>

                <p class="text-7xl font-bold text-gray-200 dark:text-slate-700 mb-4 transition-colors" aria-hidden="true">
                    @yield('code')
                </p>

                <h1 class="text-xl font-bold text-gray-900 dark:text-white mb-3 transition-colors">
                    @yield('title')
                </h1>

                <p class="text-gray-600 dark:text-slate-400 mb-8 transition-colors">
                    @yield('message')
                </p>

                <x-btn-primary :href="route('home')" class="gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                    </svg>
                    {{ __('messages.error_back_home') }}
                </x-btn-primary>
            </div>
        </div>
    </div>
</div>
@endsection
