@extends('layouts.app')

@section('noindex', true)

@section('content')
<div class="flex-1 flex items-center justify-center p-4 transition-colors">
    <div class="w-full max-w-md">
        <x-card class="p-8 text-center">
            <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-amber-100 dark:bg-amber-500/10 mb-6 transition-colors" aria-hidden="true">
                <svg class="w-7 h-7 text-amber-500 dark:text-amber-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>

            <h1 class="text-xl font-bold text-gray-900 dark:text-white mb-2 transition-colors">
                {{ __('messages.error_unavailable') }}
            </h1>

            @switch($reason)
                @case('expired')
                    <p class="text-gray-600 dark:text-slate-400 mb-6 transition-colors">
                        {{ __('messages.secret_expired') }}
                    </p>
                    @break

                @case('revoked')
                    <p class="text-gray-600 dark:text-slate-400 mb-6 transition-colors">
                        {{ __('messages.secret_revoked') }}
                    </p>
                    @break

                @case('max_views')
                    <p class="text-gray-600 dark:text-slate-400 mb-6 transition-colors">
                        {{ __('messages.secret_max_views') }}
                    </p>
                    @break

                @default
                    <p class="text-gray-600 dark:text-slate-400 mb-6 transition-colors">
                        {{ __('messages.secret_unavailable_generic') }}
                    </p>
            @endswitch

            <x-btn-primary :href="route('home')">
                {{ __('messages.btn_create_new') }}
            </x-btn-primary>
        </x-card>
    </div>
</div>
@endsection
