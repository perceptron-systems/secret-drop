@extends('layouts.app')

@section('title', __('messages.home_title'))
@section('description', __('messages.home_meta_description'))

@section('content')
<div class="flex-1 flex items-center justify-center p-4 transition-colors">
    <div class="w-full max-w-5xl">
        <div
            x-data="secretForm"
            x-cloak
            class="card-accent relative z-20 md:bg-white/80 md:dark:bg-slate-800/50 md:backdrop-blur-xl md:border md:border-gray-200 md:dark:border-slate-700/50 md:rounded-2xl md:shadow-2xl transition-colors"
        >
            <div class="grid lg:grid-cols-2">
                {{-- Left: Branding & Value Proposition --}}
                <div class="p-6 md:p-8 lg:p-12 flex flex-col justify-center md:bg-linear-to-br md:from-violet-600/5 md:to-indigo-600/5 md:dark:from-violet-600/10 md:dark:to-indigo-600/10 md:border-b lg:border-b-0 lg:border-r md:border-gray-200 md:dark:border-slate-700/50 transition-colors">
                    <div class="flex items-center gap-4 mb-4">
                        <div class="logo-icon flex items-center justify-center w-14 h-14 rounded-2xl bg-linear-to-br from-violet-500/0 to-indigo-600 shrink-0" aria-hidden="true">
                            <x-icon.lock class="w-7 h-7 text-white" />
                        </div>
                        <h1 class="text-3xl font-bold text-gray-900 dark:text-white tracking-tight transition-colors">
                            Secret Drop
                        </h1>
                    </div>

                    <p class="text-lg font-medium text-gray-900 dark:text-white mb-2 transition-colors">
                        {{ __('messages.home_hook') }}
                    </p>
                    <p class="text-gray-600 dark:text-slate-400 mb-8 transition-colors">
                        {{ __('messages.app_description') }}
                    </p>

                    <ul class="space-y-3 text-sm">
                        <li class="flex items-center gap-3 text-gray-700 dark:text-slate-300 transition-colors">
                            <x-icon.check class="w-5 h-5 text-emerald-500 dark:text-emerald-300 shrink-0" />
                            {{ __('messages.feature_encryption') }}
                        </li>
                        <li class="flex items-center gap-3 text-gray-700 dark:text-slate-300 transition-colors">
                            <x-icon.check class="w-5 h-5 text-emerald-500 dark:text-emerald-300 shrink-0" />
                            {{ __('messages.feature_zero_knowledge') }}
                        </li>
                        <li class="flex items-center gap-3 text-gray-700 dark:text-slate-300 transition-colors">
                            <x-icon.check class="w-5 h-5 text-emerald-500 dark:text-emerald-300 shrink-0" />
                            {{ __('messages.feature_auto_destroy') }}
                        </li>
                        <li class="flex items-center gap-3 text-gray-700 dark:text-slate-300 transition-colors">
                            <x-icon.check class="w-5 h-5 text-emerald-500 dark:text-emerald-300 shrink-0" />
                            {{ __('messages.feature_expiration') }}
                        </li>
                        <li class="flex items-center gap-3 text-gray-700 dark:text-slate-300 transition-colors">
                            <x-icon.check class="w-5 h-5 text-emerald-500 dark:text-emerald-300 shrink-0" />
                            {{ __('messages.feature_hosted_no_tracking') }}
                        </li>
                    </ul>

                    <div class="mt-8 pt-6 border-t border-gray-200 dark:border-slate-700/50">
                        <a href="{{ localized_route('how-it-works') }}" class="text-sm text-violet-600 dark:text-violet-400 hover:text-violet-700 dark:hover:text-violet-300 transition-colors">
                            {{ __('messages.faq_see_how') }} &rarr;
                        </a>
                    </div>
                </div>

                {{-- Right: Form --}}
                <div class="p-6 md:p-8 lg:p-12 flex flex-col justify-center">
                    {{-- Form --}}
                    <form x-show="!shareUrl" @submit.prevent="handleSubmit" @keydown.ctrl.enter.prevent="handleSubmit()" @keydown.meta.enter.prevent="handleSubmit()" class="space-y-5" autocomplete="off">
                        {{-- Mode tabs --}}
                        <div class="flex rounded-xl bg-gray-100 dark:bg-slate-900/50 p-1" role="tablist" aria-label="{{ __('messages.tab_text') }} / {{ __('messages.tab_file') }}">
                            <button
                                type="button"
                                id="tab-text"
                                role="tab"
                                :aria-selected="mode === 'text'"
                                aria-controls="tabpanel-text"
                                @click="setModeText()"
                                :class="mode === 'text' ? 'bg-white dark:bg-slate-700 text-gray-900 dark:text-white shadow' : 'text-gray-500 dark:text-slate-400 hover:text-gray-700 dark:hover:text-slate-200'"
                                class="flex-1 flex items-center justify-center gap-2 py-2 px-4 rounded-xl text-sm font-medium transition cursor-pointer"
                            >
                                <x-icon.document class="w-4 h-4" />
                                {{ __('messages.tab_text') }}
                            </button>
                            <button
                                type="button"
                                id="tab-file"
                                role="tab"
                                :aria-selected="mode === 'file'"
                                aria-controls="tabpanel-file"
                                @click="setModeFile()"
                                :class="mode === 'file' ? 'bg-white dark:bg-slate-700 text-gray-900 dark:text-white shadow' : 'text-gray-500 dark:text-slate-400 hover:text-gray-700 dark:hover:text-slate-200'"
                                class="flex-1 flex items-center justify-center gap-2 py-2 px-4 rounded-xl text-sm font-medium transition cursor-pointer"
                            >
                                <x-icon.file class="w-4 h-4" />
                                {{ __('messages.tab_file') }}
                            </button>
                        </div>

                        {{-- Text mode: Secret textarea --}}
                        <div x-show="mode === 'text'" id="tabpanel-text" role="tabpanel" aria-labelledby="tab-text">
                            <label for="secret" class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-2 transition-colors">
                                {{ __('messages.your_secret') }}
                            </label>
                            <div class="relative h-28">
                                <textarea
                                    id="secret"
                                    x-model="secret"
                                    placeholder="{{ __('messages.secret_placeholder') }}"
                                    class="w-full h-28 px-4 py-3 pb-6 bg-gray-50 dark:bg-slate-900/50 border border-gray-300 dark:border-slate-600/50 rounded-xl text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-violet-500/50 focus:border-violet-500/50 transition resize-none"
                                ></textarea>
                                <div class="absolute bottom-1.5 right-3 text-xs tabular-nums pointer-events-none" :class="secret.length > 50000 ? 'text-red-500 dark:text-red-400' : 'text-gray-400 dark:text-slate-500'" x-text="secret.length.toLocaleString() + ' / 50 000'"></div>
                            </div>
                        </div>

                        {{-- File mode: Drag & drop zone --}}
                        {{-- Note: both tabpanels share this container; file panel starts here --}}
                        <div x-show="mode === 'file'" x-cloak id="tabpanel-file" role="tabpanel" aria-labelledby="tab-file">
                            <label for="fileInput" class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-2 transition-colors">
                                {{ __('messages.your_file') }}
                            </label>

                            {{-- Drop zone (when no file selected) --}}
                            <div
                                x-show="!file"
                                @dragover.prevent="isDragging = true"
                                @dragleave.prevent="isDragging = false"
                                @drop.prevent="handleFileDrop($event)"
                                :class="isDragging ? 'border-violet-500 bg-violet-50 dark:bg-violet-500/10' : 'border-gray-300 dark:border-slate-600/50 hover:border-gray-400 dark:hover:border-slate-500'"
                                class="relative flex flex-col items-center justify-center h-28 border border-dashed rounded-xl cursor-pointer transition"
                            >
                                <input
                                    id="fileInput"
                                    type="file"
                                    @change="handleFileSelect($event)"
                                    class="absolute inset-0 w-full h-full opacity-0 cursor-pointer"
                                >
                                <x-icon.upload class="w-8 h-8 mb-2 text-gray-500 dark:text-slate-400" />
                                <p class="text-sm text-gray-600 dark:text-slate-400">
                                    <span class="font-medium text-violet-600 dark:text-violet-400">{{ __('messages.file_drop_click') }}</span> {{ __('messages.file_drop_or_drag') }}
                                </p>
                                <p class="mt-1 text-xs text-gray-600 dark:text-slate-400">
                                    {{ __('messages.file_max_size') }}
                                </p>
                            </div>

                            {{-- File preview (when file selected) --}}
                            <div
                                x-show="file"
                                class="flex items-center gap-4 p-4 h-28 bg-gray-50 dark:bg-slate-900/50 border border-gray-300 dark:border-slate-600/50 rounded-xl"
                            >
                                <div class="flex items-center justify-center w-12 h-12 rounded-xl bg-violet-100 dark:bg-violet-500/10 shrink-0">
                                    <x-icon.file class="w-6 h-6 text-violet-600 dark:text-violet-400" />
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-gray-900 dark:text-white truncate" x-text="fileName()"></p>
                                    <p class="text-xs text-gray-500 dark:text-slate-400" x-text="fileSizeFormatted()"></p>
                                </div>
                                <button
                                    type="button"
                                    @click="file = null"
                                    aria-label="{{ __('messages.a11y_remove_file') }}"
                                    class="p-2 text-gray-400 hover:text-red-500 dark:text-slate-400 dark:hover:text-red-400 transition cursor-pointer"
                                >
                                    <x-icon.x-mark class="w-5 h-5" />
                                </button>
                            </div>
                        </div>

                        {{-- Options grid --}}
                        <div class="grid grid-cols-2 gap-4">
                            {{-- Expiration --}}
                            <div>
                                <div class="flex items-center gap-1.5 mb-2">
                                    <label for="expiration" class="block text-sm font-medium text-gray-700 dark:text-slate-300 transition-colors">
                                        {{ __('messages.expires_in') }}
                                    </label>
                                    <x-hint-tooltip id="expirationHint" :text="__('messages.expires_in_hint')" />
                                </div>
                                <select
                                    id="expiration"
                                    x-model="expiration"
                                    class="w-full h-11 px-4 bg-gray-50 dark:bg-slate-900/50 border border-gray-300 dark:border-slate-600/50 rounded-xl text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-violet-500/50 focus:border-violet-500/50 transition"
                                >
                                    @foreach(array_keys(config('secrets.expirations')) as $key)
                                        <option value="{{ $key }}">{{ __("messages.expiration_{$key}") }}</option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- Max views --}}
                            <div>
                                <div class="flex items-center gap-1.5 mb-2">
                                    <label for="maxViews" class="block text-sm font-medium text-gray-700 dark:text-slate-300 transition-colors">
                                        {{ __('messages.max_reads') }}
                                    </label>
                                    <x-hint-tooltip id="maxViewsHint" :text="__('messages.max_reads_hint')" position="end" />
                                </div>
                                <input
                                    id="maxViews"
                                    type="number"
                                    x-model="maxViews"
                                    min="1"
                                    max="100"
                                    autocomplete="one-time-code"
                                    placeholder="{{ __('messages.max_reads_placeholder') }}"
                                    class="w-full h-11 px-4 bg-gray-50 dark:bg-slate-900/50 border border-gray-300 dark:border-slate-600/50 rounded-xl text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-violet-500/50 focus:border-violet-500/50 transition"
                                >
                            </div>
                        </div>

                        {{-- Collapsible options --}}
                        <div class="grid grid-cols-2 gap-4 items-center">
                            <button
                                type="button"
                                @click="showAdvanced = !showAdvanced"
                                :aria-expanded="showAdvanced"
                                aria-controls="advancedOptions"
                                class="flex items-center gap-2 text-sm text-gray-500 dark:text-slate-400 hover:text-gray-900 dark:hover:text-white transition cursor-pointer"
                            >
                                <x-icon.chevron-right class="w-4 h-4 transition-transform rtl:-scale-x-100" x-bind:class="{ 'rotate-90': showAdvanced }" />
                                {{ __('messages.advanced_options') }}
                            </button>

                            {{-- Max security CTA --}}
                            <button
                                type="button"
                                @click="applyMaxSecurity()"
                                class="flex items-center justify-center gap-1.5 text-xs font-medium px-3 py-1.5 rounded-xl bg-amber-500 dark:bg-amber-600 text-white hover:bg-amber-600 dark:hover:bg-amber-500 shadow-sm hover:shadow transition cursor-pointer"
                            >
                                <x-icon.shield-check />
                                {{ __('messages.max_security') }}
                            </button>
                        </div>

                            <div id="advancedOptions" x-show="showAdvanced" x-collapse class="mt-4 space-y-4">
                                {{-- Passphrase --}}
                                <div>
                                    <div class="flex items-center gap-1.5 mb-2">
                                        <label for="passphrase" class="block text-sm font-medium text-gray-700 dark:text-slate-300 transition-colors">
                                            {{ __('messages.passphrase') }}
                                        </label>
                                        <x-hint-tooltip id="passphraseHint" :text="__('messages.passphrase_hint')" />
                                    </div>
                                    <div class="relative">
                                        <input
                                            id="passphrase"
                                            :type="showPassphrase ? 'text' : 'password'"
                                            x-model="passphrase"
                                            autocomplete="one-time-code"
                                            placeholder="{{ __('messages.passphrase_placeholder') }}"
                                            class="w-full px-4 py-2.5 pe-12 bg-gray-50 dark:bg-slate-900/50 border border-gray-300 dark:border-slate-600/50 rounded-xl text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-violet-500/50 focus:border-violet-500/50 transition"
                                        >
                                        <button
                                            type="button"
                                            @click="showPassphrase = !showPassphrase"
                                            :aria-label="passphraseAriaLabel()"
                                            class="absolute end-3 top-1/2 -translate-y-1/2 text-gray-500 dark:text-slate-300 hover:text-gray-700 dark:hover:text-white transition cursor-pointer"
                                        >
                                            <x-icon.eye x-show="!showPassphrase" class="w-5 h-5" />
                                            <x-icon.eye-off x-show="showPassphrase" class="w-5 h-5" />
                                        </button>
                                    </div>
                                    {{-- Passphrase strength indicator --}}
                                    <div
                                        x-show="passphrase.length > 0"
                                        x-transition:enter="transition-opacity duration-200"
                                        x-transition:enter-start="opacity-0"
                                        x-transition:enter-end="opacity-100"
                                        class="mt-1.5"
                                        aria-live="polite"
                                        aria-atomic="true"
                                    >
                                        <div class="h-1 w-full bg-gray-200 dark:bg-slate-700 rounded-full overflow-hidden">
                                            <div
                                                class="h-full rounded-full transition-all duration-300"
                                                :class="getPassphraseStrengthClass()"
                                            ></div>
                                        </div>
                                        {{-- Criteria list --}}
                                        <ul class="mt-2 grid grid-cols-2 gap-x-4 gap-y-1 text-xs" aria-label="{{ __('messages.a11y_passphrase_criteria') }}">
                                            <li class="flex items-center gap-1.5 transition-colors" :class="hasMinLength() ? 'text-green-700 dark:text-green-300' : 'text-gray-500 dark:text-slate-400'">
                                                <x-icon.check-circle x-show="hasMinLength()" class="shrink-0" />
                                                <x-icon.x-circle x-show="!hasMinLength()" class="shrink-0" />
                                                <span>{{ __('messages.passphrase_min_length') }}</span>
                                                <span class="sr-only" x-text="hasMinLength() ? '✓' : '✗'"></span>
                                            </li>
                                            <li class="flex items-center gap-1.5 transition-colors" :class="hasLowercase() ? 'text-green-700 dark:text-green-300' : 'text-gray-500 dark:text-slate-400'">
                                                <x-icon.check-circle x-show="hasLowercase()" class="shrink-0" />
                                                <x-icon.x-circle x-show="!hasLowercase()" class="shrink-0" />
                                                <span>{{ __('messages.passphrase_lowercase') }}</span>
                                                <span class="sr-only" x-text="hasLowercase() ? '✓' : '✗'"></span>
                                            </li>
                                            <li class="flex items-center gap-1.5 transition-colors" :class="hasUppercase() ? 'text-green-700 dark:text-green-300' : 'text-gray-500 dark:text-slate-400'">
                                                <x-icon.check-circle x-show="hasUppercase()" class="shrink-0" />
                                                <x-icon.x-circle x-show="!hasUppercase()" class="shrink-0" />
                                                <span>{{ __('messages.passphrase_uppercase') }}</span>
                                                <span class="sr-only" x-text="hasUppercase() ? '✓' : '✗'"></span>
                                            </li>
                                            <li class="flex items-center gap-1.5 transition-colors" :class="hasDigit() ? 'text-green-700 dark:text-green-300' : 'text-gray-500 dark:text-slate-400'">
                                                <x-icon.check-circle x-show="hasDigit()" class="shrink-0" />
                                                <x-icon.x-circle x-show="!hasDigit()" class="shrink-0" />
                                                <span>{{ __('messages.passphrase_digit') }}</span>
                                                <span class="sr-only" x-text="hasDigit() ? '✓' : '✗'"></span>
                                            </li>
                                            <li class="flex items-center gap-1.5 transition-colors" :class="hasSpecial() ? 'text-green-700 dark:text-green-300' : 'text-gray-500 dark:text-slate-400'">
                                                <x-icon.check-circle x-show="hasSpecial()" class="shrink-0" />
                                                <x-icon.x-circle x-show="!hasSpecial()" class="shrink-0" />
                                                <span>{{ __('messages.passphrase_special') }}</span>
                                                <span class="sr-only" x-text="hasSpecial() ? '✓' : '✗'"></span>
                                            </li>
                                        </ul>
                                    </div>
                                </div>

                                {{-- Creator email --}}
                                <div>
                                    <label for="creatorEmail" class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-2 transition-colors">
                                        {{ __('messages.your_email') }}
                                    </label>
                                    <input
                                        id="creatorEmail"
                                        type="email"
                                        x-model="creatorEmail"
                                        autocomplete="off"
                                        placeholder="{{ __('messages.email_placeholder') }}"
                                        class="w-full px-4 py-2.5 bg-gray-50 dark:bg-slate-900/50 border border-gray-300 dark:border-slate-600/50 rounded-xl text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-violet-500/50 focus:border-violet-500/50 transition"
                                    >
                                    <p class="mt-1.5 text-xs text-gray-600 dark:text-slate-400">
                                        {{ __('messages.email_hint') }}
                                    </p>
                                </div>

                                {{-- Split mode --}}
                                <div class="flex items-start gap-3" x-data="{ showHint: false }">
                                    <label for="splitMode" class="flex items-start gap-3 cursor-pointer group flex-1">
                                        <div class="relative mt-0.5">
                                            <input type="checkbox" id="splitMode" x-model="splitMode" class="sr-only peer">
                                            <div class="w-11 h-6 bg-gray-300 dark:bg-slate-700 rounded-full peer-checked:bg-violet-600 peer-focus-visible:ring-2 peer-focus-visible:ring-violet-500 peer-focus-visible:ring-offset-2 transition"></div>
                                            <div class="absolute start-0.5 top-0.5 w-5 h-5 bg-white rounded-full shadow transition peer-checked:ltr:translate-x-5 peer-checked:rtl:-translate-x-5"></div>
                                        </div>
                                        <div>
                                            <span class="text-sm text-gray-600 dark:text-slate-300 group-hover:text-gray-900 dark:group-hover:text-white transition">
                                                {{ __('messages.split_mode') }}
                                            </span>
                                            <p class="mt-0.5 text-xs text-gray-600 dark:text-slate-400">
                                                {{ __('messages.split_mode_hint') }}
                                            </p>
                                        </div>
                                    </label>
                                    <div class="mt-0.5">
                                        <x-hint-tooltip id="splitModeHint" :text="__('messages.split_mode_tooltip')" position="end" />
                                    </div>
                                </div>
                            </div>

                        {{-- Error message --}}
                        <div x-show="error && !captchaRequired" x-cloak role="alert" class="p-4 bg-red-50 dark:bg-red-500/10 border border-red-200 dark:border-red-500/20 rounded-xl transition-colors">
                            <p class="text-sm text-red-600 dark:text-red-300" x-text="error"></p>
                        </div>

                        {{-- Captcha challenge --}}
                        <div x-show="captchaRequired" x-cloak class="p-4 bg-amber-50 dark:bg-amber-500/10 border border-amber-200 dark:border-amber-500/20 rounded-xl transition-colors">
                            <p class="text-sm text-amber-700 dark:text-amber-300 mb-3">
                                {{ __('messages.rate_limit_exceeded') }}
                            </p>
                            <label for="captchaAnswer" class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-2">
                                {{ __('messages.captcha_label') }}
                            </label>
                            <div class="flex items-center gap-3">
                                <div class="px-4 py-2 bg-white dark:bg-slate-800 border border-gray-300 dark:border-slate-600 rounded-xl font-mono text-lg text-gray-900 dark:text-white">
                                    <span x-text="captchaChallenge"></span> = ?
                                </div>
                                <input
                                    id="captchaAnswer"
                                    type="number"
                                    x-model="captchaAnswer"
                                    placeholder="{{ __('messages.captcha_placeholder') }}"
                                    class="flex-1 px-4 py-2 bg-gray-50 dark:bg-slate-900/50 border border-gray-300 dark:border-slate-600/50 rounded-xl text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-violet-500/50 focus:border-violet-500/50 transition"
                                    @keydown.enter.prevent="submitWithCaptcha()"
                                >
                            </div>
                            <div x-show="error" class="mt-2 text-sm text-red-600 dark:text-red-300" x-text="error"></div>
                        </div>

                        {{-- Submit button --}}
                        <x-btn-primary
                            x-show="!captchaRequired"
                            type="submit"
                            x-bind:disabled="isSubmitting || (mode === 'text' && !secret.trim()) || (mode === 'file' && !file)"
                            class="w-full"
                        >
                            <span x-show="!isSubmitting">{{ __('messages.btn_encrypt') }}</span>
                            <span x-show="isSubmitting" role="status" class="inline-flex items-center justify-center gap-2">
                                <x-spinner />
                                <span x-text="encryptingButtonText()"></span>
                            </span>
                        </x-btn-primary>

                        {{-- Captcha submit button --}}
                        <x-btn-primary
                            x-show="captchaRequired"
                            x-cloak
                            type="button"
                            @click="submitWithCaptcha()"
                            x-bind:disabled="isSubmitting || !captchaAnswer.trim()"
                            class="w-full"
                        >
                            <span x-show="!isSubmitting">{{ __('messages.btn_encrypt') }}</span>
                            <span x-show="isSubmitting" role="status" class="inline-flex items-center justify-center gap-2">
                                <x-spinner />
                                <span x-text="encryptingButtonText()"></span>
                            </span>
                        </x-btn-primary>
                    </form>

                    {{-- Success state --}}
                    <div x-show="shareUrl" x-cloak class="space-y-6">
                        <div class="text-center">
                            <div class="logo-icon inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-linear-to-br from-emerald-500/0 to-emerald-600 mb-4 transition-colors" style="--accent-rgb: 16, 185, 129" aria-hidden="true">
                                <x-icon.check class="w-7 h-7 text-white" />
                            </div>
                            <h2 class="text-xl font-semibold text-gray-900 dark:text-white transition-colors">{{ __('messages.secret_created') }}</h2>
                            <p class="mt-1 text-sm text-gray-500 dark:text-slate-400 transition-colors">{{ __('messages.share_link_instruction') }}</p>
                        </div>

                        {{-- Standard mode: single URL with key in fragment --}}
                        <div x-show="!shareKey" class="space-y-4">
                            <div class="relative">
                                <label for="shareUrl" class="sr-only">{{ __('messages.share_link_instruction') }}</label>
                                <input
                                    id="shareUrl"
                                    type="text"
                                    readonly
                                    :value="shareUrl"
                                    class="w-full px-4 py-3 pe-24 bg-gray-50 dark:bg-slate-900/50 border border-gray-300 dark:border-slate-600/50 rounded-xl text-gray-900 dark:text-white text-sm font-mono transition-colors"
                                >
                                <button
                                    type="button"
                                    @click="copyToClipboard()"
                                    :aria-label="copied ? '{{ __('messages.btn_copied') }}' : '{{ __('messages.btn_copy') }}'"
                                    class="absolute end-2 top-1/2 -translate-y-1/2 px-3 py-1.5 bg-violet-600 hover:bg-violet-500 text-white text-sm font-medium rounded-xl transition cursor-pointer"
                                >
                                    <span x-show="!copied">{{ __('messages.btn_copy') }}</span>
                                    <span x-show="copied">{{ __('messages.btn_copied') }}</span>
                                </button>
                            </div>

                            <div class="p-3 bg-amber-50 dark:bg-amber-500/10 border border-amber-200 dark:border-amber-500/20 rounded-xl transition-colors">
                                <p class="text-xs text-amber-700 dark:text-amber-300" x-show="!passphraseUsed">
                                    <strong>{{ __('messages.label_important') }}</strong> {{ __('messages.warning_link_contains_key') }}
                                </p>
                                <p class="text-xs text-amber-700 dark:text-amber-300" x-show="passphraseUsed">
                                    <strong>{{ __('messages.label_important') }}</strong> {{ __('messages.warning_passphrase_required') }}
                                </p>
                            </div>
                        </div>

                        {{-- Split mode: separate URL and key --}}
                        <div x-show="shareKey" class="space-y-4">
                            {{-- Share link --}}
                            <div>
                                <label for="shareUrlSplit" class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-2 transition-colors">
                                    {{ __('messages.share_link_label') }}
                                </label>
                                <div class="relative">
                                    <input
                                        id="shareUrlSplit"
                                        type="text"
                                        readonly
                                        :value="shareUrl"
                                        class="w-full px-4 py-3 pe-24 bg-gray-50 dark:bg-slate-900/50 border border-gray-300 dark:border-slate-600/50 rounded-xl text-gray-900 dark:text-white text-sm font-mono transition-colors"
                                    >
                                    <button
                                        type="button"
                                        @click="copyToClipboard()"
                                        :aria-label="copied ? '{{ __('messages.btn_copied') }}' : '{{ __('messages.btn_copy') }}'"
                                        class="absolute end-2 top-1/2 -translate-y-1/2 px-3 py-1.5 bg-violet-600 hover:bg-violet-500 text-white text-sm font-medium rounded-xl transition cursor-pointer"
                                    >
                                        <span x-show="!copied">{{ __('messages.btn_copy') }}</span>
                                        <span x-show="copied">{{ __('messages.btn_copied') }}</span>
                                    </button>
                                </div>
                            </div>

                            {{-- Decryption key --}}
                            <div>
                                <label for="shareKeySplit" class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-2 transition-colors">
                                    {{ __('messages.share_key_label') }}
                                </label>
                                <div class="relative">
                                    <input
                                        id="shareKeySplit"
                                        type="text"
                                        readonly
                                        :value="shareKey"
                                        class="w-full px-4 py-3 pe-24 bg-gray-50 dark:bg-slate-900/50 border border-gray-300 dark:border-slate-600/50 rounded-xl text-gray-900 dark:text-white text-sm font-mono transition-colors"
                                    >
                                    <button
                                        type="button"
                                        @click="copyKeyToClipboard()"
                                        :aria-label="keyCopied ? '{{ __('messages.btn_copied') }}' : '{{ __('messages.btn_copy') }}'"
                                        class="absolute end-2 top-1/2 -translate-y-1/2 px-3 py-1.5 bg-violet-600 hover:bg-violet-500 text-white text-sm font-medium rounded-xl transition cursor-pointer"
                                    >
                                        <span x-show="!keyCopied">{{ __('messages.btn_copy') }}</span>
                                        <span x-show="keyCopied">{{ __('messages.btn_copied') }}</span>
                                    </button>
                                </div>
                            </div>

                            <div class="p-3 bg-amber-50 dark:bg-amber-500/10 border border-amber-200 dark:border-amber-500/20 rounded-xl transition-colors">
                                <p class="text-xs text-amber-700 dark:text-amber-300">
                                    <strong>{{ __('messages.label_important') }}</strong> {{ __('messages.split_mode_warning') }}
                                </p>
                                <p class="text-xs text-amber-700 dark:text-amber-300 mt-1" x-show="passphraseUsed">
                                    {{ __('messages.warning_passphrase_required') }}
                                </p>
                            </div>
                        </div>

                        {{-- QR Code section --}}
                        <div class="border-t border-gray-200 dark:border-slate-700/50 pt-4 mt-4">
                            <button
                                type="button"
                                @click="toggleQrCode()"
                                :aria-expanded="showQrCode"
                                aria-controls="qr-code-panel"
                                class="w-full flex items-center justify-center gap-2 py-2.5 text-sm text-gray-600 dark:text-slate-400 hover:text-violet-600 dark:hover:text-violet-400 transition cursor-pointer"
                            >
                                <x-icon.qr-code class="w-5 h-5" />
                                <span x-text="showQrCode ? '{{ __('messages.hide_qr_code') }}' : '{{ __('messages.show_qr_code') }}'"></span>
                            </button>

                            <div id="qr-code-panel" x-show="showQrCode" x-collapse class="mt-4">
                                <div class="flex flex-col items-center gap-4">
                                    <div class="p-4 bg-white rounded-xl shadow-sm">
                                        <img :src="qrCodeDataUrl" :alt="'{{ __('messages.qr_code_alt') }}'" width="256" height="256" class="w-64 h-64">
                                    </div>
                                    <button
                                        type="button"
                                        @click="downloadQrCode()"
                                        class="flex items-center gap-2 px-4 py-2 text-sm text-violet-600 dark:text-violet-400 hover:text-violet-700 dark:hover:text-violet-300 transition cursor-pointer"
                                    >
                                        <x-icon.download class="w-4 h-4" />
                                        {{ __('messages.download_qr_code') }}
                                    </button>
                                    <p class="text-xs text-gray-600 dark:text-slate-400 text-center">
                                        {{ __('messages.qr_code_hint') }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <p x-show="creatorEmail" class="text-sm text-gray-600 dark:text-slate-400 text-center">
                            {{ __('messages.success_admin_hint', ['link' => __('messages.footer_manage')]) }}
                        </p>

                        <button
                            type="button"
                            @click="reset()"
                            class="w-full py-2.5 text-sm text-gray-500 dark:text-slate-400 hover:text-gray-900 dark:hover:text-white border border-gray-300 dark:border-slate-600/50 hover:border-gray-400 dark:hover:border-slate-500 rounded-xl transition cursor-pointer"
                        >
                            {{ __('messages.btn_create_new') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <p class="mt-4 text-center text-xs text-gray-400 dark:text-slate-500 tracking-wide">
            Open source <span class="mx-1">·</span> Laravel <span class="hidden sm:inline"><span class="mx-1">·</span> {{ __('messages.app_tagline') }}</span> <span class="mx-1">·</span>
            <a href="https://github.com/perceptron-systems/secret-drop" target="_blank" rel="noopener" class="hover:text-gray-600 dark:hover:text-slate-300 transition-colors">GitHub</a>
        </p>
    </div>
</div>
@endsection
