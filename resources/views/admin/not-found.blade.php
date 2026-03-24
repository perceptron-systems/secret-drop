@extends('layouts.app')

@section('noindex', true)
@section('title', __('messages.admin_not_found_title'))

@section('content')
<div class="flex-1 flex items-center justify-center p-4 transition-colors">
    <div class="w-full max-w-md">
        <x-card class="p-8 text-center">
            <div class="inline-flex items-center justify-center w-14 h-14 rounded-full bg-red-100 dark:bg-red-500/10 mb-4" aria-hidden="true">
                <x-icon.warning class="w-7 h-7 text-red-600 dark:text-red-300" />
            </div>

            <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">{{ __('messages.admin_not_found_title') }}</h1>
            <p class="text-gray-600 dark:text-slate-400 mb-6">{{ __('messages.admin_not_found_description') }}</p>

            <x-btn-primary :href="route('home')" class="w-full">
                {{ __('messages.btn_create_new') }}
            </x-btn-primary>
        </x-card>
    </div>
</div>
@endsection
