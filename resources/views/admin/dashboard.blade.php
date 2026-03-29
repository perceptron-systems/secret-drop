@extends('layouts.app')

@section('noindex', true)
@section('title', __('messages.admin_dashboard_title'))

@section('content')
<div class="flex-1 py-8 px-4 transition-colors">
    <div class="max-w-4xl mx-auto">
        {{-- Header --}}
        <div class="flex items-center justify-between mb-8">
            <div class="flex items-center gap-4">
                <div class="logo-icon flex items-center justify-center w-14 h-14 rounded-2xl bg-linear-to-br from-violet-500/0 to-indigo-600 shrink-0">
                    <x-icon.settings class="w-7 h-7 text-white" />
                </div>
                <div>
                    <h1 class="text-xl font-bold text-gray-900 dark:text-white inline-flex items-center gap-2">
                        {{ __('messages.admin_dashboard_title') }}
                        @if(!$secrets->isEmpty())
                            <x-poll-ring />
                        @endif
                    </h1>
                    <p class="text-sm text-gray-500 dark:text-slate-400">{{ trans_choice('messages.admin_secrets_count', count($secrets), ['count' => count($secrets)]) }}</p>
                </div>
            </div>
            <form action="{{ route('admin.logout') }}" method="POST">
                @csrf
                <button type="submit" class="text-sm text-gray-500 dark:text-slate-400 hover:text-red-600 dark:hover:text-red-400 focus:outline-none focus:ring-2 focus:ring-violet-500/50 focus:rounded-xl transition">
                    {{ __('messages.admin_logout') }}
                </button>
            </form>
        </div>

        {{-- Empty state --}}
        @if($secrets->isEmpty())
            <div class="card-accent bg-white/80 dark:bg-slate-800/50 backdrop-blur-xl border border-gray-200 dark:border-slate-700/50 rounded-2xl shadow-xl p-12 text-center">
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-gray-100 dark:bg-slate-700 mb-4">
                    <x-icon.inbox class="w-8 h-8 text-gray-400 dark:text-slate-500" />
                </div>
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">{{ __('messages.admin_no_secrets') }}</h2>
                <p class="text-gray-500 dark:text-slate-400 mb-6">{{ __('messages.admin_no_secrets_description') }}</p>
                <x-btn-primary :href="route('home')">
                    {{ __('messages.btn_create_new') }}
                </x-btn-primary>
            </div>
        @else
            {{-- Secrets list --}}
            <div
                class="space-y-4"
                x-data="adminSecrets"
                data-extend-url="{{ route('admin.extend', ['id' => '__ID__']) }}"
                data-revoke-url="{{ route('admin.revoke', ['id' => '__ID__']) }}"
                data-poll-url="{{ route('admin.poll') }}"
                data-current-page="{{ $secrets->currentPage() }}"
                data-total="{{ $secrets->total() }}"
                data-label-active="{{ __('messages.admin_status_active') }}"
                data-label-expired="{{ __('messages.admin_status_expired') }}"
                data-label-revoked="{{ __('messages.admin_status_revoked') }}"
                data-label-consumed="{{ __('messages.admin_status_consumed') }}"
            >
                {{-- Error banner --}}
                <div
                    x-show="errorMessage"
                    x-cloak
                    x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="opacity-0 -translate-y-2"
                    x-transition:enter-end="opacity-100 translate-y-0"
                    x-transition:leave="transition ease-in duration-150"
                    x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0"
                    role="alert"
                    aria-live="assertive"
                    class="p-4 bg-red-50 dark:bg-red-500/10 border border-red-200 dark:border-red-500/20 rounded-xl text-sm text-red-700 dark:text-red-300"
                    x-text="errorMessage"
                ></div>

                <x-revoke-modal />

                @foreach($secrets as $secret)
                    @include('admin.secret-card', ['secret' => $secret])
                @endforeach
            </div>

            {{-- Pagination --}}
            @if($secrets->hasPages())
                <div class="mt-6">
                    {{ $secrets->links() }}
                </div>
            @endif
        @endif

        {{-- Create new link --}}
        <div class="mt-8 text-center">
            <a href="{{ route('home') }}" class="text-sm text-gray-500 dark:text-slate-400 hover:text-violet-600 dark:hover:text-violet-400 transition">
                {{ __('messages.btn_create_new') }}
            </a>
        </div>
    </div>
</div>
@endsection
