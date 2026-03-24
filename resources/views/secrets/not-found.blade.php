@extends('layouts.app')

@section('noindex', true)

@section('content')
<div class="flex-1 flex items-center justify-center p-4 transition-colors">
    <div class="w-full max-w-md">
        <x-card class="p-8 text-center">
            <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-red-100 dark:bg-red-500/10 mb-6 transition-colors" aria-hidden="true">
                <x-icon.warning class="w-7 h-7 text-red-500 dark:text-red-300" />
            </div>

            <h1 class="text-xl font-bold text-gray-900 dark:text-white mb-2 transition-colors">
                {{ __('messages.error_not_found') }}
            </h1>
            <p class="text-gray-600 dark:text-slate-400 mb-6 transition-colors">
                {{ __('messages.secret_not_exist') }}
            </p>

            <x-btn-primary :href="route('home')">
                {{ __('messages.btn_create_new') }}
            </x-btn-primary>
        </x-card>
    </div>
</div>
@endsection
