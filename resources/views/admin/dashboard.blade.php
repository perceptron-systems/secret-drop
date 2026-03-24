@extends('layouts.app')

@section('noindex', true)
@section('title', __('messages.admin_dashboard_title'))

@section('content')
<div class="flex-1 py-8 px-4 transition-colors">
    <div class="max-w-4xl mx-auto">
        {{-- Header --}}
        <div class="flex items-center justify-between mb-8">
            <div class="flex items-center gap-4">
                <div class="flex items-center justify-center w-12 h-12 rounded-xl bg-linear-to-br from-violet-500 to-indigo-600 shadow-lg shadow-violet-500/25 shrink-0">
                    <svg class="w-6 h-6 text-white" aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                </div>
                <div>
                    <h1 class="text-xl font-bold text-gray-900 dark:text-white">{{ __('messages.admin_dashboard_title') }}</h1>
                    <p class="text-sm text-gray-500 dark:text-slate-400">{{ trans_choice('messages.admin_secrets_count', count($secrets), ['count' => count($secrets)]) }}</p>
                </div>
            </div>
            <form action="{{ route('admin.logout') }}" method="POST">
                @csrf
                <button type="submit" class="text-sm text-gray-500 dark:text-slate-400 hover:text-red-600 dark:hover:text-red-400 focus:outline-none focus:ring-2 focus:ring-violet-500/50 focus:rounded-lg transition">
                    {{ __('messages.admin_logout') }}
                </button>
            </form>
        </div>

        {{-- Empty state --}}
        @if($secrets->isEmpty())
            <div class="card-accent bg-white/80 dark:bg-slate-800/50 backdrop-blur-xl border border-gray-200 dark:border-slate-700/50 rounded-2xl shadow-xl p-12 text-center">
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-gray-100 dark:bg-slate-700 mb-4">
                    <svg class="w-8 h-8 text-gray-400 dark:text-slate-500" aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                    </svg>
                </div>
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">{{ __('messages.admin_no_secrets') }}</h2>
                <p class="text-gray-500 dark:text-slate-400 mb-6">{{ __('messages.admin_no_secrets_description') }}</p>
                <x-btn-primary :href="route('home')">
                    {{ __('messages.btn_create_new') }}
                </x-btn-primary>
            </div>
        @else
            {{-- Secrets list --}}
            <div class="space-y-4" x-data="adminSecrets" data-extend-url="{{ route('admin.extend', ['id' => '__ID__']) }}" data-revoke-url="{{ route('admin.revoke', ['id' => '__ID__']) }}">
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

                {{-- Revoke confirmation modal --}}
                <div
                    x-show="showRevokeModal"
                    x-cloak
                    x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="opacity-0"
                    x-transition:enter-end="opacity-100"
                    x-transition:leave="transition ease-in duration-150"
                    x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0"
                    class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm"
                    @click.self="closeRevokeModal()"
                    @keydown.escape.window="closeRevokeModal()"
                    role="dialog"
                    aria-modal="true"
                    aria-labelledby="revoke-modal-title"
                    aria-describedby="revoke-modal-description"
                >
                    <div
                        x-show="showRevokeModal"
                        x-transition:enter="transition ease-out duration-200"
                        x-transition:enter-start="opacity-0 scale-95"
                        x-transition:enter-end="opacity-100 scale-100"
                        x-transition:leave="transition ease-in duration-150"
                        x-transition:leave-start="opacity-100 scale-100"
                        x-transition:leave-end="opacity-0 scale-95"
                        x-trap.noscroll="showRevokeModal"
                        class="bg-white dark:bg-slate-800 rounded-2xl shadow-2xl max-w-md w-full p-6 border border-gray-200 dark:border-slate-700/50"
                    >
                        <div class="flex items-center gap-4 mb-4">
                            <div class="flex items-center justify-center w-12 h-12 rounded-full bg-red-100 dark:bg-red-500/10 shrink-0">
                                <x-icon.warning class="w-6 h-6 text-red-600 dark:text-red-300" />
                            </div>
                            <div>
                                <h3 id="revoke-modal-title" class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('messages.admin_revoke') }}</h3>
                                <p id="revoke-modal-description" class="text-sm text-gray-500 dark:text-slate-400">{{ __('messages.admin_revoke_confirm') }}</p>
                            </div>
                        </div>
                        <div class="flex justify-end gap-3 mt-6">
                            <button
                                @click="closeRevokeModal()"
                                type="button"
                                class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-slate-300 bg-gray-100 dark:bg-slate-700 hover:bg-gray-200 dark:hover:bg-slate-600 rounded-lg transition cursor-pointer"
                            >
                                {{ __('messages.btn_cancel') }}
                            </button>
                            <button
                                @click="confirmRevoke()"
                                x-ref="confirmRevokeBtn"
                                type="button"
                                class="px-4 py-2 text-sm font-medium text-white bg-red-600 hover:bg-red-500 rounded-lg transition focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 dark:focus:ring-offset-slate-800 cursor-pointer"
                            >
                                {{ __('messages.admin_revoke') }}
                            </button>
                        </div>
                    </div>
                </div>

                @foreach($secrets as $secret)
                    <div
                        x-data="{ expanded: false, extending: false, revoking: false }"
                        class="card-accent bg-white/80 dark:bg-slate-800/50 backdrop-blur-xl border border-gray-200 dark:border-slate-700/50 rounded-2xl shadow-xl overflow-hidden transition-colors"
                    >
                        {{-- Secret header --}}
                        <button type="button" class="w-full p-5 flex items-center justify-between cursor-pointer text-left" @click="expanded = !expanded" :aria-expanded="expanded" aria-label="{{ __('messages.a11y_expand_secret') }}">
                            <div class="flex items-center gap-4">
                                {{-- Type icon --}}
                                <div class="flex items-center justify-center w-10 h-10 rounded-lg shrink-0 {{ $secret->type === 'text' ? 'bg-blue-100 dark:bg-blue-500/10' : 'bg-purple-100 dark:bg-purple-500/10' }}">
                                    @if($secret->type === 'text')
                                        <x-icon.document class="w-5 h-5 text-blue-600 dark:text-blue-300" />
                                    @else
                                        <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                        </svg>
                                    @endif
                                </div>

                                <div>
                                    <div class="flex items-center gap-2">
                                        <span class="font-medium text-gray-900 dark:text-white">
                                            {{ $secret->type === 'text' ? __('messages.type_text') : $secret->filename }}
                                        </span>
                                        {{-- Status badge --}}
                                        @if($secret->isRevoked())
                                            <span class="px-2 py-0.5 text-xs font-medium rounded-full bg-red-100 text-red-700 dark:bg-red-500/10 dark:text-red-300">
                                                {{ __('messages.admin_status_revoked') }}
                                            </span>
                                        @elseif($secret->isExpired())
                                            <span class="px-2 py-0.5 text-xs font-medium rounded-full bg-amber-100 text-amber-700 dark:bg-amber-500/10 dark:text-amber-300">
                                                {{ __('messages.admin_status_expired') }}
                                            </span>
                                        @elseif($secret->hasReachedMaxViews())
                                            <span class="px-2 py-0.5 text-xs font-medium rounded-full bg-gray-100 text-gray-700 dark:bg-slate-700 dark:text-slate-300">
                                                {{ __('messages.admin_status_consumed') }}
                                            </span>
                                        @else
                                            <span class="px-2 py-0.5 text-xs font-medium rounded-full bg-emerald-100 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300">
                                                {{ __('messages.admin_status_active') }}
                                            </span>
                                        @endif
                                    </div>
                                    <p class="text-sm text-gray-500 dark:text-slate-400">
                                        {{ __('messages.admin_created') }}: {{ $secret->created_at->translatedFormat('d M Y H:i') }}
                                    </p>
                                </div>
                            </div>

                            <svg class="w-5 h-5 text-gray-400 transition-transform" :class="{ 'rotate-180': expanded }" aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>

                        {{-- Expanded content --}}
                        <div x-show="expanded" x-collapse class="border-t border-gray-200 dark:border-slate-700/50">
                            <div class="p-5 space-y-4">
                                {{-- Info grid --}}
                                <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                                    <div class="p-3 bg-gray-50 dark:bg-slate-900/50 rounded-lg">
                                        <p class="text-xs text-gray-500 dark:text-slate-400">{{ __('messages.admin_expires') }}</p>
                                        <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $secret->expire_at->translatedFormat('d M Y H:i') }}</p>
                                    </div>
                                    <div class="p-3 bg-gray-50 dark:bg-slate-900/50 rounded-lg">
                                        <p class="text-xs text-gray-500 dark:text-slate-400">{{ __('messages.admin_read_count') }}</p>
                                        <p class="text-sm font-medium text-gray-900 dark:text-white">
                                            {{ $secret->read_count }}
                                            @if($secret->max_views)
                                                <span class="text-gray-400 dark:text-slate-500">/ {{ $secret->max_views }}</span>
                                            @endif
                                        </p>
                                    </div>
                                    @if($secret->first_read_at)
                                        <div class="p-3 bg-gray-50 dark:bg-slate-900/50 rounded-lg">
                                            <p class="text-xs text-gray-500 dark:text-slate-400">{{ __('messages.admin_first_read') }}</p>
                                            <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $secret->first_read_at->translatedFormat('d M Y H:i') }}</p>
                                        </div>
                                    @endif
                                    <div class="p-3 bg-gray-50 dark:bg-slate-900/50 rounded-lg">
                                        <p class="text-xs text-gray-500 dark:text-slate-400">{{ __('messages.admin_mode') }}</p>
                                        <p class="text-sm font-medium text-gray-900 dark:text-white">
                                            {{ $secret->max_views ? trans_choice('messages.admin_limited_views', $secret->max_views, ['count' => $secret->max_views]) : __('messages.admin_unlimited') }}
                                        </p>
                                    </div>
                                </div>

                                {{-- Actions --}}
                                @if(!$secret->isRevoked())
                                    <div class="flex flex-col sm:flex-row gap-3 pt-2">
                                        {{-- Extend --}}
                                        <div class="flex items-center gap-2">
                                            <select
                                                x-model="extendDays"
                                                aria-label="{{ __('messages.a11y_extend_days') }}"
                                                class="px-3 py-2 bg-gray-50 dark:bg-slate-900/50 border border-gray-300 dark:border-slate-600/50 rounded-lg text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-violet-500/50"
                                            >
                                                <option value="1">+1 {{ __('messages.admin_day') }}</option>
                                                <option value="7">+7 {{ __('messages.admin_days') }}</option>
                                                <option value="30">+30 {{ __('messages.admin_days') }}</option>
                                            </select>
                                            <button
                                                data-secret-id="{{ $secret->id }}"
                                                @click="extend($el)"
                                                :disabled="extending"
                                                class="px-4 py-2 bg-emerald-600 hover:bg-emerald-500 text-white text-sm font-medium rounded-lg disabled:opacity-50 transition cursor-pointer"
                                            >
                                                <span x-show="!extending">{{ __('messages.admin_extend') }}</span>
                                                <span x-show="extending" class="flex items-center gap-1">
                                                    <x-spinner />
                                                </span>
                                            </button>
                                        </div>

                                        {{-- Revoke --}}
                                        @if($secret->isAccessible())
                                            <button
                                                data-secret-id="{{ $secret->id }}"
                                                @click="openRevokeModal($el)"
                                                :disabled="revoking"
                                                class="px-4 py-2 text-red-600 dark:text-red-300 border border-red-300 dark:border-red-500/30 hover:bg-red-50 dark:hover:bg-red-500/10 text-sm font-medium rounded-lg disabled:opacity-50 transition cursor-pointer"
                                            >
                                                <span x-show="!revoking">{{ __('messages.admin_revoke') }}</span>
                                                <span x-show="revoking" class="flex items-center gap-1">
                                                    <x-spinner />
                                                </span>
                                            </button>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
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
