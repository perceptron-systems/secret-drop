@extends('layouts.app')

@section('noindex', true)
@section('title', __('messages.view_secret_title'))

@section('content')
<div class="flex-1 flex items-center justify-center p-4 transition-colors">
    <div class="w-full max-w-2xl">
        <div
            x-data="secretViewer" data-token="{{ $token }}"
            class="card-accent bg-white/80 dark:bg-slate-800/50 backdrop-blur-xl border border-gray-200 dark:border-slate-700/50 rounded-2xl shadow-2xl overflow-hidden transition-colors"
        >
            <div class="p-5 sm:p-8 lg:p-12">
                {{-- Loading state --}}
                <div x-show="isLoading" class="text-center py-8" role="status">
                    <x-spinner class="h-10 w-10 mx-auto text-violet-500" />
                    <p class="mt-4 text-gray-600 dark:text-slate-400 transition-colors">{{ __('messages.loading_secret') }}</p>
                </div>

                {{-- Error announcer for screen readers --}}
                <div aria-live="assertive" aria-atomic="true" class="sr-only" x-text="errorMessage()"></div>

                {{-- Not found error --}}
                <x-error-block
                    x-show="isNotFound()" x-cloak
                    color="red"
                    :title="__('messages.error_not_found')"
                    iconName="icon.warning"
                >
                    <p class="text-gray-600 dark:text-slate-400 mb-6 transition-colors" x-text="loadErrorMessage()"></p>
                    <x-btn-primary :href="route('home')">
                        {{ __('messages.btn_create_new') }}
                    </x-btn-primary>
                </x-error-block>

                {{-- Unavailable error --}}
                <x-error-block
                    x-show="isUnavailable()" x-cloak
                    color="amber"
                    :title="__('messages.error_unavailable')"
                    iconName="icon.clock"
                >
                    <p class="text-gray-600 dark:text-slate-400 mb-6 transition-colors" x-text="loadErrorMessage()"></p>
                    <x-btn-primary :href="route('home')">
                        {{ __('messages.btn_create_new') }}
                    </x-btn-primary>
                </x-error-block>

                {{-- Generic error --}}
                <x-error-block
                    x-show="isGenericError()" x-cloak
                    color="red"
                    :title="__('messages.error_generic')"
                    iconName="icon.exclamation-circle"
                >
                    <p class="text-gray-600 dark:text-slate-400 mb-6 transition-colors" x-text="loadErrorMessage()"></p>
                    <x-btn-primary type="button" @click="reload()">
                        {{ __('messages.btn_retry') }}
                    </x-btn-primary>
                </x-error-block>

                {{-- Secret content --}}
                <div x-show="!isLoading && !loadError" x-cloak>
                    {{-- Header --}}
                    <div class="text-center mb-5 sm:mb-8">
                        <div class="logo-icon inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-linear-to-br from-violet-500/0 to-indigo-600 mb-4 sm:mb-6" aria-hidden="true">
                            <template x-if="type === 'text'">
                                <x-icon.eye class="w-7 h-7 text-white" />
                            </template>
                            <template x-if="type === 'file'">
                                <x-icon.file class="w-7 h-7 text-white" />
                            </template>
                        </div>
                        <h1 class="text-2xl font-bold text-gray-900 dark:text-white tracking-tight transition-colors">
                            <span x-text="secretTypeTitle()"></span>
                        </h1>
                        <p class="mt-2 text-gray-600 dark:text-slate-400 transition-colors" x-show="!decrypted && !error">
                            <span x-text="encryptedDescription()"></span>
                        </p>
                        <div x-show="type === 'file' && !decrypted && !error" class="mt-3 text-sm text-gray-600 dark:text-slate-400">
                            <span class="text-gray-600 dark:text-slate-400">{{ __('messages.file_encrypted_info') }}</span>
                        </div>
                    </div>

                    {{-- Confirmation step for last read --}}
                    <div x-show="awaitingConfirmation && !decrypted" x-cloak class="space-y-6">
                        <div class="p-4 bg-amber-50 dark:bg-amber-500/10 border border-amber-200 dark:border-amber-500/20 rounded-xl transition-colors">
                            <div class="flex gap-3">
                                <x-icon.warning class="w-5 h-5 text-amber-600 dark:text-amber-300 shrink-0 mt-0.5" />
                                <div>
                                    <p class="font-medium text-amber-800 dark:text-amber-300">
                                        {{ __('messages.last_read_warning_title') }}
                                    </p>
                                    <p class="mt-1 text-sm text-amber-700 dark:text-amber-300">
                                        {{ __('messages.last_read_warning_text') }}
                                    </p>
                                </div>
                            </div>
                        </div>
                        <x-btn-primary type="button" @click="confirmAndDecrypt()" class="w-full">
                            {{ __('messages.btn_reveal_secret') }}
                        </x-btn-primary>
                    </div>

                    {{-- Manual key input (split mode) --}}
                    <div x-show="needsManualKey && !decrypted && !error && !awaitingConfirmation" x-cloak class="space-y-4">
                        <p class="text-sm text-gray-700 dark:text-slate-300 text-center transition-colors">
                            {{ __('messages.enter_key_manually') }}
                        </p>
                        {{-- Last read warning in manual key form --}}
                        <x-alert-warning x-show="willBeDestroyed" :label="__('messages.label_important')">{{ __('messages.last_read_warning_short') }}</x-alert-warning>
                        <form @submit.prevent="submitManualKey()" class="space-y-4">
                            <div>
                                <label for="manualKey" class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-2 transition-colors">
                                    {{ __('messages.share_key_label') }}
                                </label>
                                <input
                                    id="manualKey"
                                    type="text"
                                    x-model="manualKey"
                                    required
                                    aria-required="true"
                                    autofocus
                                    autocomplete="off"
                                    placeholder="{{ __('messages.key_placeholder') }}"
                                    class="w-full px-4 py-3 bg-gray-50 dark:bg-slate-900/50 border border-gray-300 dark:border-slate-600/50 rounded-xl text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-slate-400 font-mono text-sm focus:outline-none focus:ring-2 focus:ring-violet-500/50 focus:border-violet-500/50 transition"
                                >
                            </div>
                            <x-btn-primary type="submit" x-bind:disabled="!manualKey.trim()" class="w-full">
                                {{ __('messages.btn_unlock') }}
                            </x-btn-primary>
                        </form>
                    </div>

                    {{-- Passphrase input --}}
                    <div x-show="needsPassphrase && !needsManualKey && !decrypted && !error && !awaitingConfirmation" x-cloak class="space-y-4">
                        <p class="text-sm text-gray-700 dark:text-slate-300 text-center transition-colors">
                            {{ __('messages.passphrase_protected') }}
                        </p>
                        {{-- Last read warning in passphrase form --}}
                        <x-alert-warning x-show="willBeDestroyed" :label="__('messages.label_important')">{{ __('messages.last_read_warning_short') }}</x-alert-warning>
                        <form @submit.prevent="decrypt()" class="space-y-4">
                            <div>
                                <label for="passphrase" class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-2 transition-colors">
                                    {{ __('messages.passphrase') }}
                                </label>
                                <input
                                    id="passphrase"
                                    type="password"
                                    x-model="passphrase"
                                    required
                                    aria-required="true"
                                    autofocus
                                    placeholder="{{ __('messages.passphrase_input_placeholder') }}"
                                    class="w-full px-4 py-3 bg-gray-50 dark:bg-slate-900/50 border border-gray-300 dark:border-slate-600/50 rounded-xl text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-violet-500/50 focus:border-violet-500/50 transition"
                                >
                            </div>
                            <x-btn-primary type="submit" x-bind:disabled="isDecrypting || !passphrase.trim()" class="w-full">
                                <span x-show="!isDecrypting">{{ __('messages.btn_decrypt') }}</span>
                                <span x-show="isDecrypting" role="status" class="inline-flex items-center justify-center gap-2">
                                    <x-spinner />
                                    {{ __('messages.btn_decrypting') }}
                                </span>
                            </x-btn-primary>
                        </form>
                    </div>

                    {{-- Loading state for decryption --}}
                    <div x-show="isDecrypting && !needsPassphrase" x-cloak class="text-center py-8" role="status">
                        <x-spinner class="h-8 w-8 mx-auto text-violet-500" />
                        <p class="mt-4 text-gray-600 dark:text-slate-400 transition-colors">
                            <span x-text="decryptingText()"></span>
                        </p>
                    </div>

                    {{-- Decrypted text content --}}
                    <div x-show="decrypted && type === 'text'" x-cloak class="space-y-6">
                        <div class="relative">
                            <pre
                                x-text="plaintext"
                                role="region"
                                aria-label="{{ __('messages.a11y_decrypted_content') }}"
                                class="w-full p-4 bg-gray-50 dark:bg-slate-900/50 border border-gray-300 dark:border-slate-600/50 rounded-xl text-gray-900 dark:text-white text-sm font-mono whitespace-pre-wrap break-words max-h-96 overflow-auto transition-colors"
                            ></pre>
                            <button
                                type="button"
                                @click="copyToClipboard()"
                                :aria-label="copied ? '{{ __('messages.btn_copied') }}' : '{{ __('messages.btn_copy') }}'"
                                class="absolute top-3 end-3 px-3 py-1.5 bg-gray-200 dark:bg-slate-700/50 hover:bg-gray-300 dark:hover:bg-slate-600/50 text-gray-700 dark:text-slate-300 hover:text-gray-900 dark:hover:text-white text-sm font-medium rounded-lg transition cursor-pointer"
                            >
                                <span x-show="!copied">{{ __('messages.btn_copy') }}</span>
                                <span x-show="copied">{{ __('messages.btn_copied') }}</span>
                            </button>
                        </div>

                        <x-alert-warning x-show="willBeDestroyed" :label="__('messages.label_note')">{{ __('messages.note_destroyed_text') }}</x-alert-warning>
                    </div>

                    {{-- Decrypted file content --}}
                    <div x-show="decrypted && type === 'file'" x-cloak class="space-y-6">
                        <div class="p-6 bg-gray-50 dark:bg-slate-900/50 border border-gray-300 dark:border-slate-600/50 rounded-xl text-center transition-colors">
                            <div class="logo-icon inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-linear-to-br from-emerald-500/0 to-teal-600 mb-4" aria-hidden="true">
                                <x-icon.check class="w-7 h-7 text-white" />
                            </div>
                            <p class="text-gray-900 dark:text-white font-medium mb-1">{{ __('messages.file_decrypted') }}</p>
                            <p class="text-sm text-gray-500 dark:text-slate-400" x-text="filename"></p>
                        </div>

                        <x-alert-warning x-show="willBeDestroyed" :label="__('messages.label_note')">{{ __('messages.note_destroyed_file') }}</x-alert-warning>
                    </div>

                    {{-- Decryption error --}}
                    <div x-show="error" x-cloak class="space-y-4">
                        <div role="alert" class="p-4 bg-red-50 dark:bg-red-500/10 border border-red-200 dark:border-red-500/20 rounded-xl transition-colors">
                            <p class="text-sm text-red-600 dark:text-red-300" x-text="error"></p>
                        </div>
                        <button
                            x-show="needsPassphrase || needsManualKey"
                            type="button"
                            @click="clearRetryError()"
                            class="w-full py-2.5 text-sm text-gray-500 dark:text-slate-400 hover:text-gray-900 dark:hover:text-white border border-gray-300 dark:border-slate-600/50 hover:border-gray-400 dark:hover:border-slate-500 rounded-xl transition cursor-pointer"
                        >
                            {{ __('messages.btn_retry') }}
                        </button>
                    </div>

                    {{-- Create new --}}
                    <div class="mt-5 pt-4 sm:mt-8 sm:pt-6 border-t border-gray-200 dark:border-slate-700/50 text-center transition-colors">
                        <a
                            href="{{ route('home') }}"
                            class="text-sm text-gray-500 dark:text-slate-400 hover:text-gray-900 dark:hover:text-white transition"
                        >
                            {{ __('messages.btn_create_new') }}
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
